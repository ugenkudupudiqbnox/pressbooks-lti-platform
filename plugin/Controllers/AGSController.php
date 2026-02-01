<?php
namespace PB_LTI\Controllers;

use PB_LTI\Services\AGSClient;

class AGSController {
    public static function post_score($request) {
        $lineitem = $request->get_param('lineitem_url');
        $score = $request->get_param('score');
        $user_id = $request->get_param('user_id');

        if (!$lineitem || $score === null || !$user_id) {
            return new \WP_Error('invalid_request','Missing parameters',['status'=>400]);
        }

        AGSClient::send_score($lineitem, $score, $user_id);
        return ['status' => 'sent'];
    }
}
