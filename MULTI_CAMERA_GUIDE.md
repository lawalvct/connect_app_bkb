# Multi-Camera Streaming Setup Guide

## Overview

This guide explains how to set up and use the multi-camera streaming functionality with Agora RTC for live broadcasts.

## Key Features

-   **Device Detection**: Automatically detect all available camera devices (webcams, phones, tablets, etc.)
-   **Real-time Preview**: Preview all cameras simultaneously
-   **Live Switching**: Switch between cameras during live broadcast without interruption
-   **Camera Management**: Add, remove, and configure camera sources
-   **Mobile Integration**: Support for mobile devices as camera sources

## How to Use Multi-Camera Streaming

### 1. Access Camera Management

-   Go to **Admin Panel > Streams**
-   Select your stream
-   Click **"Camera Management"** button

### 2. Detect Camera Devices

-   Click **"Detect Camera Devices"** in the Camera Management page
-   Grant camera permissions when prompted
-   The system will scan for all available video devices

### 3. Add Camera Sources

**Option A: Auto-detection**

-   Connected cameras (webcams, external cameras) are detected automatically
-   Click "Add" next to each detected device

**Option B: Mobile Devices**

-   Open the stream URL on mobile device
-   Join as a camera source (implementation needed for mobile app)
-   Device appears in available cameras list

### 4. Configure Cameras

-   **Primary Camera**: The main camera being streamed live
-   **Secondary Cameras**: Available for switching during broadcast
-   **Resolution Settings**: Set quality per camera (480p, 720p, 1080p, 4K)
-   **Device Labels**: Rename cameras for easy identification

### 5. Live Broadcasting with Multi-Camera

-   Go to **Broadcast Studio**
-   All detected cameras appear in the camera dropdown
-   Switch between cameras during broadcast:
    -   Click camera dropdown in broadcast controls
    -   Select different camera source
    -   Stream switches seamlessly without disconnection

### 6. Camera Switching During Live Stream

-   **Instant Switching**: Click any camera preview to make it primary
-   **Smooth Transitions**: Agora handles the track switching automatically
-   **No Interruption**: Viewers see seamless camera changes
-   **History Tracking**: All camera switches are logged

## Technical Implementation

### Device Detection

```javascript
// Detect available camera devices
const devices = await navigator.mediaDevices.enumerateDevices();
const videoDevices = devices.filter((device) => device.kind === "videoinput");

// Create video tracks for each device
const videoTrack = await AgoraRTC.createCameraVideoTrack({
    cameraId: device.deviceId,
    encoderConfig: {
        width: 1280,
        height: 720,
        frameRate: 30,
        bitrateMin: 1000,
        bitrateMax: 3000,
    },
});
```

### Camera Switching

```javascript
// Switch camera during live stream
async function switchCamera(newDeviceId) {
    // Create new track
    const newTrack = await AgoraRTC.createCameraVideoTrack({
        cameraId: newDeviceId,
    });

    // Unpublish old track and publish new one
    await agoraClient.unpublish([oldTrack]);
    await agoraClient.publish([newTrack]);

    // Update preview
    newTrack.play("videoContainer");
}
```

## Mobile Device Integration

### Using Phones as Camera Sources

1. **Join Stream as Camera**: Mobile devices can join the stream channel as camera sources
2. **Remote Camera Control**: Admin can switch to mobile camera feeds
3. **Multiple Angles**: Use phones for different angles (wide shot, close-up, side view)

### Setup Mobile Camera Source

```javascript
// Mobile device joins as camera source
const mobileClient = AgoraRTC.createClient({ mode: "live", codec: "vp8" });
mobileClient.setClientRole("host");

// Create mobile camera track
const mobileTrack = await AgoraRTC.createCameraVideoTrack({
    facingMode: "environment", // Use back camera
});

// Join channel
await mobileClient.join(APP_ID, CHANNEL_NAME, token, uid);
await mobileClient.publish([mobileTrack]);
```

## Camera Management Features

### Camera Status Monitoring

-   **Connected**: Camera is active and ready
-   **Disconnected**: Camera is offline or unavailable
-   **Primary**: Currently streaming camera
-   **Secondary**: Available backup cameras

### Resolution Management

-   **Auto**: System selects optimal resolution
-   **480p**: 640x480 (low bandwidth)
-   **720p**: 1280x720 (standard HD)
-   **1080p**: 1920x1080 (full HD)
-   **4K**: 3840x2160 (ultra HD)

### Network Optimization

-   **Adaptive Bitrate**: Adjusts quality based on network conditions
-   **Fallback Resolution**: Automatically reduces quality if bandwidth is limited
-   **Connection Monitoring**: Tracks camera connection status

## Testing Multi-Camera Setup

### Test Page

A test page is available at: `/test-multi-camera.html`

**Features:**

-   Device detection and listing
-   Live camera previews
-   Camera switching test
-   Agora streaming test

### Usage:

1. Open `http://your-domain.com/test-multi-camera.html`
2. Click "Detect Cameras"
3. Grant camera permissions
4. See all available cameras in grid view
5. Click any camera to make it primary
6. Test streaming with "Start Streaming"

## Troubleshooting

### Common Issues

**1. No Cameras Detected**

-   Check camera permissions in browser
-   Ensure cameras are connected and not used by other apps
-   Try refreshing the page
-   Check browser compatibility (Chrome/Firefox recommended)

**2. Camera Permission Denied**

-   Go to browser settings
-   Allow camera access for the site
-   Refresh and try again

**3. Poor Video Quality**

-   Check network bandwidth
-   Reduce resolution settings
-   Close other bandwidth-intensive applications

**4. Camera Switching Fails**

-   Ensure camera is not being used by another application
-   Check if camera supports the requested resolution
-   Try disconnecting and reconnecting the camera

### Browser Compatibility

-   **Chrome**: Full support (recommended)
-   **Firefox**: Full support
-   **Safari**: Limited support (iOS/macOS)
-   **Edge**: Full support

### Mobile Browser Support

-   **Chrome Mobile**: Full support
-   **Safari Mobile**: Limited support
-   **Firefox Mobile**: Full support

## Advanced Configuration

### Custom Encoder Settings

```javascript
const encoderConfigs = {
    low: { width: 640, height: 480, frameRate: 15, bitrateMax: 500 },
    medium: { width: 1280, height: 720, frameRate: 30, bitrateMax: 1500 },
    high: { width: 1920, height: 1080, frameRate: 30, bitrateMax: 3000 },
    ultra: { width: 3840, height: 2160, frameRate: 30, bitrateMax: 8000 },
};
```

### Multiple Camera Types

-   **Built-in Webcam**: Laptop/desktop integrated camera
-   **USB Camera**: External USB webcam or professional camera
-   **Mobile Device**: Phone/tablet camera via network
-   **IP Camera**: Network-connected cameras (requires additional setup)

## Security Considerations

### Camera Access Permissions

-   Always request user permission before accessing cameras
-   Clearly explain why camera access is needed
-   Provide option to deny and use alternative methods

### Network Security

-   Use HTTPS for all camera streaming
-   Implement proper authentication for camera sources
-   Monitor for unauthorized camera access attempts

## Performance Optimization

### Multi-Camera Performance Tips

1. **Limit Active Cameras**: Only activate cameras currently in use
2. **Resolution Management**: Use appropriate resolution for each camera
3. **Bandwidth Allocation**: Distribute bandwidth efficiently among cameras
4. **CPU Usage**: Monitor CPU usage when multiple cameras are active

### Recommended Hardware

-   **CPU**: Multi-core processor (Intel i5/AMD Ryzen 5 or better)
-   **RAM**: 8GB minimum, 16GB recommended
-   **Network**: Stable internet with upload speed >5 Mbps per camera
-   **Cameras**: HD webcams or better for professional quality

## API Integration

### Backend Camera Management

The camera management system integrates with the existing Laravel backend:

**Routes:**

-   `GET /admin/api/streams/{id}/cameras` - List cameras
-   `POST /admin/api/streams/{id}/cameras` - Add camera
-   `DELETE /admin/api/streams/{id}/cameras/{cameraId}` - Remove camera
-   `POST /admin/api/streams/{id}/switch-camera` - Switch primary camera
-   `PUT /admin/api/streams/{id}/cameras/{cameraId}/status` - Update camera status

**Database Tables:**

-   `stream_cameras` - Camera configurations
-   `camera_switches` - Switch history
-   `stream_mixer_settings` - Mixer and layout settings

This multi-camera system provides professional-grade streaming capabilities with seamless camera switching and device management.
