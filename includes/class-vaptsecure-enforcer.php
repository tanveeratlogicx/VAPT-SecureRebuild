<?php

/**
 * VAPTSECURE_Enforcer: The Global Security Hammer
 * 
 * Acts as a generic dispatcher that routes enforcement requests to specific drivers
 * (Htaccess, Hooks, etc.) based on the feature's generated_schema.
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Enforcer
{

  public static function init()
  {
    // Listen for workbench saves
    add_action('vaptsecure_feature_saved', array(__CLASS__, 'dispatch_enforcement'), 10, 2);

    // Apply PHP-based hooks at runtime
    self::runtime_enforcement();
  }

  /**
   * Applies all active 'hook' based enforcements on every request
   */
  public static function runtime_enforcement()
  {
    $cache_key = 'vaptsecure_active_enforcements';
    $enforced = get_transient($cache_key);

    if (false === $enforced) {
      global $wpdb;
      $table = $wpdb->prefix . 'vaptsecure_feature_meta';
      $enforced = $wpdb->get_results("
        SELECT m.*, s.status 
        FROM $table m
        LEFT JOIN {$wpdb->prefix}vaptsecure_feature_status s ON m.feature_key = s.feature_key
        WHERE m.is_enforced = 1
      ", ARRAY_A);
      set_transient($cache_key, $enforced, HOUR_IN_SECONDS);
    }

    if (empty($enforced)) return;

    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-hook-driver.php';

    foreach ($enforced as $meta) {
      $status = isset($meta['status']) ? strtolower($meta['status']) : 'draft';

      // Override Logic
      $use_override_schema = in_array($status, ['test', 'release']) && !empty($meta['override_schema']);
      $raw_schema = $use_override_schema ? $meta['override_schema'] : $meta['generated_schema'];
      $schema = !empty($raw_schema) ? json_decode($raw_schema, true) : array();

      $use_override_impl = in_array($status, ['test', 'release']) && !empty($meta['override_implementation_data']);
      $raw_impl = $use_override_impl ? $meta['override_implementation_data'] : $meta['implementation_data'];
      $impl_data = !empty($raw_impl) ? json_decode($raw_impl, true) : array();

      $driver = isset($schema['enforcement']['driver']) ? $schema['enforcement']['driver'] : '';

      // Hook driver is universally shared for PHP-based fallback rules
      // [v2.0.5] Include config/wp-config to ensure enforcement markers (headers) are registered
      if ($driver === 'hook' || $driver === 'universal' || $driver === 'htaccess' || $driver === 'config' || $driver === 'wp-config' || $driver === 'wp_config') {
        if (class_exists('VAPTSECURE_Hook_Driver')) {
          VAPTSECURE_Hook_Driver::apply($impl_data, $schema, $meta['feature_key']);
        }
      }
    }
  }

  /**
   * Entry point for enforcement after a feature is saved.
   * Always triggers a rebuild so toggling OFF also removes rules from config files.
   */
  public static function dispatch_enforcement($key, $data)
  {
    // Clear runtime cache so changes apply instantly
    delete_transient('vaptsecure_active_enforcements');

    $meta = VAPTSECURE_DB::get_feature_meta($key);
    if (!$meta) return;

    // Fetch Status for Context
    global $wpdb;
    $status_row = $wpdb->get_row($wpdb->prepare("SELECT status FROM {$wpdb->prefix}vaptsecure_feature_status WHERE feature_key = %s", $key));
    $status = $status_row ? strtolower($status_row->status) : 'draft';

    // Override Logic
    $use_override_schema = in_array($status, ['test', 'release']) && !empty($meta['override_schema']);
    $raw_schema = $use_override_schema ? $meta['override_schema'] : $meta['generated_schema'];
    $schema = !empty($raw_schema) ? json_decode($raw_schema, true) : array();

    // [FIX v1.4.0] Always rebuild even if this feature has no enforcement block.
    // This ensures that toggling OFF removes previously written rules from config files.
    if (empty($schema['enforcement'])) {
      $server = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : '';
      if (strpos($server, 'nginx') !== false) {
        self::rebuild_nginx();
      } else {
        self::rebuild_htaccess();
      }
      self::rebuild_config();
      return;
    }

    // [v4.0.0] Adaptive Deployment Orchestration
    if (!empty($meta['is_adaptive_deployment'])) {
      require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-deployment-orchestrator.php';
      $orchestrator = new VAPTSECURE_Deployment_Orchestrator();

      // Use profile from settings if available, else default to auto_detect
      $profile = get_option('vaptsecure_deployment_profile', 'auto_detect');
      $results = $orchestrator->orchestrate($key, $schema, $profile);

      error_log("VAPT: Adaptive Deployment for {$key} results: " . json_encode($results));
      return;
    }

    $driver_name = $schema['enforcement']['driver'];

    // Dispatch to the correct driver
    if ($driver_name === 'htaccess') {
      // UNIVERSAL FIX: Rebuild based on Server Type
      $server = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : '';

      if (strpos($server, 'nginx') !== false) {
        self::rebuild_nginx();
      } elseif (strpos($server, 'iis') !== false || strpos($server, 'windows') !== false) {
        self::rebuild_iis();
      } else {
        // Default to Apache/.htaccess
        self::rebuild_htaccess();
      }
      self::rebuild_config();
    } elseif ($driver_name === 'nginx') {
      self::rebuild_nginx();
    } elseif ($driver_name === 'iis') {
      self::rebuild_iis();
    } elseif ($driver_name === 'caddy') {
      self::rebuild_caddy();
    } elseif ($driver_name === 'cloudflare') {
      self::rebuild_cloudflare();
    } elseif ($driver_name === 'config' || $driver_name === 'wp_config' || $driver_name === 'wp-config') {
      self::rebuild_config();
    } else {
      // For hooks, we just rely on the runtime loader (next request will pick it up)
      // No explicit action needed other than clearing cache (done above).
    }
  }

  /**
   * Rebuilds Nginx Rules File
   */
  private static function rebuild_nginx()
  {
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-nginx-driver.php';

    $features = self::get_enforced_features();
    $all_rules = [];

    foreach ($features as $meta) {
      $schema = self::resolve_schema($meta);
      $impl = self::resolve_impl($meta);

      if (($schema['enforcement']['driver'] ?? '') === 'htaccess') {
        $rules = VAPTSECURE_Nginx_Driver::generate_rules($impl, $schema);
        if ($rules) $all_rules = array_merge($all_rules, $rules);
      }
    }

    VAPTSECURE_Nginx_Driver::write_batch($all_rules);
  }

  /**
   * Rebuilds IIS Config
   */
  private static function rebuild_iis()
  {
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-iis-driver.php';
    if (!class_exists('VAPTSECURE_IIS_Driver')) return;

    $features = self::get_enforced_features();
    $all_rules = [];

    foreach ($features as $meta) {
      $schema = self::resolve_schema($meta);
      $impl = self::resolve_impl($meta);
      $driver = $schema['enforcement']['driver'] ?? '';

      if ($driver === 'iis' || $driver === 'htaccess') {
        $rules = VAPTSECURE_IIS_Driver::generate_rules($impl, $schema);
        if ($rules) $all_rules = array_merge($all_rules, $rules);
      }
    }

    VAPTSECURE_IIS_Driver::write_batch($all_rules);
  }

  /**
   * Rebuilds Caddy Rules File
   */
  private static function rebuild_caddy()
  {
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-caddy-driver.php';
    if (!class_exists('VAPTSECURE_Caddy_Driver')) return;

    $features = self::get_enforced_features();
    $all_rules = [];

    foreach ($features as $meta) {
      $schema = self::resolve_schema($meta);
      $impl = self::resolve_impl($meta);
      $driver = $schema['enforcement']['driver'] ?? '';

      if ($driver === 'caddy' || $driver === 'htaccess') {
        $rules = VAPTSECURE_Caddy_Driver::generate_rules($impl, $schema);
        if ($rules) $all_rules = array_merge($all_rules, $rules);
      }
    }

    VAPTSECURE_Caddy_Driver::write_batch($all_rules);
  }

  /**
   * Rebuilds Cloudflare (Interface/API Meta)
   */
  private static function rebuild_cloudflare()
  {
    // Cloudflare enforcement is currently informational/manual via the dashboard instructions.
    // In future versions, this would trigger an API sync.
    error_log('VAPT: Cloudflare rebuild triggered (Informational - Manual Action required in Dashboard)');
  }

  // Helper to fetch enforced features (DRY)
  private static function get_enforced_features()
  {
    global $wpdb;
    $table = $wpdb->prefix . 'vaptsecure_feature_meta';
    return $wpdb->get_results("SELECT m.*, s.status FROM $table m LEFT JOIN {$wpdb->prefix}vaptsecure_feature_status s ON m.feature_key = s.feature_key WHERE m.is_enforced = 1", ARRAY_A);
  }

  // Helpers for Schema/Impl Resolution
  private static function resolve_schema($meta)
  {
    $status = $meta['status'] ?? 'draft';
    $raw = (in_array($status, ['test', 'release']) && !empty($meta['override_schema'])) ? $meta['override_schema'] : $meta['generated_schema'];
    $schema = $raw ? json_decode($raw, true) : [];

    // [v3.12.5] Inject feature key if missing
    if (!isset($schema['feature_key']) && isset($meta['feature_key'])) {
      $schema['feature_key'] = $meta['feature_key'];
    }

    return $schema;
  }

  private static function resolve_impl($meta)
  {
    $status = $meta['status'] ?? 'draft';
    $raw = (in_array($status, ['test', 'release']) && !empty($meta['override_implementation_data'])) ? $meta['override_implementation_data'] : $meta['implementation_data'];
    return $raw ? json_decode($raw, true) : [];
  }

  /**
   * Rebuilds .htaccess files by aggregating rules from ALL enabled features.
   */
  private static function rebuild_htaccess()
  {
    error_log('VAPT DEBUG rebuild_htaccess - Function called');
    $enforced_features = self::get_enforced_features();

    // [ENHANCEMENT] Filter by Active Data Files (v3.12.0)
    // [FIX v1.4.0] Only apply key filter when we actually have active keys - prevents
    // silently dropping all features when the active file resolves to an empty key list.
    $active_keys = self::get_active_file_keys();
    if (!empty($active_keys)) {
      $enforced_features = array_filter($enforced_features, function ($feat) use ($active_keys) {
        // [FIX] Always allow XML-RPC regardless of key mismatch (v3.12.13)
        if (strpos($feat['feature_key'], 'xml-rpc') !== false || strpos($feat['feature_key'], 'xmlrpc') !== false || $feat['feature_key'] === 'RISK-016-001') {
          return true;
        }
        return in_array($feat['feature_key'], $active_keys);
      });
    }

    error_log('VAPT DEBUG rebuild_htaccess - Found ' . count($enforced_features) . ' enforced features after filtering');

    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-htaccess-driver.php';
    if (!class_exists('VAPTSECURE_Htaccess_Driver')) return;

    // Group rules by target
    $targets_rules = array(
      'root' => array(),
      'uploads' => array()
    );

    foreach ($enforced_features as $meta) {
      $schema = self::resolve_schema($meta);
      $impl_data = self::resolve_impl($meta);
      $driver = isset($schema['enforcement']['driver']) ? $schema['enforcement']['driver'] : '';
      $target = isset($schema['enforcement']['target']) ? $schema['enforcement']['target'] : 'root';

      // 🛡️ Map common alias ".htaccess" to standard "root" target (v3.13.15)
      if ($target === '.htaccess') {
        $target = 'root';
      }

      if ($driver === 'htaccess') {
        $feature_rules = VAPTSECURE_Htaccess_Driver::generate_rules($impl_data, $schema);
        if (!empty($feature_rules)) {
          if (!isset($targets_rules[$target])) {
            $targets_rules[$target] = array();
          }
          $targets_rules[$target] = array_merge($targets_rules[$target], $feature_rules);
        }
      }
    }

    // Write batch for each target
    foreach ($targets_rules as $target => $rules) {
      VAPTSECURE_Htaccess_Driver::write_batch($rules, $target);
    }
  }

  /**
   * Rebuilds all wp-config.php rules across active features
   */
  public static function rebuild_config()
  {
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-config-driver.php';

    $enforced_features = self::get_enforced_features();

    // [ENHANCEMENT] Filter by Active Data Files (v3.12.0)
    // [FIX v1.4.0] Only apply key filter when we actually have active keys.
    $active_keys = self::get_active_file_keys();
    if (!empty($active_keys)) {
      $enforced_features = array_filter($enforced_features, function ($feat) use ($active_keys) {
        return in_array($feat['feature_key'], $active_keys);
      });
    }

    $all_rules = array();

    if (!empty($enforced_features)) {
      foreach ($enforced_features as $meta) {
        $schema = self::resolve_schema($meta);
        $impl_data = self::resolve_impl($meta);
        $driver = $schema['enforcement']['driver'] ?? '';

        if ($driver === 'config' || $driver === 'wp-config' || $driver === 'wp_config') {
          $feature_rules = VAPTSECURE_Config_Driver::generate_rules($impl_data, $schema);
          if (!empty($feature_rules)) {
            $all_rules[] = "// Rule for: " . ($meta['feature_key']);
            $all_rules = array_merge($all_rules, $feature_rules);
          }
        }
      }
    }

    return VAPTSECURE_Config_Driver::write_batch($all_rules);
  }

  /**
   * Rebuilds all enforcements across all active drivers
   */
  public static function rebuild_all()
  {
    self::rebuild_htaccess();
    self::rebuild_config();
    self::rebuild_nginx();
    self::rebuild_iis();
    self::rebuild_caddy();
    delete_transient('vaptsecure_active_enforcements');
  }

  /**
   * Helper to fetch all feature keys present in the currently active data files.
   */
  private static function get_active_file_keys()
  {
    $active_files_raw = defined('VAPTSECURE_ACTIVE_DATA_FILE') ? VAPTSECURE_ACTIVE_DATA_FILE : get_option('vaptsecure_active_feature_file', '');
    $files = array_filter(explode(',', $active_files_raw));
    $keys = [];

    foreach ($files as $file) {
      $path = VAPTSECURE_PATH . 'data/' . sanitize_file_name(trim($file));
      if (file_exists($path)) {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if ($data) {
          $features = $data['risk_catalog'] ?? $data['features'] ?? $data['wordpress_vapt'] ?? $data['risk_interfaces'] ?? null;

          if ($features && (is_array($features) || is_object($features))) {
            foreach ($features as $k => $v) {
              if (is_array($v) || is_object($v)) {
                $keys[] = $v['risk_id'] ?? $v['id'] ?? $v['key'] ?? (is_string($k) ? $k : '');
              }
            }
          } else {
            // Flat Object structure (v1.1)
            foreach ($data as $k => $v) {
              if (is_array($v) && (isset($v['risk_id']) || isset($v['id']) || isset($v['key']))) {
                $keys[] = $v['risk_id'] ?? $v['id'] ?? $v['key'] ?? $k;
              } else if (preg_match('/^RISK-\d+/', $k)) {
                // Heuristic: If key looks like RISK-NNN, it's a feature
                $keys[] = $k;
              }
            }
          }
        }
      }
    }
    return array_unique(array_filter($keys));
  }

  /**
   * Robustly extract implementation code from a mapping.
   * Handles strings, arrays, and JSON-encoded platform objects.
   * 
   * [v1.4.0] Support for v1.2/v2.0 Schema-First Architecture Platform Objects.
   */
  public static function extract_code_from_mapping($directive, $platform = 'htaccess')
  {
    if (empty($directive)) return '';

    // If it's a JSON string, decode it first
    if (is_string($directive) && strpos(trim($directive), '{') === 0) {
      $decoded = json_decode($directive, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        $directive = $decoded;
      }
    }

    if (is_array($directive)) {
      // 1. Check for specific platform keys
      $platform_keys = [
        $platform,
        '.' . ltrim($platform, '.'), // .htaccess
        str_replace('-', '_', $platform), // wp_config
        str_replace('_', '-', $platform), // wp-config
      ];

      foreach ($platform_keys as $pK) {
        if (isset($directive[$pK])) {
          $inner = $directive[$pK];
          return is_array($inner) ? ($inner['code'] ?? '') : $inner;
        }
      }

      // 1b. Robust Iteration (Handle leading/trailing whitespace in keys)
      foreach ($directive as $k => $v) {
        $tk = trim((string)$k);
        foreach ($platform_keys as $pK) {
          if ($tk === $pK) {
            return is_array($v) ? ($v['code'] ?? '') : $v;
          }
        }
      }

      // 2. Fallback to generic 'code' field
      if (isset($directive['code'])) {
        return $directive['code'];
      }

      // 3. Fallback to first non-array element (v3.12.5 legacy)
      foreach ($directive as $v) {
        if (is_string($v) && strlen($v) > 0) return $v;
      }
    }

    return is_string($directive) ? $directive : '';
  }
}
