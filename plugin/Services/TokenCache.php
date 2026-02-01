<?php
namespace PB_LTI\Services;

class TokenCache {

    public static function get(string $issuer): ?string {
        $cached = get_site_transient('pb_lti_token_' . md5($issuer));
        if (!$cached) {
            return null;
        }
        if ($cached['expires_at'] <= time()) {
            delete_site_transient('pb_lti_token_' . md5($issuer));
            return null;
        }
        return $cached['token'];
    }

    public static function set(string $issuer, string $token, int $expires_in): void {
        set_site_transient(
            'pb_lti_token_' . md5($issuer),
            [
                'token' => $token,
                'expires_at' => time() + $expires_in - 30
            ],
            $expires_in
        );
    }
}
