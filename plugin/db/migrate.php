<?php
defined('ABSPATH') || exit;

function pb_lti_run_migrations() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach (pb_lti_schema_sql() as $sql) {
        dbDelta($sql);
    }
}
