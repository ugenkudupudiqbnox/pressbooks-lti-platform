<?php
namespace PB_LTI\Controllers;

use PB_LTI\Services\PlatformRegistry;

class LoginController {
    public static function handle($request) {
        $iss = $request->get_param('iss');
        $login_hint = $request->get_param('login_hint');
        $target = $request->get_param('target_link_uri');

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

        $auth_url = add_query_arg([
            'client_id' => $platform->client_id,
            'login_hint' => $login_hint,
            'nonce' => $nonce,
            'state' => $state,
            'redirect_uri' => rest_url('pb-lti/v1/launch'),
            'response_type' => 'id_token',
            'scope' => 'openid',
            'prompt' => 'none'
        ], $platform->auth_login_url);

        wp_redirect($auth_url);
        exit;
    }
}
