<?php
namespace PB_LTI\Services;

use GuzzleHttp\Client;

class AGSClient {

    /**
     * Post score to Moodle gradebook via AGS
     *
     * @param object $platform Platform configuration
     * @param string $lineitem_url AGS lineitem URL
     * @param int $user_id WordPress user ID
     * @param float $score Score given (0-100)
     * @param float $max_score Maximum score (default 100)
     * @param string $activity_progress Activity progress status
     * @param string $grading_progress Grading progress status
     * @return array Result array with success status
     */
    public static function post_score($platform, $lineitem_url, $user_id, $score, $max_score = 100, $activity_progress = 'Completed', $grading_progress = 'FullyGraded') {
        try {
            // Get OAuth2 token
            $token = TokenCache::get($platform->issuer);
            if (!$token) {
                $token = self::fetch_token($platform);
            }

            // Post score to AGS endpoint
            $client = new Client();
            $response = $client->post($lineitem_url . '/scores', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/vnd.ims.lis.v1.score+json'
                ],
                'json' => [
                    'userId' => (string)$user_id,
                    'scoreGiven' => (float)$score,
                    'scoreMaximum' => (float)$max_score,
                    'activityProgress' => $activity_progress,
                    'gradingProgress' => $grading_progress,
                    'timestamp' => date('c')
                ]
            ]);

            return ['success' => true, 'status' => $response->getStatusCode()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

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
        $res = $client->post($platform->auth_token_url, [
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
