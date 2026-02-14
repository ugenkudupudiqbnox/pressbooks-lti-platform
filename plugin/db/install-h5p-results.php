<?php
/**
 * Install H5P Results Database Tables
 *
 * Creates tables for Pressbooks-style H5P grading configuration
 */

function pb_lti_install_h5p_results_tables() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // H5P Grading Configuration Table
    $table_name = $wpdb->prefix . 'lti_h5p_grading_config';

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        post_id bigint(20) unsigned NOT NULL COMMENT 'Chapter/post ID',
        h5p_id int(10) unsigned NOT NULL COMMENT 'H5P content ID',
        include_in_scoring tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Include this activity in grading',
        grading_scheme varchar(20) NOT NULL DEFAULT 'best' COMMENT 'best, average, first, last',
        weight decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Weight for weighted average calculation',
        created_at datetime NOT NULL,
        updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY post_h5p (post_id, h5p_id),
        KEY post_id (post_id),
        KEY h5p_id (h5p_id),
        KEY idx_grading_config_lookup (post_id, include_in_scoring)
    ) $charset_collate;";

    dbDelta($sql);

    // H5P Grade Sync Log Table
    $table_name = $wpdb->prefix . 'lti_h5p_grade_sync_log';

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL COMMENT 'WordPress user ID',
        post_id bigint(20) unsigned NOT NULL COMMENT 'Chapter/post ID',
        result_id bigint(20) unsigned NOT NULL COMMENT 'H5P result ID that triggered sync',
        score_sent decimal(10,2) DEFAULT NULL COMMENT 'Score sent to LMS',
        max_score decimal(10,2) DEFAULT NULL COMMENT 'Maximum score',
        synced_at datetime NOT NULL,
        status varchar(20) DEFAULT 'success' COMMENT 'success, failed',
        error_message text DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_post (user_id, post_id),
        KEY synced_at (synced_at),
        KEY idx_sync_log_lookup (user_id, post_id, synced_at)
    ) $charset_collate;";

    dbDelta($sql);

    // Update version
    update_option('pb_lti_h5p_results_db_version', '1.0.0');

    error_log('[PB-LTI] H5P Results database tables created successfully');
}

/**
 * Check if tables need to be installed or upgraded
 */
function pb_lti_check_h5p_results_tables() {
    $current_version = get_option('pb_lti_h5p_results_db_version', '0');
    $target_version = '1.0.0';

    if (version_compare($current_version, $target_version, '<')) {
        pb_lti_install_h5p_results_tables();
    }
}

// Hook to check/install tables on plugin load
add_action('plugins_loaded', 'pb_lti_check_h5p_results_tables');
