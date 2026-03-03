<?php

/**
 * VAPTSECURE_Nginx_Deployer: Adaptive Nginx Deployment
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Nginx_Deployer
{
  private $nginx_rules_path;

  public function __construct()
  {
    // v4.0 standard: Nginx rules go to a dedicated file meant for manual include
    $this->nginx_rules_path = VAPTSECURE_PATH . 'data/vapt-nginx-protection.conf';
  }

  public function can_deploy()
  {
    // Check if directory is writable for rule generation
    $dir = dirname($this->nginx_rules_path);
    return is_writable($dir);
  }

  public function deploy($risk_id, $implementation)
  {
    if (!$this->can_deploy()) {
      return new WP_Error('vapt_deploy_failed', 'Nginx rules directory is not writable.');
    }

    $rules = $this->extract_rules($implementation);
    if (empty($rules)) {
      return new WP_Error('vapt_no_rules', 'No Nginx rules found in implementation.');
    }

    return $this->update_rules_file($risk_id, $rules);
  }

  private function extract_rules($implementation)
  {
    if (isset($implementation['nginx'])) {
      $inner = $implementation['nginx'];
      return is_array($inner) ? ($inner['code'] ?? '') : $inner;
    }

    if (class_exists('VAPTSECURE_Enforcer')) {
      return VAPTSECURE_Enforcer::extract_code_from_mapping($implementation, 'nginx');
    }

    return '';
  }

  private function update_rules_file($risk_id, $rules)
  {
    $content = file_exists($this->nginx_rules_path) ? file_get_contents($this->nginx_rules_path) : "# VAPT Nginx Protections - Generated at " . date('Y-m-d H:i:s') . "\n";

    $start_marker = "# BEGIN VAPT PROTECTION: {$risk_id}";
    $end_marker = "# END VAPT PROTECTION: {$risk_id}";

    // Remove existing block
    $pattern = "/" . preg_quote($start_marker, '/') . ".*?" . preg_quote($end_marker, '/') . "/s";
    $content = preg_replace($pattern, '', $content);

    // Add new block
    $new_block = "\n{$start_marker}\n{$rules}\n{$end_marker}\n";
    $content .= $new_block;

    $result = file_put_contents($this->nginx_rules_path, trim($content) . "\n", LOCK_EX);

    return $result !== false ? ['status' => 'deployed', 'platform' => 'nginx_config', 'file' => $this->nginx_rules_path] : new WP_Error('vapt_write_error', 'Failed to write Nginx rules file.');
  }

  public function undeploy($risk_id)
  {
    if (!file_exists($this->nginx_rules_path)) return true;

    $content = file_get_contents($this->nginx_rules_path);
    $start_marker = "# BEGIN VAPT PROTECTION: {$risk_id}";
    $end_marker = "# END VAPT PROTECTION: {$risk_id}";

    $pattern = "/" . preg_quote($start_marker, '/') . ".*?" . preg_quote($end_marker, '/') . "/s";
    $new_content = preg_replace($pattern, '', $content);

    if ($new_content !== $content) {
      return file_put_contents($this->nginx_rules_path, trim($new_content) . "\n", LOCK_EX);
    }

    return true;
  }
}
