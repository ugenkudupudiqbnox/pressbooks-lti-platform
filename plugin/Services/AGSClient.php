<?php
namespace PB_LTI\Services;

use GuzzleHttp\Client;

class AGSClient {

    public static function send_score(string $lineitem_url, float $score, string $user_id) {
        $platform = self::resolve_platform_from_lineitem($lineitem_url);
        $token = self::get_access_token($platform);

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

    private static function get_access_token($platform) {
        $client = new Client();
        $res = $client->post($platform->token_url, [
            'auth' => [$platform->client_id, 'CLIENT_SECRET'],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/score'
            ]
        ]);

        $data = json_decode($res->getBody(), true);
        return $data['access_token'];
    }

    private static function resolve_platform_from_lineitem(string $lineitem_url) {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}pb_lti_lineitems WHERE lineitem_url=%s", $lineitem_url)
        );
        if (!$row) {
            throw new \Exception('LineItem not registered');
        }
        return PlatformRegistry::find_by_context($row->context_id);
    }
}
