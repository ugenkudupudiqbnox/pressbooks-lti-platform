<?php
defined('ABSPATH') || exit;

function pb_lti_schema_sql() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    return [
        "platforms" => "
        CREATE TABLE {$wpdb->prefix}pb_lti_platforms (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            issuer VARCHAR(255) NOT NULL UNIQUE,
            client_id VARCHAR(255) NOT NULL,
            auth_login_url TEXT NOT NULL,
            jwks_url TEXT NOT NULL,
            created_at DATETIME NOT NULL
        ) $charset;",

        "nonces" => "
        CREATE TABLE {$wpdb->prefix}pb_lti_nonces (
            nonce VARCHAR(255) PRIMARY KEY,
            expires_at DATETIME NOT NULL
        ) $charset;"
    ];
}
