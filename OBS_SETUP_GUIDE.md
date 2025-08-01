# OBS Studio Setup Guide for Multi-Camera Streaming

## 🎯 Overview

This guide will help you set up OBS Studio for professional multi-camera streaming with your Laravel application.

## 📋 Requirements

### System Requirements

-   **Windows 10/11** (64-bit) - ✅ You have Windows
-   **4GB RAM minimum** (8GB+ recommended)
-   **DirectX 11** compatible graphics card
-   **Multiple camera devices** or video sources

### Software Requirements

-   **OBS Studio** (Latest version - Free)
-   **Your existing Laravel streaming app** - ✅ Already set up
-   **Web browser** (Chrome/Edge recommended for WebRTC)

## 🚀 Installation Steps

### Step 1: Download OBS Studio

1. Go to: https://obsproject.com/download
2. Download **OBS Studio** for Windows
3. Run installer as Administrator
4. Follow installation wizard

### Step 2: Initial OBS Configuration

1. **Launch OBS Studio**
2. **Auto-Configuration Wizard** will appear:
    - Choose: "Optimize for streaming"
    - Service: "Custom"
    - Server: "Not needed for virtual camera"
    - Skip stream key
3. **Apply Settings**

### Step 3: Set Up Multiple Camera Sources

#### Add Camera Sources:

1. **Sources Panel** → Click **+** → **Video Capture Device**
2. **Create New** → Name it "Camera 1" → **OK**
3. **Device**: Select your first camera
4. **Resolution**: Set to **1280x720** or **1920x1080**
5. **FPS**: Set to **30**
6. **Click OK**

#### Repeat for Additional Cameras:

-   Add "Camera 2", "Camera 3", etc.
-   Each camera gets its own source

### Step 4: Create Scenes for Camera Switching

#### Scene 1 - Camera 1:

1. **Scenes Panel** → Click **+** → Name: "Camera 1 View"
2. **Add Source**: Video Capture Device (Camera 1)
3. **Resize/Position** to fill frame

#### Scene 2 - Camera 2:

1. **Scenes Panel** → Click **+** → Name: "Camera 2 View"
2. **Add Source**: Video Capture Device (Camera 2)
3. **Resize/Position** to fill frame

#### Scene 3 - Multi-Camera:

1. **Scenes Panel** → Click **+** → Name: "Multi-Camera View"
2. **Add multiple camera sources**
3. **Arrange in split-screen layout**

### Step 5: Enable Virtual Camera

1. **Controls Panel** → Click **Start Virtual Camera**
2. **Output Type**: "Camera"
3. **Target Camera**: "OBS Virtual Camera"
4. **Click Start**

✅ **OBS Virtual Camera is now available to your web application!**

## 🌐 Web Application Integration

### Step 1: Update Your Broadcast Page

Your Laravel app will now see "OBS Virtual Camera" as an available device.

1. **Open your broadcast page**: `/admin/streams/{id}/broadcast`
2. **Click "Cameras" dropdown**
3. **Select "OBS Virtual Camera"**
4. **Start streaming**

### Step 2: Camera Switching Workflow

**Instead of switching cameras in the web app:**

1. **Keep "OBS Virtual Camera" selected** in web app
2. **Switch scenes in OBS** to change camera views
3. **Instant switching** - no web app conflicts!

## 🎨 Advanced OBS Features

### Professional Scene Setup

```
Scene Structure:
├── Camera 1 - Close Up
├── Camera 2 - Wide Shot
├── Camera 3 - Side Angle
├── Screen Share Scene
├── Multi-Camera Split
└── Presentation Mode
```

### Add Professional Elements:

1. **Text Sources** - Stream title, branding
2. **Image Sources** - Logos, overlays
3. **Browser Sources** - Chat widgets, alerts
4. **Audio Sources** - Multiple microphones
5. **Filters** - Color correction, noise suppression

### Transitions:

1. **Scene Transitions Panel**
2. **Add transitions**: Fade, Cut, Slide
3. **Set transition duration**: 300-500ms

## ⚙️ Optimal Settings for Your Stream

### Video Settings:

```
Base Resolution: 1920x1080
Output Resolution: 1280x720 (for better streaming)
FPS: 30
```

### Advanced Settings:

```
Process Priority: High
Renderer: Direct3D 11
Color Format: NV12
```

## 🔧 Integration with Your Laravel App

### Current Camera Management:

Your app has these camera management features:

-   ✅ `/admin/streams/{id}/cameras` - Camera management page
-   ✅ API endpoints for camera CRUD operations
-   ✅ Camera switching functionality

### OBS Integration Benefits:

1. **Simpler Web App**: Just use "OBS Virtual Camera"
2. **No camera switching conflicts** in browser
3. **Professional switching** in OBS
4. **Better performance** and stability

### Recommended Workflow:

1. **Configure cameras in OBS** (not web app)
2. **Create scenes for different views**
3. **Use OBS Virtual Camera** in web app
4. **Switch scenes in OBS** during streaming

## 🎯 Quick Start Checklist

-   [ ] Install OBS Studio
-   [ ] Set up camera sources
-   [ ] Create scenes for different views
-   [ ] Enable OBS Virtual Camera
-   [ ] Test in your broadcast page
-   [ ] Practice scene switching
-   [ ] Go live with professional multi-camera setup!

## 🚨 Troubleshooting

### OBS Virtual Camera Not Showing:

1. **Restart browser** after enabling virtual camera
2. **Check camera permissions** in browser
3. **Restart OBS** if virtual camera fails

### Performance Issues:

1. **Lower resolution** in OBS (720p vs 1080p)
2. **Reduce FPS** to 30
3. **Close unnecessary programs**
4. **Use hardware encoding** if available

### Camera Access Issues:

1. **Close other apps** using cameras
2. **Run OBS as Administrator**
3. **Update camera drivers**

## 📈 Next Steps

1. **Master basic OBS setup** with 2-3 cameras
2. **Add professional overlays** and branding
3. **Set up hotkeys** for quick scene switching
4. **Practice streaming workflow**
5. **Consider audio mixing** for professional sound

## 🔗 Useful Resources

-   **OBS Studio Documentation**: https://obsproject.com/help
-   **OBS Forums**: https://obsproject.com/forum/
-   **YouTube Tutorials**: Search "OBS Multi-Camera Setup"

---

**🎉 You're now ready for professional multi-camera streaming!**

The combination of OBS Studio + your Laravel streaming app will give you broadcast-quality results with reliable camera switching.
