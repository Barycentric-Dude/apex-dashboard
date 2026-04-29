#!/bin/bash
set -e

# ============================================
# APEX DASHBOARD DEBUG & TEST SCRIPT
# ============================================

MQTT_BROKER="43.204.150.164"
MQTT_PORT="1883"
MQTT_USER="apexusr"
MQTT_PASS="jaijaishreeram"
DASHBOARD_URL="http://127.0.0.1"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo ""
echo "=============================================="
echo "  APEX DASHBOARD DEBUG & TEST"
echo "=============================================="
echo ""

# ============================================
# 1. Bridge Service Status
# ============================================
echo -e "${CYAN}[1] Bridge Service Status${NC}"
echo "-----------------------------------"
if sudo systemctl is-active --quiet apex-bridge; then
    echo -e "${GREEN}apex-bridge: RUNNING${NC}"
    sudo systemctl status apex-bridge --no-pager -l | head -15 | sed 's/^/  /'
else
    echo -e "${RED}apex-bridge: NOT RUNNING${NC}"
    echo "  Start with: sudo systemctl start apex-bridge"
    echo "  Logs:       sudo journalctl -u apex-bridge -n 50"
fi
echo ""

# ============================================
# 2. Bridge Logs (Last 10 lines)
# ============================================
echo -e "${CYAN}[2] Bridge Logs (Last 10 lines)${NC}"
echo "-----------------------------------"
sudo journalctl -u apex-bridge -n 10 --no-pager 2>/dev/null | sed 's/^/  /' || \
    echo -e "${RED}  Could not read journal for apex-bridge${NC}"
echo ""

# ============================================
# 3. MQTT Broker Connectivity
# ============================================
echo -e "${CYAN}[3] MQTT Broker Connectivity${NC}"
echo "-----------------------------------"
echo -n "  TCP connection: "
if timeout 3 bash -c "echo >/dev/tcp/$MQTT_BROKER/$MQTT_PORT" 2>/dev/null; then
    echo -e "${GREEN}Reachable${NC}"
else
    echo -e "${RED}Unreachable${NC}"
fi

echo -n "  mosquitto_sub available: "
if command -v mosquitto_sub &> /dev/null; then
    echo -e "${GREEN}Yes${NC}"
else
    echo -e "${YELLOW}Not installed (sudo apt install mosquitto-clients)${NC}"
fi
echo ""

# ============================================
# 4. Dashboard API Health
# ============================================
echo -e "${CYAN}[4] Dashboard API Health${NC}"
echo "-----------------------------------"
echo -n "  HTTP response: "
HTTP_CODE=$(curl -sf -o /dev/null -w "%{http_code}" "$DASHBOARD_URL" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}200 OK${NC}"
else
    echo -e "${RED}$HTTP_CODE${NC}"
fi

echo -n "  API endpoint: "
API_CODE=$(curl -sf -o /dev/null -w "%{http_code}" "$DASHBOARD_URL/api/panel-ingest" 2>/dev/null || echo "000")
if [ "$API_CODE" = "401" ]; then
    echo -e "${GREEN}Responding (401 = auth required, expected)${NC}"
else
    echo -e "${YELLOW}$API_CODE${NC}"
fi
echo ""

# ============================================
# 5. Data Files Status
# ============================================
echo -e "${CYAN}[5] Data Files Status${NC}"
echo "-----------------------------------"
DATA_DIR="/var/www/html/dashboard.apexenergycontrol/data"

if [ -d "$DATA_DIR" ]; then
    for file in telemetry_logs.json latest_states.json alerts.json; do
        if [ -f "$DATA_DIR/$file" ]; then
            count=$(grep -c '"' "$DATA_DIR/$file" 2>/dev/null || echo "0")
            echo -e "  $file: ${GREEN}${count} records${NC}"
        else
            echo -e "  $file: ${YELLOW}Not found${NC}"
        fi
    done
else
    echo -e "${RED}  Data directory not found${NC}"
fi
echo ""

# ============================================
# 6. Interactive MQTT Subscribe (if available)
# ============================================
if command -v mosquitto_sub &> /dev/null; then
    echo -e "${CYAN}[6] MQTT Subscribe Test${NC}"
    echo "-----------------------------------"
    echo "  Subscribing to sensors/# for 5 seconds..."
    echo -e "  ${YELLOW}(Press Ctrl+C to stop early)${NC}"
    echo ""
    timeout 5 mosquitto_sub \
        -h "$MQTT_BROKER" \
        -p "$MQTT_PORT" \
        -u "$MQTT_USER" \
        -P "$MQTT_PASS" \
        -t "sensors/#" \
        -v 2>/dev/null || true
    echo ""
fi

# ============================================
# 7. Test Panel Ingest (with known token)
# ============================================
echo -e "${CYAN}[7] Test Panel Ingest${NC}"
echo "-----------------------------------"

PANEL_TOKEN=$(grep -o '"token":"[^"]*"' /var/www/html/dashboard.apexenergycontrol/data/panels.json 2>/dev/null | head -1 | cut -d'"' -f4)
PANEL_DEVICE=$(grep -o '"device_id":"[^"]*"' /var/www/html/dashboard.apexenergycontrol/data/panels.json 2>/dev/null | head -1 | cut -d'"' -f4)

if [ -n "$PANEL_TOKEN" ] && [ -n "$PANEL_DEVICE" ]; then
    echo "  Panel: $PANEL_DEVICE"
    echo -n "  Sending test data: "
    RESPONSE=$(curl -sf -X POST "$DASHBOARD_URL/api/panel-ingest" \
        -H "Authorization: Bearer $PANEL_TOKEN" \
        -H "Content-Type: application/json" \
        -d "[{\"device_id\":\"$PANEL_DEVICE\",\"panel_input\":\"DI_1\",\"event_type\":\"NORMAL\",\"current\":5.12,\"device_status\":1,\"water_level\":85,\"mains_status\":1,\"batt_status\":1}]" \
        2>&1)
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Success${NC}"
        echo "  Response: $RESPONSE"
    else
        echo -e "${RED}Failed${NC}"
        echo "  Error: $RESPONSE"
    fi
else
    echo -e "${YELLOW}  No panels found in data/panels.json${NC}"
fi
echo ""

# ============================================
# Summary
# ============================================
echo "=============================================="
echo "  QUICK COMMANDS"
echo "=============================================="
echo ""
echo "  Watch bridge logs:    sudo journalctl -u apex-bridge -f"
echo "  Restart bridge:       sudo systemctl restart apex-bridge"
echo "  MQTT subscribe:       mosquitto_sub -h $MQTT_BROKER -p $MQTT_PORT -u $MQTT_USER -P $MQTT_PASS -t 'sensors/#' -v"
echo "  nginx error log:      sudo tail -f /var/log/nginx/error.log"
echo ""
