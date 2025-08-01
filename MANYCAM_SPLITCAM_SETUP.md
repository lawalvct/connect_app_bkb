# ManyCam & SplitCam Integration Guide

## Quick Setup Summary

Your Laravel streaming project **fully supports** RTMP streaming from professional software like ManyCam and SplitCam. Here's how to connect them:

### 🚀 Immediate Setup Steps

1. **Run the migration** to create RTMP support:

    ```bash
    php artisan migrate
    ```

2. **Configure your .env file**:

    ```env
    # Add these RTMP settings to your .env
    RTMP_SERVER_URL=rtmp://your-domain.com/live
    RTMP_SERVER_KEY=your-secret-key
    NGINX_RTMP_ENABLED=true
    STREAM_BRIDGE_ENABLED=true

    # Streaming defaults
    DEFAULT_STREAM_RESOLUTION=1920x1080
    DEFAULT_STREAM_BITRATE=3000
    DEFAULT_STREAM_FPS=30
    ```

3. **Access RTMP details** in your broadcast page:
    - Go to any stream's broadcast page
    - Click "Cameras" dropdown
    - Click "Get RTMP Details" in the purple Professional Streaming section
    - Copy the RTMP URL and Stream Key

## ManyCam Configuration

### Step-by-Step Setup

1. **Open ManyCam**
2. **Add your camera sources**:

    - Add multiple cameras, screen capture, etc.
    - Set up scenes with different camera layouts
    - Configure transitions and effects

3. **Configure Streaming**:

    - Go to **Settings** → **Streaming**
    - Select **"Custom RTMP"**
    - Enter details from your Laravel app:
        - **RTMP URL**: `rtmp://your-domain.com/live`
        - **Stream Key**: [Get from Laravel broadcast page]

4. **Video Settings**:

    - **Resolution**: 1920x1080 (1080p)
    - **Bitrate**: 3000-4000 kbps
    - **Frame Rate**: 30 FPS
    - **Keyframe Interval**: 2 seconds

5. **Audio Settings**:

    - **Bitrate**: 128 kbps
    - **Sample Rate**: 44100 Hz
    - **Channels**: Stereo

6. **Start Streaming**:
    - Click **"Start Streaming"** in ManyCam
    - Your Laravel viewers will see the ManyCam feed through Agora RTC

## SplitCam Configuration

### Step-by-Step Setup

1. **Open SplitCam**
2. **Set up your scenes**:

    - Add multiple camera sources
    - Configure picture-in-picture layouts
    - Set up overlays and effects

3. **Configure Broadcasting**:

    - Click **"Share & Record"** → **"Media Server"**
    - Select **"Custom RTMP Server"**
    - Enter your Laravel RTMP details:
        - **Server**: `rtmp://your-domain.com/live`
        - **Stream Key**: [From your Laravel app]

4. **Quality Settings**:

    - **Video Quality**: High (1080p)
    - **Audio Quality**: High
    - **Bitrate**: 3000-4000 kbps

5. **Start Broadcasting**:
    - Click **"Start Broadcasting"**
    - Monitor the connection status in SplitCam

## Architecture Overview

```
┌─────────────────┐    RTMP Stream    ┌─────────────────┐    WebRTC    ┌─────────────────┐
│   ManyCam/      │ ────────────────► │   Your Laravel  │ ───────────► │   Web Viewers   │
│   SplitCam      │                   │   Application   │              │   (Agora RTC)   │
└─────────────────┘                   └─────────────────┘              └─────────────────┘
        ▲
        │
┌─────────────────┐
│  Multiple       │
│  Cameras &      │
│  Sources        │
└─────────────────┘
```

## Benefits of This Setup

### ✅ Advantages

-   **Professional multi-camera switching** without browser limitations
-   **No more "CAN_NOT_PUBLISH_MULTIPLE_VIDEO_TRACKS" errors**
-   **Advanced features**: Picture-in-picture, overlays, effects, transitions
-   **Stable streaming** - professional software handles camera management
-   **Better performance** - reduces browser resource usage
-   **Scene presets** - quickly switch between different layouts
-   **Recording capability** - record locally while streaming

### 🔧 Technical Benefits

-   **Eliminates web browser camera conflicts**
-   **Professional encoding** with better quality/bandwidth optimization
-   **Hardware acceleration** support
-   **Multiple audio sources** mixing
-   **Green screen/chroma key** support
-   **Real-time effects** without performance impact on web browser

## Alternative RTMP Servers

If you don't have an RTMP server yet, here are options:

### 1. NGINX with RTMP Module (Self-hosted)

```bash
# Install NGINX with RTMP
sudo apt update
sudo apt install nginx-full
# Configure RTMP in nginx.conf
```

### 2. Cloud RTMP Services

-   **YouTube Live** (free, but public)
-   **Twitch** (free, gaming focused)
-   **Facebook Live** (free, social media)
-   **AWS MediaLive** (paid, professional)
-   **Wowza Cloud** (paid, professional)

### 3. Quick Test Setup (For Development)

You can use a local RTMP server for testing:

```bash
# Using Docker
docker run -p 1935:1935 tiangolo/nginx-rtmp
# Your RTMP URL would be: rtmp://localhost:1935/live
```

## Integration with Existing Features

### Current Agora RTC System

Your existing Agora RTC viewers will continue to work normally. The RTMP stream gets bridged to Agora RTC, so:

-   ✅ Web viewers still use Agora RTC (no changes needed)
-   ✅ Chat system continues to work
-   ✅ Viewer counting remains functional
-   ✅ Admin controls still available
-   ✅ Stream recording/analytics preserved

### Camera Management

Your existing camera management system becomes a **configuration hub**:

-   Define camera sources for ManyCam/SplitCam
-   Set preferred resolutions and settings
-   Track which software is being used
-   Monitor stream health and connection status

## Next Steps

1. **Run the migration**: `php artisan migrate`
2. **Configure environment variables** in `.env`
3. **Set up RTMP server** (or use cloud service)
4. **Download ManyCam or SplitCam**
5. **Get RTMP details** from your Laravel broadcast page
6. **Configure streaming software** with your RTMP details
7. **Test the setup** with a live stream

## Support

If you need help with any part of this setup:

-   Check the RTMP connection details in your broadcast page
-   Monitor the stream status in Laravel admin
-   Use the built-in debugging tools
-   Test with a simple RTMP client first

This solution gives you **professional streaming capabilities** while maintaining all your existing Laravel features!
