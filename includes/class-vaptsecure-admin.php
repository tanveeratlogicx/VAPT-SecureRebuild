<?php

/**
 * VAPT Admin Interface
 */

if (! defined('ABSPATH')) {
  exit;
}

class VAPTSECURE_Admin
{

  public function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_notices', array($this, 'show_nginx_notice'));
    add_action('admin_notices', array($this, 'show_block_notifications'));
    add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
  }

  /**
   * Show recent block notifications recorded by the enforcement engine.
   */
  public function show_block_notifications()
  {
    if (!current_user_can('manage_options')) return;

    $events = get_transient('vaptsecure_block_events');
    if (empty($events) || !is_array($events)) {
      return;
    }

    foreach ($events as $e) {
      $time = isset($e['time']) ? esc_html($e['time']) : esc_html(current_time('mysql'));
      $reason = isset($e['reason']) ? esc_html($e['reason']) : 'Protection Triggered';
      $details = '';
      if (isset($e['uri'])) $details .= ' URI: ' . esc_html($e['uri']);
      if (isset($e['file'])) $details .= ' File: ' . esc_html($e['file']);
      if (isset($e['query'])) $details .= ' Query: ' . esc_html($e['query']);

?>
      <div class="notice notice-warning is-dismissible">
        <p><strong>VAPT Secure blocked: <?php echo $reason; ?></strong></p>
        <p><?php echo $time; ?><?php if (!empty($details)) echo ' - ' . $details; ?></p>
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=vaptsecure-domain-admin')); ?>">Open VAPT Secure Dashboard</a></p>
      </div>
<?php
    }

    // Clear transient after displaying to avoid repeat notices
    delete_transient('vaptsecure_block_events');
  }

  public function show_nginx_notice()
  {
    if (!is_vaptsecure_superadmin()) return;

    $server = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : '';
    if (strpos($server, 'nginx') === false) return;

    $upload_dir = wp_upload_dir();
    $rules_file = $upload_dir['basedir'] . '/vapt-nginx-rules.conf';

    if (file_exists($rules_file)) {
      $include_path = $rules_file;
?>
      <div class="notice notice-info is-dismissible">
        <p><strong>VAPT Nginx Configuration (Action Required)</strong></p>
        <p>To apply VAPT security rules on Nginx, you must include the generated rules file in your main <code>nginx.conf</code> server block:</p>
        <code style="display:block; padding:10px; background:#fff; margin:5px 0;">include <?php echo esc_html($include_path); ?>;</code>
        <p><em>After adding this line, restart Nginx to apply changes.</em></p>
      </div>
<?php
    }
  }



  public function add_admin_menu()
  {
    // Add an Audit subpage under the main VAPT Secure menu for site administrators
    add_submenu_page(
      'vaptsecure',
      __('VAPT Secure Audit', 'vaptsecure'),
      __('Audit', 'vaptsecure'),
      'manage_options',
      'vaptsecure-audit',
      array($this, 'render_audit_page')
    );

    // Detail view for individual events
    add_submenu_page(
      null,
      __('VAPT Secure Audit Detail', 'vaptsecure'),
      __('Audit Detail', 'vaptsecure'),
      'manage_options',
      'vaptsecure-audit-detail',
      array($this, 'render_audit_detail_page')
    );
  }

  /**
   * Add a small dashboard widget to the WP Dashboard linking to the audit page
   */
  public function add_dashboard_widget()
  {
    if (!current_user_can('manage_options')) return;
    wp_add_dashboard_widget('vaptsecure_blocks_widget', __('VAPT Secure Blocks', 'vaptsecure'), array($this, 'dashboard_widget_render'));
  }

  public function dashboard_widget_render()
  {
    $events = VAPTSECURE_DB::get_security_events(5);
    if (empty($events)) {
      echo '<p>' . esc_html__('No recent VAPT Secure blocks', 'vaptsecure') . '</p>';
      return;
    }
    echo '<ul style="margin:0; padding-left:18px">';
    foreach ($events as $e) {
      $label = esc_html($e['feature_key'] . ' — ' . $e['event_type']);
      $time = esc_html($e['created_at']);
      $link = esc_url(admin_url('admin.php?page=vaptsecure-audit&event_id=' . intval($e['id'])));
      echo '<li style="margin-bottom:6px">' . $label . ' <small>(' . $time . ')</small> — <a href="' . $link . '">' . esc_html__('View details', 'vaptsecure') . '</a></li>';
    }
    echo '</ul>';
  }

  public function enqueue_scripts($hook)
  {
    if ($hook !== 'toplevel_page_vapt-auditor' && $hook !== 'vaptsecure_page_vapt-auditor') {
      return;
    }

    // 1. Enqueue Dependencies
    wp_enqueue_script('vapt-interface-generator', VAPTSECURE_URL . 'assets/js/modules/interface-generator.js', array(), VAPTSECURE_VERSION, true);
    wp_enqueue_script('vapt-generated-interface-ui', VAPTSECURE_URL . 'assets/js/modules/generated-interface.js', array('wp-element', 'wp-components'), VAPTSECURE_VERSION, true);

    // 2. Enqueue Admin Dashboard Script with full dependency block
    wp_enqueue_script(
      'vapt-admin-js',
      VAPTSECURE_URL . 'assets/js/admin.js',
      array('wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'vapt-interface-generator', 'vapt-generated-interface-ui'),
      VAPTSECURE_VERSION,
      true
    );

    wp_enqueue_style('vapt-admin-css', VAPTSECURE_URL . 'assets/css/admin.css', array('wp-components'), VAPTSECURE_VERSION);

    wp_localize_script('vapt-admin-js', 'vaptSecureSettings', array(
      'root' => esc_url_raw(rest_url()),
      'homeUrl' => esc_url_raw(home_url()),
      'nonce' => wp_create_nonce('wp_rest'),
      'isSuper' => is_vaptsecure_superadmin(),
      'pluginVersion' => VAPTSECURE_VERSION
    ));

    wp_localize_script('vapt-admin-js', 'vaptsecure_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('vaptsecure_scan_nonce')
    ));
  }


  public function admin_page()
  {
    wp_die(__('The VAPT Auditor has been removed.', 'vaptsecure'));
  }

  /**
   * Render the audit list page
   */
  public function render_audit_page()
  {
    if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions', 'vaptsecure'));

    $limit = 100;
    $events = VAPTSECURE_DB::get_security_events($limit);
?>
    <div class="wrap">
      <h1><?php esc_html_e('VAPT Secure - Security Events', 'vaptsecure'); ?></h1>
      <p><?php esc_html_e('Recent blocked requests recorded by VAPT Secure. Click "View details" to inspect an event and related logs.', 'vaptsecure'); ?></p>
      <table class="widefat fixed" cellspacing="0">
        <thead>
          <tr>
            <th><?php esc_html_e('ID', 'vaptsecure'); ?></th>
            <th><?php esc_html_e('Time', 'vaptsecure'); ?></th>
            <th><?php esc_html_e('Feature', 'vaptsecure'); ?></th>
            <th><?php esc_html_e('Type', 'vaptsecure'); ?></th>
            <th><?php esc_html_e('IP', 'vaptsecure'); ?></th>
            <th><?php esc_html_e('Request URI', 'vaptsecure'); ?></th>
            <th><?php esc_html_e('Actions', 'vaptsecure'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)) : ?>
            <tr><td colspan="7"><?php esc_html_e('No events found.', 'vaptsecure'); ?></td></tr>
          <?php else : ?>
            <?php foreach ($events as $e) : ?>
              <tr>
                <td><?php echo intval($e['id']); ?></td>
                <td><?php echo esc_html($e['created_at']); ?></td>
                <td><?php echo esc_html($e['feature_key']); ?></td>
                <td><?php echo esc_html($e['event_type']); ?></td>
                <td><?php echo esc_html($e['ip_address']); ?></td>
                <td><?php echo esc_html($e['request_uri']); ?></td>
                <td><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=vaptsecure-audit-detail&id=' . intval($e['id']))); ?>"><?php esc_html_e('View details', 'vaptsecure'); ?></a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
<?php
  }

  /**
   * Render the audit detail page for a single event
   */
  public function render_audit_detail_page()
  {
    if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions', 'vaptsecure'));
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['event_id']) ? intval($_GET['event_id']) : 0);
    if (!$id) wp_die(__('Missing event id', 'vaptsecure'));

    $event = VAPTSECURE_DB::get_security_event($id);
    if (!$event) wp_die(__('Event not found', 'vaptsecure'));

    $details = json_decode($event['details'], true);
    $debug_file = VAPTSECURE_PATH . 'vapt-debug.txt';
?>
    <div class="wrap">
      <h1><?php esc_html_e('VAPT Secure - Event Details', 'vaptsecure'); ?></h1>
      <p><a href="<?php echo esc_url(admin_url('admin.php?page=vaptsecure-audit')); ?>">&larr; <?php esc_html_e('Back to events', 'vaptsecure'); ?></a></p>

      <table class="widefat fixed" cellspacing="0" style="max-width:1000px">
        <tbody>
          <tr><th style="width:200px"><?php esc_html_e('ID', 'vaptsecure'); ?></th><td><?php echo intval($event['id']); ?></td></tr>
          <tr><th><?php esc_html_e('Time', 'vaptsecure'); ?></th><td><?php echo esc_html($event['created_at']); ?></td></tr>
          <tr><th><?php esc_html_e('Feature', 'vaptsecure'); ?></th><td><?php echo esc_html($event['feature_key']); ?></td></tr>
          <tr><th><?php esc_html_e('Type', 'vaptsecure'); ?></th><td><?php echo esc_html($event['event_type']); ?></td></tr>
          <tr><th><?php esc_html_e('IP Address', 'vaptsecure'); ?></th><td><?php echo esc_html($event['ip_address']); ?></td></tr>
          <tr><th><?php esc_html_e('Request URI', 'vaptsecure'); ?></th><td><?php echo esc_html($event['request_uri']); ?></td></tr>
        </tbody>
      </table>

      <h2><?php esc_html_e('Details', 'vaptsecure'); ?></h2>
      <pre style="white-space:pre-wrap; background:#f7f7f7; padding:12px; border:1px solid #ddd; border-radius:4px; max-width:1000px;"><?php echo esc_html(json_encode($details, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></pre>

      <?php if (file_exists($debug_file)) : ?>
        <h2><?php esc_html_e('Plugin Debug Log', 'vaptsecure'); ?></h2>
        <p><?php esc_html_e('Contents of the plugin debug file (read-only):', 'vaptsecure'); ?></p>
        <pre style="white-space:pre-wrap; background:#111; color:#fff; padding:12px; border-radius:4px; max-width:1000px; overflow:auto; max-height:400px;"><?php echo esc_html(file_get_contents($debug_file)); ?></pre>
      <?php endif; ?>

    </div>
<?php
  }
}
