#!/bin/bash
set -e

# ============================================
# APEX DASHBOARD AUTO-DEPLOY SCRIPT
# ============================================

# ----- CONFIGURATION -----
REPO_URL="https://github.com/Barycentric-Dude/apex-dashboard.git"
WEBSITE_PATH="/var/www/html/apexenergycontrol"
DASHBOARD_PATH="/var/www/html/dashboard.apexenergycontrol"
BACKUP_PATH="/tmp/apex-backup"

MAIN_DOMAIN="apexenergycontrol.com"
DASHBOARD_SUBDOMAIN="dashboard.apexenergycontrol.com"

MQTT_BROKER="43.204.150.164"
MQTT_PORT="1883"
MQTT_USER="apexusr"
MQTT_PASS="jaijaishreeram"

BRIDGE_SERVICE="apex-bridge"
BRIDGE_SERVICE_FILE="/etc/systemd/system/${BRIDGE_SERVICE}.service"

ISSUES_FOUND=()
FIXES_APPLIED=()

# ----- COLORS -----
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_fix()  { echo -e "${BLUE}[FIX]${NC} $1"; }

# ============================================
# MAIN
# ============================================
main() {
    echo ""
    echo "=============================================="
    echo "  APEX DASHBOARD AUTO-DEPLOY SCRIPT"
    echo "=============================================="
    echo ""

    sudo mkdir -p "$BACKUP_PATH"

    check_ports
    check_nginx
    check_directories
    check_mqtt
    check_dns
    check_firewall

    deploy_code
    configure_nginx
    start_bridge

    show_report
}

# ============================================
# 1. CHECK & FIX PORT CONFLICTS
# ============================================
check_ports() {
    log_info "Checking port conflicts..."

    for port in 80 443; do
        if sudo ss -tuln 2>/dev/null | grep -q ":${port} "; then
            pid=$(sudo ss -tulpn 2>/dev/null | grep ":${port} " | grep -oP 'pid=\K[0-9]+' | head -1)
            if [ -n "$pid" ]; then
                proc=$(ps -p "$pid" -o comm= 2>/dev/null | tr -d ' ')
                log_warn "Port $port in use by PID $pid ($proc)"
                ISSUES_FOUND+=("Port $port used by $proc (PID: $pid)")
                if [ "$proc" = "apache2" ]; then
                    sudo systemctl stop apache2 2>/dev/null || true
                    log_fix "Stopped apache2 on port $port"
                    FIXES_APPLIED+=("Stopped apache2 on port $port")
                fi
            fi
        else
            log_info "Port $port: OK"
        fi
    done
}

# ============================================
# 2. CHECK & INSTALL NGINX, PHP, CERTBOT
# ============================================
check_nginx() {
    log_info "Checking nginx and PHP..."

    if ! command -v nginx &> /dev/null; then
        log_warn "nginx not found. Installing..."
        sudo apt-get update -qq
        sudo apt-get install -y nginx
        FIXES_APPLIED+=("Installed nginx")
    else
        log_info "nginx: $(nginx -v 2>&1 | tr -d '\n')"
    fi

    if ! command -v php &> /dev/null; then
        log_warn "PHP not found. Installing..."
        sudo apt-get update -qq
        sudo apt-get install -y php php-fpm
        FIXES_APPLIED+=("Installed PHP and PHP-FPM")
    else
        log_info "PHP: $(php -v | head -1)"
    fi

    if ! command -v certbot &> /dev/null; then
        log_warn "certbot not found. Installing..."
        sudo apt-get update -qq
        sudo apt-get install -y certbot python3-certbot-nginx
        FIXES_APPLIED+=("Installed certbot")
    fi

    # Install mosquitto-clients for debugging
    if ! command -v mosquitto_sub &> /dev/null; then
        log_warn "mosquitto-clients not found. Installing for debugging..."
        sudo apt-get update -qq
        sudo apt-get install -y mosquitto-clients
        FIXES_APPLIED+=("Installed mosquitto-clients")
    else
        log_info "mosquitto-clients: OK"
    fi

    if ! sudo nginx -t 2>/dev/null; then
        log_error "Nginx configuration has errors"
        ISSUES_FOUND+=("Nginx config test failed")
    else
        log_info "Nginx configuration OK"
    fi
}

# ============================================
# 3. CHECK DIRECTORIES
# ============================================
check_directories() {
    log_info "Checking directories..."

    for path in "$WEBSITE_PATH" "$DASHBOARD_PATH"; do
        if [ -d "$path" ] && [ -n "$(ls -A "$path" 2>/dev/null)" ]; then
            log_warn "Directory not empty: $path"
            ISSUES_FOUND+=("Directory not empty: $path")
            backup_name="$BACKUP_PATH/$(basename "$path")_$(date +%Y%m%d_%H%M%S)"
            sudo cp -r "$path" "$backup_name"
            log_fix "Backed up: $path -> $backup_name"
            FIXES_APPLIED+=("Backed up: $path")
        fi
    done

    log_info "Directories checked"
}

# ============================================
# 4. CHECK MQTT
# ============================================
check_mqtt() {
    log_info "Checking MQTT broker..."

    if timeout 3 bash -c "echo >/dev/tcp/$MQTT_BROKER/$MQTT_PORT" 2>/dev/null; then
        log_info "MQTT broker reachable: $MQTT_BROKER:$MQTT_PORT"
    else
        log_error "Cannot reach MQTT broker at $MQTT_BROKER:$MQTT_PORT"
        ISSUES_FOUND+=("MQTT broker unreachable - check firewall/network")
    fi
}

# ============================================
# 5. CHECK DNS
# ============================================
check_dns() {
    log_info "Checking DNS..."

    for domain in "$MAIN_DOMAIN" "$DASHBOARD_SUBDOMAIN"; do
        ip=$(dig +short "$domain" 2>/dev/null | tail -1)
        if [ -n "$ip" ]; then
            log_info "DNS OK: $domain -> $ip"
        else
            log_warn "DNS not resolving: $domain"
            ISSUES_FOUND+=("DNS not pointing to server: $domain")
        fi
    done
}

# ============================================
# 6. CHECK FIREWALL
# ============================================
check_firewall() {
    log_info "Checking firewall..."

    for port in 80 443; do
        if sudo iptables -L INPUT -n 2>/dev/null | grep -q "DROP.*:$port"; then
            log_warn "Port $port may be blocked"
            ISSUES_FOUND+=("Firewall may block port $port")
            sudo iptables -I INPUT -p tcp --dport "$port" -j ACCEPT 2>/dev/null
            log_fix "Opened port $port"
            FIXES_APPLIED+=("Opened firewall port: $port")
        fi
    done

    log_info "Firewall checked"
}

# ============================================
# 7. DEPLOY CODE
# ============================================
deploy_code() {
    log_info "Deploying code..."

    TEMP_DIR="/tmp/apex-deploy"

    if [ -d "$TEMP_DIR/.git" ]; then
        cd "$TEMP_DIR" && git pull origin master
    else
        rm -rf "$TEMP_DIR"
        git clone "$REPO_URL" "$TEMP_DIR"
    fi

    # Website
    sudo mkdir -p "$WEBSITE_PATH"
    sudo cp -r "$TEMP_DIR/website/"* "$WEBSITE_PATH/"
    sudo chown -R www-data:www-data "$WEBSITE_PATH"

    # Dashboard — application code only (no Docker files)
    sudo mkdir -p "$DASHBOARD_PATH"
    sudo cp -r "$TEMP_DIR/src" "$TEMP_DIR/views" "$DASHBOARD_PATH/"

    for item in public data bridge.py; do
        [ -e "$TEMP_DIR/$item" ] && sudo cp -r "$TEMP_DIR/$item" "$DASHBOARD_PATH/"
    done

    sudo chown -R www-data:www-data "$DASHBOARD_PATH"

    log_info "Code deployed"
}

# ============================================
# 8. CONFIGURE NGINX
# ============================================
configure_nginx() {
    log_info "Configuring nginx..."

    sudo tee /etc/nginx/sites-available/apex-energy > /dev/null <<'EOF'
server {
    listen 80;
    server_name apexenergycontrol.com www.apexenergycontrol.com;

    root /var/www/html/apexenergycontrol;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location /.well-known/acme-challenge/ {
        root /var/www/html/apexenergycontrol;
    }
}
EOF

    sudo tee /etc/nginx/sites-available/apex-dashboard > /dev/null <<'EOF'
server {
    listen 80;
    server_name dashboard.apexenergycontrol.com;

    root /var/www/html/dashboard.apexenergycontrol/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location /.well-known/acme-challenge/ {
        root /var/www/html/dashboard.apexenergycontrol/public;
    }
}
EOF

    sudo ln -sf /etc/nginx/sites-available/apex-energy /etc/nginx/sites-enabled/
    sudo ln -sf /etc/nginx/sites-available/apex-dashboard /etc/nginx/sites-enabled/

    sudo nginx -t && sudo systemctl reload nginx

    log_info "nginx configured"

    setup_ssl_certs
}

# ============================================
# 8b. SETUP SSL CERTIFICATES
# ============================================
setup_ssl_certs() {
    log_info "Setting up SSL certificates..."

    for domain in "apexenergycontrol.com" "www.apexenergycontrol.com" "dashboard.apexenergycontrol.com"; do
        cert_path="/etc/letsencrypt/live/$domain"
        if [ ! -d "$cert_path" ]; then
            log_info "Requesting certificate for $domain..."
            sudo certbot certonly --nginx -d "$domain" --non-interactive --agree-tos -m "admin@apexenergycontrol.com" 2>/dev/null || \
            sudo certbot certonly --webroot -w /var/www/html/apexenergycontrol -d "$domain" --non-interactive --agree-tos -m "admin@apexenergycontrol.com" 2>/dev/null || {
                log_warn "Failed to get certificate for $domain - will retry later"
                ISSUES_FOUND+=("SSL certificate pending for $domain")
            }
        else
            log_info "SSL certificate already exists for $domain"
        fi
    done

    if ! sudo crontab -l 2>/dev/null | grep -q "certbot"; then
        echo "0 3 * * * sudo certbot renew --quiet --deploy-hook 'systemctl reload nginx'" | sudo crontab -
        log_info "SSL auto-renewal cron job added"
        FIXES_APPLIED+=("Added SSL auto-renewal cron")
    fi
}

# ============================================
# 9. INSTALL PYTHON DEPS & START BRIDGE SERVICE
# ============================================
start_bridge() {
    log_info "Installing Python dependencies..."

    sudo apt-get update -qq
    sudo apt-get install -y python3 python3-pip
    sudo pip3 install --break-system-packages paho-mqtt requests 2>/dev/null || \
        sudo pip3 install paho-mqtt requests
    FIXES_APPLIED+=("Installed paho-mqtt and requests via pip3")

    log_info "Writing systemd service: $BRIDGE_SERVICE_FILE"

    sudo tee "$BRIDGE_SERVICE_FILE" > /dev/null <<EOF
[Unit]
Description=Apex IoT MQTT-to-HTTP Bridge
Documentation=https://github.com/Barycentric-Dude/apex-dashboard
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=${DASHBOARD_PATH}

ExecStart=/usr/bin/python3 ${DASHBOARD_PATH}/bridge.py

Environment=MQTT_BROKER=${MQTT_BROKER}
Environment=MQTT_PORT=${MQTT_PORT}
Environment=MQTT_USERNAME=${MQTT_USER}
Environment=MQTT_PASSWORD=${MQTT_PASS}
Environment=MQTT_TOPICS=sensors/#,panels/#
Environment=DASHBOARD_URL=http://127.0.0.1/api
Environment=DASHBOARD_TOKEN=
Environment=OFFLINE_THRESHOLD=24

Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=apex-bridge

[Install]
WantedBy=multi-user.target
EOF

    sudo systemctl daemon-reload
    sudo systemctl enable "$BRIDGE_SERVICE"
    sudo systemctl restart "$BRIDGE_SERVICE"

    # Health check
    sleep 5
    if sudo systemctl is-active --quiet "$BRIDGE_SERVICE"; then
        log_info "apex-bridge service: RUNNING"
        FIXES_APPLIED+=("Started apex-bridge systemd service")
    else
        log_error "apex-bridge service: FAILED TO START"
        log_error "Check logs with: sudo journalctl -u apex-bridge -n 50"
        ISSUES_FOUND+=("apex-bridge service failed to start")
    fi

    # Verify dashboard responds via nginx
    sleep 2
    if curl -sf -o /dev/null http://127.0.0.1/ 2>/dev/null; then
        log_info "Dashboard HTTP: RESPONDING"
    else
        log_warn "Dashboard HTTP: NOT RESPONDING (nginx may need a moment)"
        ISSUES_FOUND+=("Dashboard not responding on port 80")
    fi
}

# ============================================
# REPORT
# ============================================
show_report() {
    echo ""
    echo "=============================================="
    echo "  DEPLOYMENT COMPLETE"
    echo "=============================================="
    echo ""

    if [ ${#ISSUES_FOUND[@]} -gt 0 ]; then
        echo -e "${RED}ISSUES FOUND:${NC}"
        for issue in "${ISSUES_FOUND[@]}"; do
            echo "  - $issue"
        done
        echo ""
    fi

    if [ ${#FIXES_APPLIED[@]} -gt 0 ]; then
        echo -e "${BLUE}FIXES APPLIED:${NC}"
        for fix in "${FIXES_APPLIED[@]}"; do
            echo "  + $fix"
        done
        echo ""
    fi

    echo -e "${GREEN}ACCESS URLs:${NC}"
    echo "  Website:   http://$MAIN_DOMAIN"
    echo "  Dashboard: http://$DASHBOARD_SUBDOMAIN"
    echo ""
    echo -e "${GREEN}BRIDGE STATUS:${NC}"
    echo "  sudo systemctl status apex-bridge"
    echo "  sudo journalctl -u apex-bridge -f"
    echo ""
}

# Run main
main "$@"
