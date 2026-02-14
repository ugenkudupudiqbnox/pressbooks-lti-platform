-- H5P Results / Pressbooks-style grading tables
-- These tables store chapter-level H5P grading configuration

-- H5P Grading Configuration
-- Stores which H5P activities are included in chapter grading
CREATE TABLE IF NOT EXISTS `{prefix}lti_h5p_grading_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL COMMENT 'Chapter/post ID',
  `h5p_id` int(10) unsigned NOT NULL COMMENT 'H5P content ID',
  `include_in_scoring` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Include this activity in grading',
  `grading_scheme` varchar(20) NOT NULL DEFAULT 'best' COMMENT 'best, average, first, last',
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Weight for weighted average calculation',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_h5p` (`post_id`, `h5p_id`),
  KEY `post_id` (`post_id`),
  KEY `h5p_id` (`h5p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- H5P Grade Sync Log
-- Tracks when grades are synced to LMS
CREATE TABLE IF NOT EXISTS `{prefix}lti_h5p_grade_sync_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'WordPress user ID',
  `post_id` bigint(20) unsigned NOT NULL COMMENT 'Chapter/post ID',
  `result_id` bigint(20) unsigned NOT NULL COMMENT 'H5P result ID that triggered sync',
  `score_sent` decimal(10,2) DEFAULT NULL COMMENT 'Score sent to LMS',
  `max_score` decimal(10,2) DEFAULT NULL COMMENT 'Maximum score',
  `synced_at` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'success' COMMENT 'success, failed',
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_post` (`user_id`, `post_id`),
  KEY `synced_at` (`synced_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for performance
CREATE INDEX idx_grading_config_lookup ON `{prefix}lti_h5p_grading_config` (`post_id`, `include_in_scoring`);
CREATE INDEX idx_sync_log_lookup ON `{prefix}lti_h5p_grade_sync_log` (`user_id`, `post_id`, `synced_at`);
