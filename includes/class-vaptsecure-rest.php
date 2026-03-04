<?php

/**
 * REST API Handler for VAPT Secure
 */

if (! defined('ABSPATH')) {
  exit;
}

class VAPTSECURE_REST
{

  public function __construct()
  {
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes()
  {
    register_rest_route('vaptsecure/v1', '/features', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_features'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/data-files/all', array(
      'methods' => 'GET',
      'callback' => array($this, 'get_all_data_files'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/data-files', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_data_files'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/update-hidden-files', array(
      'methods' => 'POST',
      'callback' => array($this, 'update_hidden_files'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/data-files/remove', array(
      'methods' => 'POST',
      'callback' => array($this, 'remove_data_file'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/reset-limit', array(
      'methods' => 'POST',
      'callback' => array($this, 'reset_rate_limit'),
      'permission_callback' => '__return_true', // Public endpoint for testing (limited to user IP)
    ));


    register_rest_route('vaptsecure/v1', '/features/update', array(
      'methods'  => 'POST',
      'callback' => array($this, 'update_feature'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/transition', array(
      'methods'  => 'POST',
      'callback' => array($this, 'transition_feature'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/(?P<key>[a-zA-Z0-9_-]+)/history', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_feature_history'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/(?P<key>[a-zA-Z0-9_-]+)/stats', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_feature_stats'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/(?P<key>[a-zA-Z0-9_-]+)/verify', array(
      'methods'             => 'POST',
      'callback'            => array($this, 'verify_implementation'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/(?P<key>[a-zA-Z0-9_-]+)/reset', array(
      'methods'  => 'POST',
      'callback' => array($this, 'reset_feature_stats'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/assignees', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_assignees'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/assign', array(
      'methods'  => 'POST',
      'callback' => array($this, 'update_assignment'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/upload-json', array(
      'methods'  => 'POST',
      'callback' => array($this, 'upload_json'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/domains', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_domains'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/domains/update', array(
      'methods'  => 'POST',
      'callback' => array($this, 'update_domain'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/domains/features', array(
      'methods'  => 'POST',
      'callback' => array($this, 'update_domain_features'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/domains/delete', array(
      'methods'  => 'DELETE',
      'callback' => array($this, 'delete_domain'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/domains/batch-delete', array(
      'methods'  => 'POST',
      'callback' => array($this, 'batch_delete_domains'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/build/generate', array(
      'methods'  => 'POST',
      'callback' => array($this, 'generate_build'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/build/save-config', array(
      'methods'  => 'POST',
      'callback' => array($this, 'save_config_to_root'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/settings/enforcement', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_global_enforcement'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/settings/enforcement', array(
      'methods'  => 'POST',
      'callback' => array($this, 'update_global_enforcement'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/upload-media', array(
      'methods'  => 'POST',
      'callback' => array($this, 'upload_media'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/data-files/meta', array(
      'methods'  => 'POST',
      'callback' => array($this, 'update_file_meta'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/active-file', array(
      'methods'  => array('GET', 'POST'),
      'callback' => array($this, 'handle_active_file'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/build/sync-config', array(
      'methods'  => 'POST',
      'callback' => array($this, 'sync_config_from_file'),
      'permission_callback' => array($this, 'check_permission'),
    ));


    register_rest_route('vaptsecure/v1', '/ping', array(
      'methods'  => 'GET',
      'callback' => function () {
        return new WP_REST_Response(['pong' => true], 200);
      },
      'permission_callback' => '__return_true',
    ));

    // v1.9.2 – Batch Revert Develop → Draft (Preview & Execute)
    register_rest_route('vaptsecure/v1', '/features/preview-revert', array(
      'methods'             => 'GET',
      'callback'            => array($this, 'preview_revert_to_draft'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/features/batch-revert', array(
      'methods'             => 'POST',
      'callback'            => array($this, 'batch_revert_to_draft'),
      'permission_callback' => array($this, 'check_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/security/stats', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_security_stats'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));

    register_rest_route('vaptsecure/v1', '/security/logs', array(
      'methods'  => 'GET',
      'callback' => array($this, 'get_security_logs'),
      'permission_callback' => array($this, 'check_read_permission'),
    ));
  }

  public function check_permission()
  {
    return is_vaptsecure_superadmin();
  }

  public function check_read_permission()
  {
    return is_vaptsecure_superadmin() || current_user_can('manage_options');
  }

  public function get_features($request)
  {
    $default_file = defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : 'interface_schema_v2.0.json';
    $requested_file = $request->get_param('file') ?: $default_file;

    // 1. Resolve which files to load
    $files_to_load = [];
    if ($requested_file === '__all__') {
      $data_dir = VAPTSECURE_PATH . 'data';
      if (is_dir($data_dir)) {
        $all_json = array_filter(scandir($data_dir), function ($f) {
          return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'json';
        });
        $hidden_files = get_option('vaptsecure_hidden_json_files', array());
        $removed_files = get_option('vaptsecure_removed_json_files', array());
        $hidden_normalized = array_map('sanitize_file_name', $hidden_files);
        $removed_normalized = array_map('sanitize_file_name', $removed_files);

        foreach ($all_json as $f) {
          $normalized = sanitize_file_name($f);
          if (!in_array($normalized, $hidden_normalized) && !in_array($normalized, $removed_normalized)) {
            $files_to_load[] = $f;
          }
        }
      }
    } else {
      $files_to_load = array_filter(explode(',', $requested_file));
    }

    // v3.12.1: Final filter against existence to prevent stale entries
    $files_to_load = array_filter($files_to_load, function ($f) {
      return file_exists(VAPTSECURE_PATH . 'data/' . sanitize_file_name($f));
    });

    // 2. Pre-fetch global state (Status map and History counts)
    $statuses = VAPTSECURE_DB::get_feature_statuses_full();
    $status_map = [];
    foreach ($statuses as $row) {
      $status_map[$row['feature_key']] = array(
        'status' => $row['status'],
        'implemented_at' => $row['implemented_at'],
        'assigned_to' => $row['assigned_to']
      );
    }

    global $wpdb;
    $history_table = $wpdb->prefix . 'vaptsecure_feature_history';
    $history_counts = $wpdb->get_results("SELECT feature_key, COUNT(*) as count FROM $history_table GROUP BY feature_key", OBJECT_K);

    $is_superadmin = is_vaptsecure_superadmin();
    $scope = $request->get_param('scope');

    $features = [];
    $schema = [];
    $merged_features = []; // Track by normalized label
    $design_prompt = null;
    $ai_agent_instructions = null;
    $global_settings = null;

    // 3. Load and process each file
    foreach ($files_to_load as $file) {
      $json_path = VAPTSECURE_PATH . 'data/' . sanitize_file_name($file);
      if (! file_exists($json_path)) continue;

      $content = file_get_contents($json_path);
      $raw_data = json_decode($content, true);
      if (! is_array($raw_data)) continue;

      if (!$design_prompt && isset($raw_data['design_prompt'])) $design_prompt = $raw_data['design_prompt'];
      if (!$ai_agent_instructions && isset($raw_data['ai_agent_instructions'])) $ai_agent_instructions = $raw_data['ai_agent_instructions'];
      if (!$global_settings && isset($raw_data['global_settings'])) $global_settings = $raw_data['global_settings'];

      $current_features = [];
      $current_schema = [];

      if (isset($raw_data['wordpress_vapt']) && is_array($raw_data['wordpress_vapt'])) {
        $current_features = $raw_data['wordpress_vapt'];
        $current_schema = isset($raw_data['schema']) ? $raw_data['schema'] : [];
      } elseif (isset($raw_data['features']) && is_array($raw_data['features'])) {
        $current_features = $raw_data['features'];
        $current_schema = isset($raw_data['schema']) ? $raw_data['schema'] : [];
      } elseif (isset($raw_data['risk_catalog']) && is_array($raw_data['risk_catalog'])) {
        foreach ($raw_data['risk_catalog'] as $item) {
          if (isset($item['risk_id']) && empty($item['id'])) $item['id'] = $item['risk_id'];
          if (isset($item['risk_id']) && empty($item['key'])) $item['key'] = $item['risk_id'];
          if (isset($item['description']) && is_array($item['description'])) {
            $item['original_description'] = $item['description'];
            $item['description'] = isset($item['description']['summary']) ? $item['description']['summary'] : '';
          }
          if (isset($item['severity']) && is_array($item['severity'])) {
            $item['original_severity'] = $item['severity'];
            $item['severity'] = isset($item['severity']['level']) ? $item['severity']['level'] : 'medium';
          }
          if (empty($item['test_method']) && isset($item['testing']['test_method'])) $item['test_method'] = $item['testing']['test_method'];

          // Hyper-Personalization: Attach source-specific root nodes to each feature (v3.13.1)
          $item['root_design_prompt'] = isset($raw_data['design_prompt']) ? $raw_data['design_prompt'] : null;
          $item['root_ai_agent_instructions'] = isset($raw_data['ai_agent_instructions']) ? $raw_data['ai_agent_instructions'] : null;
          $item['root_global_settings'] = isset($raw_data['global_settings']) ? $raw_data['global_settings'] : null;
          $item['source_file'] = $file;

          if (empty($item['verification_engine']) && isset($item['protection']['automated_protection'])) $item['verification_engine'] = $item['protection']['automated_protection'];
          if (isset($item['testing']) && isset($item['testing']['verification_steps']) && is_array($item['testing']['verification_steps'])) {
            $steps = [];
            foreach ($item['testing']['verification_steps'] as $step) {
              if (is_array($step) && isset($step['action'])) $steps[] = $step['action'];
              elseif (is_string($step)) $steps[] = $step;
            }
            $item['verification_steps'] = $steps;
          }
          if (isset($item['protection']) && is_array($item['protection'])) {
            if (isset($item['protection']['automated_protection']['implementation_steps'][0]['code'])) {
              $item['remediation'] = $item['protection']['automated_protection']['implementation_steps'][0]['code'];
            }
          }
          if (isset($item['owasp_mapping']) && isset($item['owasp_mapping']['owasp_top_10_2021'])) $item['owasp'] = $item['owasp_mapping']['owasp_top_10_2021'];
          $current_features[] = $item;
        }
        $current_schema = isset($raw_data['schema']) ? $raw_data['schema'] : [];
      } elseif (isset($raw_data['risk_interfaces']) && is_array($raw_data['risk_interfaces'])) {
        // 🛡️ INTERFACE SCHEMA FORMAT (risk_interfaces node — e.g. interface_schema_full125.json)
        // Converts the keyed RISK-NNN dictionary into the standard flat feature array.
        foreach ($raw_data['risk_interfaces'] as $risk_key => $item) {
          $item['id']          = isset($item['risk_id'])  ? $item['risk_id']  : $risk_key;
          $item['key']         = $item['id'];
          $item['name']        = isset($item['title'])    ? $item['title']    : $risk_key;
          $item['label']       = $item['name'];
          // Map summary → description so the Feature List "Description" column is populated
          if (!isset($item['description']) && isset($item['summary'])) {
            $item['description'] = $item['summary'];
          }
          // Flatten severity if it is an object (guard for mixed catalogues)
          if (isset($item['severity']) && is_array($item['severity'])) {
            $item['severity'] = isset($item['severity']['level']) ? $item['severity']['level'] : 'medium';
          }
          // Attach root-level metadata for Hyper-Personalization compatibility
          $item['root_design_prompt']          = null;
          $item['root_ai_agent_instructions']  = null;
          $item['root_global_settings']        = isset($raw_data['global_ui_config']) ? $raw_data['global_ui_config'] : null;
          $item['source_file']                 = $file;
          $current_features[] = $item;
        }
        $current_schema = isset($raw_data['schema']) ? $raw_data['schema'] : array(
          'item_fields' => array('id', 'category', 'title', 'severity', 'description')
        );
      } else {
        $current_features = $raw_data;
      }

      if (empty($schema) && !empty($current_schema)) $schema = $current_schema;

      foreach ($current_features as &$feature) {
        $label = isset($feature['name']) ? $feature['name'] : (isset($feature['title']) ? $feature['title'] : (isset($feature['label']) ? $feature['label'] : __('Unnamed Feature', 'vaptsecure')));
        $feature['label'] = $label;

        // Hyper-Personalization: Attach source-specific root nodes to each feature (v3.13.1)
        $feature['root_design_prompt'] = isset($raw_data['design_prompt']) ? $raw_data['design_prompt'] : null;
        $feature['root_ai_agent_instructions'] = isset($raw_data['ai_agent_instructions']) ? $raw_data['ai_agent_instructions'] : null;
        $feature['root_global_settings'] = isset($raw_data['global_settings']) ? $raw_data['global_settings'] : null;
        // The source_file is already set below, but for consistency with the risk_catalog block, we can add it here too.
        // However, the existing line `feature['source_file'] = $file;` is sufficient.

        $key = isset($feature['id']) ? $feature['id'] : (isset($feature['key']) ? $feature['key'] : sanitize_title($label));
        $feature['key'] = $key;

        $dedupe_key = strtolower(trim($label));

        if (isset($merged_features[$dedupe_key])) {
          $merged_features[$dedupe_key]['exists_in_multiple_files'] = true;
          continue;
        }

        $st = isset($status_map[$key]) ? $status_map[$key] : array('status' => 'Draft', 'implemented_at' => null, 'assigned_to' => null);
        $norm_status = strtolower($st['status']);
        if ($norm_status === 'implemented') $norm_status = 'release';
        if ($norm_status === 'in_progress') $norm_status = 'develop';
        if ($norm_status === 'testing')     $norm_status = 'test';
        if ($norm_status === 'available')   $norm_status = 'draft';
        $feature['normalized_status'] = $norm_status;
        $feature['status'] = ucfirst($norm_status);
        $feature['implemented_at'] = $st['implemented_at'];
        $feature['assigned_to'] = $st['assigned_to'];
        $feature['has_history'] = isset($history_counts[$key]) && $history_counts[$key]->count > 0;
        $feature['source_file'] = $file;
        $feature['exists_in_multiple_files'] = false;

        $meta = VAPTSECURE_DB::get_feature_meta($key);
        if ($meta) {
          $feature['include_test_method'] = (bool) $meta['include_test_method'];
          $feature['include_verification'] = (bool) $meta['include_verification'];
          $feature['include_verification_engine'] = isset($meta['include_verification_engine']) ? (bool) $meta['include_verification_engine'] : false;
          $feature['include_verification_guidance'] = isset($meta['include_verification_guidance']) ? (bool) $meta['include_verification_guidance'] : true;
          $feature['is_enforced'] = (bool) $meta['is_enforced'];
          $feature['is_adaptive_deployment'] = isset($meta['is_adaptive_deployment']) ? (bool) $meta['is_adaptive_deployment'] : false;
          $feature['wireframe_url'] = $meta['wireframe_url'];
          $feature['dev_instruct'] = isset($meta['dev_instruct']) ? $meta['dev_instruct'] : '';

          $schema_data = array();
          $use_override_schema = in_array($norm_status, ['test', 'release']) && !empty($meta['override_schema']);
          $source_schema_json = $use_override_schema ? $meta['override_schema'] : $meta['generated_schema'];
          if (!empty($source_schema_json)) {
            $decoded = json_decode($source_schema_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
              $schema_data = $decoded;
              // [v3.12.17] Translate URL placeholders when returning schema to UI
              $schema_data = self::translate_url_placeholders($schema_data);
              if ($use_override_schema) $feature['is_overridden'] = true;
            }
          }
          $feature['generated_schema'] = $schema_data;
          $source_impl_json = (in_array($norm_status, ['test', 'release']) && !empty($meta['override_implementation_data'])) ? $meta['override_implementation_data'] : $meta['implementation_data'];
          $feature['implementation_data'] = $source_impl_json ? json_decode($source_impl_json, true) : array();

          // [v3.12.17] Include manual_protocol and operational_notes in response
          if (!empty($meta['manual_protocol_content'])) {
            $feature['manual_protocol'] = $meta['manual_protocol_content'];
          }
          if (!empty($meta['operational_notes_content'])) {
            $feature['operational_notes'] = $meta['operational_notes_content'];
          }
        }

        $merged_features[$dedupe_key] = $feature;
      }
    }

    $features = array_values($merged_features);

    if (empty($schema)) $schema = array('item_fields' => array('id', 'category', 'title', 'severity', 'description'));

    if ($scope === 'client') {
      $domain = $request->get_param('domain');
      $enabled_features = [];
      if ($domain) {
        $dom_row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}vaptsecure_domains WHERE domain = %s", $domain));
        if (!$dom_row && strpos($domain, '.') !== false) {
          $domain_base = explode('.', $domain)[0];
          $dom_row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}vaptsecure_domains WHERE domain = %s", $domain_base));
        }
        if ($dom_row) {
          $feat_rows = $wpdb->get_results($wpdb->prepare("SELECT feature_key FROM {$wpdb->prefix}vaptsecure_domain_features WHERE domain_id = %d AND enabled = 1", $dom_row->id), ARRAY_N);
          $enabled_features = array_column($feat_rows, 0);
        }
      }
      $features = array_filter($features, function ($f) use ($enabled_features, $is_superadmin) {
        $s = $f['normalized_status'];
        if ($s === 'release') return in_array($f['key'], $enabled_features);
        return $is_superadmin && in_array($s, ['draft', 'develop', 'test']);
      });
      $features = array_values($features);
    }

    // 🛡️ v1.1 FALLBACK: Ensure AI Agent Instructions and Global Settings are loaded (v3.13.8)
    if (!$ai_agent_instructions && defined('VAPTSECURE_AI_INSTRUCTIONS')) {
      $instr_path = VAPTSECURE_PATH . 'data/' . VAPTSECURE_AI_INSTRUCTIONS;
      if (file_exists($instr_path)) {
        $instr_data = json_decode(file_get_contents($instr_path), true);
        if (isset($instr_data['ai_agent_instructions'])) {
          $ai_agent_instructions = $instr_data['ai_agent_instructions'];
        }
      }
    }

    if (!$global_settings) {
      // Try to find global_ui_config in the same file as instructions or active file
      if (isset($instr_data['global_ui_config'])) {
        $global_settings = $instr_data['global_ui_config'];
      }
    }

    $response_data = array(
      'features' => $features,
      'schema' => $schema,
      'design_prompt' => $design_prompt,
      'ai_agent_instructions' => $ai_agent_instructions,
      'global_settings' => $global_settings
    );
    if ($is_superadmin) {
      $response_data['active_catalog'] = $requested_file;
      $response_data['total_features'] = count($features);
    }
    return new WP_REST_Response($response_data, 200);
  }

  public function get_data_files()
  {
    $data_dir = VAPTSECURE_PATH . 'data';
    if (!is_dir($data_dir)) return new WP_REST_Response([], 200);

    $files = array_diff(scandir($data_dir), array('..', '.'));
    $json_files = [];

    $hidden_files  = get_option('vaptsecure_hidden_json_files', array());
    $removed_files = get_option('vaptsecure_removed_json_files', array());
    $active_option = get_option('vaptsecure_active_feature_file');
    $current_active = $active_option ? explode(',', $active_option) : array();

    $hidden_normalized  = array_map('sanitize_file_name', $hidden_files);
    $removed_normalized = array_map('sanitize_file_name', $removed_files);
    $active_normalized  = array_map('sanitize_file_name', $current_active);

    foreach ($files as $file) {
      if (is_dir($data_dir . '/' . $file)) continue;
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if ($ext !== 'json') continue;

      $normalized = sanitize_file_name($file);
      if (in_array($normalized, $removed_normalized)) continue;

      $is_hidden = in_array($normalized, $hidden_normalized);
      $is_active = in_array($normalized, $active_normalized);

      if ($is_active || !$is_hidden) {
        $json_files[] = array(
          'label' => $file,
          'value' => $file
        );
      }
    }

    return new WP_REST_Response($json_files, 200);
  }

  // Scanner methods
  public function start_scan($request)
  {
    $target_url = $request->get_param('target_url');
    if (!$target_url || !filter_var($target_url, FILTER_VALIDATE_URL)) {
      return new WP_REST_Response(['error' => 'Invalid target URL'], 400);
    }

    $scanner = new VAPTSECURE_Scanner();
    $scan_id = $scanner->start_scan($target_url);

    if ($scan_id === false) {
      return new WP_REST_Response(['error' => 'Failed to start scan'], 500);
    }

    return new WP_REST_Response(['scan_id' => $scan_id, 'status' => 'started'], 200);
  }

  public function get_scan_report($request)
  {
    $scan_id = $request->get_param('id');
    $scanner = new VAPTSECURE_Scanner();
    $report = $scanner->generate_report($scan_id);

    if (!$report) {
      return new WP_REST_Response(['error' => 'Scan not found'], 404);
    }

    return new WP_REST_Response($report, 200);
  }

  public function get_scans($request)
  {
    global $wpdb;
    $scans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vaptsecure_scans ORDER BY created_at DESC", ARRAY_A);
    return new WP_REST_Response($scans, 200);
  }

  public function update_feature($request)
  {
    $key = $request->get_param('key');
    $status = $request->get_param('status');
    $include_test = $request->get_param('include_test_method');
    $include_verification = $request->get_param('include_verification');
    $is_enforced = $request->get_param('is_enforced');
    $wireframe_url = $request->get_param('wireframe_url');
    $generated_schema = $request->get_param('generated_schema');
    $implementation_data = $request->get_param('implementation_data');
    $reset_history = $request->get_param('reset_history');

    // FIX: Lifecycle race condition. Capture initial status before transition runs.
    $current_feat_db = VAPTSECURE_DB::get_feature($key);
    $initial_status = $current_feat_db ? strtolower($current_feat_db['status']) : 'draft';

    if ($status) {
      $note = $request->get_param('history_note') ?: ($request->get_param('transition_note') ?: '');
      $result = VAPTSECURE_Workflow::transition_feature($key, $status, $note);
      if (is_wp_error($result)) {
        return new WP_REST_Response($result, 400);
      }
    }

    $meta_updates = array();
    if ($include_test !== null) $meta_updates['include_test_method'] = $include_test ? 1 : 0;
    if ($include_verification !== null) $meta_updates['include_verification'] = $include_verification ? 1 : 0;

    $include_verification_engine = $request->get_param('include_verification_engine');
    if ($include_verification_engine !== null) $meta_updates['include_verification_engine'] = $include_verification_engine ? 1 : 0;

    $include_verification_guidance = $request->get_param('include_verification_guidance');
    if ($include_verification_guidance !== null) $meta_updates['include_verification_guidance'] = $include_verification_guidance ? 1 : 0;

    $include_manual_protocol = $request->get_param('include_manual_protocol');
    if ($include_manual_protocol !== null) $meta_updates['include_manual_protocol'] = $include_manual_protocol ? 1 : 0;

    $include_operational_notes = $request->get_param('include_operational_notes');
    if ($include_operational_notes !== null) $meta_updates['include_operational_notes'] = $include_operational_notes ? 1 : 0;

    if ($is_enforced !== null) $meta_updates['is_enforced'] = $is_enforced ? 1 : 0;

    $is_adaptive = $request->get_param('is_adaptive_deployment');
    if ($is_adaptive !== null) $meta_updates['is_adaptive_deployment'] = $is_adaptive ? 1 : 0;

    if ($wireframe_url !== null) $meta_updates['wireframe_url'] = $wireframe_url;

    $dev_instruct = $request->get_param('dev_instruct');
    if ($dev_instruct !== null) $meta_updates['dev_instruct'] = $dev_instruct;

    if ($request->has_param('generated_schema')) {
      $generated_schema = $request->get_param('generated_schema');
      if ($generated_schema === null) {
        $meta_updates['generated_schema'] = null;
      } else {
        $schema = (is_array($generated_schema) || is_object($generated_schema))
          ? json_decode(json_encode($generated_schema), true)
          : json_decode($generated_schema, true);

        // 🛡️ LIFECYCLE ENFORCEMENT: Schema updates allowed only in Draft/Develop stages
        // Update: 'Test' stage allows updates but saves to OVERRIDE meta (Local customization)
        $current_status = $initial_status; // Use captured status to prevent locking during transition

        if (!in_array($current_status, ['draft', 'develop', 'test'])) {
          return new WP_REST_Response(array(
            'error' => 'Lifecycle Restriction',
            'message' => 'Design/Schema changes are strictly locked in Release stage. Current status: ' . ucfirst($current_status),
            'code' => 'lifecycle_locked'
          ), 403);
        }

        $is_legacy_format = isset($schema['type']) && in_array($schema['type'], ['wp_config', 'htaccess', 'manual', 'complex_input']);

        if (!$is_legacy_format) {
          $schema['is_adaptive_deployment'] = $is_adaptive ? 1 : 0;
          $schema = self::sanitize_and_fix_schema($schema);
          $validation = self::validate_schema($schema);
          if (is_wp_error($validation)) {
            return new WP_REST_Response(array(
              'error' => 'Schema validation failed',
              'message' => $validation->get_error_message(),
              'code' => $validation->get_error_code(),
              'schema_received' => $schema
            ), 400);
          }

          // 🛡️ DATA EXTRACTION (v3.13.0)
          // Extract rich context for specific UI tabs
          if (isset($schema['manual_protocol'])) {
            $meta_updates['manual_protocol_content'] = is_string($schema['manual_protocol'])
              ? $schema['manual_protocol']
              : json_encode($schema['manual_protocol']);
          }
          if (isset($schema['operational_notes'])) {
            $meta_updates['operational_notes_content'] = is_string($schema['operational_notes'])
              ? $schema['operational_notes']
              : json_encode($schema['operational_notes']);
          }

          // 🛡️ URL TRANSLATION (v3.12.17)
          // Translate {{site_url}} and other placeholders to fully qualified URLs
          $schema = self::translate_url_placeholders($schema);

          // 🛡️ INTELLIGENT ENFORCEMENT (v3.3.9)
          $schema = self::analyze_enforcement_strategy($schema, $key);

          // [FIX] Self-Healing for XML-RPC (v3.12.13)
          // Detected missing enforcement in legacy schema, patching from catalog.
          $is_xml_rpc = (stripos($key, 'xml-rpc') !== false) || (stripos($key, 'xmlrpc') !== false) || $key === 'RISK-016-001';

          if ($is_xml_rpc && empty($schema['enforcement'])) {
            $schema['enforcement'] = [
              'driver' => 'htaccess',
              'target' => 'root',
              'mappings' => [
                'UI-xml-rpc-api-security-001' => "<Files xmlrpc.php>\n  Order Deny,Allow\n  Deny from all\n</Files>"
              ]
            ];
            // Auto-update the generated schema variable
            $generated_schema = $schema;
          }
        }

        if ($current_status === 'test') {
          $meta_updates['override_schema'] = json_encode($schema);
        } else {
          $meta_updates['generated_schema'] = json_encode($schema);
        }
      }
    }

    if ($request->has_param('implementation_data')) {
      $current_status = $initial_status; // Use captured status

      // 🛡️ VALIDATION: Check implementation data against schema (v3.6.19)
      // Get the effective schema for validation
      $schema_for_val = null;
      if ($request->has_param('generated_schema')) {
        $schema_for_val = $schema; // Already decoded above
      } else {
        $meta = VAPTSECURE_DB::get_feature_meta($key);
        $raw_schema = ($current_status === 'test') ? ($meta['override_schema'] ?? $meta['generated_schema']) : ($meta['generated_schema'] ?? null);
        $schema_for_val = $raw_schema ? json_decode($raw_schema, true) : null;
      }

      $implementation_data = $request->get_param('implementation_data');

      // 🛡️ TYPE SANITIZATION: Handle stringified JSON from client (v3.6.19 Fix)
      if (is_string($implementation_data)) {
        $decoded = json_decode($implementation_data, true);
        if (is_array($decoded)) {
          $implementation_data = $decoded;
        }
      }

      if ($schema_for_val) {
        $val_result = self::validate_implementation_data($implementation_data, $schema_for_val);
        if (is_wp_error($val_result)) {
          // [FIX] Proactive Error Reporting
          return new WP_REST_Response(array(
            'error' => 'Implementation validation failed',
            'message' => $val_result->get_error_message(),
            'code' => $val_result->get_error_code()
          ), 400);
        }
      }

      $val = ($implementation_data === null) ? null : (is_array($implementation_data) ? json_encode($implementation_data) : $implementation_data);

      if ($current_status === 'test') {
        $meta_updates['override_implementation_data'] = $val;
      } else {
        $meta_updates['implementation_data'] = $val;
      }
    }

    if (! empty($meta_updates)) {
      VAPTSECURE_DB::update_feature_meta($key, $meta_updates);
      do_action('vaptsecure_feature_saved', $key, $meta_updates);
    }

    if ($reset_history) {
      global $wpdb;
      $history_table = $wpdb->prefix . 'vaptsecure_feature_history';
      $wpdb->delete($history_table, array('feature_key' => $key), array('%s'));
    }

    return new WP_REST_Response(array('success' => true), 200);
  }

  // =========================================================================
  // v1.8.0 – HINT BACKFILL: enrich existing generated_schemas with `help`
  // =========================================================================
  /**
   * Preview what would be affected by a batch revert to Draft.
   * GET /vaptsecure/v1/features/preview-revert
   */
  public function preview_revert_to_draft($request)
  {
    $include_broken = (bool) $request->get_param('include_broken');
    $result = VAPTSECURE_Workflow::preview_revert_to_draft($include_broken);
    return new WP_REST_Response($result, 200);
  }

  /**
   * Execute batch revert all Develop features to Draft.
   * POST /vaptsecure/v1/features/batch-revert
   */
  public function batch_revert_to_draft($request)
  {
    $note = $request->get_param('note') ?: 'Batch revert to Draft via Workbench';
    $include_broken = (bool) $request->get_param('include_broken');

    $result = VAPTSECURE_Workflow::batch_revert_to_draft($note, $include_broken);

    if (!$result['success']) {
      return new WP_REST_Response($result, 207); // Multi-status (partial success)
    }

    return new WP_REST_Response($result, 200);
  }

  public function update_file_meta($request)
  {
    $file = $request->get_param('file');
    $key = $request->get_param('key');
    $value = $request->get_param('value');

    if (!$file || !$key) {
      return new WP_REST_Response(array('error' => 'Missing file or key param'), 400);
    }

    $json_path = VAPTSECURE_PATH . 'data/' . sanitize_file_name($file);

    if (!file_exists($json_path)) {
      return new WP_REST_Response(array('error' => 'File not found'), 404);
    }

    $content = file_get_contents($json_path);
    $data = json_decode($content, true);

    if (!is_array($data)) {
      return new WP_REST_Response(array('error' => 'Invalid JSON in file'), 500);
    }

    if ($value === null) {
      unset($data[$key]);
    } else {
      $data[$key] = $value;
    }

    $saved = file_put_contents($json_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if ($saved === false) {
      return new WP_REST_Response(array('error' => 'Failed to write to file'), 500);
    }

    return new WP_REST_Response(array('success' => true, 'updated_key' => $key), 200);
  }

  public function transition_feature($request)
  {
    $key = $request->get_param('key');
    $status = $request->get_param('status');
    $note = $request->get_param('note') ?: '';

    $result = VAPTSECURE_Workflow::transition_feature($key, $status, $note);

    if (is_wp_error($result)) {
      return new WP_REST_Response($result, 400);
    }

    return new WP_REST_Response(array('success' => true), 200);
  }

  public function get_feature_history($request)
  {
    $key = $request['key'];
    $history = VAPTSECURE_Workflow::get_history($key);

    return new WP_REST_Response($history, 200);
  }

  public function get_feature_stats($request)
  {
    $key = $request['key'];
    require_once(VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-hook-driver.php');
    if (method_exists('VAPTSECURE_Hook_Driver', 'get_feature_stats')) {
      $stats = VAPTSECURE_Hook_Driver::get_feature_stats($key);
      return new WP_REST_Response($stats, 200);
    }
    return new WP_REST_Response(['error' => 'Method not supported'], 500);
  }

  public function reset_feature_stats($request)
  {
    $key = $request['key'];
    require_once(VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-hook-driver.php');
    if (method_exists('VAPTSECURE_Hook_Driver', 'reset_feature_stats')) {
      $count = VAPTSECURE_Hook_Driver::reset_feature_stats($key);
      return new WP_REST_Response(['success' => true, 'deleted_locks' => $count], 200);
    }
    return new WP_REST_Response(['error' => 'Method not supported'], 500);
  }

  public function upload_json($request)
  {
    error_log('VAPT Secure: Starting JSON upload...');

    $files = $request->get_file_params();
    if (empty($files['file'])) {
      error_log('VAPT Secure: No file param found.');
      return new WP_REST_Response(array('error' => 'No file uploaded'), 400);
    }

    $file = $files['file'];
    error_log('VAPT Secure: Received file ' . $file['name'] . ' size ' . $file['size']);

    if ($file['error'] !== UPLOAD_ERR_OK) {
      error_log('VAPT Secure: PHP Upload Error ' . $file['error']);
      return new WP_REST_Response(array('error' => 'PHP Upload Error: ' . $file['error']), 500);
    }

    $filename = sanitize_file_name($file['name']);
    $content = file_get_contents($file['tmp_name']);

    if ($content === false) {
      error_log('VAPT Secure: Could not read temp file.');
      return new WP_REST_Response(array('error' => 'Failed to read uploaded file.'), 500);
    }

    $data = json_decode($content, true);
    if (is_null($data)) {
      error_log('VAPT Secure: Invalid JSON content.');
      return new WP_REST_Response(array('error' => 'Invalid JSON'), 400);
    }

    $json_path = VAPTSECURE_PATH . 'data/' . $filename;
    $data_dir = VAPTSECURE_PATH . 'data/';

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    WP_Filesystem();
    global $wp_filesystem;

    if (!$wp_filesystem->is_dir($data_dir)) {
      if (!$wp_filesystem->mkdir($data_dir)) {
        return new WP_REST_Response(array('error' => 'Data directory missing and could not be created: ' . $data_dir), 500);
      }
    }

    // Proactively attempt to fix permissions if not writable
    if (!$wp_filesystem->is_writable($data_dir)) {
      $wp_filesystem->chmod($data_dir, 0755);
    }

    if (!$wp_filesystem->is_writable($data_dir)) {
      $method = $wp_filesystem->method;
      return new WP_REST_Response(array(
        'error' => "Data directory is not writable via standard WordPress API ($method method). Please check folder permissions for: " . $data_dir
      ), 500);
    }

    $saved = $wp_filesystem->put_contents($json_path, $content, FS_CHMOD_FILE);

    if (!$saved) {
      return new WP_REST_Response(array('error' => 'WP_Filesystem failed to write file to: ' . $json_path), 500);
    }

    error_log('VAPT Secure: Upload successful to ' . $json_path);

    // ... rest of the logic remains same ...

    // Auto-unhide if it was hidden
    $hidden_files = get_option('vaptsecure_hidden_json_files', array());
    $normalized_hidden = array_map('sanitize_file_name', $hidden_files);

    if (in_array($filename, $normalized_hidden) || in_array($files['file']['name'], $hidden_files)) {
      $new_hidden = array_filter($hidden_files, function ($f) use ($filename, $files) {
        return sanitize_file_name($f) !== $filename && $f !== $files['file']['name'];
      });
      update_option('vaptsecure_hidden_json_files', array_values($new_hidden));
    }

    // Auto-restore if it was removed
    $removed_files = get_option('vaptsecure_removed_json_files', array());
    $normalized_removed = array_map('sanitize_file_name', $removed_files);

    if (in_array($filename, $normalized_removed) || in_array($files['file']['name'], $removed_files)) {
      $new_removed = array_filter($removed_files, function ($f) use ($filename, $files) {
        return sanitize_file_name($f) !== $filename && $f !== $files['file']['name'];
      });
      update_option('vaptsecure_removed_json_files', array_values($new_removed));
    }

    return new WP_REST_Response(array('success' => true, 'filename' => $filename), 200);
  }

  public function update_hidden_files($request)
  {
    $hidden_files = $request->get_param('hidden_files');
    if (!is_array($hidden_files)) {
      $hidden_files = array();
    }

    $hidden_files = array_map('sanitize_file_name', $hidden_files);

    update_option('vaptsecure_hidden_json_files', $hidden_files);
    $this->sanitize_active_file();

    return new WP_REST_Response(array('success' => true, 'hidden_files' => $hidden_files), 200);
  }

  public function remove_data_file($request)
  {
    $filename = $request->get_param('filename');
    if (!$filename) {
      return new WP_REST_Response(array('error' => 'Missing filename'), 400);
    }

    $active_file = defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : 'VAPT-SixTee-Risk-Catalogue-12-EntReady_v3.4.json';
    if ($filename === $active_file || sanitize_file_name($filename) === sanitize_file_name($active_file)) {
      return new WP_REST_Response(array('error' => 'Cannot remove the active file.'), 400);
    }

    $removed_files = get_option('vaptsecure_removed_json_files', array());
    if (!in_array($filename, $removed_files)) {
      $removed_files[] = $filename;
      update_option('vaptsecure_removed_json_files', $removed_files);
      $this->sanitize_active_file();
    }

    return new WP_REST_Response(array('success' => true), 200);
  }

  public function reset_rate_limit($request)
  {
    require_once(VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-hook-driver.php');
    if (class_exists('VAPTSECURE_Hook_Driver')) {
      $result = VAPTSECURE_Hook_Driver::reset_limit();
      return new WP_REST_Response(array('success' => true, 'debug' => $result), 200);
    }
    return new WP_REST_Response(array('error' => 'Hook driver not found'), 500);
  }

  /**
   * Clear the enforcement cache transient.
   * POST /vaptsecure/v1/clear-cache
   */
  public function clear_enforcement_cache($request)
  {
    delete_transient('vaptsecure_active_enforcements');

    // Also clear any other VAPT transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vaptsecure_%' OR option_name LIKE '_site_transient_vaptsecure_%'");

    return new WP_REST_Response(array(
      'success' => true,
      'message' => 'Enforcement cache cleared successfully. Refresh the page to see updated features.'
    ), 200);
  }

  /**
   * Debug enforcement state - shows what's in the database and cache.
   * GET /vaptsecure/v1/debug-enforcement
   */
  public function debug_enforcement_state($request)
  {
    global $wpdb;

    // Check transient cache
    $cached = get_transient('vaptsecure_active_enforcements');

    // Check database directly
    $table = $wpdb->prefix . 'vaptsecure_feature_meta';
    $db_results = $wpdb->get_results("
      SELECT m.feature_key, m.implementation_data, m.is_enforced, s.status
      FROM $table m
      LEFT JOIN {$wpdb->prefix}vaptsecure_feature_status s ON m.feature_key = s.feature_key
      WHERE m.implementation_data IS NOT NULL 
        AND m.implementation_data != '' 
        AND m.implementation_data != '{}'
        AND m.implementation_data != 'null'
    ", ARRAY_A);

    // Check all features with is_enforced = 1
    $enforced_features = $wpdb->get_results("
      SELECT m.feature_key, m.implementation_data, m.is_enforced, s.status
      FROM $table m
      LEFT JOIN {$wpdb->prefix}vaptsecure_feature_status s ON m.feature_key = s.feature_key
      WHERE m.is_enforced = 1
    ", ARRAY_A);

    return new WP_REST_Response(array(
      'cache_exists' => $cached !== false,
      'cache_count' => is_array($cached) ? count($cached) : 0,
      'cache_keys' => is_array($cached) ? array_column($cached, 'feature_key') : [],
      'db_count' => count($db_results),
      'db_keys' => array_column($db_results, 'feature_key'),
      'enforced_flag_count' => count($enforced_features),
      'enforced_flag_keys' => array_column($enforced_features, 'feature_key'),
      'db_results' => $db_results,
    ), 200);
  }

  public function get_all_data_files()
  {
    $data_dir = VAPTSECURE_PATH . 'data';
    if (!is_dir($data_dir)) return new WP_REST_Response([], 200);

    $files = array_diff(scandir($data_dir), array('..', '.'));
    $json_files = [];
    $hidden_files  = get_option('vaptsecure_hidden_json_files', array());
    $removed_files = get_option('vaptsecure_removed_json_files', array());

    $hidden_normalized  = array_map('sanitize_file_name', $hidden_files);
    $removed_normalized = array_map('sanitize_file_name', $removed_files);

    foreach ($files as $file) {
      if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'json') {
        $normalized_current = sanitize_file_name($file);

        // Skip removed files
        if (in_array($normalized_current, $removed_normalized) || in_array($file, $removed_files)) {
          continue;
        }

        $json_files[] = array(
          'filename' => $file,
          'isHidden' => in_array($normalized_current, $hidden_normalized) || in_array($file, $hidden_files)
        );
      }
    }

    return new WP_REST_Response($json_files, 200);
  }

  public function get_domains()
  {
    global $wpdb;
    $domains = VAPTSECURE_DB::get_domains();

    foreach ($domains as &$domain) {
      $domain_id = $domain['id'];
      $feat_rows = $wpdb->get_results($wpdb->prepare("SELECT feature_key FROM {$wpdb->prefix}vaptsecure_domain_features WHERE domain_id = %d AND enabled = 1", $domain_id), ARRAY_N);
      $domain['features'] = array_column($feat_rows, 0);
      $domain['imported_at'] = get_option('vaptsecure_imported_at_' . $domain['domain'], null);
    }

    return new WP_REST_Response($domains, 200);
  }

  public function update_domain($request)
  {
    global $wpdb;
    $domain = $request->get_param('domain');
    $is_wildcard = $request->get_param('is_wildcard');
    $license_id = $request->get_param('license_id');
    $license_type = $request->get_param('license_type') ?: 'standard';
    $manual_expiry_date = $request->get_param('manual_expiry_date');
    $auto_renew = $request->get_param('auto_renew') !== null ? ($request->get_param('auto_renew') ? 1 : 0) : null;
    $action = $request->get_param('action');
    $license_scope = $request->get_param('license_scope');
    $installation_limit = $request->get_param('installation_limit');

    $id = $request->get_param('id');
    if ($id) {
      $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vaptsecure_domains WHERE id = %d", $id), ARRAY_A);
      if ($current && !$domain) $domain = $current['domain'];
    } else {
      $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vaptsecure_domains WHERE domain = %s", $domain), ARRAY_A);
    }

    $history = $current && !empty($current['renewal_history']) ? json_decode($current['renewal_history'], true) : array();

    $renewals_count = $request->has_param('renewals_count') ? (int) $request->get_param('renewals_count') : ($current ? (int)$current['renewals_count'] : 0);
    if ($auto_renew === null && $current) $auto_renew = (int)$current['auto_renew'];

    if ($request->has_param('is_wildcard')) {
      $val = $request->get_param('is_wildcard');
      $is_wildcard = (is_string($val)) ? ($val === 'true' || $val === '1') : (bool)$val;
    } else if ($current) {
      $is_wildcard = (int)$current['is_wildcard'];
    }

    if ($request->has_param('is_enabled')) {
      $val = $request->get_param('is_enabled');
      $is_enabled = (is_string($val)) ? ($val === 'true' || $val === '1') : (bool)$val;
    } else if ($current) {
      $is_enabled = (int)$current['is_enabled'];
    } else {
      $is_enabled = 1;
    }
    if ($license_id === null && $current) $license_id = $current['license_id'];
    if ($manual_expiry_date === null && $current) $manual_expiry_date = $current['manual_expiry_date'];
    if ($license_scope === null && $current) $license_scope = $current['license_scope'] ?: 'single';
    if ($installation_limit === null && $current) $installation_limit = $current['installation_limit'] ?: 1;

    // Auto-generate license ID for new domains if missing (Glitch Fix)
    if (!$current && empty($license_id)) {
      $prefix = 'STD-';
      if ($license_type === 'pro') $prefix = 'PRO-';
      if ($license_type === 'developer') $prefix = 'DEV-';
      $license_id = $prefix . strtoupper(substr(md5(uniqid()), 0, 9));
    }

    if ($manual_expiry_date) {
      $manual_expiry_date = date('Y-m-d 00:00:00', strtotime($manual_expiry_date));
    }

    $today_ts = strtotime(date('Y-m-d 00:00:00'));
    $current_exp_ts = ($current && !empty($current['manual_expiry_date'])) ? strtotime(date('Y-m-d', strtotime($current['manual_expiry_date']))) : 0;
    $new_exp_ts = $manual_expiry_date ? strtotime(date('Y-m-d', strtotime($manual_expiry_date))) : 0;

    if ($action === 'invalidate') {
      $manual_expiry_date = '1970-01-01 00:00:00';
    } else if ($action === 'undo' && !empty($history)) {
      $last = array_pop($history);
      $days = (int) $last['duration_days'];
      $manual_expiry_date = date('Y-m-d 00:00:00', strtotime($current['manual_expiry_date'] . " -$days days"));
      $renewals_count = max(0, (int)$current['renewals_count'] - 1);
    } else if ($action === 'reset' && !empty($history)) {
      $temp_expiry_ts = $current_exp_ts;
      $temp_count = $renewals_count;

      while (!empty($history)) {
        $entry = end($history);
        if ($entry['source'] === 'auto') break;

        $days = (int) $entry['duration_days'];
        $potential_expiry_ts = strtotime(date('Y-m-d 00:00:00', $temp_expiry_ts) . " -$days days");

        if ($potential_expiry_ts < $today_ts) break;

        array_pop($history);
        $temp_expiry_ts = $potential_expiry_ts;
        $temp_count = max(0, $temp_count - 1);
      }
      $manual_expiry_date = date('Y-m-d 00:00:00', $temp_expiry_ts);
      $renewals_count = $temp_count;
    } else {
      if ($current && $new_exp_ts > $current_exp_ts) {
        $diff = $new_exp_ts - $current_exp_ts;
        $days = round($diff / 86400);

        if ($days > 0) {
          $source = $request->get_param('renew_source') ?: 'manual';
          $history[] = array(
            'date_added' => current_time('mysql'),
            'duration_days' => $days,
            'license_type' => $license_type,
            'source' => $source
          );
          $renewals_count++;
        }
      }

      if ($auto_renew && $new_exp_ts < $today_ts) {
        $duration = '+30 days';
        $days = 30;
        if ($license_type === 'pro') {
          $duration = '+1 year';
          $days = 365;
        }
        if ($license_type === 'developer') {
          $duration = '+100 years';
          $days = 36500;
        }

        $manual_expiry_date = date('Y-m-d 00:00:00', strtotime($manual_expiry_date . ' ' . $duration));
        $renewals_count++;

        $history[] = array(
          'date_added' => current_time('mysql'),
          'duration_days' => $days,
          'license_type' => $license_type,
          'source' => 'auto'
        );
      }
    }

    $result_id = VAPTSECURE_DB::update_domain($domain, $is_wildcard ? 1 : 0, $is_enabled ? 1 : 0, $id, $license_id, $license_type, $manual_expiry_date, $auto_renew, $renewals_count, $history, $license_scope, $installation_limit);

    if ($result_id === false) {
      return new WP_REST_Response(array('error' => 'Database update failed'), 500);
    }

    $fresh = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vaptsecure_domains WHERE id = %d", $result_id), ARRAY_A);

    return new WP_REST_Response(array('success' => true, 'domain' => $fresh), 200);
  }

  public function delete_domain($request)
  {
    $domain_id = $request->get_param('id');
    if (!$domain_id) {
      return new WP_REST_Response(array('error' => 'Missing domain ID'), 400);
    }

    VAPTSECURE_DB::delete_domain($domain_id);
    return new WP_REST_Response(array('success' => true), 200);
  }

  public function batch_delete_domains($request)
  {
    $ids = $request->get_param('ids');
    if (!$ids || !is_array($ids)) {
      return new WP_REST_Response(array('error' => 'Missing or invalid domain IDs'), 400);
    }

    VAPTSECURE_DB::batch_delete_domains($ids);
    return new WP_REST_Response(array('success' => true), 200);
  }

  public function update_domain_features($request)
  {
    global $wpdb;
    $domain_id = $request->get_param('domain_id');
    $features = $request->get_param('features');

    if (! is_array($features)) {
      return new WP_REST_Response(array('error' => 'Invalid features format'), 400);
    }

    $table = $wpdb->prefix . 'vaptsecure_domain_features';

    $wpdb->delete($table, array('domain_id' => $domain_id), array('%d'));

    foreach ($features as $key) {
      $wpdb->insert($table, array(
        'domain_id'   => $domain_id,
        'feature_key' => $key,
        'enabled'     => 1
      ), array('%d', '%s', '%d'));
    }

    return new WP_REST_Response(array('success' => true), 200);
  }

  public function generate_build($request)
  {
    $data = $request->get_json_params();
    if (!is_array($data)) {
      $data = [];
    }

    // Merge other parameters
    $data['include_config'] = $request->get_param('include_config');
    $data['include_data'] = $request->get_param('include_data');
    $data['license_scope'] = $request->get_param('license_scope');
    $data['installation_limit'] = $request->get_param('installation_limit');

    // Delegate to Build Class
    require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-build.php';
    try {
      $download_url = VAPTSECURE_Build::generate($data);
      return new WP_REST_Response(array('success' => true, 'download_url' => $download_url), 200);
    } catch (Exception $e) {
      return new WP_REST_Response(array('success' => false, 'message' => $e->getMessage()), 500);
    }
  }

  public function save_config_to_root($request)
  {
    $domain = $request->get_param('domain');
    $version = $request->get_param('version');
    $features = $request->get_param('features');
    $license_scope = $request->get_param('license_scope') ?: 'single';
    $installation_limit = $request->get_param('installation_limit') ?: 1;

    if (!$domain || !$version) {
      return new WP_REST_Response(array('error' => 'Missing domain or version'), 400);
    }

    require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-build.php';
    $config_content = VAPTSECURE_Build::generate_config_content($domain, $version, $features, null, $license_scope, $installation_limit);
    $filename = "vapt-{$domain}-config-{$version}.php";
    $filepath = VAPTSECURE_PATH . $filename;

    $saved = file_put_contents($filepath, $config_content);

    if ($saved !== false) {
      return new WP_REST_Response(array('success' => true, 'path' => $filepath, 'filename' => $filename), 200);
    } else {
      return new WP_REST_Response(array('error' => 'Failed to write config file to plugin root'), 500);
    }
  }

  public function sync_config_from_file($request)
  {
    $domain = $request->get_param('domain');
    if (!$domain) {
      return new WP_REST_Response(array('error' => 'Missing domain'), 400);
    }

    $files = glob(VAPTSECURE_PATH . "vapt-*-config-*.php");
    $matched_file = null;

    if ($files) {
      foreach ($files as $file) {
        if (strpos(basename($file), "vapt-{$domain}-config-") !== false) {
          $matched_file = $file;
          break;
        }
      }
    }

    if (!$matched_file && file_exists(VAPTSECURE_PATH . 'vapt-locked-config.php')) {
      $matched_file = VAPTSECURE_PATH . 'vapt-locked-config.php';
    }

    if (!$matched_file) {
      return new WP_REST_Response(array('error' => 'No config file found for domain: ' . $domain), 404);
    }

    $content = file_get_contents($matched_file);
    preg_match_all("/define\( 'VAPTSECURE_FEATURE_(.*?)', true \);/", $content, $matches);

    $features = array();
    if (!empty($matches[1])) {
      foreach ($matches[1] as $key_upper) {
        $features[] = strtolower($key_upper);
      }
    }

    $version = 'Unknown';
    if (preg_match("/Build Version: (.*?)[\r\n]/", $content, $v_match)) {
      $version = trim($v_match[1]);
    }

    update_option('vaptsecure_imported_at_' . $domain, current_time('mysql'));
    update_option('vaptsecure_imported_version_' . $domain, $version);

    return new WP_REST_Response(array(
      'success' => true,
      'imported_at' => current_time('mysql'),
      'version' => $version,
      'features_count' => count($features),
      'features' => $features
    ), 200);
  }

  public function get_assignees()
  {
    $users = get_users(array('role' => 'administrator'));
    $assignees = array_map(function ($u) {
      return array('id' => $u->ID, 'name' => $u->display_name);
    }, $users);

    return new WP_REST_Response($assignees, 200);
  }

  public function update_assignment($request)
  {
    global $wpdb;
    $key = $request->get_param('key');
    $user_id = $request->get_param('user_id');
    $table_status = $wpdb->prefix . 'vaptsecure_feature_status';
    $wpdb->update($table_status, array('assigned_to' => $user_id ? $user_id : null), array('feature_key' => $key));

    return new WP_REST_Response(array('success' => true), 200);
  }

  public function upload_media($request)
  {
    if (empty($_FILES['file'])) {
      return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
    }

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $upload_dir_filter = function ($uploads) {
      $subdir = '/vapt-wireframes';
      $uploads['subdir'] = $subdir;
      $uploads['path']   = $uploads['basedir'] . $subdir;
      $uploads['url']    = $uploads['baseurl'] . $subdir;

      if (! file_exists($uploads['path'])) {
        wp_mkdir_p($uploads['path']);
      }
      return $uploads;
    };

    add_filter('upload_dir', $upload_dir_filter);

    $file = $_FILES['file'];
    $upload_overrides = array('test_form' => false);

    $movefile = wp_handle_upload($file, $upload_overrides);

    remove_filter('upload_dir', $upload_dir_filter);

    if ($movefile && ! isset($movefile['error'])) {
      $filename = $movefile['file'];
      $attachment = array(
        'guid'           => $movefile['url'],
        'post_mime_type' => $movefile['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
        'post_content'   => '',
        'post_status'    => 'inherit'
      );

      $attach_id = wp_insert_attachment($attachment, $filename);
      $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
      wp_update_attachment_metadata($attach_id, $attach_data);

      return new WP_REST_Response(array(
        'success' => true,
        'url'     => $movefile['url'],
        'id'      => $attach_id
      ), 200);
    } else {
      return new WP_Error('upload_error', $movefile['error'], array('status' => 500));
    }
  }

  /**
   * 🛡️ INTELLIGENT ENFORCEMENT STRATEGY (v3.3.9)
   * Analyzes the schema and automatically corrects driver selection 
   * if it detects physical file targets being handled by PHP hooks.
   */
  private static function analyze_enforcement_strategy($schema, $feature_key)
  {
    if (!isset($schema['enforcement'])) {
      $schema['enforcement'] = [
        'driver' => 'hook',
        'mappings' => []
      ];
    }
    $driver = $schema['enforcement']['driver'] ?? 'hook';
    $mappings = $schema['enforcement']['mappings'] ?? array();

    $physical_file_patterns = [
      'readme.html',
      'license.txt',
      'xmlrpc.php',
      'wp-config.php',
      '.env',
      'wp-links-opml.php',
      'debug.log',
      '.htaccess'
    ];

    $block_indicators = ['<Files', 'Require all', 'Deny from', 'Order allow,deny', 'Options -Indexes'];

    $needs_htaccess = false;
    foreach ($mappings as $key => $value) {
      $val_to_test = '';
      if (is_string($value)) {
        $val_to_test = $value;
      } elseif (is_array($value)) {
        // v1.1 rich mapping detection
        if (isset($value['.htaccess'])) {
          $needs_htaccess = true;
          $val_to_test = is_array($value['.htaccess']) ? ($value['.htaccess']['code'] ?? '') : $value['.htaccess'];
        }
      }

      if (!$val_to_test) continue;

      // Check for physical file mentions or Apache directives in mappings
      foreach ($physical_file_patterns as $file) {
        if (stripos($val_to_test, $file) !== false) {
          $needs_htaccess = true;
          break 2;
        }
      }

      foreach ($block_indicators as $indicator) {
        if (stripos($val_to_test, $indicator) !== false) {
          $needs_htaccess = true;
          break 2;
        }
      }
    }

    // Auto-Correct if driver is 'hook' but needs 'htaccess' or 'wp-config'
    if ($needs_htaccess && $driver === 'hook') {
      error_log("VAPT Intelligence: Auto-switching driver to 'htaccess' for feature $feature_key based on physical file target.");
      $schema['enforcement']['driver'] = 'htaccess';
      $schema['enforcement']['target'] = $schema['enforcement']['target'] ?? 'root';
      $driver = 'htaccess'; // Update local variable for subsequent logic
    }

    // [v3.13.2] Auto-Correct for wp-config constants
    $needs_config = false;
    foreach ($mappings as $key => $value) {
      $val_to_test = '';
      if (is_string($value)) {
        $val_to_test = $value;
      } elseif (is_array($value)) {
        if (isset($value['wp-config.php']) || isset($value['wp_config'])) {
          $needs_config = true;
          $inner = $value['wp-config.php'] ?? $value['wp_config'];
          $val_to_test = is_array($inner) ? ($inner['code'] ?? '') : $inner;
        }
      }

      if ($val_to_test && strpos($val_to_test, 'define(') !== false) {
        $needs_config = true;
        break;
      }
    }

    if ($needs_config && $driver === 'hook') {
      error_log("VAPT Intelligence: Auto-switching driver to 'wp-config' for feature $feature_key based on define constant.");
      $schema['enforcement']['driver'] = 'wp-config';
      $driver = 'wp-config'; // Update local
    }

    // Auto-Correct Mapping Key Mismatch (feat_key vs feat_enabled)
    if (isset($mappings['feat_key'])) {
      $has_feat_key = false;
      $primary_toggle = null;
      $items = $schema['controls'] ?? ($schema['components'] ?? []);

      foreach ($items as $ctrl) {
        $ctrl_key = $ctrl['key'] ?? ($ctrl['component_id'] ?? null);
        if ($ctrl_key === 'feat_key') $has_feat_key = true;
        if (isset($ctrl['type']) && $ctrl['type'] === 'toggle' && $ctrl_key) {
          $primary_toggle = $ctrl_key;
        }
      }

      if (!$has_feat_key && $primary_toggle) {
        error_log("VAPT Intelligence: Auto-correcting mapping key 'feat_key' to '$primary_toggle' for feature $feature_key.");
        $schema['enforcement']['mappings'][$primary_toggle] = $mappings['feat_key'];
        unset($schema['enforcement']['mappings']['feat_key']);
      }
    }

    // [v3.12.3] Ensure Production Defaults
    if ($driver === 'htaccess') {
      $schema['enforcement']['backup'] = $schema['enforcement']['backup'] ?? true;
      $schema['enforcement']['rollback_on_disable'] = $schema['enforcement']['rollback_on_disable'] ?? true;
    }

    // 🛡️ SUB-DIRECTORY ENFORCEMENT (v1.1)
    if ($driver === 'htaccess' && !isset($schema['enforcement']['target_file'])) {
      $target_file = self::resolve_target_file_from_catalogue($feature_key);
      if ($target_file !== '.htaccess') {
        error_log("VAPT Intelligence: Setting custom target_file $target_file for $feature_key");
        $schema['enforcement']['target_file'] = $target_file;
      }
    }

    // 🛡️ GLOBAL ENFORCEMENT BRIDGE (v1.1)
    // If mappings are empty or incomplete, resolve them from the pattern library or catalogue
    if ($driver === 'htaccess' || $driver === 'wp-config' || $driver === 'hook') {
      if (empty($schema['enforcement']['mappings'])) {
        // Try htaccess first if hook
        $bridge_driver = ($driver === 'hook') ? 'htaccess' : $driver;
        $code = self::resolve_enforcement_from_catalogue($feature_key, $bridge_driver);

        if ($code) {
          $mapping_key = 'feat_key'; // Default
          if (isset($schema['components'])) {
            foreach ($schema['components'] as $comp) {
              if (isset($comp['type']) && $comp['type'] === 'toggle' && isset($comp['component_id'])) {
                $mapping_key = $comp['component_id'];
                break;
              }
            }
          } elseif (isset($schema['controls'])) {
            foreach ($schema['controls'] as $ctrl) {
              if (isset($ctrl['type']) && $ctrl['type'] === 'toggle' && isset($ctrl['key'])) {
                $mapping_key = $ctrl['key'];
                break;
              }
            }
          }
          $schema['enforcement']['mappings'][$mapping_key] = $code;
          $schema['enforcement']['driver'] = $bridge_driver;
          error_log("VAPT Intelligence: Bridged missing enforcement code for $feature_key using $bridge_driver driver.");
        }
      }
    }

    // 🛡️ HTACCESS SYNTAX GUARD (v1.1)
    if ($driver === 'htaccess' && !empty($schema['enforcement']['mappings'])) {
      foreach ($schema['enforcement']['mappings'] as $key => &$code) {
        if (!is_string($code)) continue;

        // 1. Forbidden <Directory> replacement
        if (stripos($code, '<Directory') !== false) {
          error_log("VAPT Syntax Guard: Replacing forbidden <Directory> block in $feature_key");
          $code = preg_replace('/<Directory\s+[^>]+>/i', '<FilesMatch ".*">', $code);
          $code = str_ireplace('</Directory>', '</FilesMatch>', $code);
        }

        // 2. Forbidden Server-Level Directives
        $forbidden = ['TraceEnable', 'ServerSignature', 'ServerTokens', 'UseCanonicalName'];
        foreach ($forbidden as $directive) {
          if (stripos($code, $directive) !== false) {
            error_log("VAPT Syntax Guard: Stripping forbidden server directive $directive from $feature_key");
            $code = preg_replace('/' . $directive . '\s+\w+/i', '# [REMOVED BY GUARD] ' . $directive, $code);
          }
        }

        // 3. Ensure RewriteEngine On is present if RewriteRule/RewriteCond is used
        if ((stripos($code, 'RewriteRule') !== false || stripos($code, 'RewriteCond') !== false) && stripos($code, 'RewriteEngine On') === false) {
          $code = "RewriteEngine On\n" . $code;
        }
      }
    }

    return $schema;
  }

  /**
   * 🛡️ RESOLVE ENFORCEMENT FROM CATALOGUE (v3.13.5)
   */
  private static function resolve_enforcement_from_catalogue($feature_key, $driver)
  {
    // 🛡️ v1.1 Pattern Library Priority
    $pattern_lib_path = VAPTSECURE_PATH . 'data/' . (defined('VAPTSECURE_PATTERN_LIBRARY') ? VAPTSECURE_PATTERN_LIBRARY : 'enforcer_pattern_library_v1.1.json');
    if (file_exists($pattern_lib_path)) {
      $lib = json_decode(file_get_contents($pattern_lib_path), true);
      if (isset($lib['patterns'][$feature_key])) {
        $p = $lib['patterns'][$feature_key];

        // Handle corrected htaccess
        if ($driver === 'htaccess' && isset($p['htaccess_corrected']['code'])) {
          return $p['htaccess_corrected']['code'];
        }

        // Handle wp-config enriched
        if ($driver === 'wp-config' && isset($p['wp_config_enriched']['code'])) {
          return $p['wp_config_enriched']['code'];
        }

        // Fallback to platform-specific keys if direct corrected not found
        if (isset($p[$driver]['code'])) {
          return $p[$driver]['code'];
        }
      }
    }

    // 🛡️ Legacy Fallback (v3.13.5)
    $catalog_path = VAPTSECURE_PATH . 'data/VAPT-Risk-Catalogue-Full-125-v3.4.1.json';
    if (!file_exists($catalog_path)) return null;

    $catalog = json_decode(file_get_contents($catalog_path), true);
    if (!isset($catalog['risk_catalog'])) return null;

    foreach ($catalog['risk_catalog'] as $item) {
      $id = $item['risk_id'] ?? $item['id'] ?? $item['key'] ?? '';
      if ($id === $feature_key) {
        $steps = $item['protection']['automated_protection']['implementation_steps'] ?? [];
        foreach ($steps as $step) {
          $enforcer = strtolower($step['enforcer'] ?? '');
          if (($driver === 'htaccess' && strpos($enforcer, 'htaccess') !== false) ||
            ($driver === 'wp-config' && strpos($enforcer, 'config') !== false)
          ) {
            if (!empty($step['code'])) return $step['code'];
          }
        }
        $examples = $item['code_examples'] ?? [];
        foreach ($examples as $ex) {
          $desc = strtolower($ex['description'] ?? '');
          if (($driver === 'htaccess' && (strpos($desc, 'htaccess') !== false || strpos($desc, 'apache') !== false)) ||
            ($driver === 'wp-config' && strpos($desc, 'config') !== false)
          ) {
            $code = $ex['code'] ?? '';
            if (preg_match('/```(?:apache|php|htaccess)?\s*(.*?)\s*```/is', $code, $m)) {
              $code = $m[1];
            }
            return trim($code);
          }
        }
      }
    }
    return null;
  }

  /**
   * 🛡️ RESOLVE TARGET ENDPOINT (v3.13.5)
   */
  private static function resolve_target_endpoint_from_catalogue($feature_key)
  {
    $catalog_path = VAPTSECURE_PATH . 'data/VAPT-Risk-Catalogue-Full-125-v3.4.1.json';
    if (!file_exists($catalog_path)) return null;

    $catalog = json_decode(file_get_contents($catalog_path), true);
    if (!isset($catalog['risk_catalog'])) return null;

    foreach ($catalog['risk_catalog'] as $item) {
      $id = $item['risk_id'] ?? $item['id'] ?? $item['key'] ?? '';
      if ($id === $feature_key) {
        $steps = $item['protection']['automated_protection']['implementation_steps'] ?? [];
        foreach ($steps as $step) {
          $path = !empty($step['target_pattern']) ? $step['target_pattern'] : (!empty($step['target']) ? $step['target'] : '');
          if ($path) return trim(ltrim(rtrim($path, '$'), '^'));
        }
        $rollback = $item['protection']['rollback_steps'] ?? [];
        foreach ($rollback as $rb) {
          if (!empty($rb['target'])) return trim(ltrim(rtrim($rb['target'], '$'), '^'));
        }
      }
    }
    return null;
  }

  /**
   * 🛡️ RESOLVE TARGET FILE (v1.1)
   * Resolves the physical file target, supporting sub-directories (e.g. uploads/.htaccess)
   */
  private static function resolve_target_file_from_catalogue($feature_key)
  {
    $pattern_lib_path = VAPTSECURE_PATH . 'data/' . (defined('VAPTSECURE_PATTERN_LIBRARY') ? VAPTSECURE_PATTERN_LIBRARY : 'enforcer_pattern_library_v1.1.json');
    if (file_exists($pattern_lib_path)) {
      $lib = json_decode(file_get_contents($pattern_lib_path), true);
      if (isset($lib['patterns'][$feature_key]['htaccess_corrected']['target_file'])) {
        return $lib['patterns'][$feature_key]['htaccess_corrected']['target_file'];
      }
    }
    return '.htaccess'; // Default
  }

  /**
   * Auto-fix common schema issues before validation.
   */
  private static function sanitize_and_fix_schema($schema)
  {
    if (!isset($schema['controls']) || !is_array($schema['controls'])) {
      return $schema;
    }

    $no_key_types = ['button', 'info', 'alert', 'section', 'group', 'divider', 'html', 'header', 'label', 'evidence_uploader', 'risk_indicators', 'assurance_badges', 'remediation_steps', 'test_checklist', 'evidence_list'];

    foreach ($schema['controls'] as $index => &$control) {
      if (!is_array($control)) continue;

      // Map 'dropdown' to 'select' for backward compatibility
      if (isset($control['type']) && $control['type'] === 'dropdown') {
        $control['type'] = 'select';
      }

      // Fix missing key
      if (empty($control['key']) && !empty($control['type']) && !in_array($control['type'], $no_key_types)) {
        // Try to find a meaningful ID
        $base = $control['id'] ?? ($control['component_id'] ?? 'control');
        $control['key'] = sanitize_key($base . '_' . $index . '_' . wp_generate_password(4, false));
      }
    }

    return $schema;
  }

  private static function validate_schema($schema)
  {
    if (!is_array($schema)) {
      return new WP_Error('invalid_schema', 'Schema must be an object/array', array('status' => 400));
    }

    if (!isset($schema['controls']) || !is_array($schema['controls'])) {
      return new WP_Error(
        'invalid_schema',
        'Schema must have a "controls" array',
        array('status' => 400)
      );
    }

    foreach ($schema['controls'] as $index => $control) {
      if (!is_array($control)) {
        return new WP_Error(
          'invalid_schema',
          sprintf('Control at index %d must be an object', $index),
          array('status' => 400)
        );
      }

      if (empty($control['type'])) {
        return new WP_Error(
          'invalid_schema',
          sprintf('Control at index %d must have a "type" field', $index),
          array('status' => 400)
        );
      }

      $no_key_types = ['button', 'info', 'alert', 'section', 'group', 'divider', 'html', 'header', 'label', 'evidence_uploader', 'risk_indicators', 'assurance_badges', 'remediation_steps', 'test_checklist', 'evidence_list'];
      if (empty($control['key']) && !in_array($control['type'], $no_key_types)) {
        return new WP_Error(
          'invalid_schema',
          sprintf('Control at index %d must have a "key" field', $index),
          array('status' => 400)
        );
      }

      $valid_types = ['toggle', 'input', 'select', 'textarea', 'code', 'test_action', 'button', 'info', 'alert', 'section', 'group', 'divider', 'html', 'header', 'label', 'password', 'evidence_uploader', 'risk_indicators', 'assurance_badges', 'remediation_steps', 'test_checklist', 'evidence_list'];
      if (!in_array($control['type'], $valid_types)) {
        return new WP_Error(
          'invalid_schema',
          sprintf(
            'Control at index %d has invalid type "%s". Valid types: %s',
            $index,
            $control['type'],
            implode(', ', $valid_types)
          ),
          array('status' => 400)
        );
      }

      if ($control['type'] === 'test_action') {
        if (empty($control['test_logic'])) {
          return new WP_Error(
            'invalid_schema',
            sprintf(
              'Test action control "%s" must have a "test_logic" field',
              $control['key'] ?? $index
            ),
            array('status' => 400)
          );
        }
      }
    }

    if (isset($schema['enforcement'])) {
      if (!is_array($schema['enforcement'])) {
        return new WP_Error(
          'invalid_schema',
          'Enforcement section must be an object',
          array('status' => 400)
        );
      }

      if (empty($schema['enforcement']['driver'])) {
        return new WP_Error(
          'invalid_schema',
          'Enforcement must specify a "driver" (hook or htaccess)',
          array('status' => 400)
        );
      }

      $valid_drivers = ['hook', 'htaccess', 'universal', 'manual', 'config', 'wp-config'];
      if (!in_array($schema['enforcement']['driver'], $valid_drivers)) {
        return new WP_Error(
          'invalid_schema',
          sprintf(
            'Invalid enforcement driver "%s". Valid drivers: %s',
            $schema['enforcement']['driver'],
            implode(', ', $valid_drivers)
          ),
          array('status' => 400)
        );
      }

      if ($schema['enforcement']['driver'] === 'htaccess' && empty($schema['enforcement']['target'])) {
        return new WP_Error(
          'invalid_schema',
          'Htaccess driver must specify a "target" (root or uploads)',
          array('status' => 400)
        );
      }

      if (isset($schema['enforcement']['mappings']) && !is_array($schema['enforcement']['mappings'])) {
        return new WP_Error(
          'invalid_schema',
          'Enforcement mappings must be an object/array',
          array('status' => 400)
        );
      }
    }

    return true;
  }

  /**
   * 🛡️ IMPLEMENTATION VALIDATOR (v3.6.19)
   * Validates user-provided implementation settings against the feature's JSON schema.
   */
  private static function validate_implementation_data($data, $schema)
  {
    if (!isset($schema['controls']) || !is_array($schema['controls'])) {
      return true; // No controls to validate against (dynamic features)
    }

    if (!is_array($data)) {
      return new WP_Error('invalid_impl_data', 'Implementation data must be an object/array', array('status' => 400));
    }

    foreach ($schema['controls'] as $control) {
      $key = $control['key'] ?? null;
      if (!$key) continue;

      if (!isset($data[$key])) {
        // Only error if it's marked as required (non-existent field currently, but for future proofing)
        if (!empty($control['required'])) {
          return new WP_Error('missing_field', sprintf('Missing required field: %s', $key), array('status' => 400));
        }
        continue;
      }

      $value = $data[$key];
      $type = $control['type'] ?? 'text';

      switch ($type) {
        case 'toggle':
          if (!is_bool($value) && $value !== 0 && $value !== 1 && $value !== '0' && $value !== '1') {
            return new WP_Error('invalid_type', sprintf('Field %s must be a boolean/toggle', $key), array('status' => 400));
          }
          break;

        case 'input':
        case 'password':
          if ($control['input_type'] === 'number') {
            if (!is_numeric($value)) {
              return new WP_Error('invalid_type', sprintf('Field %s must be numeric', $key), array('status' => 400));
            }
            if (isset($control['min']) && (float)$value < (float)$control['min']) {
              return new WP_Error('out_of_range', sprintf('Field %s is below minimum (%s)', $key, $control['min']), array('status' => 400));
            }
            if (isset($control['max']) && (float)$value > (float)$control['max']) {
              return new WP_Error('out_of_range', sprintf('Field %s is above maximum (%s)', $key, $control['max']), array('status' => 400));
            }
          }
          break;

        case 'select':
          if (isset($control['options'])) {
            $valid_values = array_map(function ($opt) {
              return is_array($opt) ? $opt['value'] : $opt;
            }, $control['options']);
            if (!in_array($value, $valid_values)) {
              return new WP_Error('invalid_option', sprintf('Field %s contains an invalid option', $key), array('status' => 400));
            }
          }
          break;

        case 'code':
        case 'textarea':
          if (!is_string($value) && $value !== null) {
            return new WP_Error('invalid_type', sprintf('Field %s must be a string', $key), array('status' => 400));
          }
          break;
      }
    }

    return true;
  }

  /**
   * 🔍 VERIFICATION PING (v3.6.19)
   * Two-Way Activation/Deactivation check.
   */
  public function verify_implementation($request)
  {
    $key = $request['key'];
    $meta = VAPTSECURE_DB::get_feature_meta($key);
    if (!$meta) {
      return new WP_Error('not_found', 'Feature not found', array('status' => 404));
    }

    $current_feat = VAPTSECURE_DB::get_feature($key);
    $current_status = $current_feat ? strtolower($current_feat['status']) : 'draft';

    $schema_raw = ($current_status === 'test' ? ($meta['override_schema'] ?? $meta['generated_schema']) : $meta['generated_schema']);
    $impl_raw = ($current_status === 'test' ? ($meta['override_implementation_data'] ?? $meta['implementation_data']) : $meta['implementation_data']);

    $schema = json_decode($schema_raw, true);
    $impl_data = json_decode($impl_raw, true);

    if (!$schema) {
      return new WP_REST_Response(array(
        'success'  => false,
        'message'  => 'No schema generated for this feature.',
        'status'   => 'unconfigured'
      ), 200);
    }

    $driver = $schema['enforcement']['driver'] ?? 'hook';
    $is_active = false;

    // Instantiate appropriate driver for verification
    switch ($driver) {
      case 'hook':
        $is_active = VAPTSECURE_Hook_Driver::verify($key, $impl_data, $schema);
        break;
      case 'htaccess':
        $is_active = VAPTSECURE_Htaccess_Driver::verify($key, $impl_data, $schema);
        break;
      default:
        // For nginx/iis, we might just check if the implementation data exists and is enabled
        $is_active = isset($impl_data['enabled']) ? (bool)$impl_data['enabled'] : false;
    }

    // Two-Way Strategy: If UI says disabled, we expect is_active to be false
    $expected_enabled = isset($impl_data['enabled']) ? (bool)$impl_data['enabled'] : false;
    $sync_status = ($is_active === $expected_enabled) ? 'in_sync' : 'out_of_sync';

    return new WP_REST_Response(array(
      'success'     => true,
      'is_active'   => $is_active,
      'expected'    => $expected_enabled,
      'sync_status' => $sync_status,
      'timestamp'   => current_time('mysql'),
      'driver'      => $driver
    ), 200);
  }

  public function handle_active_file($request)
  {
    if ($request->get_method() === 'POST') {
      $file = $request->get_param('file');
      if (!$file) {
        return new WP_REST_Response(array('error' => 'No file specified'), 400);
      }

      $files = array_filter(explode(',', $file));
      $sanitized_files = array_map('sanitize_file_name', $files);

      // v3.12.1: Strict existence check
      $valid_files = [];
      foreach ($sanitized_files as $f) {
        if (file_exists(VAPTSECURE_PATH . 'data/' . $f)) {
          $valid_files[] = $f;
        }
      }

      if (empty($valid_files)) {
        return new WP_REST_Response(array('error' => 'None of the specified files exist on the server'), 400);
      }

      $filename = implode(',', $valid_files);
      update_option('vaptsecure_active_feature_file', $filename);
      return new WP_REST_Response(array('success' => true, 'active_file' => $filename), 200);
    }

    $active = get_option('vaptsecure_active_feature_file');
    if (!$active && defined('VAPTSECURE_ACTIVE_DATA_FILE')) {
      $active = VAPTSECURE_ACTIVE_DATA_FILE;
    }
    if (!$active) {
      $active = 'VAPT-SixTee-Risk-Catalogue-12-EntReady_v3.4.json';
    }


    return new WP_REST_Response(array(
      'active_file' => $active
    ), 200);
  }

  /**
   * v3.12.1: Strictly sanitize the active file option against existence
   */
  private function sanitize_active_file()
  {
    $active = get_option('vaptsecure_active_feature_file');
    if (!$active) return;

    $files = array_filter(explode(',', $active));
    $valid_files = [];
    foreach ($files as $f) {
      if (file_exists(VAPTSECURE_PATH . 'data/' . sanitize_file_name($f))) {
        $valid_files[] = $f;
      }
    }

    if (count($valid_files) !== count($files)) {
      if (empty($valid_files)) {
        update_option('vaptsecure_active_feature_file', 'VAPT-SixTee-Risk-Catalogue-12-EntReady_v3.4.json');
      } else {
        update_option('vaptsecure_active_feature_file', implode(',', $valid_files));
      }
    }
  }

  /**
   * Translate URL placeholders in schema to fully qualified URLs (v3.12.17)
   * 
   * @param array $schema The schema array
   * @return array The schema with translated URLs
   */
  private static function translate_url_placeholders($schema)
  {
    $site_url = get_site_url();
    $home_url = get_home_url();
    $admin_url = get_admin_url();

    $replacements = array(
      '{{site_url}}' => $site_url,
      '{{home_url}}' => $home_url,
      '{{admin_url}}' => $admin_url,
    );

    // Recursively walk through the schema and replace placeholders
    array_walk_recursive($schema, function (&$value) use ($replacements) {
      if (is_string($value)) {
        $value = str_replace(array_keys($replacements), array_values($replacements), $value);
      }
    });

    return $schema;
  }
  /**
   * GET /vaptsecure/v1/security/stats
   */
  public function get_security_stats()
  {
    return new WP_REST_Response(VAPTSECURE_DB::get_security_stats_summary(), 200);
  }

  /**
   * GET /vaptsecure/v1/security/logs
   */
  public function get_security_logs($request)
  {
    $limit = $request->get_param('limit') ?: 50;
    $offset = $request->get_param('offset') ?: 0;
    return new WP_REST_Response(VAPTSECURE_DB::get_security_events($limit, $offset), 200);
  }

  /**
   * Get Global Enforcement
   * @v3.13.20
   */
  public function get_global_enforcement($request)
  {
    return new WP_REST_Response(array(
      'enabled' => VAPTSECURE_DB::get_global_enforcement()
    ), 200);
  }

  /**
   * Update Global Enforcement
   * @v3.13.20
   */
  public function update_global_enforcement($request)
  {
    $params = $request->get_json_params();
    $enabled = isset($params['enabled']) ? (bool)$params['enabled'] : true;

    VAPTSECURE_DB::update_global_enforcement($enabled);

    // [v3.13.20] Trigger immediate rebuild of all config files
    require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-enforcer.php';
    VAPTSECURE_Enforcer::rebuild_all();

    return new WP_REST_Response(array(
      'success' => true,
      'enabled' => $enabled
    ), 200);
  }
}
