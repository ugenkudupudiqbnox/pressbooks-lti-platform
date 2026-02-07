<?php
namespace PB_LTI\Controllers;

use Firebase\JWT\JWT;

class DeepLinkController {
    public static function handle($request) {
        global $wpdb;

        // Fetch private key from database
        $key_row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}lti_keys WHERE kid = 'pb-lti-2024'");
        if (!$key_row) {
            return new \WP_Error('no_keys', 'RSA keys not configured', ['status' => 500]);
        }

        // Minimal Deep Linking response: return selected Pressbooks URL
        $return_url = $request->get_param('deep_link_return_url');
        $content_url = home_url('/');

        $jwt = JWT::encode([
            'iss' => home_url(),
            'aud' => $request->get_param('client_id'),
            'iat' => time(),
            'exp' => time() + 300,
            'nonce' => wp_generate_password(32, false),
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => [[
                'type' => 'ltiResourceLink',
                'title' => 'Pressbooks Content',
                'url' => $content_url
            ]]
        ], $key_row->private_key, 'RS256', 'pb-lti-2024');

        wp_redirect($return_url . '?JWT=' . urlencode($jwt));
        exit;
    }
}
