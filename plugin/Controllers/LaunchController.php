<?php
namespace PB_LTI\Controllers;

use PB_LTI\Services\JwtValidator;
use PB_LTI\Services\NonceService;

class LaunchController {
    public static function handle($request) {
        $jwt = $request->get_param('id_token');
        if (!$jwt) {
            return new \WP_Error('missing_token', 'Missing id_token', ['status'=>400]);
        }

        $claims = JwtValidator::validate($jwt);
        NonceService::consume($claims->nonce);

        wp_set_current_user(1);
        wp_set_auth_cookie(1);

        return ['status' => 'ok'];
    }
}
