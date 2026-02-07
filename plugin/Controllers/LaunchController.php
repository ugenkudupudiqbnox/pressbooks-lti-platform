<?php
namespace PB_LTI\Controllers;

use PB_LTI\Services\JwtValidator;
use PB_LTI\Services\NonceService;
use PB_LTI\Services\DeploymentRegistry;
use PB_LTI\Services\RoleMapper;

class LaunchController {
    public static function handle($request) {
        $jwt = $request->get_param('id_token');
        if (!$jwt) {
            return new \WP_Error('missing_token', 'Missing id_token', ['status'=>400]);
        }

        $claims = JwtValidator::validate($jwt);

        DeploymentRegistry::validate(
            $claims->iss,
            $claims->{'https://purl.imsglobal.org/spec/lti/claim/deployment_id'}
        );

        NonceService::consume($claims->nonce);

        $user_id = RoleMapper::login_user($claims);

        // Get target link URI from claims
        $target_link_uri = $claims->{'https://purl.imsglobal.org/spec/lti/claim/target_link_uri'} ?? home_url();

        // Redirect to target or home
        wp_redirect($target_link_uri);
        exit;
    }
}
