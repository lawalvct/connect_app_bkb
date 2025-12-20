#!/bin/bash

# RTMP Server Setup Script for aaPanel
# Run this on your VPS server as root

set -e

echo "=========================================="
echo "RTMP Server Setup for aaPanel"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root (use: sudo bash setup_rtmp_aapanel.sh)${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Installing NGINX RTMP Module...${NC}"
apt update
apt install -y libnginx-mod-rtmp || {
    echo -e "${YELLOW}Standard package not available, will compile from source...${NC}"
    apt install -y build-essential libpcre3 libpcre3-dev libssl-dev zlib1g-dev git
    cd /tmp
    git clone https://github.com/arut/nginx-rtmp-module.git
    echo -e "${GREEN}✓ RTMP module downloaded${NC}"
}

echo ""
echo -e "${YELLOW}Step 2: Backing up current NGINX config...${NC}"
if [ -f /www/server/nginx/conf/nginx.conf ]; then
    cp /www/server/nginx/conf/nginx.conf /www/server/nginx/conf/nginx.conf.backup.$(date +%Y%m%d_%H%M%S)
    echo -e "${GREEN}✓ Backup created${NC}"
else
    echo -e "${RED}aaPanel NGINX config not found. Are you using aaPanel?${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3: Checking if RTMP block already exists...${NC}"
if grep -q "rtmp {" /www/server/nginx/conf/nginx.conf; then
    echo -e "${YELLOW}RTMP block already exists in config${NC}"
else
    echo -e "${YELLOW}Adding RTMP configuration...${NC}"

    # Create RTMP config
    cat > /tmp/rtmp_block.conf << 'EOF'

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

            # Optional: Enable HLS
            # hls on;
            # hls_path /tmp/hls;
            # hls_fragment 3;
        }
    }
}

EOF

    # Insert RTMP block before http block
    sed -i '/^http {/i\
# RTMP Streaming Server\
rtmp {\
    server {\
        listen 1935;\
        chunk_size 4096;\
        \
        application live {\
            live on;\
            record off;\
            \
            # Laravel stream authentication\
            on_publish http://127.0.0.1/api/rtmp/auth;\
            on_publish_done http://127.0.0.1/api/rtmp/end;\
        }\
    }\
}' /www/server/nginx/conf/nginx.conf

    echo -e "${GREEN}✓ RTMP configuration added${NC}"
fi

echo ""
echo -e "${YELLOW}Step 4: Opening firewall port 1935...${NC}"

# Try UFW first
if command -v ufw &> /dev/null; then
    ufw allow 1935/tcp
    echo -e "${GREEN}✓ Port 1935 opened in UFW${NC}"
# Then try firewalld
elif command -v firewall-cmd &> /dev/null; then
    firewall-cmd --permanent --add-port=1935/tcp
    firewall-cmd --reload
    echo -e "${GREEN}✓ Port 1935 opened in firewalld${NC}"
# Fallback to iptables
else
    iptables -A INPUT -p tcp --dport 1935 -j ACCEPT
    # Try to save iptables rules
    if command -v netfilter-persistent &> /dev/null; then
        netfilter-persistent save
    fi
    echo -e "${GREEN}✓ Port 1935 opened in iptables${NC}"
fi

echo ""
echo -e "${YELLOW}Step 5: Opening port in aaPanel firewall...${NC}"
if command -v bt &> /dev/null; then
    bt 14  # aaPanel firewall management
    echo -e "${YELLOW}Please manually add port 1935 in aaPanel Security panel${NC}"
else
    echo -e "${YELLOW}aaPanel CLI not found, please add port 1935 manually in aaPanel Security${NC}"
fi

echo ""
echo -e "${YELLOW}Step 6: Testing NGINX configuration...${NC}"
/www/server/nginx/sbin/nginx -t
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ NGINX configuration is valid${NC}"
else
    echo -e "${RED}✗ NGINX configuration has errors${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 7: Restarting NGINX...${NC}"
/www/server/nginx/sbin/nginx -s reload || systemctl restart nginx
echo -e "${GREEN}✓ NGINX restarted${NC}"

echo ""
echo -e "${YELLOW}Step 8: Verifying RTMP port is listening...${NC}"
sleep 2
if netstat -tlnp | grep -q ":1935"; then
    echo -e "${GREEN}✓ RTMP server is listening on port 1935${NC}"
else
    echo -e "${RED}✗ RTMP port 1935 is not listening${NC}"
    echo "Checking with ss command..."
    ss -tlnp | grep 1935 || echo "Port not found"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}Setup Complete!${NC}"
echo "=========================================="
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo ""
echo "1. Get your server's real IP address:"
echo "   curl ifconfig.me"
echo ""
echo "2. Add DNS record in Cloudflare:"
echo "   Name: rtmp"
echo "   Type: A"
echo "   Content: [YOUR SERVER IP]"
echo "   Proxy: DNS only (gray cloud)"
echo ""
echo "3. Update Laravel .env file:"
echo "   RTMP_SERVER_URL=rtmp://rtmp.connectinc.app/live"
echo ""
echo "4. Test RTMP connection from your PC:"
echo "   Test-NetConnection -ComputerName rtmp.connectinc.app -Port 1935"
echo ""
echo "5. Use in ManyCam/OBS:"
echo "   Server: rtmp://rtmp.connectinc.app/live"
echo "   Stream Key: Get from Laravel broadcast page"
echo ""
echo -e "${YELLOW}IMPORTANT: Also open port 1935 in your cloud provider's firewall!${NC}"
echo ""
