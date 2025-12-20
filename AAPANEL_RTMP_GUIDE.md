# aaPanel RTMP Setup - Step by Step Guide

## 📋 Prerequisites

-   SSH access to your server
-   Root/sudo privileges
-   aaPanel installed

---

## 🚀 Quick Setup (Automated)

### 1. Upload the setup script to your server

From your local machine, upload `setup_rtmp_aapanel.sh` to your server:

```powershell
# Using SCP from Windows
scp setup_rtmp_aapanel.sh root@YOUR_SERVER_IP:/root/
```

Or copy the script content and create it on the server:

```bash
nano /root/setup_rtmp_aapanel.sh
# Paste the script content, then Ctrl+X, Y, Enter
```

### 2. Make it executable and run

```bash
chmod +x /root/setup_rtmp_aapanel.sh
bash /root/setup_rtmp_aapanel.sh
```

---

## 📝 Manual Setup (If automated script fails)

### Step 1: SSH into your server

```bash
ssh root@YOUR_SERVER_IP
```

### Step 2: Install NGINX RTMP module

```bash
apt update
apt install -y libnginx-mod-rtmp
```

### Step 3: Edit NGINX config

```bash
nano /www/server/nginx/conf/nginx.conf
```

Add this **BEFORE** the `http {` block (around line 20-30):

```nginx
# RTMP Streaming Server
rtmp {
    server {
        listen 1935;
        chunk_size 4096;

        application live {
            live on;
            record off;

            # Laravel stream authentication
            on_publish http://127.0.0.1/api/rtmp/auth;
            on_publish_done http://127.0.0.1/api/rtmp/end;
        }
    }
}
```

Save: `Ctrl+X`, then `Y`, then `Enter`

### Step 4: Test configuration

```bash
/www/server/nginx/sbin/nginx -t
```

Should show: `syntax is ok` and `test is successful`

### Step 5: Reload NGINX

```bash
/www/server/nginx/sbin/nginx -s reload
```

### Step 6: Open firewall ports

**Ubuntu (UFW):**

```bash
ufw allow 1935/tcp
ufw status
```

**CentOS (firewalld):**

```bash
firewall-cmd --permanent --add-port=1935/tcp
firewall-cmd --reload
```

### Step 7: Open port in aaPanel

1. Login to aaPanel web interface
2. Go to **Security** menu
3. Click **Add Rule**
4. Port: `1935`
5. Protocol: `TCP`
6. Click **Save**

### Step 8: Verify RTMP is running

```bash
netstat -tlnp | grep 1935
```

Should show something like:

```
tcp        0      0 0.0.0.0:1935            0.0.0.0:*               LISTEN      12345/nginx
```

---

## 🌐 Cloudflare DNS Setup

### Important: Cloudflare does NOT proxy RTMP!

You need to create a **DNS-only** record:

1. Login to **Cloudflare Dashboard**
2. Select your domain: `connectinc.app`
3. Go to **DNS** → **Records**
4. Click **Add Record**:
    - **Type:** A
    - **Name:** rtmp
    - **IPv4 address:** [YOUR_SERVER_IP]
    - **Proxy status:** DNS only (click the cloud to make it gray)
    - **TTL:** Auto
5. Click **Save**

Wait 1-5 minutes for DNS propagation.

---

## 🔍 Get Your Server's Real IP

From your server, run:

```bash
curl ifconfig.me
```

Or:

```bash
curl -4 icanhazip.com
```

---

## ⚙️ Update Laravel Configuration

### On your development machine:

Update `.env` on your production server:

```env
RTMP_SERVER_URL=rtmp://rtmp.connectinc.app/live
```

Or use direct IP (testing only):

```env
RTMP_SERVER_URL=rtmp://YOUR_SERVER_IP/live
```

Clear config cache:

```bash
php artisan config:clear
```

---

## 🧪 Test the Setup

### From your Windows PC:

```powershell
# Test DNS resolution
nslookup rtmp.connectinc.app

# Test RTMP port
Test-NetConnection -ComputerName rtmp.connectinc.app -Port 1935
```

**Expected result:**

```
TcpTestSucceeded : True
```

### Test with OBS Studio:

1. Download OBS: https://obsproject.com/
2. Open OBS → Settings → Stream
3. Service: **Custom**
4. Server: `rtmp://rtmp.connectinc.app/live`
5. Stream Key: `46_9182bff0195e23e5e00b8ee118774233`
6. Click **OK**
7. Click **Start Streaming**

If successful, check your Laravel logs for authentication.

---

## 🐛 Troubleshooting

### Port not listening after restart

```bash
# Check NGINX error log
tail -f /www/wwwlogs/nginx_error.log

# Check if RTMP module is loaded
/www/server/nginx/sbin/nginx -V | grep rtmp

# Restart NGINX completely
systemctl restart nginx
```

### Connection refused from outside

**Check cloud provider firewall:**

-   AWS: Security Groups
-   DigitalOcean: Cloud Firewalls
-   Vultr/Linode: Firewall rules

All must allow TCP port 1935.

### Laravel not receiving auth callback

```bash
# Test locally on server
curl http://127.0.0.1/api/rtmp/auth -X POST -d "name=test_key"

# Check Laravel logs
tail -f /path/to/your/laravel/storage/logs/laravel.log
```

---

## 📊 Useful Commands

```bash
# Check NGINX status
systemctl status nginx

# View active RTMP connections
curl http://localhost:8080/stat  # If RTMP stat module enabled

# Check open ports
netstat -tlnp | grep nginx
ss -tlnp | grep nginx

# Test RTMP locally
ffmpeg -re -f lavfi -i testsrc=size=1280x720:rate=30 \
  -f lavfi -i sine=frequency=1000 \
  -c:v libx264 -b:v 2500k -c:a aac -b:a 128k \
  -f flv rtmp://localhost:1935/live/test_key
```

---

## 🎯 Final Checklist

-   [ ] NGINX RTMP module installed
-   [ ] RTMP config added to nginx.conf
-   [ ] NGINX configuration test passed
-   [ ] NGINX restarted successfully
-   [ ] Port 1935 open in server firewall (UFW/firewalld)
-   [ ] Port 1935 open in aaPanel security panel
-   [ ] Port 1935 open in cloud provider firewall
-   [ ] DNS record created in Cloudflare (DNS only, gray cloud)
-   [ ] Laravel .env updated with RTMP URL
-   [ ] Test connection succeeds from Windows PC
-   [ ] OBS/ManyCam can connect and stream

---

## 🎬 Ready to Stream!

Once all checks pass, you can stream with:

**ManyCam:**

-   Settings → Streaming → Custom RTMP
-   URL: `rtmp://rtmp.connectinc.app/live`
-   Key: Get from Laravel

**OBS Studio:**

-   Settings → Stream → Custom
-   Server: `rtmp://rtmp.connectinc.app/live`
-   Key: Get from Laravel

**Stream Key Location:**
In your Laravel app, go to broadcast page and click "Get RTMP Details"
