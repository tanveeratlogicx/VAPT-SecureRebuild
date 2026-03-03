<?php

/**
 * VAPTSECURE_Apache_Deployer: Adaptive .htaccess Deployment
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_Apache_Deployer
{
  private $htaccess_path;

  public function __construct()
  {
    // Path resolution is now dynamic per deployment
  }

  private function resolve_target_path($target)
  {
    if ($target === 'uploads') {
      $upload_dir = wp_upload_dir();
      $this->htaccess_path = $upload_dir['basedir'] . '/.htaccess';
    } else {
      $this->htaccess_path = ABSPATH . '.htaccess';
    }
  }

  public function can_deploy()
  {
    return is_writable($this->htaccess_path) || (!file_exists($this->htaccess_path) && is_writable(ABSPATH));
  }

  public function deploy($risk_id, $implementation)
  {
    $target = $implementation['target'] ?? 'root';
    $this->resolve_target_path($target);

    if (!$this->can_deploy()) {
      return new WP_Error('vapt_deploy_failed', sprintf('.htaccess is not writable at target: %s', $target));
    }

    $rules = $this->extract_rules($implementation);
    if (empty($rules)) {
      return new WP_Error('vapt_no_rules', 'No Apache rules found in implementation.');
    }

    return $this->write_rules($risk_id, $rules);
  }

  private function extract_rules($implementation)
  {
    // Try the standard format from platform_matrix
    if (isset($implementation['rules'])) {
      return is_array($implementation['rules']) ? implode("\n", $implementation['rules']) : $implementation['rules'];
    }

    // 🛡️ Compatibility: Support 'code' field (v3.13.14)
    if (isset($implementation['code'])) {
      return is_array($implementation['code']) ? implode("\n", $implementation['code']) : $implementation['code'];
    }

    // Fallback to legacy extraction logic
    if (class_exists('VAPTSECURE_Enforcer')) {
      return VAPTSECURE_Enforcer::extract_code_from_mapping($implementation, 'htaccess');
    }

    return '';
  }

  private function write_rules($risk_id, $rules)
  {
    $content = file_exists($this->htaccess_path) ? file_get_contents($this->htaccess_path) : '';

    $start_marker = "# BEGIN VAPT PROTECTION: {$risk_id}";
    $end_marker = "# END VAPT PROTECTION: {$risk_id}";

    // Remove existing block
    $pattern = "/" . preg_quote($start_marker, '/') . ".*?" . preg_quote($end_marker, '/') . "/s";
    $content = preg_replace($pattern, '', $content);

    // Add new block
    $new_block = "\n{$start_marker}\n{$rules}\n{$end_marker}\n";

    // Insert after existing VAPT markers or at the top
    if (strpos($content, '# BEGIN WordPress') !== false) {
      $content = str_replace('# BEGIN WordPress', $new_block . '# BEGIN WordPress', $content);
    } else {
      $content = $new_block . $content;
    }

    $result = file_put_contents($this->htaccess_path, trim($content) . "\n", LOCK_EX);

    return $result !== false ? ['status' => 'deployed', 'platform' => 'apache_htaccess'] : new WP_Error('vapt_write_error', 'Failed to write to .htaccess');
  }

  public function undeploy($risk_id, $target = 'root')
  {
    $this->resolve_target_path($target);
    if (!file_exists($this->htaccess_path)) return true;

    $content = file_get_contents($this->htaccess_path);
    $start_marker = "# BEGIN VAPT PROTECTION: {$risk_id}";
    $end_marker = "# END VAPT PROTECTION: {$risk_id}";

    $pattern = "/" . preg_quote($start_marker, '/') . ".*?" . preg_quote($end_marker, '/') . "/s";
    $new_content = preg_replace($pattern, '', $content);

    if ($new_content !== $content) {
      return file_put_contents($this->htaccess_path, trim($new_content) . "\n", LOCK_EX);
    }

    return true;
  }
}
