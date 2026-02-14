# Retroactive Grade Sync - Implementation Summary

## Overview

Implemented a comprehensive solution for syncing **existing/historical H5P grades** from Pressbooks to Moodle LMS via LTI 1.3 Assignment and Grade Services (AGS).

**Date:** 2026-02-14
**Status:** ✅ Deployed and Ready for Testing

---

## What Was Built

### 1. Backend Service Method
**File:** `plugin/Services/H5PGradeSyncEnhanced.php`

**New Method:** `sync_existing_grades($post_id, $user_id = null)`

**Functionality:**
- Finds all H5P results for a chapter that haven't been synced
- Groups results by user
- Checks if user has active LTI context (came from LMS)
- Calculates chapter-level score using current grading configuration
- Detects scale vs points grading
- Posts grades to LMS via AGS
- Logs sync results for auditing

**Parameters:**
- `$post_id` (int): Chapter/post ID to sync
- `$user_id` (int|null): Optional - sync specific user only, or null for all users

**Returns:** Array with results summary:
```php
[
    'success' => 5,    // Number of grades successfully posted
    'skipped' => 3,    // Number of students without LTI context
    'failed' => 1,     // Number of failed sync attempts
    'errors' => [...]  // Array of error messages
]
```

**Key Logic:**
```php
// Query all H5P results for configured activities
$h5p_results = $wpdb->get_results(...);

// For each user:
foreach ($users_to_sync as $wp_user_id => $content_ids) {
    // 1. Check LTI context exists
    $lineitem_url = get_user_meta($wp_user_id, '_lti_ags_lineitem', true);

    // 2. Calculate chapter score
    $chapter_score = H5PResultsManager::calculate_chapter_score($wp_user_id, $post_id);

    // 3. Detect scale type
    $scale_type = ScaleMapper::detect_scale($lineitem);

    // 4. Post grade via AGS
    AGSClient::post_score($platform, $lineitem_url, $lti_user_id, ...);
}
```

---

### 2. AJAX Handler
**File:** `plugin/ajax/handlers.php`

**New Action:** `wp_ajax_pb_lti_sync_existing_grades`

**Handler Function:** `pb_lti_ajax_sync_existing_grades()`

**Security:**
- Nonce verification: `check_ajax_referer('pb_lti_sync_grades', 'nonce')`
- Capability check: `current_user_can('edit_post', $post_id)`

**Request:**
```javascript
POST /wp-admin/admin-ajax.php
{
    action: 'pb_lti_sync_existing_grades',
    post_id: 123,
    nonce: 'abc123...'
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "Sync complete: 5 succeeded, 3 skipped, 1 failed",
        "results": {
            "success": 5,
            "skipped": 3,
            "failed": 1,
            "errors": ["User 125: OAuth2 token acquisition failed"]
        }
    }
}
```

---

### 3. User Interface
**File:** `plugin/admin/h5p-results-metabox.php`

**New Section:** "🔄 Sync Existing Grades"

**UI Components:**

1. **Description Text:**
   - Explains what the feature does
   - Notes that only LTI-launched students will be synced

2. **Sync Button:**
   ```html
   <button type="button"
           id="pb-lti-sync-existing-grades"
           class="button button-secondary"
           data-post-id="<?php echo $post->ID; ?>">
       🔄 Sync Existing Grades to LMS
   </button>
   ```

3. **Loading Spinner:**
   - Displays while sync is in progress
   - Uses WordPress `spinner` class

4. **Results Display:**
   - Shows success/skipped/failed counts
   - Displays detailed error messages in expandable `<details>` element
   - Color-coded notices: green (success), red (error)

**Visual Design:**
- Green background (`#f0fdf4`) with green border
- Clear section separation
- Responsive layout
- Accessible markup

---

### 4. JavaScript Logic
**File:** `plugin/admin/h5p-results-metabox.php` (embedded `<script>`)

**Event Handler:**
```javascript
$('#pb-lti-sync-existing-grades').on('click', function() {
    // 1. Show confirmation dialog
    if (!confirm('This will sync all existing H5P grades...')) return;

    // 2. Show loading state
    $button.prop('disabled', true);
    $spinner.addClass('is-active');

    // 3. Make AJAX request
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { action: 'pb_lti_sync_existing_grades', ... },

        // 4. Handle response
        success: function(response) {
            // Display results with detailed breakdown
        },
        error: function(xhr, status, error) {
            // Show error message
        }
    });
});
```

**Features:**
- Confirmation dialog before syncing (prevents accidental clicks)
- Loading state with disabled button and spinner
- Detailed results display with expandable error list
- Error handling with user-friendly messages

---

### 5. CSS Styling
**File:** `plugin/admin/h5p-results-metabox.php` (embedded `<style>`)

**New Styles:**
```css
.pb-lti-sync {
    background: #f0fdf4;          /* Light green background */
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #bbf7d0;    /* Green border */
}

#pb-lti-sync-results {
    padding: 10px;
    margin-top: 15px;
}

#pb-lti-sync-results details {
    margin-top: 10px;
    padding: 10px;
    background: rgba(0,0,0,0.05);
    border-radius: 3px;
}
```

---

## User Workflow

### Instructor Flow

1. **Edit Chapter** with H5P activities
2. **Scroll to Meta Box**: "📊 LMS Grade Reporting (LTI AGS)"
3. **Enable Grading** and configure activities
4. **Save Chapter**
5. **Click "🔄 Sync Existing Grades to LMS"** button
6. **Confirm** the action in the dialog
7. **Wait** for sync to complete (spinner shows progress)
8. **Review Results**:
   - ✅ "Sync complete: 5 succeeded, 3 skipped, 1 failed"
   - View detailed breakdown
   - Expand "View Errors" to see specific issues
9. **Verify in LMS** - Check Moodle gradebook

### What Happens Behind the Scenes

```
Button Click
    ↓
Confirmation Dialog
    ↓
AJAX Request to wp-admin/admin-ajax.php
    ↓
pb_lti_ajax_sync_existing_grades()
    ↓
H5PGradeSyncEnhanced::sync_existing_grades($post_id)
    ↓
Query wp_h5p_results for all results
    ↓
Group by user
    ↓
For each user:
    - Check LTI context (lineitem_url, lti_user_id)
    - Calculate chapter score (H5PResultsManager)
    - Detect scale type (ScaleMapper)
    - Post grade (AGSClient)
    - Log sync (wp_lti_h5p_grade_sync_log)
    ↓
Return results summary
    ↓
Display to instructor
```

---

## Technical Architecture

### Database Tables Used

1. **wp_h5p_results**: Source of historical H5P completion data
2. **wp_lti_h5p_grading_config**: Chapter grading configuration
3. **wp_usermeta**: LTI context metadata (`_lti_ags_lineitem`, `_lti_user_id`, `_lti_platform_issuer`)
4. **wp_lti_platforms**: Platform registration for OAuth2
5. **wp_lti_h5p_grade_sync_log**: Audit trail of sync operations

### Integration Points

**Existing Services:**
- `H5PResultsManager::calculate_chapter_score()` - Score calculation
- `AGSClient::fetch_lineitem()` - Fetch lineitem details
- `AGSClient::post_score()` - Post grade to LMS
- `ScaleMapper::detect_scale()` - Detect scale vs points grading
- `ScaleMapper::map_to_scale()` - Map percentage to scale value

**New Service:**
- `H5PGradeSyncEnhanced::sync_existing_grades()` - Bulk sync orchestration

### Error Handling

**User-Level Errors:**
- "Grading not enabled for this chapter"
- "No H5P activities configured for grading"
- "No grades were synced" (with specific reasons)

**Student-Level Errors:**
- "User X has no LTI context - skipping"
- "Platform not found for issuer: Y"
- "OAuth2 token acquisition failed"
- "400 Incorrect score received"

**Logging:**
All errors logged to WordPress debug log:
```
[PB-LTI H5P Sync] Found 5 H5P results to potentially sync for post 123
[PB-LTI H5P Sync] User 139 has no LTI context - skipping
[PB-LTI H5P Sync] ✅ Synced grade for user 125: 85.00/100.00 (85.0%)
[PB-LTI H5P Sync] ❌ Failed for user 130: OAuth2 token acquisition failed
```

---

## Testing Checklist

### Functional Testing

- [ ] Button appears in meta box when grading is enabled
- [ ] Confirmation dialog shows before syncing
- [ ] Spinner displays during sync
- [ ] Results display with correct counts
- [ ] Error messages are user-friendly
- [ ] Grades appear in Moodle gradebook
- [ ] Students without LTI context are skipped correctly
- [ ] Scale grading works (if LMS activity uses scales)

### Edge Cases

- [ ] Chapter with no H5P activities
- [ ] Chapter with H5P but grading disabled
- [ ] No students have completed activities
- [ ] All students accessed directly (no LTI context)
- [ ] Mixed: some students via LTI, some direct
- [ ] OAuth2 failure (invalid platform credentials)
- [ ] Network timeout during sync

### Security Testing

- [ ] Non-admin users cannot trigger sync
- [ ] Nonce verification prevents CSRF
- [ ] Capability check enforces permissions
- [ ] SQL injection protection (prepared statements)

---

## Deployment Status

✅ **Files Updated:**
1. `plugin/Services/H5PGradeSyncEnhanced.php` - New `sync_existing_grades()` method
2. `plugin/ajax/handlers.php` - New AJAX handler
3. `plugin/admin/h5p-results-metabox.php` - UI section, JavaScript, CSS

✅ **Deployed to Container:**
```bash
docker cp .../H5PGradeSyncEnhanced.php pressbooks:/var/www/html/.../
docker cp .../handlers.php pressbooks:/var/www/html/.../
docker cp .../h5p-results-metabox.php pressbooks:/var/www/html/.../
```

✅ **Syntax Verified:**
```
No syntax errors detected in H5PGradeSyncEnhanced.php
No syntax errors detected in handlers.php
No syntax errors detected in h5p-results-metabox.php
```

✅ **Apache Reloaded:**
```
AH00558: apache2: ... (harmless warning)
```

---

## Documentation

Created comprehensive documentation:

**File:** `docs/RETROACTIVE_GRADE_SYNC.md`

**Sections:**
1. Overview and Use Case
2. How to Use (Step-by-step)
3. Important Notes (Who Gets Synced, What Gets Synced)
4. Scale Grading Support
5. Troubleshooting (No Grades Synced, Partial Success, Debugging)
6. Technical Details (Database Queries, Performance, AJAX Implementation)
7. Best Practices
8. FAQ (8 common questions)

---

## Next Steps

### Testing

1. **Manual Test:**
   - Create test chapter with H5P activities
   - Have student complete H5P before enabling grading
   - Enable grading configuration
   - Click "Sync Existing Grades"
   - Verify grade appears in Moodle

2. **Debug Logging:**
   ```bash
   docker exec pressbooks tail -f /var/www/html/web/app/debug.log | grep "H5P Sync"
   ```

3. **Verify Moodle Gradebook:**
   - https://moodle.lti.qbnox.com/grade/report/grader/index.php?id=2

### Future Enhancements (Optional)

- [ ] **Batch Processing**: Sync in batches to avoid timeouts on large chapters
- [ ] **Per-Student Sync**: Add ability to sync individual student grades
- [ ] **Scheduled Sync**: Add WP-Cron job for automatic daily sync
- [ ] **Sync History**: Show previous sync results in meta box
- [ ] **Progress Bar**: Real-time progress indicator for large syncs
- [ ] **Email Notifications**: Notify instructor when sync completes

---

## Technical Notes

### Performance Considerations

**Current Implementation:**
- Synchronous processing (all students synced in one request)
- Suitable for chapters with <100 students
- May timeout on chapters with >500 students

**Optimization Strategies (if needed):**
1. Batch processing with AJAX pagination
2. Background processing via WP-Cron
3. Queue system (Action Scheduler plugin)

### Database Efficiency

**Queries Used:**
```sql
-- 1. Get all H5P results for chapter (1 query)
SELECT DISTINCT user_id, content_id, MAX(id) as latest_result_id
FROM wp_h5p_results
WHERE content_id IN (123, 124, 125)
GROUP BY user_id, content_id

-- 2. Check LTI context per user (N queries, but cached in memory)
SELECT meta_value FROM wp_usermeta
WHERE user_id = X AND meta_key = '_lti_ags_lineitem'

-- 3. Get platform config (1 query, same for all users)
SELECT * FROM wp_lti_platforms WHERE issuer = 'https://moodle...'
```

**Total Queries:** ~3 + (2 × N users)

---

## Known Limitations

1. **LTI Context Required**: Only syncs students who accessed via LTI launch. Direct access to Pressbooks doesn't create LTI context.

2. **Current Configuration**: Grades calculated based on **current** grading config, not historical config at time of completion.

3. **No Overwrite Protection**: Will overwrite manual grade adjustments in LMS.

4. **Manual Trigger**: Not automatic - instructor must click button each time.

5. **Timeout Risk**: May timeout on very large chapters (>500 students). Consider batch implementation if needed.

---

## Success Criteria

✅ **Implemented:**
- Backend service method for bulk sync
- AJAX handler with security checks
- User interface with button and results display
- JavaScript for AJAX communication
- Error handling and logging
- Comprehensive documentation

✅ **Deployed:**
- All files copied to container
- Syntax validated
- Apache reloaded

⏳ **Awaiting Testing:**
- Manual test with real H5P results
- Verification in Moodle gradebook
- Edge case testing

---

## Contact & Support

**Implementation Date:** 2026-02-14
**Implemented By:** Claude Sonnet 4.5
**Documentation:** `/root/pressbooks-lti-platform/docs/RETROACTIVE_GRADE_SYNC.md`

For issues or questions:
1. Check debug logs first
2. Review documentation
3. Test with simple case (single student, single H5P)
4. Report specific error messages with context
