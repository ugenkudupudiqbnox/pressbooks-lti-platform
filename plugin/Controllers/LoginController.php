<?php
namespace PB_LTI\Controllers;

use PB_LTI\Services\PlatformRegistry;

class LoginController {
    public static function handle($request) {
        $iss = $request->get_param('iss');
        $login_hint = $request->get_param('login_hint');
        $target = $request->get_param('target_link_uri');
        $lti_message_hint = $request->get_param('lti_message_hint');

        if (!$iss || !$login_hint || !$target) {
            return new \WP_Error('invalid_request', 'Missing parameters', ['status'=>400]);
        }

        $platform = PlatformRegistry::find($iss);
        if (!$platform) {
            return new \WP_Error('unknown_platform', 'Platform not registered', ['status'=>403]);
        }

        $state = wp_generate_password(32, false);
        $nonce = wp_generate_password(32, false);
        set_transient('pb_lti_state_' . $state, $nonce, 60);

        // Build auth URL manually to properly encode lti_message_hint
        $auth_params = array(
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'client_id' => $platform->client_id,
            'redirect_uri' => rest_url('pb-lti/v1/launch'),
            'login_hint' => $login_hint,
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'none'
        );

        // Add lti_message_hint separately and URL-encode it properly
        if ($lti_message_hint) {
            $auth_params['lti_message_hint'] = $lti_message_hint;
        }

        // Use http_build_query which properly URL-encodes all parameters
        $query_string = http_build_query($auth_params, '', '&', PHP_QUERY_RFC3986);
        $auth_url = $platform->auth_login_url . '?' . $query_string;

        error_log('[PB-LTI] Redirecting to: ' . $auth_url);

        wp_redirect($auth_url);
        exit;
    }
}
