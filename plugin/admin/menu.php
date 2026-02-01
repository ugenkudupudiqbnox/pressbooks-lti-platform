<?php
defined('ABSPATH') || exit;

add_action('network_admin_menu', function () {
    add_menu_page('LTI Platforms','LTI Platforms','manage_network','pb-lti-platforms','pb_lti_admin_page');
    add_submenu_page('pb-lti-platforms','Deployments','Deployments','manage_network','pb-lti-deployments','pb_lti_deployments_page');
});

function pb_lti_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pb_lti_platforms';

    if (isset($_POST['issuer'])) {
        $wpdb->insert($table, [
            'issuer' => sanitize_text_field($_POST['issuer']),
            'client_id' => sanitize_text_field($_POST['client_id']),
            'auth_login_url' => esc_url_raw($_POST['auth_login_url']),
            'jwks_url' => esc_url_raw($_POST['jwks_url']),
            'created_at' => current_time('mysql')
        ]);
    }

    echo '<h1>LTI Platforms</h1><form method="post">
        <input name="issuer" placeholder="Issuer" required>
        <input name="client_id" placeholder="Client ID" required>
        <input name="auth_login_url" placeholder="Auth Login URL" required>
        <input name="jwks_url" placeholder="JWKS URL" required>
        <button>Add Platform</button></form>';
}

function pb_lti_deployments_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'pb_lti_deployments';

    if (isset($_POST['deployment_id'])) {
        $wpdb->insert($table, [
            'platform_issuer' => sanitize_text_field($_POST['platform_issuer']),
            'deployment_id' => sanitize_text_field($_POST['deployment_id'])
        ]);
    }

    echo '<h1>LTI Deployments</h1><form method="post">
        <input name="platform_issuer" placeholder="Platform Issuer" required>
        <input name="deployment_id" placeholder="Deployment ID" required>
        <button>Add Deployment</button></form>';
}
