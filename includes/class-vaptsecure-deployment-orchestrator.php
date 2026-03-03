<?php

/**
 * VAPTSECURE_Deployment_Orchestrator: Grade A+ Orchestration Logic
 * 
 * Coordinates multi-platform deployment based on environment detection.
 * Implements the v4.0.0 Deployment Engine.
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Deployment_Orchestrator
{
  private $detector;
  private $deployers = [];
  private $deployment_log = [];

  public function __construct()
  {
    require_once VAPTSECURE_PATH . 'includes/class-vaptsecure-environment-detector.php';
    $this->detector = new VAPTSECURE_Environment_Detector();

    $this->init_deployers();
  }

  private function init_deployers()
  {
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-apache-deployer.php';
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-nginx-deployer.php';
    require_once VAPTSECURE_PATH . 'includes/enforcers/class-vaptsecure-php-deployer.php';

    $this->deployers = [
      'apache_htaccess' => new VAPTSECURE_Apache_Deployer(),
      'nginx_config'    => new VAPTSECURE_Nginx_Deployer(),
      'php_functions'   => new VAPTSECURE_PHP_Deployer()
    ];
  }

  /**
   * Orchestrates deployment for a specific feature
   * 
   * @param string $risk_id
   * @param array $schema The interface schema (v3.2+)
   * @param string $profile auto_detect|maximum|conservative
   */
  public function orchestrate($risk_id, $schema, $profile = 'auto_detect')
  {
    $env = $this->detector->detect();
    $results = [];

    // 1. Resolve Platform Matrix
    $platform_matrix = $schema['platform_matrix'] ?? $this->derive_matrix_from_legacy($schema);

    // 2. Select Targets
    $targets = $this->resolve_targets($profile, $env, $platform_matrix);

    // 3. Execute Deployment
    foreach ($targets as $platform) {
      if (isset($this->deployers[$platform]) && isset($platform_matrix[$platform])) {
        $deployer = $this->deployers[$platform];
        $implementation = $platform_matrix[$platform];

        $res = $deployer->deploy($risk_id, $implementation);
        if (is_wp_error($res)) {
          $results[$platform] = [
            'success' => false,
            'error' => $res->get_error_message()
          ];
        } else {
          $results[$platform] = array_merge(['success' => true], $res);
        }
      }
    }

    // 4. Update Deployment History
    $this->log_deployment($risk_id, $results, $env, $profile);

    return $results;
  }

  private function resolve_targets($profile, $env, $matrix)
  {
    $targets = [];
    $optimal = $env['optimal_platform'];

    switch ($profile) {
      case 'maximum_protection':
        // Deploy to all available platforms defined in the matrix
        $targets = array_keys($matrix);
        break;

      case 'conservative':
        // Only deploy to PHP and .htaccess if safe
        if (isset($matrix['php_functions'])) $targets[] = 'php_functions';
        if ($optimal === 'apache_htaccess') $targets[] = 'apache_htaccess';
        break;

      case 'auto_detect':
      default:
        // Primary: Optimal Platform
        if (isset($matrix[$optimal])) {
          $targets[] = $optimal;
        }

        // Fallback: Always include PHP if defined and not already optimal
        if ($optimal !== 'php_functions' && isset($matrix['php_functions'])) {
          $targets[] = 'php_functions';
        }
        break;
    }

    return array_unique($targets);
  }

  private function derive_matrix_from_legacy($schema)
  {
    $matrix = [];
    $driver = $schema['enforcement']['driver'] ?? 'hook';
    $mappings = $schema['enforcement']['mappings'] ?? [];
    $target = $schema['enforcement']['target'] ?? 'root';

    if (empty($mappings)) return $matrix;

    // Map legacy drivers to v4.0 platforms
    if ($driver === 'htaccess') {
      $matrix['apache_htaccess'] = ['rules' => $mappings, 'target' => $target];
      // Also provide a PHP fallback for mixed environments
      $matrix['php_functions'] = ['code' => '/* Managed via htaccess redirect */'];
    } elseif ($driver === 'nginx') {
      $matrix['nginx_config'] = ['rules' => $mappings];
      $matrix['php_functions'] = ['code' => '/* Managed via nginx config */'];
    } elseif ($driver === 'wp-config') {
      $matrix['php_functions'] = ['code' => $mappings]; // wp-config rules are essentially PHP defines
    } else {
      $matrix['php_functions'] = ['code' => $mappings];
    }

    return $matrix;
  }

  private function log_deployment($risk_id, $results, $env, $profile)
  {
    $history = get_option('vapt_deployment_history', []);
    $history[] = [
      'risk_id' => $risk_id,
      'timestamp' => time(),
      'profile' => $profile,
      'environment' => $env['optimal_platform'],
      'results' => $results
    ];

    // Keep last 100 entries
    if (count($history) > 100) array_shift($history);

    update_option('vapt_deployment_history', $history);
  }
}
