#!/usr/bin/env python3
"""
Apex IoT MQTT-to-HTTP Bridge
Subscribes to Mosquitto MQTT broker and forwards panel data to dashboard API
"""

import os
import re
import paho.mqtt.client as mqtt
import requests
import json
import time
import threading
from datetime import datetime, timezone

# Configuration from environment
MQTT_BROKER = os.getenv("MQTT_BROKER", "127.0.0.1")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_USERNAME = os.getenv("MQTT_USERNAME", "")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD", "")
DASHBOARD_URL = os.getenv("DASHBOARD_URL")
DASHBOARD_TOKEN = os.getenv("DASHBOARD_TOKEN", "")

# Panel state tracking
panel_states = {}

def on_connect(client, userdata, flags, rc, properties=None):
    """Callback when connected to MQTT broker"""
    if rc == 0:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Connected to MQTT broker", flush=True)
        client.subscribe("#")
        print("  Subscribed to: # (all topics)", flush=True)
    else:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Connection failed with code {rc}", flush=True)

def on_message(client, userdata, msg, properties=None):
    """Callback when message received from MQTT broker"""
    try:
        # Parse MQTT payload
        payload_str = msg.payload.decode()
        data = json.loads(payload_str)

        # Extract device_id (handle both lowercase and uppercase)
        panel_id = data.get('device_id') or data.get('DEVICE_ID')

        if not panel_id:
            print(f"[{datetime.now(timezone.utc).isoformat()}] Warning: No device_id/DEVICE_ID in message: {data}", flush=True)
            return

        panel_id_str = str(panel_id)
        if len(panel_id_str) > 64:
            print(f"[{datetime.now(timezone.utc).isoformat()}] Warning: device_id too long ({len(panel_id_str)} chars), skipping: {panel_id}", flush=True)
            return
        if not re.match(r'^[a-zA-Z0-9_:.-]+$', panel_id_str):
            print(f"[{datetime.now(timezone.utc).isoformat()}] Warning: Invalid device_id format, skipping: {panel_id}", flush=True)
            return

        print(f"[{datetime.now(timezone.utc).isoformat()}] Received message from {panel_id}: {msg.topic}", flush=True)
        print(f"  Raw payload: {payload_str}", flush=True)
        print(f"  Payload size: {len(payload_str)} bytes", flush=True)

        # Update panel state
        panel_states[panel_id] = {
            'last_seen': datetime.now(timezone.utc).isoformat(),
            'data': data,
            'online': True
        }

        # Process batch format: iterate DI_1 to DI_8
        process_batch_for_dashboard(data, panel_id)

    except json.JSONDecodeError as e:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Error decoding JSON: {e}")
    except Exception as e:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Error processing message: {e}")

def process_batch_for_dashboard(data, panel_id):
    """Process batch format payload (DI_1 to DI_8) and send to dashboard"""
    try:
        # Extract mains/batt status from payload (handle both cases)
        mains_status = data.get('mains_status', data.get('MAINS_MODE', 1))
        batt_status = data.get('batt_status', data.get('BATT_MODE', 1))

        # Iterate through DI_1 to DI_8
        for i in range(1, 9):
            di_key = f'DI_{i}'
            if di_key not in data:
                continue

            di_value = data[di_key]

            # Determine event_type based on value
            # 0 = NORMAL (inactive), 1 = ACTIVE (triggered)
            event_type = 'NORMAL' if di_value == 0 else 'ACTIVE'

            dashboard_data = {
                'device_id': panel_id,
                'panel_input': di_key,
                'event_type': event_type,
                'current': None,  # Not provided by device
                'device_status': 1,  # Assume online if sending data
                'mains_status': mains_status,
                'batt_status': batt_status,
                'reported_at': datetime.now(timezone.utc).isoformat()
            }

            print(f"  Processing {di_key}: value={di_value}, event_type={event_type}", flush=True)
            send_to_dashboard(dashboard_data)

    except Exception as e:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Error processing batch for dashboard: {e}")

def send_to_dashboard(data):
    """Send transformed data to dashboard API"""
    try:
        if not DASHBOARD_TOKEN:
            print("  [ERROR] DASHBOARD_TOKEN is empty; cannot authenticate ingest request")
            return

        headers = {
            'X-Panel-Token': DASHBOARD_TOKEN,
            'Content-Type': 'application/json'
        }

        response = requests.post(
            f"{DASHBOARD_URL.rstrip('/')}/panel-ingest",
            json=data,
            headers=headers,
            timeout=10
        )

        if response.status_code in [200, 202, 204]:
            print(f"  [OK] Data sent successfully: {response.status_code}")
        else:
            print(f"  [WARN] Dashboard API returned {response.status_code}: {response.text}")

    except requests.exceptions.ConnectionError:
        print(f"  [ERROR] Cannot connect to dashboard API at {DASHBOARD_URL}")
    except requests.exceptions.Timeout:
        print(f"  [WARN] Dashboard API timeout")
    except Exception as e:
        print(f"  [ERROR] Failed to send to dashboard: {e}")

def print_status():
    """Print current status periodically"""
    while True:
        time.sleep(300)  # Every 5 minutes
        print(f"\n[{datetime.now(timezone.utc).isoformat()}] Status Report:")
        online = len([s for s in panel_states.values() if s['online']])
        print(f"  Connected panels: {online}")
        print(f"  Total tracked: {len(panel_states)}")

        # Show recent panel states
        for panel_id, state in list(panel_states.items())[:5]:
            status = "ONLINE" if state['online'] else "OFFLINE"
            last = state.get('last_seen', 'N/A')
            print(f"    {panel_id}: {status} (last: {last})")

# Main execution
if __name__ == "__main__":
    print("="*60)
    print("Apex IoT MQTT-to-HTTP Bridge")
    print(f"MQTT Broker: {MQTT_BROKER}:{MQTT_PORT}")
    print(f"Dashboard API: {DASHBOARD_URL}")
    print("="*60)

    if not DASHBOARD_TOKEN:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Failed to start bridge: DASHBOARD_TOKEN is required")
        exit(1)

    # Create MQTT client for paho-mqtt 2.x
    client = mqtt.Client(
        mqtt.CallbackAPIVersion.VERSION2,
        client_id="apex_bridge",
        clean_session=False
    )

    # Set MQTT credentials if provided
    if MQTT_USERNAME and MQTT_PASSWORD:
        client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)

    # Set callbacks
    client.on_connect = on_connect
    client.on_message = on_message

    # Enable automatic reconnection
    client.reconnect_delay_set(min_delay=1, max_delay=120)

    # Connect to MQTT broker
    try:
        client.connect(MQTT_BROKER, MQTT_PORT, 60)
    except Exception as e:
        print(f"[{datetime.now(timezone.utc).isoformat()}] Failed to connect to MQTT broker: {e}")
        print("Make sure Mosquitto is running: sudo systemctl start mosquitto")
        exit(1)

    # Start status reporter thread
    status_thread = threading.Thread(target=print_status, daemon=True)
    status_thread.start()

    # Start MQTT loop (blocking)
    try:
        client.loop_forever()
    except KeyboardInterrupt:
        print(f"\n[{datetime.now(timezone.utc).isoformat()}] Shutting down bridge...")
        client.disconnect()
        print("Bridge stopped.")
