<?php
namespace PB_LTI\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtValidator {
    public static function validate(string $jwt) {
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $jwt)[0]));
        $iss = null;

        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $jwt)[1]));
        $iss = $payload->iss;

        $platform = PlatformRegistry::find($iss);
        if (!$platform) {
            throw new \Exception('Unknown issuer');
        }

        $jwks = json_decode(file_get_contents($platform->jwks_url), true);
        $key = $jwks['keys'][0];

        return JWT::decode($jwt, new Key(JWT::urlsafeB64Decode($key['n']), 'RS256'));
    }
}
