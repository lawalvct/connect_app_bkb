# Recorded Video Upload Feature Documentation

## Overview

This feature allows admins to upload pre-recorded videos (past livestreams or events) that users can watch or pay for, with full control over availability periods and download permissions.

## Features Implemented

### 1. Database Changes

- **New Migration**: `2026_01_18_000001_add_recorded_video_fields_to_streams_table.php`
- **New Fields Added to `streams` table**:
    - `is_recorded` (boolean): Indicates if content is a recorded video or live stream
    - `video_file` (string): Path to uploaded video file
    - `video_url` (string): Full URL to access the video
    - `video_duration` (integer): Video duration in seconds (can be populated with ffmpeg)
    - `is_downloadable` (boolean): Controls if users can download the video
    - `available_from` (timestamp): When the video becomes available
    - `available_until` (timestamp): When the video expires
    - Index added for efficient querying of availability

### 2. Model Updates (Stream.php)

- **New Fillable Fields**: All recorded video fields added
- **New Casts**: Proper casting for boolean and datetime fields
- **New Scopes**:
    - `scopeRecorded()`: Filter only recorded videos
    - `scopeLiveStreams()`: Filter only live streams
    - `scopeAvailable()`: Filter available content based on date ranges
- **New Methods**:
    - `isAvailable()`: Check if recorded video is currently available
    - `getFormattedDuration()`: Format video duration as HH:MM:SS
    - `getAvailabilityStatus()`: Get current availability status (scheduled/available/expired)

### 3. Admin Interface Updates

#### Create Form (create.blade.php)

- **Content Type Selection**: Radio buttons to choose between "Live Stream" or "Upload Recorded Video"
- **Video Upload**: Drag-and-drop file upload for videos (MP4, MOV, AVI, WebM up to 2GB)
- **Downloadable Option**: Checkbox to allow/disallow video downloads
- **Availability Period**: Date/time pickers for:
    - `Available From`: When video becomes accessible (optional - immediate if empty)
    - `Available Until`: When video expires (optional - permanent if empty)
- **Conditional Display**: Form fields show/hide based on content type selection

#### Index View (index.blade.php)

- **New Filter**: "Content Type" dropdown to filter by:
    - All Content
    - Live Streams only
    - Recorded Videos only
- Video indicators in the streams table

### 4. Controller Updates (StreamManagementController.php)

#### Store Method (Enhanced)

- **Dynamic Validation**: Different rules based on content type
- **Live Stream Validation**:
    - `stream_type` and `scheduled_at` required
- **Recorded Video Validation**:
    - `video_file` required (MP4, MOV, AVI, WMV, FLV, WebM, max 2GB)
    - `available_from` and `available_until` optional with date validation
- **Video Processing**:
    - Uploads to `public/streams/videos/`
    - Generates unique filename with timestamp and UUID
    - Sets `status` to 'ended' for recorded videos
    - Does NOT send email notifications for recorded videos
- **Availability Handling**: Properly sets date ranges

#### GetStreams Method (Enhanced)

- **New Filter**: `content_type` parameter to filter results
- **Enhanced Response**: Includes all recorded video fields in API response

## Usage Instructions

### Setup

1. Run the setup script:

    ```bash
    # Windows
    setup_recorded_videos.bat

    # Linux/Mac
    bash setup_recorded_videos.sh
    ```

2. Or manually:

    ```bash
    # Create directories
    mkdir -p public/streams/videos
    chmod -R 755 public/streams

    # Run migration
    php artisan migrate
    ```

### Uploading a Recorded Video

1. **Navigate to Admin Panel** → Streams → Create Stream

2. **Select Content Type**: Choose "Upload Recorded Video"

3. **Fill in Details**:
    - Title (required)
    - Description
    - Banner Image
    - User (who the video belongs to)
    - Pricing:
        - Free Minutes: How many minutes are free
        - Price: Cost after free minutes (if applicable)
        - Currency: NGN, USD, EUR, or GBP

4. **Upload Video**:
    - Click or drag-and-drop video file
    - Supported formats: MP4, MOV, AVI, WMV, FLV, WebM
    - Maximum size: 2GB

5. **Set Options**:
    - **Downloadable**: Check to allow users to download
    - **Availability Period**:
        - Leave both empty: Available immediately and permanently
        - Set "Available From": Video becomes available at that date/time
        - Set "Available Until": Video expires at that date/time

6. **Submit**: Click "Create Stream"

### Managing Recorded Videos

#### Viewing

- All recorded videos appear in the Streams list with an indicator
- Filter by "Recorded Videos" using the Content Type dropdown
- View details by clicking on any recorded video

#### Availability Statuses

- **scheduled**: Video not yet available (before `available_from`)
- **available**: Currently available for viewing
- **expired**: No longer available (after `available_until`)

## Key Differences: Live vs Recorded

| Feature         | Live Stream         | Recorded Video      |
| --------------- | ------------------- | ------------------- |
| Status          | upcoming/live/ended | Always 'ended'      |
| Video File      | N/A                 | Required upload     |
| Scheduling      | Stream timing       | Availability period |
| Notifications   | Sent to users       | NOT sent            |
| Broadcasting    | Real-time           | Pre-recorded        |
| Download Option | N/A                 | Optional            |

## Technical Notes

### File Storage

- **Location**: `public/streams/videos/`
- **Naming**: `{timestamp}_{uuid}.{extension}`
- **URL Generation**: `asset('streams/videos/{filename}')`

### Video Duration

- Currently set to `null` on upload
- Can be enhanced using:
    - FFmpeg: `ffmpeg -i video.mp4 2>&1 | grep Duration`
    - getID3 library: `composer require james-heinrich/getid3`

### Security Considerations

1. File uploads validated by MIME type and extension
2. Unique filenames prevent overwrites
3. 2GB file size limit prevents abuse
4. User authentication required for admin access

### Performance

- Large file uploads may timeout on some servers
- Consider increasing PHP limits:
    ```ini
    upload_max_filesize = 2048M
    post_max_size = 2048M
    max_execution_time = 600
    memory_limit = 512M
    ```

### Future Enhancements

1. **Video Processing**:
    - Auto-extract duration using FFmpeg
    - Generate thumbnails from video
    - Multiple quality options (720p, 1080p)
    - Video compression

2. **Advanced Features**:
    - Bulk upload multiple videos
    - Video chapters/markers
    - Subtitle upload support
    - Analytics (watch time, completion rate)

3. **User Features**:
    - Watch history
    - Continue watching from last position
    - Playlists
    - Recommendations

## API Endpoints

### Create Stream/Upload Video

```
POST /admin/streams
Content-Type: multipart/form-data

Parameters:
- title (required)
- content_type: 'live' or 'recorded' (required)
- video_file (required if content_type=recorded)
- is_downloadable (boolean, optional)
- available_from (datetime, optional)
- available_until (datetime, optional)
- [other stream fields...]
```

### List Streams with Filters

```
GET /admin/api/streams?content_type=recorded
Parameters:
- content_type: 'live' or 'recorded'
- status: 'upcoming', 'live', 'ended'
- search: keyword
- page: pagination
```

## Backward Compatibility

✅ **Fully Backward Compatible**

- Existing live streams continue to work unchanged
- All existing functionality preserved
- New fields have sensible defaults:
    - `is_recorded` defaults to `false`
    - `is_downloadable` defaults to `false`
    - Availability dates default to `null` (no restrictions)

## Testing Checklist

- [ ] Create a live stream (immediate)
- [ ] Create a live stream (scheduled)
- [ ] Upload a recorded video
- [ ] Set availability period
- [ ] Toggle downloadable option
- [ ] Filter by content type
- [ ] Verify file storage
- [ ] Check file size limits
- [ ] Test different video formats
- [ ] Verify no notifications sent for recorded videos

## Support

For issues or questions:

1. Check error logs: `storage/logs/laravel.log`
2. Verify file permissions on `public/streams/videos`
3. Check PHP configuration for upload limits
4. Ensure migration ran successfully

## Version History

- **v1.0.0** (2026-01-18): Initial release
    - Basic recorded video upload
    - Availability period control
    - Download permission toggle
    - Admin interface integration
