<?php
defined('ABSPATH') || exit;

use PB_LTI\Controllers\AGSController;

add_action('rest_api_init', function () {
    register_rest_route('pb-lti/v1', '/ags/post-score', [
        'methods' => 'POST',
        'callback' => [AGSController::class, 'post_score'],
        'permission_callback' => '__return_true',
    ]);
});
