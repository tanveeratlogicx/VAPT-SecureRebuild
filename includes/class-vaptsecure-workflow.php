<?php

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Class VAPTSECURE_Workflow
 * Manages the state machine and history for security features.
 */
class VAPTSECURE_Workflow
{
  /**
   * Validate if a transition from old status to new status is allowed.
   */
  public static function is_transition_allowed($old_status, $new_status)
  {
    // Map legacy status if they exist and normalize to lowercase for rules
    $old = strtolower(self::map_status($old_status));
    $new = strtolower(self::map_status($new_status));

    if ($old === $new) return true;

    // Transition Rules
    $rules = array(
      'draft'   => array('develop'),
      'develop' => array('draft', 'test', 'release'),
      'test'    => array('develop', 'release'),
      'release' => array('test', 'develop') // Allow downgrading if bug found
    );

    return isset($rules[$old]) && in_array($new, $rules[$old]);
  }

  /**
   * Transition a feature to a new status.
   */
  public static function transition_feature($feature_key, $new_status, $note = '', $user_id = 0)
  {
    global $wpdb;
    $table_status = $wpdb->prefix . 'vaptsecure_feature_status';
    $table_history = $wpdb->prefix . 'vaptsecure_feature_history';

    // Get current status
    $current = $wpdb->get_row($wpdb->prepare(
      "SELECT status FROM $table_status WHERE feature_key = %s",
      $feature_key
    ));

    $old_status = $current ? $current->status : 'draft';

    if (! self::is_transition_allowed($old_status, $new_status)) {
      return new WP_Error('invalid_transition', sprintf(__('Transition from %s to %s is not allowed.', 'vaptsecure'), $old_status, $new_status));
    }

    // Update Status
    $update_data = array('status' => $new_status);
    if ($new_status === 'Release' || $new_status === 'release') {
      $update_data['implemented_at'] = current_time('mysql');
    } else {
      $update_data['implemented_at'] = null;
    }

    if ($current) {
      $wpdb->update($table_status, $update_data, array('feature_key' => $feature_key));
    } else {
      $update_data['feature_key'] = $feature_key;
      $wpdb->insert($table_status, $update_data);
    }

    // Record History
    $wpdb->insert($table_history, array(
      'feature_key' => $feature_key,
      'old_status'  => $old_status,
      'new_status'  => $new_status,
      'user_id'     => $user_id ? $user_id : get_current_user_id(),
      'note'        => $note,
      'created_at'  => current_time('mysql')
    ));

    // Special Case: Reset if moving back to Draft
    if (strtolower($new_status) === 'draft') {
      // 1. Wipe History
      $wpdb->delete($table_history, array('feature_key' => $feature_key));

      // 2. Wipe Implementation Data (Meta)
      // We keep the row but nullify the data fields
      $table_meta = $wpdb->prefix . 'vaptsecure_feature_meta';
      $wpdb->update($table_meta, array(
        'generated_schema' => null,
        'implementation_data' => null,
        'override_schema' => null,
        'override_implementation_data' => null,
        'is_enforced' => 0
      ), array('feature_key' => $feature_key));
    }

    return true;
  }

  /**
   * Get history for a feature.
   */
  public static function get_history($feature_key)
  {
    global $wpdb;
    $table_history = $wpdb->prefix . 'vaptsecure_feature_history';

    return $wpdb->get_results($wpdb->prepare(
      "SELECT h.*, u.display_name as user_name 
       FROM $table_history h
       LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID
       WHERE h.feature_key = %s 
       ORDER BY h.created_at DESC",
      $feature_key
    ));
  }

  /**
   * Preview what would be affected by a batch revert to Draft.
   * Does NOT make any changes - read-only operation.
   * 
   * Broken features = Features in Draft status that have history records.
   * These indicate a feature was transitioned but history wasn't properly cleaned.
   * 
   * @param bool $include_broken Whether to include broken features in the preview
   * @return array Preview of affected features and data
   */
  public static function preview_revert_to_draft($include_broken = false)
  {
    global $wpdb;

    $table_status = $wpdb->prefix . 'vaptsecure_feature_status';
    $table_history = $wpdb->prefix . 'vaptsecure_feature_history';
    $table_meta = $wpdb->prefix . 'vaptsecure_feature_meta';

    // 1. Get all features in 'Develop' status
    $develop_features = $wpdb->get_results($wpdb->prepare(
      "SELECT feature_key, implemented_at, assigned_to, 'develop' as source FROM $table_status WHERE status = %s",
      'Develop'
    ), ARRAY_A);

    // 2. Get BROKEN features (Draft status + has history records)
    // These are features that have history but are in Draft state (inconsistent)
    $broken_features = $wpdb->get_results(
      "SELECT DISTINCT s.feature_key, s.implemented_at, s.assigned_to, 'broken' as source
       FROM $table_status s
       INNER JOIN $table_history h ON s.feature_key = h.feature_key
       WHERE s.status = 'Draft'",
      ARRAY_A
    );

    // 3. Merge based on include_broken flag
    $all_features = $develop_features ?: array();
    if ($include_broken && $broken_features) {
      $all_features = array_merge($all_features, $broken_features);
    }

    if (empty($all_features)) {
      return array(
        'success' => true,
        'count' => 0,
        'features' => array(),
        'total_history_records' => 0,
        'total_with_schema' => 0,
        'total_with_impl' => 0,
        'total_enforced' => 0,
        'broken_count' => count($broken_features ?: array()),
        'develop_count' => count($develop_features ?: array()),
        'message' => 'No features in Develop status to revert.'
      );
    }

    $feature_keys = wp_list_pluck($all_features, 'feature_key');

    // Build IN clause safely
    $placeholders = implode(',', array_fill(0, count($feature_keys), '%s'));
    $prepared_in = $wpdb->prepare($placeholders, $feature_keys);

    // 4. Count history records per feature
    $history_counts = $wpdb->get_results(
      "SELECT feature_key, COUNT(*) as count FROM $table_history WHERE feature_key IN ($prepared_in) GROUP BY feature_key",
      OBJECT_K
    );

    // 5. Check which features have implementation data
    $impl_data = $wpdb->get_results(
      "SELECT feature_key, generated_schema IS NOT NULL as has_schema, implementation_data IS NOT NULL as has_impl, is_enforced 
       FROM $table_meta WHERE feature_key IN ($prepared_in)",
      OBJECT_K
    );

    // 6. Build preview response
    $preview = array();
    $broken_count = 0;
    foreach ($all_features as $feature) {
      $key = $feature['feature_key'];
      $is_broken = isset($feature['source']) && $feature['source'] === 'broken';
      if ($is_broken) $broken_count++;

      $preview[] = array(
        'feature_key' => $key,
        'implemented_at' => $feature['implemented_at'],
        'assigned_to' => $feature['assigned_to'],
        'source' => isset($feature['source']) ? $feature['source'] : 'develop',
        'is_broken' => $is_broken,
        'history_records' => isset($history_counts[$key]) ? (int) $history_counts[$key]->count : 0,
        'has_generated_schema' => isset($impl_data[$key]) && (bool) $impl_data[$key]->has_schema,
        'has_implementation_data' => isset($impl_data[$key]) && (bool) $impl_data[$key]->has_impl,
        'is_enforced' => isset($impl_data[$key]) && (bool) $impl_data[$key]->is_enforced,
      );
    }

    return array(
      'success' => true,
      'count' => count($preview),
      'broken_count' => count($broken_features ?: array()),
      'develop_count' => count($develop_features ?: array()),
      'included_broken_count' => $broken_count,
      'features' => $preview,
      'total_history_records' => array_sum(wp_list_pluck($preview, 'history_records')),
      'total_with_schema' => count(array_filter($preview, function ($f) {
        return $f['has_generated_schema'];
      })),
      'total_with_impl' => count(array_filter($preview, function ($f) {
        return $f['has_implementation_data'];
      })),
      'total_enforced' => count(array_filter($preview, function ($f) {
        return $f['is_enforced'];
      })),
    );
  }

  /**
   * Batch revert all features in 'Develop' status to 'Draft'.
   * Optionally includes broken features (Draft status + has history records).
   * 
   * @param string $note Optional note for the operation
   * @param bool $include_broken Whether to include broken features
   * @return array Result with counts of affected features
   */
  public static function batch_revert_to_draft($note = 'Batch revert to Draft', $include_broken = false)
  {
    global $wpdb;

    $table_status = $wpdb->prefix . 'vaptsecure_feature_status';
    $table_history = $wpdb->prefix . 'vaptsecure_feature_history';

    // 1. Get all features in 'Develop' status
    $develop_features = $wpdb->get_col($wpdb->prepare(
      "SELECT feature_key FROM $table_status WHERE status = %s",
      'Develop'
    ));

    // 2. Get BROKEN features (Draft status + has history records)
    $broken_features = $wpdb->get_col(
      "SELECT DISTINCT s.feature_key
       FROM $table_status s
       INNER JOIN $table_history h ON s.feature_key = h.feature_key
       WHERE s.status = 'Draft'"
    );

    // 3. Merge based on include_broken flag
    $all_features = $develop_features ?: array();
    if ($include_broken && $broken_features) {
      $all_features = array_unique(array_merge($all_features, $broken_features));
    }

    if (empty($all_features)) {
      return array(
        'success' => true,
        'reverted_count' => 0,
        'broken_count' => count($broken_features ?: array()),
        'develop_count' => count($develop_features ?: array()),
        'message' => 'No features in Develop status to revert.'
      );
    }

    $reverted = array();
    $errors = array();

    foreach ($all_features as $feature_key) {
      $result = self::transition_feature($feature_key, 'Draft', $note);
      if (is_wp_error($result)) {
        $errors[] = array(
          'feature_key' => $feature_key,
          'error' => $result->get_error_message()
        );
      } else {
        $reverted[] = $feature_key;
      }
    }

    return array(
      'success' => empty($errors),
      'reverted_count' => count($reverted),
      'broken_count' => count($broken_features ?: array()),
      'develop_count' => count($develop_features ?: array()),
      'error_count' => count($errors),
      'reverted' => $reverted,
      'errors' => $errors
    );
  }

  /**
   * Helper to normalize status.
   */
  private static function map_status($status)
  {
    $map = array(
      'available'   => 'draft',
      'in_progress' => 'develop',
      'testing'     => 'test',
      'implemented' => 'release'
    );
    return isset($map[$status]) ? $map[$status] : $status;
  }
}
