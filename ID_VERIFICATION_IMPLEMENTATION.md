# ID Card Verification System Implementation

## Overview

Complete implementation of the ID card verification system for user identity verification with admin review workflow.

## Database Schema

### Migration: `2025_08_09_004256_create_user_verifications_table.php`

```sql
CREATE TABLE user_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    id_card_type ENUM('national_id', 'passport', 'drivers_license', 'voters_card', 'international_passport') NOT NULL,
    id_card_image VARCHAR(255) NOT NULL,
    admin_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_reason TEXT NULL,
    submitted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, admin_status),
    INDEX idx_admin_status (admin_status),
    INDEX idx_submitted_at (submitted_at)
);
```

## Models

### UserVerification Model

-   **Location**: `app/Models/UserVerification.php`
-   **Fillable Fields**: user_id, id_card_type, id_card_image, admin_status, admin_reason, submitted_at, reviewed_at, reviewed_by
-   **Relationships**:
    -   `user()` - belongsTo User
    -   `reviewer()` - belongsTo User (admin who reviewed)
-   **Scopes**:
    -   `pending()` - pending verifications
    -   `approved()` - approved verifications
    -   `rejected()` - rejected verifications
-   **Methods**:
    -   `isPending()` - check if pending
    -   `isApproved()` - check if approved
    -   `isRejected()` - check if rejected
    -   `approve($reviewerId, $reason)` - approve verification
    -   `reject($reviewerId, $reason)` - reject verification
-   **Accessors**:
    -   `getIdCardImageUrlAttribute()` - full URL for ID card image

### User Model Enhancements

-   **Location**: `app/Models/User.php`
-   **New Relationships**:
    -   `verifications()` - hasMany UserVerification
    -   `latestVerification()` - hasOne UserVerification (latest)
-   **New Methods**:
    -   `isVerified()` - check if user has approved verification

## API Endpoints

### Submit ID Card for Verification

**Endpoint**: `POST /api/v1/profile/verify-me`

**Headers**:

```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
```

**Request Parameters**:

```
id_card_type: required|string|in:national_id,passport,drivers_license,voters_card,international_passport
id_card_image: required|image|mimes:jpeg,png,jpg,gif|max:10048 (10MB)
```

**Success Response** (200):

```json
{
    "success": true,
    "message": "ID card submitted successfully for verification",
    "data": {
        "verification": {
            "id": 1,
            "id_card_type": "national_id",
            "status": "pending",
            "submitted_at": "2025-01-09T12:00:00.000000Z",
            "image_url": "http://localhost/uploads/verifyme/123_1704718800_abc123.jpg"
        },
        "message": "Your ID card has been submitted for admin review. You will be notified once the verification is complete."
    }
}
```

**Error Responses**:

-   **422**: Validation errors
-   **500**: User already verified or has pending verification

## File Storage

### Directory Structure

```
public/
├── uploads/
    └── verifyme/
        ├── {user_id}_{timestamp}_{unique_id}.jpg
        ├── {user_id}_{timestamp}_{unique_id}.png
        └── ...
```

### File Naming Convention

Format: `{user_id}_{timestamp}_{unique_id}.{extension}`

-   Ensures uniqueness and prevents conflicts
-   Easy to identify user and upload time
-   Maintains original file extension

## Implementation Details

### Controller: ProfileController

**Location**: `app/Http/Controllers/API/V1/ProfileController.php`

#### processIdCardVerification Method

```php
protected function processIdCardVerification($user, $idCardType, $idCardImage)
{
    // 1. Check for existing verifications (prevents duplicates)
    // 2. Create uploads/verifyme directory if needed
    // 3. Generate unique filename
    // 4. Move uploaded file to storage
    // 5. Create verification record in database
    // 6. Log the verification attempt
    // 7. Return verification object
}
```

**Features**:

-   ✅ Prevents duplicate submissions (pending/approved blocks)
-   ✅ Secure file handling with unique naming
-   ✅ Database transaction safety
-   ✅ Comprehensive error logging
-   ✅ Automatic directory creation

### Validation Rules

-   **ID Card Types**: 5 supported types (national_id, passport, drivers_license, voters_card, international_passport)
-   **File Types**: JPEG, PNG, JPG, GIF only
-   **File Size**: Maximum 10MB (10,048 KB)
-   **Duplicate Prevention**: Users cannot submit if they have pending or approved verification

## Admin Workflow

### Approval Process

```php
// Approve verification
$verification = UserVerification::find($id);
$verification->approve($adminId, 'Document verified successfully');

// Reject verification
$verification->reject($adminId, 'Document quality insufficient');
```

### Status Tracking

-   **pending**: Initial submission status
-   **approved**: Admin approved the verification
-   **rejected**: Admin rejected with reason

## Security Features

1. **File Validation**: Strict file type and size validation
2. **Unique Naming**: Prevents file conflicts and guessing
3. **Directory Isolation**: Files stored in dedicated folder
4. **Database Constraints**: Foreign key relationships ensure data integrity
5. **Duplicate Prevention**: Business logic prevents abuse
6. **Error Logging**: Comprehensive logging for debugging

## Usage Examples

### Check User Verification Status

```php
$user = User::find(1);
if ($user->isVerified()) {
    echo "User is verified";
}

$latestVerification = $user->latestVerification;
if ($latestVerification && $latestVerification->isPending()) {
    echo "Verification is pending admin review";
}
```

### Admin Review Interface

```php
// Get pending verifications
$pendingVerifications = UserVerification::pending()
    ->with('user')
    ->orderBy('submitted_at')
    ->get();

// Process verification
foreach ($pendingVerifications as $verification) {
    echo "User: " . $verification->user->name;
    echo "Type: " . $verification->id_card_type;
    echo "Image: " . $verification->id_card_image_url;
    echo "Submitted: " . $verification->submitted_at;
}
```

## Testing

### Manual Testing Steps

1. Create user account and authenticate
2. Submit POST request to `/api/v1/profile/verify-me` with valid ID card
3. Verify file is saved in `public/uploads/verifyme/`
4. Check database record in `user_verifications` table
5. Test duplicate submission prevention
6. Test admin approval/rejection workflow

### Test Cases Covered

-   ✅ Valid file upload and storage
-   ✅ Database record creation
-   ✅ Duplicate submission prevention
-   ✅ File validation (type, size)
-   ✅ Unique filename generation
-   ✅ URL generation for images
-   ✅ User verification status checking

## File Structure Summary

```
app/
├── Models/
│   ├── UserVerification.php (NEW)
│   └── User.php (UPDATED)
├── Http/Controllers/API/V1/
│   └── ProfileController.php (UPDATED)

database/migrations/
└── 2025_08_09_004256_create_user_verifications_table.php (NEW)

public/uploads/
└── verifyme/ (NEW DIRECTORY)

routes/
└── api/v1.php (EXISTING - verify-me endpoint)
```

## Status: ✅ COMPLETE

All components implemented and tested successfully. The ID card verification system is ready for production use.
