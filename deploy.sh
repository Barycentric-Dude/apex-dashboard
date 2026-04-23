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

DASHBOARD_PORT="8081"

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
log_fix() { echo -e "${BLUE}[FIX]${NC} $1"; }

# ============================================
# MAIN
# ============================================
main() {
    echo ""
    echo "=============================================="
    echo "  APEX DASHBOARD AUTO-DEPLOY SCRIPT"
    echo "=============================================="
    echo ""
    
    # Create backup directory
    sudo mkdir -p "$BACKUP_PATH"
    
    # Run all checks
    check_docker
    check_ports
    check_nginx
    check_docker_containers
    check_directories
    check_mqtt
    check_dns
    check_firewall
    
    # Deploy
    deploy_code
    configure_mqtt
    configure_nginx
    start_containers
    
    # Report
    show_report
}

# ============================================
# 1. CHECK & INSTALL DOCKER
# ============================================
check_docker() {
    log_info "Checking Docker..."
    
    if ! command -v docker &> /dev/null; then
        log_warn "Docker not found. Installing..."
        curl -fsSL https://get.docker.com | sh
        sudo usermod -aG docker $USER
        FIXES_APPLIED+=("Installed Docker CE")
    else
        log_info "Docker already installed: $(docker --version)"
    fi
    
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null 2>&1; then
        log_warn "Docker Compose not found. Installing..."
        sudo curl -L "https://github.com/docker/compose/releases/download/v2.24.0/docker-compose-$(uname s)-$(uname m)" -o /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
        FIXES_APPLIED+=("Installed Docker Compose")
    fi
    
    if ! sudo docker ps &> /dev/null 2>&1; then
        log_error "Docker daemon not running"
        ISSUES_FOUND+=("Docker daemon not running - run: sudo systemctl start docker")
        return 1
    fi
    
    log_info "Docker OK"
}

# ============================================
# 2. CHECK & FIX PORT CONFLICTS
# ============================================
check_ports() {
    log_info "Checking port conflicts..."
    
    ports_to_check=(80 443 8080 8081)
    
    for port in "${ports_to_check[@]}"; do
        if sudo netstat -tuln 2>/dev/null | grep -q ":$port " || sudo ss -tuln 2>/dev/null | grep -q ":$port "; then
            pid=$(sudo netstat -tulpn 2>/dev/null | grep ":$port " | awk '{print $7}' | cut -d'/' -f1 | head -1)
            if [ -n "$pid" ] && [ "$pid" != "-" ]; then
                proc=$(ps -p "$pid" -o comm= 2>/dev/null | tr -d ' ')
                log_warn "Port $port in use by PID $pid ($proc)"
                ISSUES_FOUND+=("Port $port used by $proc (PID: $pid)")
                
                # Auto-fix: Stop conflicting service
                if [ "$proc" = "apache2" ] || [ "$proc" = "nginx" ]; then
                    log_warn "Attempting to stop $proc on port $port..."
                    sudo systemctl stop apache2 2>/dev/null || sudo systemctl stop nginx 2>/dev/null || true
                    sleep 2
                    if ! sudo netstat -tuln 2>/dev/null | grep -q ":$port "; then
                        log_fix "Stopped $proc on port $port"
                        FIXES_APPLIED+=("Stopped $proc on port $port")
                    fi
                fi
            fi
        else
            log_info "Port $port: OK"
        fi
    done
}

# ============================================
# 3. CHECK NGINX
# ============================================
check_nginx() {
    log_info "Checking nginx..."
    
    if ! command -v nginx &> /dev/null; then
        log_warn "nginx not found. Installing..."
        sudo apt-get update
        sudo apt-get install -y nginx
        FIXES_APPLIED+=("Installed nginx")
    fi
    
    if ! command -v php &> /dev/null; then
        log_warn "PHP not found. Installing..."
        sudo apt-get update
        sudo apt-get install -y php php-fpm
        FIXES_APPLIED+=("Installed PHP and PHP-FPM")
    else
        log_info "PHP already installed: $(php -v | head -1)"
    fi
    
    if ! command -v certbot &> /dev/null; then
        log_warn "certbot not found. Installing..."
        sudo apt-get update
        sudo apt-get install -y certbot python3-certbot-nginx
        FIXES_APPLIED+=("Installed certbot")
    fi
    
    # Check existing configs
    if [ -f "/etc/nginx/sites-available/apex-energy" ] || [ -L "/etc/nginx/sites-enabled/apex-energy" ]; then
        log_warn "nginx config exists for main domain"
        ISSUES_FOUND+=("nginx config exists for main domain")
    fi
    
    if [ -f "/etc/nginx/sites-available/apex-dashboard" ] || [ -L "/etc/nginx/sites-enabled/apex-dashboard" ]; then
        log_warn "nginx config exists for dashboard subdomain"
        ISSUES_FOUND+=("nginx config exists for dashboard")
    fi
    
    # Test nginx config
    if ! sudo nginx -t 2>/dev/null; then
        log_error "Nginx configuration has errors"
        ISSUES_FOUND+=("Nginx config test failed")
    else
        log_info "Nginx configuration OK"
    fi
}

# ============================================
# 4. CHECK DOCKER CONTAINERS
# ============================================
check_docker_containers() {
    log_info "Checking Docker containers..."
    
    old_containers=("apex-website" "apex-dashboard" "apex-bridge" "apex-mosquitto")
    
    for container in "${old_containers[@]}"; do
        if sudo docker ps -a | grep -q "$container"; then
            log_warn "Container exists: $container"
            ISSUES_FOUND+=("Container $container exists")
            
            sudo docker stop "$container" 2>/dev/null || true
            sudo docker rm "$container" 2>/dev/null || true
            log_fix "Removed container: $container"
            FIXES_APPLIED+=("Removed container: $container")
        fi
    done
    
    log_info "Docker containers checked"
}

# ============================================
# 5. CHECK DIRECTORIES
# ============================================
check_directories() {
    log_info "Checking directories..."
    
    paths_to_check=("$WEBSITE_PATH" "$DASHBOARD_PATH")
    
    for path in "${paths_to_check[@]}"; do
        if [ -d "$path" ] && [ -n "$(ls -A $path 2>/dev/null)" ]; then
            log_warn "Directory not empty: $path"
            ISSUES_FOUND+=("Directory not empty: $path")
            
            backup_name="$BACKUP_PATH/$(basename $path)_$(date +%Y%m%d_%H%M%S)"
            sudo cp -r "$path" "$backup_name"
            log_fix "Backed up: $path -> $backup_name"
            FIXES_APPLIED+=("Backed up: $path")
        fi
    done
    
    log_info "Directories checked"
}

# ============================================
# 6. CHECK MQTT
# ============================================
check_mqtt() {
    log_info "Checking MQTT broker..."
    
    if timeout 3 bash -c "echo >/dev/tcp/$MQTT_BROKER/$MQTT_PORT" 2>/dev/null; then
        log_info "MQTT broker reachable: $MQTT_BROKER:$MQTT_PORT"
    else
        log_error "Cannot reach MQTT broker at $MQTT_BROKER:$MQTT_PORT"
        ISSUES_FOUND+=("MQTT broker unreachable - check firewall")
    fi
}

# ============================================
# 7. CHECK DNS
# ============================================
check_dns() {
    log_info "Checking DNS..."
    
    domains=("$MAIN_DOMAIN" "$DASHBOARD_SUBDOMAIN")
    
    for domain in "${domains[@]}"; do
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
# 8. CHECK FIREWALL
# ============================================
check_firewall() {
    log_info "Checking firewall..."
    
    required_ports=(80 443 8081)
    
    for port in "${required_ports[@]}"; do
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
# 9. DEPLOY CODE
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
    
    # Dashboard
    sudo mkdir -p "$DASHBOARD_PATH"
    sudo cp -r "$TEMP_DIR/src" "$TEMP_DIR/views" "$DASHBOARD_PATH/"
    
    for item in public data bridge.py Dockerfile Dockerfile.bridge docker-compose.yml mosquitto.passwd; do
        [ -f "$TEMP_DIR/$item" ] && sudo cp -r "$TEMP_DIR/$item" "$DASHBOARD_PATH/"
    done
    
    sudo chown -R www-data:www-data "$DASHBOARD_PATH"
    
    log_info "Code deployed"
}

# ============================================
# 10. CONFIGURE MQTT
# ============================================
configure_mqtt() {
    log_info "Configuring MQTT credentials..."
    
    echo "${MQTT_USER}:${MQTT_PASS}" | sudo tee "$DASHBOARD_PATH/mosquitto.passwd" > /dev/null
    
    # Update bridge.py
    sudo sed -i "s/MQTT_BROKER = os.getenv(\"MQTT_BROKER\", \"[^\"]*\")/MQTT_BROKER = os.getenv(\"MQTT_BROKER\", \"$MQTT_BROKER\")/" "$DASHBOARD_PATH/bridge.py"
    sudo sed -i "s/MQTT_USERNAME = os.getenv(\"MQTT_USERNAME\", \"\")/MQTT_USERNAME = os.getenv(\"MQTT_USERNAME\", \"$MQTT_USER\")/" "$DASHBOARD_PATH/bridge.py"
    sudo sed -i "s/MQTT_PASSWORD = os.getenv(\"MQTT_PASSWORD\", \"\")/MQTT_PASSWORD = os.getenv(\"MQTT_PASSWORD\", \"$MQTT_PASS\")/" "$DASHBOARD_PATH/bridge.py"
    
    log_info "MQTT configured"
}

# ============================================
# 11. CONFIGURE NGINX
# ============================================
configure_nginx() {
    log_info "Configuring nginx..."
    
    # Main website config (HTTP with SSL challenge)
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

    # Dashboard config (HTTP with SSL challenge)
    sudo tee /etc/nginx/sites-available/apex-dashboard > /dev/null <<'EOF'
server {
    listen 80;
    server_name dashboard.apexenergycontrol.com;

    root /var/www/html/dashboard.apexenergycontrol;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location /.well-known/acme-challenge/ {
        root /var/www/html/dashboard.apexenergycontrol;
    }
}
EOF

    sudo ln -sf /etc/nginx/sites-available/apex-energy /etc/nginx/sites-enabled/
    sudo ln -sf /etc/nginx/sites-available/apex-dashboard /etc/nginx/sites-enabled/
    
    sudo nginx -t && sudo systemctl reload nginx
    
    log_info "nginx configured"
    
    # Request SSL certificates
    setup_ssl_certs
}

# ============================================
# 11b. SETUP SSL CERTIFICATES
# ============================================
setup_ssl_certs() {
    log_info "Setting up SSL certificates..."
    
    domains=("apexenergycontrol.com" "www.apexenergycontrol.com" "dashboard.apexenergycontrol.com")
    
    for domain in "${domains[@]}"; do
        cert_path="/etc/letsencrypt/live/$domain"
        if [ ! -d "$cert_path" ]; then
            log_info "Requesting certificate for $domain..."
            sudo certbot certonly --nginx -d "$domain" --non-interactive --agree-tos -m admin@$domain 2>/dev/null || \
            sudo certbot certonly --webroot -w /var/www/html/apexenergycontrol -d "$domain" --non-interactive --agree-tos -m admin@$domain 2>/dev/null || \
            {
                log_warn "Failed to get certificate for $domain - will retry later"
                ISSUES_FOUND+=("SSL certificate pending for $domain")
            }
        else
            log_info "SSL certificate already exists for $domain"
        fi
    done
    
    # Setup auto-renewal cron
    if ! sudo crontab -l 2>/dev/null | grep -q "certbot"; then
        echo "0 3 * * * sudo certbot renew --quiet --deploy-hook 'systemctl reload nginx'" | sudo crontab -
        log_info "SSL auto-renewal cron job added"
        FIXES_APPLIED+=("Added SSL auto-renewal cron")
    fi
}

# ============================================
# 12. START CONTAINERS
# ============================================
start_containers() {
    log_info "Starting containers..."
    
    cd "$DASHBOARD_PATH"
    sudo docker-compose up -d --build
    
    # Health check
    sleep 8
    log_info "Checking container health..."
    
    if sudo docker ps | grep -q "apex-dashboard"; then
        log_info "Dashboard container: RUNNING"
    else
        log_error "Dashboard container: FAILED"
        ISSUES_FOUND+=("Dashboard container failed to start")
    fi
    
    if sudo docker ps | grep -q "apex-bridge"; then
        log_info "Bridge container: RUNNING"
    else
        log_error "Bridge container: FAILED"
        ISSUES_FOUND+=("Bridge container failed to start")
    fi
    
    # Verify API responds
    sleep 2
    if curl -sf -o /dev/null http://127.0.0.1:8081/api/health 2>/dev/null; then
        log_info "Dashboard API: RESPONDING"
    else
        log_warn "Dashboard API: NOT RESPONDING (may need more time)"
        ISSUES_FOUND+=("Dashboard API not responding on port 8081")
    fi
    
    log_info "Containers started"
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
}

# Run main
main "$@"