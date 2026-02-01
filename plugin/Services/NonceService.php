<?php
namespace PB_LTI\Services;

class NonceService {
    public static function consume(string $nonce) {
        global $wpdb;
        $table = $wpdb->prefix . 'pb_lti_nonces';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT nonce FROM $table WHERE nonce=%s", $nonce
        ));

        if ($exists) {
            throw new \Exception('Replay detected');
        }

        $wpdb->insert($table, [
            'nonce' => $nonce,
            'expires_at' => gmdate('Y-m-d H:i:s', time()+60)
        ]);
    }
}
