#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOST="${HOST:-127.0.0.1}"
PORT="${PORT:-8080}"

echo "Starting Apex Fire IoT Dashboard (local dev) at http://${HOST}:${PORT}"
echo ""
echo "To run the bridge locally in a separate terminal:"
echo "  MQTT_BROKER=43.204.150.164 MQTT_PORT=1883 \\"
echo "  MQTT_USERNAME=apexusr MQTT_PASSWORD=jaijaishreeram \\"
echo "  MQTT_TOPICS='sensors/#,panels/#' \\"
echo "  DASHBOARD_URL=http://${HOST}:${PORT}/api \\"
echo "  python3 ${ROOT_DIR}/bridge.py"
echo ""

cd "$ROOT_DIR"
exec php -S "${HOST}:${PORT}" -t public
