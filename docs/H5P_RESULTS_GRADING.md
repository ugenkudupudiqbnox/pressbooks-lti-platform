# H5P Results - Pressbooks-Style Grading Feature

## Overview

The **H5P Results** feature provides Pressbooks-style grading configuration for chapters containing H5P activities. It allows instructors to:

- ✅ Select which H5P activities to include in chapter grading
- ✅ Choose grading schemes (Best, Average, First, Last attempt)
- ✅ Aggregate multiple activity scores (Sum, Average, Weighted)
- ✅ Automatically sync grades to LMS gradebook via LTI 1.3 AGS

## Features

### 📊 Chapter-Level Grading Configuration

Each chapter with H5P content gets a **"LMS Grade Reporting"** meta box on the edit screen where instructors can:

1. **Enable/Disable grading** for the chapter
2. **Select activities** to include in the final grade
3. **Configure grading scheme** for each activity
4. **Set aggregation method** for combining scores

### 🎯 Grading Schemes (Per Activity)

| Scheme | Description | Use Case |
|--------|-------------|----------|
| **Best Attempt** | Highest score across all attempts | Mastery-based learning |
| **Average** | Mean of all attempt scores | Practice-focused learning |
| **First Attempt** | Only the first attempt counts | Diagnostic assessment |
| **Last Attempt** | Most recent attempt score | Iterative improvement |

### 📈 Aggregation Methods (Per Chapter)

| Method | Description | Formula |
|--------|-------------|---------|
| **Sum** | Add all activity scores | `Total = Score1 + Score2 + ...` |
| **Average** | Calculate mean score | `Total = (Score1 + Score2 + ...) / N` |
| **Weighted** | Custom weights per activity | `Total = (Score1 × W1 + Score2 × W2 + ...) / (W1 + W2 + ...)` |

## Installation

### 1. Database Setup

The feature automatically creates two database tables on activation:

```sql
wp_lti_h5p_grading_config    -- Stores chapter grading configuration
wp_lti_h5p_grade_sync_log     -- Tracks grade sync history
```

**Manual Installation (if needed):**
```bash
# From plugin directory
mysql -u USER -p DATABASE < plugin/db/h5p-results-schema.sql
```

### 2. Verify Files

Ensure these files exist in your plugin directory:

```
plugin/
├── Services/
│   ├── H5PActivityDetector.php      -- Finds H5P activities in content
│   ├── H5PResultsManager.php        -- Manages grading configuration
│   └── H5PGradeSyncEnhanced.php     -- Enhanced grade sync with chapter support
├── admin/
│   └── h5p-results-metabox.php      -- Admin UI for configuration
└── db/
    ├── h5p-results-schema.sql       -- Database schema
    └── install-h5p-results.php      -- Database installer
```

## Usage Guide

### For Instructors

#### Step 1: Add H5P Content to Chapter

Add H5P activities to your chapter using the shortcode:

```
[h5p id="123"]
```

or iframe version:

```
[h5p-iframe id="123"]
```

**Save the chapter** to detect H5P activities.

#### Step 2: Configure Grading

1. **Edit the chapter** in WordPress
2. Scroll to **"📊 LMS Grade Reporting (LTI AGS)"** meta box
3. **Enable grading** using the toggle
4. **Select activities** to include (checkboxes)
5. **Choose grading scheme** for each activity
6. **Set aggregation method** (sum/average/weighted)
7. **Save the chapter**

#### Step 3: Test Grade Sync

1. **Launch chapter from LMS** (Moodle, Canvas, etc.) as a student
2. **Complete H5P activities**
3. **Check LMS gradebook** - scores should appear automatically

### Example Configurations

#### Example 1: Quiz Chapter (Best Attempt)
```
Chapter: "Chapter 3 - Assessment"
Activities:
  ✓ Quiz 1 (10 questions) - Best Attempt - Weight: 1.0
  ✓ Quiz 2 (5 questions)  - Best Attempt - Weight: 1.0
  ✗ Practice Quiz (excluded)

Aggregation: Sum
Result: Total = Best(Quiz1) + Best(Quiz2)
```

#### Example 2: Practice Chapter (Average)
```
Chapter: "Chapter 2 - Practice"
Activities:
  ✓ Exercise 1 - Average - Weight: 1.0
  ✓ Exercise 2 - Average - Weight: 1.0
  ✓ Exercise 3 - Average - Weight: 1.0

Aggregation: Average
Result: Total = Average(Ex1) + Average(Ex2) + Average(Ex3) / 3
```

#### Example 3: Weighted Assessment
```
Chapter: "Final Assessment"
Activities:
  ✓ Multiple Choice  - Best - Weight: 2.0 (40%)
  ✓ Essay Questions  - Last - Weight: 2.0 (40%)
  ✓ Bonus Activity   - Best - Weight: 1.0 (20%)

Aggregation: Weighted
Result: Weighted average based on assigned weights
```

## Technical Architecture

### How It Works

```
┌─────────────────────────────────────────────────────────────┐
│  1. Student completes H5P activity                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  2. H5PGradeSyncEnhanced receives completion hook           │
│     - h5p_alter_user_result action                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  3. Find chapter containing H5P activity                    │
│     - H5PActivityDetector::find_chapter_containing_h5p()    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  4. Check if grading enabled for chapter                    │
│     - H5PResultsManager::is_grading_enabled()               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  5. Calculate chapter-level score                           │
│     - Apply grading scheme per activity                     │
│     - Aggregate multiple activity scores                    │
│     - H5PResultsManager::calculate_chapter_score()          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  6. Detect grading type (points vs scale)                   │
│     - AGSClient::fetch_lineitem()                           │
│     - ScaleMapper::detect_scale()                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  7. Send grade to LMS via AGS                               │
│     - AGSClient::post_score()                               │
│     - OAuth2 authentication with JWT client assertion       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  8. Log sync to database                                    │
│     - wp_lti_h5p_grade_sync_log table                       │
└─────────────────────────────────────────────────────────────┘
```

### Key Services

#### H5PActivityDetector
**Purpose:** Finds and extracts H5P activities from chapter content

**Methods:**
- `find_h5p_activities($post_id)` - Returns array of H5P activities with metadata
- `get_chapter_max_score($post_id)` - Calculates total maximum score

**Detection Patterns:**
- `[h5p id="123"]`
- `[h5p-iframe id="123"]`

#### H5PResultsManager
**Purpose:** Manages grading configuration and score calculation

**Methods:**
- `save_configuration($post_id, $config)` - Saves chapter grading settings
- `get_configuration($post_id)` - Retrieves configuration
- `calculate_score($user_id, $post_id, $h5p_id, $scheme)` - Per-activity score
- `calculate_chapter_score($user_id, $post_id)` - Aggregate chapter score
- `is_grading_enabled($post_id)` - Check if grading is enabled

**Grading Schemes:**
- `GRADING_BEST` - Highest score
- `GRADING_AVERAGE` - Mean score
- `GRADING_FIRST` - First attempt
- `GRADING_LAST` - Last attempt

#### H5PGradeSyncEnhanced
**Purpose:** Enhanced grade sync with chapter-level support

**Features:**
- Detects chapter containing H5P activity
- Checks grading configuration
- Calculates aggregate scores
- Falls back to individual sync when no configuration exists
- Supports both point and scale grading

**Fallback Behavior:**
- If no chapter configuration → Syncs individual H5P score
- If grading disabled → Syncs individual H5P score
- If H5P not in configuration → Skips sync

## Database Schema

### wp_lti_h5p_grading_config

Stores chapter-level H5P grading configuration.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) | Primary key |
| `post_id` | bigint(20) | Chapter/post ID |
| `h5p_id` | int(10) | H5P content ID |
| `include_in_scoring` | tinyint(1) | Include in grading (0/1) |
| `grading_scheme` | varchar(20) | best, average, first, last |
| `weight` | decimal(5,2) | Weight for weighted average |
| `created_at` | datetime | Creation timestamp |
| `updated_at` | datetime | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`post_id`, `h5p_id`)
- KEY (`post_id`, `include_in_scoring`)

### wp_lti_h5p_grade_sync_log

Tracks grade sync history for debugging and auditing.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) | Primary key |
| `user_id` | bigint(20) | WordPress user ID |
| `post_id` | bigint(20) | Chapter/post ID |
| `result_id` | bigint(20) | H5P result ID |
| `score_sent` | decimal(10,2) | Score sent to LMS |
| `max_score` | decimal(10,2) | Maximum score |
| `synced_at` | datetime | Sync timestamp |
| `status` | varchar(20) | success, failed |
| `error_message` | text | Error details if failed |

**Indexes:**
- PRIMARY KEY (`id`)
- KEY (`user_id`, `post_id`)
- KEY (`user_id`, `post_id`, `synced_at`)

## API Reference

### H5PResultsManager API

```php
// Save grading configuration
H5PResultsManager::save_configuration($post_id, [
    'enabled' => true,
    'aggregate' => 'sum', // or 'average', 'weighted'
    'activities' => [
        123 => [ // H5P ID
            'include' => true,
            'scheme' => 'best',
            'weight' => 1.0
        ]
    ]
]);

// Get configuration
$config = H5PResultsManager::get_configuration($post_id);

// Calculate student's chapter score
$result = H5PResultsManager::calculate_chapter_score($user_id, $post_id);
// Returns: ['score' => 85.5, 'max_score' => 100, 'percentage' => 85.5]

// Get user's attempts
$attempts = H5PResultsManager::get_user_attempts($user_id, $post_id);
```

### H5PActivityDetector API

```php
// Find all H5P activities in a chapter
$activities = H5PActivityDetector::find_h5p_activities($post_id);
/* Returns:
[
    [
        'id' => 123,
        'title' => 'Quiz: Chapter 1',
        'library' => 'H5P.MultiChoice',
        'position' => 1,
        'max_score' => 10
    ],
    ...
]
*/

// Get total maximum score
$max = H5PActivityDetector::get_chapter_max_score($post_id);
```

## Debugging

### Enable Debug Logging

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check Logs

```bash
# Pressbooks debug log
tail -f /var/www/html/web/app/debug.log | grep "PB-LTI"

# Filter for H5P Results
tail -f /var/www/html/web/app/debug.log | grep "H5P Enhanced"
```

### Common Log Messages

```
[PB-LTI H5P Enhanced] Result saved - User: 125, H5P: 123, Score: 8/10
[PB-LTI H5P Enhanced] Chapter 45 score for user 125: 85.00/100.00 (85.0%)
[PB-LTI H5P Enhanced] Using point grading: 85.00/100.00
[PB-LTI H5P Enhanced] ✅ Chapter grade posted successfully to LMS
```

### Troubleshooting

#### No grades appearing in LMS?

1. **Check LTI context:**
   ```sql
   SELECT * FROM wp_usermeta
   WHERE meta_key IN ('_lti_ags_lineitem', '_lti_user_id')
   AND user_id = <USER_ID>;
   ```

2. **Check grading configuration:**
   ```sql
   SELECT * FROM wp_lti_h5p_grading_config WHERE post_id = <CHAPTER_ID>;
   ```

3. **Check sync log:**
   ```sql
   SELECT * FROM wp_lti_h5p_grade_sync_log
   WHERE user_id = <USER_ID> AND post_id = <CHAPTER_ID>
   ORDER BY synced_at DESC LIMIT 10;
   ```

#### Grading scheme not working?

- Verify H5P attempts exist in `wp_h5p_results`
- Check that H5P content ID matches configuration
- Ensure multiple attempts exist for average/best schemes

#### Meta box not showing?

- Verify post type is 'chapter', 'front-matter', or 'back-matter'
- Check that H5P activities exist in content
- Save chapter first to trigger H5P detection

## Performance Considerations

### Database Queries

The feature uses optimized queries with proper indexes:

- Configuration lookup: 1 query per chapter load
- Score calculation: N+1 queries (N = number of configured activities)
- Sync logging: 1 INSERT per grade sync

### Caching

Consider implementing:

- Transient cache for chapter configurations
- Object cache for frequently accessed scores
- Query result caching for user attempts

### Recommendations

For large books with many H5P activities:

- ✅ Use "Sum" or "Average" aggregation (faster than weighted)
- ✅ Limit number of included activities per chapter
- ✅ Consider chapter-level caching strategy
- ✅ Monitor `wp_lti_h5p_grade_sync_log` table size

## Security

### Access Control

- Meta box only visible to users with `edit_post` capability
- Configuration saves verify nonce and capabilities
- Database queries use prepared statements

### Data Validation

- All inputs sanitized before saving
- Score calculations bounded by max_score
- Weights limited to 0-10 range

### LTI Security

- OAuth2 with JWT client assertion (RFC 7523)
- Signed AGS requests with platform verification
- LTI user ID validation before grade posting

## Roadmap

### Planned Features

- [ ] Bulk configuration for multiple chapters
- [ ] Grade preview before publishing
- [ ] Student progress dashboard
- [ ] Gradebook export functionality
- [ ] Manual grade override capability
- [ ] Email notifications for instructors
- [ ] Grade sync retry mechanism
- [ ] Advanced reporting and analytics

### Known Limitations

- Requires H5P plugin with database tables
- Only detects `[h5p]` and `[h5p-iframe]` shortcodes
- Grades only sync for LTI-launched students
- No grade sync for non-LTI access

## Support

### Documentation

- [LTI 1.3 Specification](https://www.imsglobal.org/spec/lti/v1p3/)
- [LTI AGS Specification](https://www.imsglobal.org/spec/lti-ags/v2p0)
- [H5P Documentation](https://h5p.org/documentation)

### Getting Help

1. Check debug logs first
2. Verify database configuration
3. Test with simple single-activity chapter
4. Review this documentation

---

**Version:** 1.0.0
**Last Updated:** 2026-02-14
**Compatible With:** Pressbooks LTI Platform 1.0+
