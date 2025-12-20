# Hardware Encoder Setup Guide (AVMatrix & Osee)

## ✅ Compatibility Confirmed

Your **AVMatrix** and **Osee** stream decks are **fully compatible** with this RTMP streaming setup!

Hardware encoders are actually **superior** to software solutions for professional streaming.

---

## 🎛️ Supported Hardware Encoders

-   ✅ **AVMatrix** (VS0601, PVS0613, SE series)
-   ✅ **Osee** (Stream Deck, GoStream)
-   ✅ **Blackmagic ATEM** (Mini, Television Studio)
-   ✅ **Roland V-Series** (V-1HD, V-02HD)
-   ✅ **Datavideo** (SE series)
-   ✅ **Livestream Studio**
-   ✅ Any RTMP-capable hardware encoder

---

## 🔧 AVMatrix Configuration

### Common AVMatrix Models:

-   **VS0601** - 6-channel HDMI switcher
-   **PVS0613** - 6-channel with PTZ control
-   **SE series** - Professional streaming encoders

### Setup Steps:

1. **Connect to AVMatrix Web Interface**

    - Connect AVMatrix to your network
    - Open browser: `http://192.168.X.X` (check device IP)
    - Login with default credentials

2. **Configure RTMP Settings**

    - Go to **Streaming** or **Encoder** menu
    - **Protocol:** RTMP
    - **RTMP URL:** `rtmp://rtmp.connectinc.app/live`
    - **Stream Key:** Get from your Laravel app

3. **Video Encoder Settings**

    - **Resolution:** 1920x1080 (1080p)
    - **Bitrate:** 4000-5000 kbps (higher than software)
    - **Frame Rate:** 30fps (or 60fps for sports/action)
    - **Codec:** H.264
    - **Profile:** High or Main
    - **Keyframe Interval:** 2 seconds (60 frames @ 30fps)

4. **Audio Settings**

    - **Sample Rate:** 48000 Hz (professional audio standard)
    - **Bitrate:** 128 kbps
    - **Codec:** AAC
    - **Channels:** Stereo (2)

5. **Start Streaming**
    - Click **Start Encoding** or **Go Live**
    - Check connection status on device display

---

## 🎥 Osee Configuration

### Common Osee Models:

-   **Osee GoStream**
-   **Osee Stream Deck**
-   **Osee Video Switcher series**

### Setup Steps:

1. **Access Device Settings**

    - Most Osee devices have physical control panel
    - Or connect via network and access web UI
    - Some models use mobile app

2. **Navigate to Streaming Settings**

    - Find **RTMP** or **Live Streaming** menu
    - Select **Custom RTMP Server**

3. **Enter Connection Details**

    - **Server Address:** `rtmp://rtmp.connectinc.app/live`
    - **Stream Key:** `46_9182bff0195e23e5e00b8ee118774233`
    - Or get fresh key from Laravel broadcast page

4. **Configure Video Quality**

    - **Resolution:** 1920x1080
    - **Bitrate:** 4000 kbps
    - **FPS:** 30
    - **GOP (Keyframe):** 60 (2 seconds at 30fps)

5. **Configure Audio**

    - **Input:** Select your audio source (HDMI, Line-in, etc.)
    - **Sample Rate:** 48kHz
    - **Bitrate:** 128 kbps

6. **Test and Go Live**
    - Save settings
    - Start streaming
    - Monitor connection indicator

---

## 💡 Hardware vs Software Comparison

| Feature             | Hardware Encoder            | Software (ManyCam/OBS) |
| ------------------- | --------------------------- | ---------------------- |
| **Reliability**     | ⭐⭐⭐⭐⭐ Extremely stable | ⭐⭐⭐ Can crash/hang  |
| **Quality**         | ⭐⭐⭐⭐⭐ Dedicated chip   | ⭐⭐⭐⭐ CPU dependent |
| **Latency**         | ⭐⭐⭐⭐⭐ Ultra low        | ⭐⭐⭐ Higher          |
| **Resource Use**    | ⭐⭐⭐⭐⭐ No PC needed     | ⭐⭐ Heavy CPU usage   |
| **Switching Speed** | ⭐⭐⭐⭐⭐ Instant          | ⭐⭐⭐ Slight delay    |
| **Setup**           | ⭐⭐⭐ Initial learning     | ⭐⭐⭐⭐ Easy to start |
| **Cost**            | $$$ - $$$$                  | $ - Free               |

---

## 🎯 Recommended Settings for Hardware Encoders

### For Professional Quality:

```
Video Encoder:
├── Resolution: 1920x1080 (Full HD)
├── Bitrate: 4500 kbps (higher than software)
├── Frame Rate: 30fps (or 60fps for sports)
├── Codec: H.264
├── Profile: High
├── Keyframe Interval: 2 seconds
└── Rate Control: CBR (Constant Bitrate)

Audio Encoder:
├── Sample Rate: 48000 Hz
├── Bitrate: 128 kbps
├── Codec: AAC-LC
├── Channels: Stereo (2)
└── Audio Delay: 0ms (sync with video)

Network:
├── RTMP Protocol
├── Server: rtmp://rtmp.connectinc.app/live
├── Stream Key: [from Laravel]
└── Reconnect: Auto (if supported)
```

---

## 🔌 Physical Setup

### Typical Hardware Encoder Workflow:

```
┌─────────────┐
│  Camera 1   │──HDMI──┐
└─────────────┘        │
                       │    ┌─────────────────┐    RTMP Stream    ┌──────────────┐
┌─────────────┐        ├───→│   AVMatrix/     │──────────────────→│  Your RTMP   │
│  Camera 2   │──HDMI──┤    │   Osee Device   │                   │    Server    │
└─────────────┘        │    └─────────────────┘                   └──────────────┘
                       │            ↓                                      ↓
┌─────────────┐        │     Ethernet/WiFi                         ┌──────────────┐
│  Camera 3   │──HDMI──┘                                           │   Agora RTC  │
└─────────────┘                                                    │   Viewers    │
                                                                   └──────────────┘
```

---

## 🧪 Testing Your Hardware Encoder

### Step 1: Verify Network Connection

-   Ensure encoder is on same network or has internet access
-   Test with: `ping rtmp.connectinc.app` from another device

### Step 2: Configure Encoder

-   Enter RTMP URL and stream key
-   Set recommended video/audio settings

### Step 3: Start Streaming

-   Begin encoding from device
-   Watch for connection indicator (usually LED or on-screen status)

### Step 4: Verify in Laravel

-   Check Laravel logs for authentication callback
-   View stream status in admin dashboard
-   Monitor viewer connection

### Step 5: Test Stream Quality

-   Open broadcast page in browser
-   Verify video is smooth and audio is clear
-   Check for any buffering or lag

---

## 🐛 Troubleshooting Hardware Encoders

### Connection Refused

```
✗ Check firewall (port 1935 must be open)
✗ Verify RTMP server is running: netstat -tlnp | grep 1935
✗ Use server IP instead of domain for testing
✗ Check device has internet access
```

### Authentication Failed

```
✗ Verify stream key is correct (no spaces)
✗ Check Laravel API routes are working
✗ Review Laravel logs: tail -f storage/logs/laravel.log
✗ Test auth endpoint manually: curl http://localhost/api/rtmp/auth
```

### Stream Starts but Disconnects

```
✗ Check network stability
✗ Verify bitrate isn't too high for upload speed
✗ Enable auto-reconnect in encoder settings
✗ Monitor NGINX error logs
```

### Poor Video Quality

```
✗ Increase bitrate to 4500-5000 kbps
✗ Check input source quality (HDMI signal)
✗ Verify keyframe interval is 2 seconds
✗ Use CBR (constant bitrate) not VBR
```

### Audio/Video Out of Sync

```
✗ Set audio delay/offset in encoder settings
✗ Ensure sample rate is 48kHz (not 44.1kHz)
✗ Check HDMI audio is embedded correctly
✗ Test with different audio input source
```

---

## 📊 Monitoring Your Hardware Stream

### Check Stream Health:

**Via Laravel Admin:**

```
GET /admin/api/streams/46/rtmp-status
```

**Via NGINX RTMP (if stat module enabled):**

```
curl http://localhost:8080/stat
```

**Via Device:**

-   Most hardware encoders show bitrate, fps, and connection time
-   Monitor LED indicators for status

---

## ✨ Pro Tips for Hardware Streaming

1. **Use Wired Ethernet** - More stable than WiFi
2. **Update Firmware** - Latest version = best performance
3. **Set Static IP** - Prevents connection drops
4. **Higher Bitrate** - Hardware can handle 5000+ kbps
5. **Test Offline** - Record locally first to verify settings
6. **Backup Power** - UPS prevents stream interruption
7. **Monitor Temperature** - Keep device well-ventilated
8. **Label Inputs** - Know which HDMI is which camera

---

## 🎬 You're Ready!

Your AVMatrix and Osee hardware encoders will provide **professional-grade streaming** with:

-   ✅ Rock-solid stability
-   ✅ Broadcast-quality encoding
-   ✅ Zero PC resource usage
-   ✅ Instant camera switching
-   ✅ Professional audio mixing

Just follow the setup steps and you'll be streaming in minutes!

Need help with specific models? Let me know your exact device names and I can provide model-specific instructions.
