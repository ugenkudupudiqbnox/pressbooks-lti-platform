<?php
namespace PB_LTI\Services;

/**
 * H5PActivityDetector
 *
 * Detects and extracts H5P activities from chapter content
 */
class H5PActivityDetector {

    /**
     * Find all H5P shortcodes in post content
     *
     * @param int $post_id Post ID
     * @return array Array of H5P activities with metadata
     */
    public static function find_h5p_activities($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }

        $content = $post->post_content;
        $activities = [];

        // Pattern 1: [h5p id="123"]
        if (preg_match_all('/\[h5p\s+id=["\']?(\d+)["\']?\]/i', $content, $matches)) {
            foreach ($matches[1] as $index => $h5p_id) {
                $activities[] = self::get_h5p_activity_data($h5p_id, $index);
            }
        }

        // Pattern 2: [h5p-iframe id="123"]
        if (preg_match_all('/\[h5p-iframe\s+id=["\']?(\d+)["\']?\]/i', $content, $matches)) {
            foreach ($matches[1] as $index => $h5p_id) {
                if (!self::activity_exists($activities, $h5p_id)) {
                    $activities[] = self::get_h5p_activity_data($h5p_id, $index);
                }
            }
        }

        return $activities;
    }

    /**
     * Get H5P activity metadata
     *
     * @param int $h5p_id H5P content ID
     * @param int $position Position in chapter
     * @return array Activity data
     */
    private static function get_h5p_activity_data($h5p_id, $position) {
        global $wpdb;

        // Get H5P content from database
        $h5p_table = $wpdb->prefix . 'h5p_contents';
        $h5p_content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$h5p_table} WHERE id = %d",
            $h5p_id
        ));

        $title = $h5p_content ? $h5p_content->title : 'H5P Activity #' . $h5p_id;
        $library = $h5p_content ? self::get_h5p_library_name($h5p_content->library_id) : 'Unknown';

        return [
            'id' => (int)$h5p_id,
            'title' => $title,
            'library' => $library,
            'position' => $position + 1,
            'max_score' => self::get_h5p_max_score($h5p_id)
        ];
    }

    /**
     * Get H5P library name
     *
     * @param int $library_id Library ID
     * @return string Library name
     */
    private static function get_h5p_library_name($library_id) {
        global $wpdb;

        $library_table = $wpdb->prefix . 'h5p_libraries';
        $library = $wpdb->get_row($wpdb->prepare(
            "SELECT title FROM {$library_table} WHERE id = %d",
            $library_id
        ));

        return $library ? $library->title : 'Unknown';
    }

    /**
     * Get maximum possible score for H5P content
     * Note: H5P stores max_score in wp_h5p_results, not wp_h5p_contents
     *
     * @param int $h5p_id H5P content ID
     * @return int Maximum score
     */
    private static function get_h5p_max_score($h5p_id) {
        global $wpdb;

        // Try to get max_score from recent results
        $results_table = $wpdb->prefix . 'h5p_results';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT max_score FROM {$results_table} WHERE content_id = %d ORDER BY id DESC LIMIT 1",
            $h5p_id
        ));

        if ($result && isset($result->max_score)) {
            return (int)$result->max_score;
        }

        // If no results yet, return 0 (will be updated when first result is saved)
        return 0;
    }

    /**
     * Check if activity already exists in array
     *
     * @param array $activities Activity array
     * @param int $h5p_id H5P ID to check
     * @return bool
     */
    private static function activity_exists($activities, $h5p_id) {
        foreach ($activities as $activity) {
            if ($activity['id'] == $h5p_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get total maximum score for all activities in chapter
     *
     * @param int $post_id Post ID
     * @return int Total maximum score
     */
    public static function get_chapter_max_score($post_id) {
        $activities = self::find_h5p_activities($post_id);
        $total = 0;

        foreach ($activities as $activity) {
            $total += $activity['max_score'];
        }

        return $total;
    }
}
