# Fix Firebase Cloud Messaging API Permission Error

## Error Details

```
403 Permission Denied
"Permission 'cloudmessaging.messages.create' denied on resource
'//cloudresourcemanager.googleapis.com/projects/connect-app-efa83'"
```

## Solution: Enable Firebase Cloud Messaging API

### Step 1: Enable the API

1. **Go to Google Cloud Console**:
   https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=connect-app-efa83

2. **Click "ENABLE"** button

    OR

3. **Alternative method - via Firebase Console**:
    - Go to: https://console.firebase.google.com/project/connect-app-efa83/settings/cloudmessaging
    - Under "Cloud Messaging API (HTTP v1)", click **"Manage API in Google Cloud Console"**
    - Click **"ENABLE"** button

### Step 2: Verify Service Account Permissions

1. **Go to IAM & Admin**:
   https://console.cloud.google.com/iam-admin/iam?project=connect-app-efa83

2. **Find your service account**:

    - Look for: `firebase-adminsdk-fbsvc@connect-app-efa83.iam.gserviceaccount.com`

3. **Check it has one of these roles**:

    - ✅ `Firebase Cloud Messaging Admin`
    - ✅ `Firebase Admin`
    - ✅ `Editor` (or higher)

4. **If not, add the role**:
    - Click the pencil icon next to the service account
    - Click "ADD ANOTHER ROLE"
    - Select: "Firebase Cloud Messaging Admin"
    - Click "SAVE"

### Step 3: Wait 1-2 Minutes

After enabling the API, wait 1-2 minutes for permissions to propagate.

### Step 4: Test Again

From the admin panel, try sending a push notification again.

---

## Quick Links

-   [Enable FCM API](https://console.cloud.google.com/apis/library/fcm.googleapis.com?project=connect-app-efa83)
-   [IAM Permissions](https://console.cloud.google.com/iam-admin/iam?project=connect-app-efa83)
-   [Firebase Settings](https://console.firebase.google.com/project/connect-app-efa83/settings/cloudmessaging)

---

## Current Status

-   ✅ Service account JSON: Correct project (connect-app-efa83)
-   ✅ VAPID keys: Configured
-   ⚠️ FCM API: **NOT ENABLED** (403 error)
-   ⚠️ Service account permissions: Need to verify

---

## Expected Results After Fix

When sending notifications:

-   **Expo tokens**: Should work via Expo API
-   **FCM tokens**: Should work via FCM v1 API
-   **Web push**: Should work with VAPID keys

Status should be: `"Sent: 3, Failed: 0"`
