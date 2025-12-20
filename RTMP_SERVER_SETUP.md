# RTMP Server Setup Guide

## ⚠️ Current Issue

Your Laravel application at `admin.connectinc.app` does **NOT** have an RTMP server running on port 1935.

**Test Result:**

```
TCP connect to (104.21.54.230 : 1935) failed
TcpTestSucceeded: False
```

## What You Need

RTMP streaming requires a dedicated **RTMP server** that can:

1. Receive incoming RTMP streams from ManyCam/OBS
2. Bridge the stream to Agora RTC or serve directly to viewers

Laravel alone cannot receive RTMP streams - it's an HTTP application.

---

## 🟢 RECOMMENDED: aaPanel + NGINX-RTMP Setup

Since you're using **aaPanel** on your VPS, follow these steps:

### Step 1: SSH into Your Server

```bash
ssh root@your-server-ip
```

### Step 2: Install NGINX RTMP Module

```bash
# Stop NGINX first
bt stop

# Install the RTMP module
apt update
apt install -y libnginx-mod-rtmp

# If that doesn't work, install build dependencies and compile
apt install -y build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev git

# Download NGINX RTMP module
cd /tmp
git clone https://github.com/arut/nginx-rtmp-module.git
```

### Step 3: Add RTMP Configuration

**Option A: Edit via aaPanel**

1. Login to aaPanel
2. Go to **Website** → **NGINX** → **Config**
3. Add the RTMP block (see below)

**Option B: Edit via SSH**

Edit the main NGINX config:

```bash
nano /www/server/nginx/conf/nginx.conf
```

Add this **BEFORE** the `http {` block (at the same level, not inside it):

```nginx
# RTMP Streaming Server
rtmp {
    server {
        listen 1935;
        chunk_size 4096;

        # Allow publishing from anywhere (restrict in production)
        allow publish all;

        application live {
            live on;
            record off;

            # Laravel validates stream keys
            on_publish http://127.0.0.1/api/rtmp/auth;
            on_publish_done http://127.0.0.1/api/rtmp/end;

            # Optional: Enable HLS output
            # hls on;
            # hls_path /tmp/hls;
            # hls_fragment 3;
            # hls_playlist_length 10;
        }
    }
}
```

### Step 4: Open Port 1935 in Firewall

**Via aaPanel:**

1. Go to **Security** → **Firewall**
2. Add rule: Port `1935`, Protocol `TCP`, Allow

**Via SSH:**

```bash
# UFW (Ubuntu)
ufw allow 1935/tcp

# Or firewalld (CentOS)
firewall-cmd --permanent --add-port=1935/tcp
firewall-cmd --reload

# Or iptables
iptables -A INPUT -p tcp --dport 1935 -j ACCEPT
```

### Step 5: Open Port 1935 in Cloud Firewall

If using cloud providers, also open in their firewall:

-   **AWS**: Security Groups → Inbound Rules → Add 1935 TCP
-   **DigitalOcean**: Networking → Firewalls → Add 1935 TCP
-   **Vultr/Linode**: Similar firewall settings

### Step 6: Restart NGINX

```bash
# Via aaPanel
bt restart

# Or directly
/www/server/nginx/sbin/nginx -t  # Test config
/www/server/nginx/sbin/nginx -s reload  # Reload

# Or systemctl
systemctl restart nginx
```

### Step 7: Test RTMP Port

From your local machine:

```powershell
Test-NetConnection -ComputerName admin.connectinc.app -Port 1935
```

Expected result:

```
TcpTestSucceeded: True
```

---

## ⚠️ Cloudflare Issue

Your domain `admin.connectinc.app` is behind **Cloudflare** (104.21.54.230 is a Cloudflare IP).

**Cloudflare does NOT proxy RTMP (port 1935)** on free/pro plans.

### Solutions:

**Option A: Use Server IP Directly**

```
RTMP URL: rtmp://YOUR_SERVER_IP/live
```

Update your `.env`:

```env
RTMP_SERVER_URL=rtmp://YOUR_ACTUAL_SERVER_IP/live
```

**Option B: Create DNS-Only Subdomain**

1. In Cloudflare DNS, add: `rtmp.connectinc.app` → Your Server IP
2. Set the cloud icon to **DNS Only** (gray, not orange)
3. Use: `rtmp://rtmp.connectinc.app/live`

**Option C: Cloudflare Spectrum (Enterprise)**
Cloudflare Spectrum supports RTMP but requires Enterprise plan.

---

## 🔧 Quick aaPanel Commands

```bash
# Check NGINX status
bt status

# Restart NGINX
bt restart

# View NGINX error log
tail -f /www/wwwlogs/nginx_error.log

# Test NGINX config
/www/server/nginx/sbin/nginx -t

# Check if RTMP port is listening
netstat -tlnp | grep 1935
ss -tlnp | grep 1935
```

---

## Option 2: Alternative NGINX Build with RTMP

            live on;
            record off;

            # Authentication callback to Laravel
            on_publish http://localhost/api/rtmp/auth;
            on_publish_done http://localhost/api/rtmp/end;

            # Optional: Push to Agora or other services
            # push rtmp://agora-rtmp-url/live;
        }
    }

}

````

### Open Port 1935

```bash
# UFW Firewall
sudo ufw allow 1935/tcp

# Or iptables
sudo iptables -A INPUT -p tcp --dport 1935 -j ACCEPT
````

### Restart NGINX

```bash
sudo systemctl restart nginx
```

---

## Option 2: Use Cloudflare Stream (Paid)

Since your domain uses Cloudflare (104.21.54.230), you could use Cloudflare Stream:

-   Native RTMP ingest
-   Global CDN delivery
-   No server management needed

---

## Option 3: Use Free RTMP Relays for Testing

### YouTube Live (Free)

1. Go to YouTube Studio → Go Live
2. Get your RTMP URL and Stream Key
3. Update your Laravel config to use YouTube's RTMP

### Twitch (Free)

1. Get ingest URL from https://stream.twitch.tv/ingests/
2. Get stream key from Dashboard

---

## Option 4: Docker RTMP Server (For Development)

For local testing:

```bash
docker run -d -p 1935:1935 -p 8080:8080 --name rtmp-server tiangolo/nginx-rtmp
```

Then use: `rtmp://localhost:1935/live`

---

## Alternative: Use Agora's RTMP Bridge

Agora has built-in RTMP pushing. Your current Agora setup can push TO an RTMP server, but receiving FROM RTMP requires their Media Push feature.

Check: https://docs.agora.io/en/media-push/overview

---

## Recommended Streaming Software

### OBS Studio (FREE - Recommended)

-   Download: https://obsproject.com/
-   Most reliable RTMP client
-   Works with any RTMP server

### Streamlabs Desktop (FREE)

-   Download: https://streamlabs.com/
-   OBS-based with extra features

### ManyCam (Paid features)

-   Good for virtual cameras
-   Can be finicky with RTMP

### SplitCam (FREE)

-   Windows only
-   Good multi-camera support

---

## Testing Your Setup

Once RTMP server is running, test with:

```bash
# Test port is open
nc -zv admin.connectinc.app 1935

# Or PowerShell
Test-NetConnection -ComputerName admin.connectinc.app -Port 1935
```

Expected result when working:

```
TcpTestSucceeded: True
```

---

## Laravel RTMP Auth Endpoints

Add these routes to handle RTMP server callbacks:

```php
// routes/api.php
Route::post('/rtmp/auth', [RtmpController::class, 'authenticateStream']);
Route::post('/rtmp/end', [RtmpController::class, 'streamEnded']);
```

These let NGINX-RTMP verify stream keys with your Laravel database.
