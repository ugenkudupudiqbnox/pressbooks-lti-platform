<?php
namespace PB_LTI\Services;

/**
 * SessionMonitorService
 *
 * Monitors LMS session and logs out Pressbooks user if LMS session expires
 */
class SessionMonitorService {

    /**
     * Initialize session monitoring
     */
    public static function init() {
        // Add JavaScript to monitor LMS session
        add_action('wp_footer', [__CLASS__, 'add_session_monitor_script']);
    }

    /**
     * Add JavaScript to monitor LMS session status
     */
    public static function add_session_monitor_script() {
        // Only add for logged-in LTI users
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();

        // Check if this is an LTI user
        $is_lti_user = !empty(get_user_meta($user_id, '_lti_user_id', true));
        if (!$is_lti_user) {
            return;
        }

        // Get platform issuer (Moodle URL)
        $platform_issuer = get_user_meta($user_id, '_lti_platform_issuer', true);
        if (empty($platform_issuer)) {
            return;
        }

        // Parse Moodle URL
        $parsed = parse_url($platform_issuer);
        $moodle_host = $parsed['scheme'] . '://' . $parsed['host'];

        // Logout endpoint
        $logout_url = rest_url('pb-lti/v1/logout');

        ?>
        <script>
        (function() {
            // Session monitor for LTI users
            var moodleHost = <?php echo json_encode($moodle_host); ?>;
            var logoutUrl = <?php echo json_encode($logout_url); ?>;
            var checkInterval = 30000; // Check every 30 seconds
            var failureCount = 0;
            var maxFailures = 2; // Logout after 2 consecutive failures
            var sessionCheckUrl = moodleHost + '/lib/ajax/service.php';

            function checkMoodleSession() {
                // Use fetch with credentials to check Moodle session
                fetch(sessionCheckUrl, {
                    method: 'POST',
                    credentials: 'include', // Include cookies for session check
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify([{
                        index: 0,
                        methodname: 'core_session_time_remaining',
                        args: {}
                    }])
                })
                .then(function(response) {
                    if (response.ok) {
                        return response.json();
                    } else if (response.status === 401 || response.status === 403) {
                        // Unauthorized - session expired
                        throw new Error('Session expired');
                    }
                    throw new Error('Network error');
                })
                .then(function(data) {
                    // Session is valid
                    failureCount = 0;
                    console.log('[LTI Session Monitor] Moodle session check: OK', data);
                })
                .catch(function(error) {
                    failureCount++;
                    console.log('[LTI Session Monitor] Moodle session check failed (attempt ' + failureCount + '/' + maxFailures + '): ' + error.message);

                    if (failureCount >= maxFailures) {
                        console.log('[LTI Session Monitor] Moodle session expired, logging out...');
                        window.location.href = logoutUrl;
                    }
                });
            }

            // Alternative: Detect if we're in an iframe and monitor parent window
            function detectIframeUnload() {
                if (window.self !== window.top) {
                    // We're in an iframe (embedded in Moodle)
                    console.log('[LTI Session Monitor] Detected iframe embedding');

                    // Try to detect when parent window navigates away
                    window.addEventListener('beforeunload', function(e) {
                        // Store timestamp of last activity
                        sessionStorage.setItem('pb_lti_last_active', Date.now());
                    });

                    // Check if we were inactive for too long (parent navigated away)
                    var lastActive = sessionStorage.getItem('pb_lti_last_active');
                    if (lastActive) {
                        var inactive = Date.now() - parseInt(lastActive);
                        if (inactive > 300000) { // 5 minutes
                            console.log('[LTI Session Monitor] Long inactivity detected, checking session...');
                            checkMoodleSession();
                        }
                    }
                }
            }

            // Initialize
            detectIframeUnload();

            // Check immediately on load
            setTimeout(checkMoodleSession, 5000);

            // Then check periodically
            var intervalId = setInterval(checkMoodleSession, checkInterval);

            // Check when page becomes visible again
            if (typeof document.visibilityState !== 'undefined') {
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        console.log('[LTI Session Monitor] Page visible, checking session...');
                        checkMoodleSession();
                    }
                });
            }

            // Check when window gains focus
            window.addEventListener('focus', function() {
                console.log('[LTI Session Monitor] Window focused, checking session...');
                checkMoodleSession();
            });

            console.log('[LTI Session Monitor] Initialized - checking Moodle session every ' + (checkInterval/1000) + 's');
        })();
        </script>
        <?php
    }

    /**
     * Create a session check endpoint that Moodle can call
     * This allows Moodle to explicitly trigger logout via a webhook
     */
    public static function register_session_endpoints() {
        // Endpoint for Moodle to call when user logs out
        register_rest_route('pb-lti/v1', '/session/end', [
            'methods' => ['POST', 'GET'],
            'callback' => [__CLASS__, 'handle_session_end'],
            'permission_callback' => '__return_true', // Open endpoint (will validate token)
        ]);
    }

    /**
     * Handle session end request from Moodle
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function handle_session_end($request) {
        // Get LTI user ID from request
        $lti_user_id = $request->get_param('user_id');
        $platform_issuer = $request->get_param('issuer');
        $token = $request->get_param('token'); // Optional security token

        if (empty($lti_user_id) || empty($platform_issuer)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Missing required parameters: user_id, issuer'
            ], 400);
        }

        // Find WordPress user by LTI ID
        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = '_lti_user_id' AND meta_value = %s
             AND user_id IN (
                 SELECT user_id FROM {$wpdb->usermeta}
                 WHERE meta_key = '_lti_platform_issuer' AND meta_value = %s
             )",
            $lti_user_id,
            $platform_issuer
        ));

        if (!$user_id) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Destroy all sessions for this user
        $sessions = \WP_Session_Tokens::get_instance($user_id);
        $sessions->destroy_all();

        error_log('[PB-LTI Session] Ended session for user ' . $user_id . ' (LTI: ' . $lti_user_id . ') via Moodle webhook');

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Session ended successfully',
            'user_id' => $user_id
        ], 200);
    }
}
