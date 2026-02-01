<?php
namespace PB_LTI\Services;

use GuzzleHttp\Client;

class AGSClient {

    public static function send_score(string $lineitem_url, float $score, string $user_id, $platform, array $allowed_scopes) {
        self::enforce_scope($allowed_scopes, 'https://purl.imsglobal.org/spec/lti-ags/scope/score');

        $token = TokenCache::get($platform->issuer);
        if (!$token) {
            $token = self::fetch_token($platform);
        }

        $client = new Client();
        $client->post($lineitem_url . '/scores', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/vnd.ims.lis.v1.score+json'
            ],
            'json' => [
                'userId' => $user_id,
                'scoreGiven' => $score,
                'scoreMaximum' => 100,
                'activityProgress' => 'Completed',
                'gradingProgress' => 'FullyGraded'
            ]
        ]);
    }

    private static function fetch_token($platform): string {
        $secret = SecretVault::retrieve($platform->issuer);
        if (!$secret) {
            throw new \Exception('Client secret not configured');
        }

        $client = new Client();
        $res = $client->post($platform->token_url, [
            'auth' => [$platform->client_id, $secret],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/score'
            ]
        ]);

        $data = json_decode($res->getBody(), true);
        TokenCache::set($platform->issuer, $data['access_token'], $data['expires_in']);

        return $data['access_token'];
    }

    private static function enforce_scope(array $scopes, string $required): void {
        if (!in_array($required, $scopes, true)) {
            throw new \Exception('Required AGS scope not granted');
        }
    }
}
