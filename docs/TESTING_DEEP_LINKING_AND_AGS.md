# Testing Deep Linking 2.0 and AGS

This guide covers end-to-end testing of LTI Advantage features: Deep Linking 2.0 and Assignment & Grade Services (AGS).

## Prerequisites

- ✅ LTI 1.3 tool launch working
- ✅ RSA key pair generated in `wp_lti_keys` table
- ✅ Deployment registered in `wp_lti_deployments` table
- ✅ Test activities created in Moodle

## Test 1: Deep Linking 2.0

### What is Deep Linking?
Deep Linking 2.0 allows instructors to select specific content from Pressbooks to embed in Moodle. Instead of a static URL, instructors can browse and choose books, chapters, or pages dynamically.

### Test Setup

**Already Completed:**
- ✅ Deep Linking test activity created: "Deep Linking Test"
- ✅ Tool configured with `lti_contentitem = 1`
- ✅ DeepLinkController updated to use real private key from database

### Test Steps

1. **Log in to Moodle as Instructor**
   - URL: https://moodle.lti.qbnox.com/
   - Username: `instructor`
   - Password: `Instructor123!`

2. **Navigate to Test Course**
   - Go to "LTI Test Course"
   - Look for activity: "Deep Linking Test"

3. **Launch Deep Linking Flow**
   - Click "Deep Linking Test" activity
   - **Expected:** Moodle initiates OIDC login with Deep Linking message type
   - **Expected:** Redirected to Pressbooks content selection interface

4. **Content Selection**
   - **Expected:** See Pressbooks home page or content picker
   - Select content to link (for now, default URL will be used)
   - **Expected:** Redirected back to Moodle with JWT containing selected content

5. **Verify Content Stored**
   - **Expected:** Activity now shows linked content
   - **Expected:** Students clicking activity launch directly to selected content

### Debugging Deep Linking

If Deep Linking doesn't work:

1. **Check JWT claims in launch request:**
   ```bash
   docker exec pressbooks tail -f /var/log/apache2/error.log | grep "PB-LTI"
   ```

2. **Verify Deep Linking endpoint:**
   ```bash
   curl -s https://pb.lti.qbnox.com/wp-json/pb-lti/v1/deep-link | jq
   ```

3. **Check if private key is loaded:**
   ```bash
   docker exec pressbooks wp db query "SELECT kid, LENGTH(private_key) as key_length FROM wp_lti_keys" --path=/var/www/html/web/wp --allow-root
   ```

4. **Verify JWT signature:**
   - Copy JWT from network inspector
   - Decode at https://jwt.io/
   - Verify `kid` header matches `pb-lti-2024`
   - Verify content_items claim is present

### Expected JWT Structure

```json
{
  "iss": "https://pb.lti.qbnox.com",
  "aud": "pb-lti-ce86a36fa1e79212536130fe7b6e8292",
  "iat": 1770489687,
  "exp": 1770489987,
  "nonce": "...",
  "https://purl.imsglobal.org/spec/lti-dl/claim/content_items": [
    {
      "type": "ltiResourceLink",
      "title": "Pressbooks Content",
      "url": "https://pb.lti.qbnox.com/"
    }
  ]
}
```

---

## Test 2: Assignment & Grade Services (AGS)

### What is AGS?
AGS allows Pressbooks to send grades back to the Moodle gradebook. When a student completes an activity in Pressbooks, the score can be automatically posted to Moodle.

### Test Setup

**Already Completed:**
- ✅ AGS test activity created: "AGS Graded Assignment"
- ✅ Activity configured with `instructorchoiceacceptgrades = 1`
- ✅ Grade maximum set to 100

### Test Steps

#### Phase 1: Launch and Capture AGS Endpoints

1. **Log in to Moodle as Student**
   - URL: https://moodle.lti.qbnox.com/
   - Username: `student`
   - Password: `Student123!`

2. **Launch Graded Activity**
   - Go to "LTI Test Course"
   - Click "AGS Graded Assignment"
   - **Expected:** Successfully launches to Pressbooks

3. **Capture JWT Claims**
   - Open browser dev tools (F12) → Network tab
   - Look for POST to `https://pb.lti.qbnox.com/wp-json/pb-lti/v1/launch`
   - Copy the `id_token` parameter

4. **Decode JWT and Extract AGS Claims**
   - Decode JWT at https://jwt.io/
   - Look for these claims:
     ```json
     {
       "https://purl.imsglobal.org/spec/lti-ags/claim/endpoint": {
         "scope": [
           "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
           "https://purl.imsglobal.org/spec/lti-ags/scope/score"
         ],
         "lineitems": "https://moodle.lti.qbnox.com/mod/lti/services.php/2/lineitems?type_id=1",
         "lineitem": "https://moodle.lti.qbnox.com/mod/lti/services.php/3/lineitems/1/lineitem"
       }
     }
     ```

5. **Save the lineitem URL** (you'll need it for grade posting)

#### Phase 2: Post Grade Back to Moodle

Currently, AGS grade posting requires manual API call since the Pressbooks UI doesn't have a grading interface yet.

**Option A: Using curl (Recommended for Testing)**

```bash
# Extract lineitem URL from JWT (from step 4 above)
LINEITEM_URL="https://moodle.lti.qbnox.com/mod/lti/services.php/3/lineitems/1/lineitem"

# Post a test grade (85.5 out of 100)
curl -X POST 'https://pb.lti.qbnox.com/wp-json/pb-lti/v1/ags/post-score' \
  -H 'Content-Type: application/json' \
  -d "{
    \"lineitem_url\": \"$LINEITEM_URL\",
    \"score\": 85.5,
    \"user_id\": \"4\"
  }"
```

**Option B: Using Pressbooks Plugin (Future)**

When the Pressbooks UI is built, instructors will be able to assign grades directly from the Pressbooks interface.

#### Phase 3: Verify Grade in Moodle

1. **Log in to Moodle as Instructor**
   - Username: `instructor`
   - Password: `Instructor123!`

2. **Navigate to Gradebook**
   - Go to "LTI Test Course"
   - Click "Grades" in the course navigation
   - **Alternative:** Administration → Grades

3. **Verify Grade Posted**
   - Look for student "student" row
   - Look for "AGS Graded Assignment" column
   - **Expected:** Grade shows as `85.5` / `100.0`
   - **Expected:** Grade timestamp is recent

### Debugging AGS

If grade posting doesn't work:

1. **Verify AGS endpoints exist in JWT:**
   ```bash
   # Check if launch JWT contains AGS claims
   docker exec pressbooks tail -100 /var/log/apache2/error.log | grep "lti-ags"
   ```

2. **Check OAuth2 token acquisition:**
   - AGS requires OAuth2 client credentials flow
   - Verify client secret is stored in SecretVault
   - Check TokenCache for cached access tokens

3. **Test OAuth2 token manually:**
   ```bash
   # Get platform configuration
   docker exec pressbooks wp db query "SELECT * FROM wp_lti_platforms WHERE issuer='https://moodle.lti.qbnox.com'" --path=/var/www/html/web/wp --allow-root
   ```

4. **Verify Moodle AGS endpoint:**
   ```bash
   # Moodle should provide AGS endpoints in JWT
   # Check mdl_lti_types.enabledcapability JSON
   docker exec lti-local-lab_moodle_1 php -r "
   require_once('/var/www/html/config.php');
   \$tool = \$DB->get_record('lti_types', ['name' => 'Pressbooks LTI Platform']);
   echo json_encode(json_decode(\$tool->enabledcapability), JSON_PRETTY_PRINT);
   "
   ```

### Expected AGS Flow

```
1. Student launches activity from Moodle
   ↓
2. Moodle sends JWT with AGS lineitem URL
   ↓
3. Student completes activity in Pressbooks
   ↓
4. Pressbooks POSTs score to AGS endpoint
   ↓
5. Pressbooks acquires OAuth2 token from Moodle
   ↓
6. Pressbooks POSTs score to lineitem URL
   ↓
7. Grade appears in Moodle gradebook
```

---

## Common Issues

### Issue: "Unknown issuer" error
**Cause:** Platform not registered in `wp_lti_platforms`
**Fix:** Run `scripts/register-platform.php` to register Moodle

### Issue: "Invalid deployment_id" error
**Cause:** Deployment not registered in `wp_lti_deployments`
**Fix:** Run `scripts/register-deployment.php`

### Issue: "JWT signature invalid" for Deep Linking
**Cause:** Private key not found or malformed
**Fix:** Run `scripts/generate-rsa-keys.php` to regenerate keys

### Issue: "Required AGS scope not granted"
**Cause:** AGS scopes not included in JWT claims
**Fix:** Verify `lti_acceptgrades = 2` in Moodle tool configuration

### Issue: "Client secret not configured" for AGS
**Cause:** OAuth2 client secret not stored
**Fix:** Store client secret in SecretVault:
```php
docker exec pressbooks wp eval 'PB_LTI\Services\SecretVault::store("https://moodle.lti.qbnox.com", "your-client-secret");' --path=/var/www/html/web/wp --allow-root
```

---

## Next Steps

After successful testing:

1. **Build Content Picker UI** - Replace simple redirect with interactive content browser
2. **Build Grading UI** - Add instructor interface to assign grades from Pressbooks
3. **Implement LineItemService** - Create/update grade columns dynamically
4. **Add Audit Logging** - Log all grade posts and Deep Linking selections
5. **Add Error Handling** - User-friendly error messages for failed operations

## Test Evidence

For compliance certification, capture:

- Screenshots of Deep Linking content selection
- Screenshots of grades appearing in Moodle gradebook
- JWT tokens showing content_items and AGS claims
- Network traces of OAuth2 token acquisition
- Audit logs of grade postings

Store evidence in: `docs/compliance/test-evidence/`
