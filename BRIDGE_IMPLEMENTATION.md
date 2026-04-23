# Apex IoT MQTT-to-HTTP Bridge - Implementation Complete

## Overview
This bridge connects IoT panels (via MQTT) to the PHP dashboard, enabling real-time data streaming.

## Files Created
- `bridge.py` - Main bridge service (Python)

## Architecture
```
IoT Panels → Mosquitto MQTT → bridge.py → Dashboard API → MySQL
```

## MQTT Topics
- **Subscribe:** `sensors/+/data`, `panels/+/status`, `sensors/#`, `panels/#`
- **Payload Format:** JSON with device_id, panel_input, event_type, current, device_status, water_level, mains_status, batt_status, reported_at

## Dashboard API Integration
- **Endpoint:** `POST http://127.0.0.1:8080/api/telemetry`
- **Authentication:** Bearer token (update DASHBOARD_TOKEN in bridge.py)
- **Data Transform:** MQTT → Dashboard API format

## Key Features
1. **Real-time Data Forwarding:** Subscribes to MQTT topics and forwards to dashboard
2. **Offline Detection:** Marks panels offline after 24 minutes without data
3. **Alert Processing:** Auto-generates alerts for fire and device offline events
4. **Error Handling:** Graceful handling of connection failures and invalid data
5. **Threading:** Concurrent offline checking and status reporting

## Configuration
Update in `bridge.py`:
- `MQTT_BROKER`: MQTT broker address (default: 127.0.0.1)
- `MQTT_PORT`: MQTT broker port (default: 1883)
- `DASHBOARD_API`: Dashboard API base URL
- `DASHBOARD_TOKEN`: Bearer token for authentication
- `OFFLINE_THRESHOLD`: Offline detection threshold in minutes (default: 24)

## Usage

### Start Mosquitto Broker
```bash
mosquitto -d -p 1883
```

### Start Bridge
```bash
python3 bridge.py
```

### Start PHP Dashboard
```bash
php -S 127.0.0.1:8080 -t public
```

## Testing
Run integration test:
```bash
python3 final_test.py
```

## Data Flow
1. IoT panel publishes JSON to MQTT topic (e.g., `sensors/data`)
2. Bridge receives message via MQTT callback
3. Bridge transforms data to dashboard API format
4. Bridge forwards to dashboard via HTTP POST
5. Dashboard stores data in MySQL/JSON files
6. Dashboard displays real-time panel status, alerts, and sensor data

## Alert Types
- **fire:** Event type = "FIRE"
- **panel_offline:** Device status = 0
- **device_offline:** Panel not seen for 24+ minutes

## Dependencies
- Python 3.x
- paho-mqtt
- requests

Install: `pip3 install paho-mqtt requests`