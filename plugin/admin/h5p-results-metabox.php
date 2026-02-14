<?php
namespace PB_LTI\Admin;

use PB_LTI\Services\H5PActivityDetector;
use PB_LTI\Services\H5PResultsManager;

/**
 * H5P Results Meta Box
 *
 * Adds LMS Grade Reporting configuration to chapter edit screen
 */
class H5PResultsMetaBox {

    /**
     * Initialize meta box hooks
     */
    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_box']);
        add_action('save_post', [__CLASS__, 'save_meta_box'], 10, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
    }

    /**
     * Register meta box
     */
    public static function add_meta_box() {
        $post_types = ['chapter', 'front-matter', 'back-matter'];

        foreach ($post_types as $post_type) {
            add_meta_box(
                'pb_lti_h5p_results',
                '📊 LMS Grade Reporting (LTI AGS)',
                [__CLASS__, 'render_meta_box'],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        global $post;
        if (!in_array($post->post_type, ['chapter', 'front-matter', 'back-matter'])) {
            return;
        }

        wp_enqueue_style(
            'pb-lti-h5p-results',
            plugin_dir_url(__FILE__) . '../assets/css/h5p-results-metabox.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'pb-lti-h5p-results',
            plugin_dir_url(__FILE__) . '../assets/js/h5p-results-metabox.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    /**
     * Render meta box content
     *
     * @param \WP_Post $post Current post object
     */
    public static function render_meta_box($post) {
        // Security nonce
        wp_nonce_field('pb_lti_h5p_results', 'pb_lti_h5p_results_nonce');

        // Get current configuration
        $config = H5PResultsManager::get_configuration($post->ID);
        $grading_enabled = $config['enabled'];
        $aggregate_method = $config['aggregate'];

        // Detect H5P activities in content
        $activities = H5PActivityDetector::find_h5p_activities($post->ID);

        if (empty($activities)) {
            echo '<div class="notice notice-info inline">';
            echo '<p><strong>ℹ️ No H5P activities detected in this chapter.</strong></p>';
            echo '<p>Add H5P content using the <code>[h5p id="X"]</code> shortcode, then save the chapter to configure grading.</p>';
            echo '</div>';
            return;
        }

        ?>
        <div class="pb-lti-h5p-results-wrapper">
            <!-- Enable Grading Toggle -->
            <div class="pb-lti-section">
                <label class="pb-lti-toggle">
                    <input type="checkbox"
                           name="pb_lti_h5p_grading_enabled"
                           value="1"
                           <?php checked($grading_enabled, true); ?>
                           class="pb-lti-enable-grading">
                    <span class="pb-lti-toggle-label">
                        <strong>Enable LMS Grade Reporting for this Chapter</strong>
                    </span>
                </label>
                <p class="description">
                    When enabled, student scores will be sent to the LMS gradebook via LTI Assignment and Grade Services (AGS).
                </p>
            </div>

            <!-- Grading Configuration (only shown when enabled) -->
            <div class="pb-lti-grading-config" style="<?php echo $grading_enabled ? '' : 'display:none;'; ?>">

                <!-- Aggregate Method -->
                <div class="pb-lti-section">
                    <h3>📊 Score Aggregation Method</h3>
                    <select name="pb_lti_h5p_aggregate" class="regular-text">
                        <option value="sum" <?php selected($aggregate_method, 'sum'); ?>>
                            Sum - Add all activity scores
                        </option>
                        <option value="average" <?php selected($aggregate_method, 'average'); ?>>
                            Average - Calculate mean score
                        </option>
                        <option value="weighted" <?php selected($aggregate_method, 'weighted'); ?>>
                            Weighted - Custom weights per activity
                        </option>
                    </select>
                    <p class="description">
                        How should multiple H5P activity scores be combined?
                    </p>
                </div>

                <!-- Activity List -->
                <div class="pb-lti-section">
                    <h3>🎯 H5P Activities Configuration</h3>
                    <p class="description">Select which activities to include and configure grading for each.</p>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" id="pb-lti-select-all">
                                </th>
                                <th style="width: 5%;">Position</th>
                                <th style="width: 35%;">Activity</th>
                                <th style="width: 15%;">Library</th>
                                <th style="width: 10%;">Max Score</th>
                                <th style="width: 20%;">Grading Scheme</th>
                                <th style="width: 15%;">Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity):
                                $h5p_id = $activity['id'];
                                $is_included = isset($config['activities'][$h5p_id]['include']) ?
                                              $config['activities'][$h5p_id]['include'] : false;
                                $scheme = isset($config['activities'][$h5p_id]['scheme']) ?
                                         $config['activities'][$h5p_id]['scheme'] : 'best';
                                $weight = isset($config['activities'][$h5p_id]['weight']) ?
                                         $config['activities'][$h5p_id]['weight'] : 1.0;
                            ?>
                            <tr class="pb-lti-activity-row" data-h5p-id="<?php echo esc_attr($h5p_id); ?>">
                                <th class="check-column">
                                    <input type="checkbox"
                                           name="pb_lti_activities[<?php echo $h5p_id; ?>][include]"
                                           value="1"
                                           <?php checked($is_included, true); ?>
                                           class="pb-lti-activity-checkbox">
                                </th>
                                <td><?php echo $activity['position']; ?></td>
                                <td>
                                    <strong><?php echo esc_html($activity['title']); ?></strong>
                                    <div class="row-actions">
                                        <span class="h5p-id">ID: <?php echo $h5p_id; ?></span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($activity['library']); ?></td>
                                <td><?php echo $activity['max_score']; ?></td>
                                <td>
                                    <select name="pb_lti_activities[<?php echo $h5p_id; ?>][scheme]"
                                            class="small-text pb-lti-scheme-select">
                                        <option value="best" <?php selected($scheme, 'best'); ?>>
                                            🏆 Best Attempt
                                        </option>
                                        <option value="average" <?php selected($scheme, 'average'); ?>>
                                            📊 Average
                                        </option>
                                        <option value="first" <?php selected($scheme, 'first'); ?>>
                                            1️⃣ First Attempt
                                        </option>
                                        <option value="last" <?php selected($scheme, 'last'); ?>>
                                            🔄 Last Attempt
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number"
                                           name="pb_lti_activities[<?php echo $h5p_id; ?>][weight]"
                                           value="<?php echo esc_attr($weight); ?>"
                                           min="0"
                                           max="10"
                                           step="0.1"
                                           class="small-text pb-lti-weight-input"
                                           <?php echo $aggregate_method !== 'weighted' ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="pb-lti-section pb-lti-summary">
                    <h4>📋 Summary</h4>
                    <ul>
                        <li>
                            <strong>Total Activities:</strong>
                            <span class="pb-lti-total-count"><?php echo count($activities); ?></span>
                        </li>
                        <li>
                            <strong>Included in Grading:</strong>
                            <span class="pb-lti-included-count">
                                <?php echo count(array_filter($config['activities'], function($a) { return $a['include']; })); ?>
                            </span>
                        </li>
                        <li>
                            <strong>Total Maximum Score:</strong>
                            <span class="pb-lti-max-score">
                                <?php echo H5PActivityDetector::get_chapter_max_score($post->ID); ?>
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Sync Existing Grades -->
                <div class="pb-lti-section pb-lti-sync">
                    <h4>🔄 Sync Existing Grades</h4>
                    <p class="description">
                        If students completed H5P activities before this grading configuration was enabled,
                        you can retroactively send their scores to the LMS gradebook.
                    </p>
                    <button type="button"
                            id="pb-lti-sync-existing-grades"
                            class="button button-secondary"
                            data-post-id="<?php echo esc_attr($post->ID); ?>">
                        🔄 Sync Existing Grades to LMS
                    </button>
                    <span class="pb-lti-sync-spinner spinner" style="float: none; margin-left: 10px;"></span>
                    <div id="pb-lti-sync-results" class="notice" style="display: none; margin-top: 15px;"></div>
                    <p class="description" style="margin-top: 10px;">
                        <strong>Note:</strong> Only grades for students who previously accessed this chapter via LTI will be synced.
                        Students who accessed directly (not through LMS) will be skipped.
                    </p>
                </div>

                <!-- Help Text -->
                <div class="pb-lti-section pb-lti-help">
                    <h4>ℹ️ How It Works</h4>
                    <ol>
                        <li><strong>Enable grading</strong> for this chapter using the toggle above.</li>
                        <li><strong>Select activities</strong> to include in the final grade calculation.</li>
                        <li><strong>Choose grading scheme</strong> for each activity:
                            <ul>
                                <li><strong>Best Attempt:</strong> Uses highest score across all attempts</li>
                                <li><strong>Average:</strong> Calculates mean of all attempts</li>
                                <li><strong>First Attempt:</strong> Only uses the first attempt score</li>
                                <li><strong>Last Attempt:</strong> Only uses the most recent attempt</li>
                            </ul>
                        </li>
                        <li><strong>Set aggregation method</strong> to combine multiple activity scores.</li>
                        <li>When students complete activities, scores are <strong>automatically sent to the LMS gradebook</strong> via LTI AGS.</li>
                    </ol>
                    <p>
                        <strong>Note:</strong> Grades are only sent for students who access this chapter via an LTI launch from your LMS.
                    </p>
                </div>
            </div>
        </div>

        <style>
            .pb-lti-h5p-results-wrapper {
                padding: 15px;
            }
            .pb-lti-section {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }
            .pb-lti-section:last-child {
                border-bottom: none;
            }
            .pb-lti-toggle {
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
            }
            .pb-lti-toggle input[type="checkbox"] {
                width: 20px;
                height: 20px;
            }
            .pb-lti-activity-row.disabled {
                opacity: 0.5;
            }
            .pb-lti-summary {
                background: #f0f9ff;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #bae6fd;
            }
            .pb-lti-summary ul {
                margin: 10px 0 0 20px;
            }
            .pb-lti-help {
                background: #fffbeb;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #fef3c7;
            }
            .pb-lti-help ol {
                margin-left: 20px;
            }
            .pb-lti-help ul {
                margin-left: 40px;
                list-style-type: disc;
            }
            .pb-lti-sync {
                background: #f0fdf4;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #bbf7d0;
            }
            .pb-lti-sync button {
                margin-top: 10px;
            }
            #pb-lti-sync-results {
                padding: 10px;
                margin-top: 15px;
            }
            #pb-lti-sync-results ul {
                margin-left: 20px;
                list-style-type: disc;
            }
            #pb-lti-sync-results details {
                margin-top: 10px;
                padding: 10px;
                background: rgba(0,0,0,0.05);
                border-radius: 3px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Toggle grading configuration
            $('.pb-lti-enable-grading').on('change', function() {
                $('.pb-lti-grading-config').toggle(this.checked);
            });

            // Select all activities
            $('#pb-lti-select-all').on('change', function() {
                $('.pb-lti-activity-checkbox').prop('checked', this.checked);
                updateSummary();
            });

            // Update summary when checkboxes change
            $('.pb-lti-activity-checkbox').on('change', function() {
                updateSummary();
            });

            // Enable/disable weight inputs based on aggregate method
            $('select[name="pb_lti_h5p_aggregate"]').on('change', function() {
                const isWeighted = $(this).val() === 'weighted';
                $('.pb-lti-weight-input').prop('disabled', !isWeighted);
            });

            function updateSummary() {
                const checked = $('.pb-lti-activity-checkbox:checked').length;
                $('.pb-lti-included-count').text(checked);
            }

            // Sync existing grades AJAX handler
            $('#pb-lti-sync-existing-grades').on('click', function() {
                const $button = $(this);
                const $spinner = $('.pb-lti-sync-spinner');
                const $results = $('#pb-lti-sync-results');
                const postId = $button.data('post-id');

                // Confirm before syncing
                if (!confirm('This will sync all existing H5P grades for this chapter to the LMS gradebook. Continue?')) {
                    return;
                }

                // Show loading state
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                $results.hide().removeClass('notice-success notice-error notice-warning');

                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pb_lti_sync_existing_grades',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('pb_lti_sync_grades'); ?>'
                    },
                    success: function(response) {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);

                        if (response.success) {
                            $results.addClass('notice-success')
                                   .html('<p><strong>✅ Success:</strong> ' + response.data.message + '</p>')
                                   .show();

                            // Show detailed results if available
                            if (response.data.results) {
                                const r = response.data.results;
                                const details = '<ul>' +
                                    '<li>Successfully synced: ' + r.success + '</li>' +
                                    '<li>Skipped (no LTI context): ' + r.skipped + '</li>' +
                                    '<li>Failed: ' + r.failed + '</li>' +
                                    '</ul>';
                                $results.find('p').append(details);

                                if (r.errors && r.errors.length > 0) {
                                    $results.find('p').append(
                                        '<details style="margin-top: 10px;">' +
                                        '<summary>View Errors</summary>' +
                                        '<ul><li>' + r.errors.join('</li><li>') + '</li></ul>' +
                                        '</details>'
                                    );
                                }
                            }
                        } else {
                            $results.addClass('notice-error')
                                   .html('<p><strong>❌ Error:</strong> ' + response.data.message + '</p>')
                                   .show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $spinner.removeClass('is-active');
                        $button.prop('disabled', false);
                        $results.addClass('notice-error')
                               .html('<p><strong>❌ Error:</strong> ' + error + '</p>')
                               .show();
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public static function save_meta_box($post_id, $post) {
        // Security checks
        if (!isset($_POST['pb_lti_h5p_results_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['pb_lti_h5p_results_nonce'], 'pb_lti_h5p_results')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!in_array($post->post_type, ['chapter', 'front-matter', 'back-matter'])) {
            return;
        }

        // Prepare configuration data
        $config = [
            'enabled' => isset($_POST['pb_lti_h5p_grading_enabled']),
            'aggregate' => isset($_POST['pb_lti_h5p_aggregate']) ?
                          sanitize_text_field($_POST['pb_lti_h5p_aggregate']) : 'sum',
            'activities' => []
        ];

        if (isset($_POST['pb_lti_activities']) && is_array($_POST['pb_lti_activities'])) {
            foreach ($_POST['pb_lti_activities'] as $h5p_id => $activity_data) {
                $config['activities'][(int)$h5p_id] = [
                    'include' => isset($activity_data['include']),
                    'scheme' => sanitize_text_field($activity_data['scheme'] ?? 'best'),
                    'weight' => floatval($activity_data['weight'] ?? 1.0)
                ];
            }
        }

        // Save configuration
        H5PResultsManager::save_configuration($post_id, $config);

        // Log the configuration
        error_log('[PB-LTI H5P Results] Saved configuration for post ' . $post_id . ': ' . json_encode($config));
    }
}
