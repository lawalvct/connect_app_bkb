# Quick Start Guide: Upload Recorded Videos

## Overview

Admins can now upload pre-recorded videos (past events, livestream replays) that users can watch or pay for.

## How to Upload a Recorded Video

### Step 1: Access the Create Stream Page

1. Go to Admin Panel → **Streams** → **Create Stream**

### Step 2: Choose Content Type

- Select **"Upload Recorded Video"** radio button
- (The form will automatically adjust to show video upload options)

### Step 3: Fill Basic Information

- **Title**: Give your video a descriptive title
- **Description**: Add details about the content
- **Banner Image**: Upload a thumbnail/cover image (optional)

### Step 4: Set Pricing

- **Free Minutes**: How many minutes users can watch for free (0 = fully paid)
- **Price**: Cost to watch beyond free minutes
- **Currency**: Choose NGN, USD, EUR, or GBP

### Step 5: Upload Video

- Click **"Upload a video"** or drag and drop
- **Supported formats**: MP4, MOV, AVI, WMV, FLV, WebM
- **Maximum size**: 2GB
- Wait for the file to upload (large files may take time)

### Step 6: Configure Options

#### Allow Downloads?

- ☑ **Check** to let users download the video
- ☐ **Uncheck** to only allow streaming (no downloads)

#### Set Availability Period (Optional)

- **Available From**: When the video becomes available
    - Leave empty = Available immediately
- **Available Until**: When the video expires
    - Leave empty = Available permanently

**Example Scenarios:**

- **Immediate & Permanent**: Leave both empty
- **Launch Later**: Set "Available From" to future date
- **Limited Time Offer**: Set both dates (e.g., available for 30 days)
- **Event Replay Window**: From event date to 7 days after

### Step 7: Submit

- Click **"Create Stream"**
- Wait for confirmation message
- Video is now available based on your settings

## Quick Tips

✅ **Do's:**

- Use high-quality videos for better user experience
- Add clear, descriptive titles
- Set appropriate pricing based on content value
- Use banner images that represent the content
- Test the upload with a small file first

❌ **Don'ts:**

- Don't upload videos over 2GB (they'll be rejected)
- Don't set "Available Until" before "Available From"
- Don't forget to set pricing if you want it to be paid content
- Don't upload copyrighted content without permission

## Understanding Availability Status

| Status        | Meaning                                                             |
| ------------- | ------------------------------------------------------------------- |
| **Scheduled** | Video uploaded but not yet available (before "Available From" date) |
| **Available** | Currently accessible to users                                       |
| **Expired**   | No longer available (after "Available Until" date)                  |

## Filtering & Finding Videos

In the **Streams** index page:

- Use **"Content Type"** dropdown → Select **"Recorded Videos"**
- This will show only uploaded videos (not live streams)

## Differences: Live Stream vs Recorded Video

| Feature       | Live Stream            | Recorded Video      |
| ------------- | ---------------------- | ------------------- |
| Content       | Real-time broadcast    | Pre-recorded file   |
| When Created  | User goes live         | Admin uploads       |
| Notifications | Sent to users          | NOT sent            |
| Timing        | Scheduled or immediate | Availability period |
| Download      | N/A                    | Optional            |

## Common Issues & Solutions

### "File too large" error

- **Solution**: Video exceeds 2GB. Compress it or split into parts.

### Video won't upload

- **Solution**: Check file format. Only MP4, MOV, AVI, WMV, FLV, WebM allowed.

### Users can't see video

- **Solution**: Check "Available From" date. Make sure it's not in the future.

### Need more storage

- **Solution**: Contact system administrator to increase server storage.

## Example Use Cases

1. **Conference Replay**: Upload last month's conference for attendees who missed it
2. **Training Series**: Upload training videos with 30-day access periods
3. **Premium Content**: Upload exclusive content with payment required
4. **Free Samples**: Set first 5 minutes free, charge for full video
5. **Seasonal Events**: Upload holiday event with expiration after the season

## Need Help?

Contact your system administrator or check the full documentation in `RECORDED_VIDEO_FEATURE.md`
