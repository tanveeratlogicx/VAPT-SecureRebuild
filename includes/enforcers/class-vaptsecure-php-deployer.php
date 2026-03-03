<?php

/**
 * VAPTSECURE_PHP_Deployer: Universal Hook-based Fallback
 */

if (!defined('ABSPATH')) exit;

class VAPTSECURE_PHP_Deployer
{

  public function can_deploy()
  {
    return true; // Universal fallback
  }

  public function deploy($risk_id, $implementation)
  {
    // PHP implementations are typically handled by VAPTSECURE_Enforcer::runtime_enforcement
    // This deployer just validates that the implementation exists.

    $code = $this->extract_code($implementation);

    if (empty($code)) {
      return new WP_Error('vapt_no_code', 'No PHP protection code found in implementation.');
    }

    return [
      'status' => 'deployed',
      'platform' => 'php_functions',
      'note' => 'Active via runtime hooks.'
    ];
  }

  private function extract_code($implementation)
  {
    if (isset($implementation['code'])) return $implementation['code'];
    if (isset($implementation['php_functions'])) return $implementation['php_functions'];

    if (class_exists('VAPTSECURE_Enforcer')) {
      return VAPTSECURE_Enforcer::extract_code_from_mapping($implementation, 'hook');
    }

    return '';
  }

  public function undeploy($risk_id)
  {
    // Nothing to do for PHP hooks as they are active based on the 'is_enforced' meta flag
    return true;
  }
}
