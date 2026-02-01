<?php
namespace PB_LTI\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtValidator {
    public static function validate(string $jwt) {
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $jwt)[0]));
        $payload = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $jwt)[1]));

        $platform = PlatformRegistry::find($payload->iss);
        if (!$platform) {
            throw new \Exception('Unknown issuer');
        }

        if (!in_array($platform->client_id, (array)$payload->aud, true)) {
            throw new \Exception('Invalid audience');
        }

        $jwks = json_decode(file_get_contents($platform->jwks_url), true);
        foreach ($jwks['keys'] as $jwk) {
            try {
                return JWT::decode($jwt, new Key(JWT::urlsafeB64Decode($jwk['n']), 'RS256'));
            } catch (\Exception $e) {
                continue;
            }
        }
        throw new \Exception('JWT signature invalid');
    }
}
