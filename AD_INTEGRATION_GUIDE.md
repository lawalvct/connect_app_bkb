# Ad Integration Guide - AVMatrix + ManyCam Setup

## 🎯 Your Goal: Show Ads During Broadcasts

You have **3 options** for running ads with your setup:

---

## ✅ Option 1: AVMatrix → ManyCam (BEST for Ads)

### Hardware Setup:

```
┌─────────────┐
│  Camera 1   │──HDMI──┐
└─────────────┘        │
                       │    ┌─────────────┐   HDMI   ┌─────────────┐   RTMP   ┌──────────┐
┌─────────────┐        ├───→│  AVMatrix   │─────────→│  Capture    │─────────→│ ManyCam  │
│  Camera 2   │──HDMI──┤    │   Switcher  │          │    Card     │          │   Pro    │
└─────────────┘        │    └─────────────┘          └─────────────┘          └──────────┘
                       │                                    ↓                        ↓
┌─────────────┐        │                                   USB                   RTMP Out
│  Camera 3   │──HDMI──┘                              (to Computer)          (to Your Server)
└─────────────┘
```

### What You Need:

-   ✅ AVMatrix (you have this)
-   ✅ ManyCam Pro subscription (you have this)
-   ⚠️ **HDMI Capture Card** (you need this)

### Recommended Capture Cards:

| Card                     | Price  | Quality    | Latency      |
| ------------------------ | ------ | ---------- | ------------ |
| **Elgato HD60 S+**       | $180   | ⭐⭐⭐⭐⭐ | Ultra low    |
| **Blackmagic Intensity** | $200   | ⭐⭐⭐⭐⭐ | Professional |
| **AVerMedia Live Gamer** | $150   | ⭐⭐⭐⭐   | Good         |
| **Generic USB 3.0**      | $20-50 | ⭐⭐⭐     | Medium       |

### Setup Steps:

1. **Connect AVMatrix to Computer**

    ```
    AVMatrix HDMI Out → Capture Card → USB 3.0 → Computer
    ```

2. **Configure ManyCam**

    - Add **Video Source** → Select your capture card
    - You'll see AVMatrix output in ManyCam
    - Now AVMatrix switches cameras, ManyCam adds ads

3. **Add Ads in ManyCam**

    - Go to **Playlist** tab in ManyCam
    - Add your ad videos
    - Create ad scenes with overlays
    - Set up hotkeys for quick ad insertion

4. **ManyCam Streaming Setup**
    - Settings → Streaming → Custom RTMP
    - URL: `rtmp://rtmp.connectinc.app/live`
    - Key: Get from Laravel
    - Start streaming

### Workflow:

1. **Use AVMatrix** to switch between cameras (hardware buttons)
2. **Use ManyCam** to:
    - Overlay lower thirds
    - Insert video ads
    - Add graphics/logos
    - Picture-in-picture ads

### Benefits:

-   ✅ **Hardware switching** (AVMatrix) = Reliable
-   ✅ **Software ads** (ManyCam) = Flexible
-   ✅ **Professional control** = Best of both worlds

---

## ✅ Option 2: AVMatrix PIP for Simple Ads

**If you don't want a capture card**, use AVMatrix's built-in features:

### Setup:

```
Camera 1 ──┐
Camera 2 ──┤
Camera 3 ──┼──→ AVMatrix ──RTMP──→ Your Server
Laptop ────┤   (with PIP)
(Ads)  ────┘
```

### Steps:

1. **Connect Ad Source to AVMatrix**

    - Laptop/Media Player → HDMI input on AVMatrix
    - Load ad videos on laptop

2. **Use AVMatrix PIP Feature**

    - Set up Picture-in-Picture overlay
    - Position ad overlay on screen
    - Switch to ad input when needed

3. **Manual Ad Control**
    - Press AVMatrix button to show ad layer
    - Ad plays over main content
    - Press again to hide

### Limitations:

-   ❌ Manual switching only
-   ❌ No automated ad scheduling
-   ❌ Basic positioning

---

## ✅ Option 3: Server-Side Ad Injection (AUTOMATED)

**I've built this system for you!** It injects ads automatically on the viewer side.

### How It Works:

```
AVMatrix → RTMP → Your Server → Laravel Ad System → Viewers see ads
```

### Features Created:

✅ **Automatic Ad Insertion**

-   Ads show every 10 minutes (configurable)
-   No manual intervention needed

✅ **Ad Management System**

-   Upload ad videos
-   Set duration, skip timing
-   Track views, clicks, skips

✅ **Broadcaster Control**

-   Manual ad trigger button
-   Ad preview before showing
-   Real-time ad stats

✅ **Viewer Experience**

-   Video ads play smoothly
-   Skip button (optional)
-   Click-through tracking

### Files Created:

-   ✅ `AdInjectionService.php` - Ad logic
-   ✅ `Advertisement` model - Ad database
-   ✅ `AdController.php` - API endpoints
-   ✅ Migration for ads table

---

## 🎯 Recommended Setup

**For your use case (ManyCam Pro subscriber wanting ads):**

### Setup A (Professional):

```
AVMatrix → Capture Card → ManyCam → RTMP Server
```

-   **Cost:** $50-200 for capture card
-   **Quality:** ⭐⭐⭐⭐⭐
-   **Flexibility:** Maximum
-   **Automation:** Manual with hotkeys

### Setup B (Hybrid):

```
AVMatrix → RTMP Server + Server-Side Ads
```

-   **Cost:** $0 (use what you built)
-   **Quality:** ⭐⭐⭐⭐
-   **Flexibility:** Good
-   **Automation:** Fully automated

---

## 🚀 Implementation Steps

### If choosing Setup A (AVMatrix + ManyCam):

1. **Order Capture Card**

    - Get Elgato HD60 S+ or generic USB 3.0

2. **Physical Connection**

    - AVMatrix HDMI Out → Capture Card → Computer

3. **ManyCam Configuration**

    - Add capture card as source
    - Load ad videos in playlist
    - Set up ad scenes

4. **Streaming**
    - Stream to: `rtmp://rtmp.connectinc.app/live`

### If choosing Setup B (Server-Side Ads):

1. **Run Migration**

    ```bash
    php artisan migrate
    ```

2. **Upload Ad Videos**

    - Store videos in `storage/app/public/ads/`
    - Or use external URLs (YouTube, Vimeo)

3. **Create Ads in Database**

    ```php
    Advertisement::create([
        'title' => 'Product Ad',
        'video_url' => 'https://your-cdn.com/ad1.mp4',
        'duration_seconds' => 30,
        'skip_after_seconds' => 5,
        'is_active' => true,
        'start_date' => now(),
        'end_date' => now()->addMonth()
    ]);
    ```

4. **Frontend Integration** (already done in your broadcast page)
    - Ads show automatically every 10 minutes
    - Manual trigger button available

---

## 💰 Cost Comparison

| Method                 | Equipment Cost         | Subscription           | Total   |
| ---------------------- | ---------------------- | ---------------------- | ------- |
| **AVMatrix + ManyCam** | $50-200 (capture card) | ManyCam Pro (you have) | $50-200 |
| **Server-Side Ads**    | $0                     | $0                     | FREE    |
| **AVMatrix PIP**       | $0                     | $0                     | FREE    |

---

## 📊 Feature Comparison

| Feature              | AVMatrix + ManyCam | Server-Side     | AVMatrix PIP |
| -------------------- | ------------------ | --------------- | ------------ |
| **Ad Quality**       | ⭐⭐⭐⭐⭐         | ⭐⭐⭐⭐        | ⭐⭐⭐       |
| **Automation**       | Manual hotkeys     | Fully automated | Manual       |
| **Flexibility**      | Very flexible      | Very flexible   | Limited      |
| **Setup Complexity** | Medium             | Easy            | Easy         |
| **Ongoing Cost**     | None               | None            | None         |
| **Ad Analytics**     | No                 | Yes (built-in)  | No           |

---

## 🎬 My Recommendation

**Since you already have ManyCam Pro:**

1. **Short term (now):** Use server-side ads (FREE, already built)
2. **Long term (invest):** Get capture card + use ManyCam for maximum control

**Why both?**

-   Server-side handles automated ad breaks
-   ManyCam gives you creative control for sponsored segments
-   Best of both worlds!

---

## 🛠️ Need Help?

Let me know:

1. Which AVMatrix model you have (I can give specific instructions)
2. If you want to buy a capture card (I can recommend based on budget)
3. If you prefer server-side ads (I'll help with frontend integration)

The ad injection system is **ready to use** - just run the migration!
