#!/usr/bin/env python3
"""
Apex IoT MQTT-to-HTTP Bridge
Subscribes to Mosquitto MQTT broker and forwards panel data to dashboard API
"""

import os
import paho.mqtt.client as mqtt
import requests
import json
import time
import threading
from datetime import datetime

# Configuration from environment
MQTT_BROKER = os.getenv("MQTT_BROKER", "127.0.0.1")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_USERNAME = os.getenv("MQTT_USERNAME", "")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD", "")
MQTT_TOPICS = os.getenv("MQTT_TOPICS", "sensors/#,panels/#").split(",")
DASHBOARD_URL = os.getenv("DASHBOARD_URL", "http://localhost:8080")
DASHBOARD_TOKEN = os.getenv("DASHBOARD_TOKEN", "")
OFFLINE_THRESHOLD = int(os.getenv("OFFLINE_THRESHOLD", "24"))  # minutes

# Panel state tracking
panel_states = {}
alerts = []

def on_connect(client, userdata, flags, rc):
    """Callback when connected to MQTT broker"""
    if rc == 0:
        print(f"[{datetime.now().isoformat()}] Connected to MQTT broker")
        # Subscribe to all sensor and panel topics
        for topic in MQTT_TOPICS:
            client.subscribe(topic)
            print(f"  Subscribed to: {topic}")
    else:
        print(f"[{datetime.now().isoformat()}] Connection failed with code {rc}")

def on_message(client, userdata, msg):
    """Callback when message received from MQTT broker"""
    try:
        # Parse MQTT payload
        data = json.loads(msg.payload.decode())
        panel_id = data.get('device_id')
        
        if not panel_id:
            print(f"[{datetime.now().isoformat()}] Warning: No device_id in message: {data}")
            return
        
        print(f"[{datetime.now().isoformat()}] Received message from {panel_id}: {msg.topic}")
        
        # Update panel state
        panel_states[panel_id] = {
            'last_seen': datetime.now().isoformat(),
            'data': data,
            'online': True
        }
        
        # Process for dashboard
        process_for_dashboard(data, panel_id, msg.topic)
        
        # Check for alerts
        check_alerts(data, panel_id)
        
    except json.JSONDecodeError as e:
        print(f"[{datetime.now().isoformat()}] Error decoding JSON: {e}")
    except Exception as e:
        print(f"[{datetime.now().isoformat()}] Error processing message: {e}")

def check_alerts(data, panel_id):
    """Generate alerts based on MQTT message content"""
    event_type = data.get('event_type', 'NORMAL')
    device_status = data.get('device_status', 1)
    
    if event_type == 'FIRE':
        alerts.append({
            'type': 'fire',
            'panel_id': panel_id,
            'panel_input': data.get('panel_input'),
            'timestamp': datetime.now().isoformat(),
            'severity': 'critical'
        })
        print(f"[{datetime.now().isoformat()}] ALERT: Fire detected on {panel_id}")
    
    if device_status == 0:
        alerts.append({
            'type': 'panel_offline',
            'panel_id': panel_id,
            'timestamp': datetime.now().isoformat(),
            'severity': 'warning'
        })
        print(f"[{datetime.now().isoformat()}] ALERT: Panel {panel_id} status changed to offline")

def process_for_dashboard(data, panel_id, topic):
    """Transform MQTT data to dashboard API format and send"""
    try:
        # Extract panel input from topic (e.g., "panels/panel_apex_1/status" -> "DI_1")
        panel_input = ""
        if '/status' in topic:
            panel_input = topic.split('/')[-2]  # Get the part before /status
        elif '/data' in topic:
            panel_input = topic.split('/')[-2]
        elif 'panel_input' in data:
            panel_input = data['panel_input']
        
        # Transform MQTT data to dashboard API format
        dashboard_data = {
            'device_id': data.get('device_id'),
            'panel_input': panel_input,
            'event_type': data.get('event_type'),
            'current': data.get('current'),
            'device_status': data.get('device_status'),
            'water_level': data.get('water_level'),
            'mains_status': data.get('mains_status'),
            'batt_status': data.get('batt_status'),
            'reported_at': data.get('reported_at', datetime.now().isoformat())
        }
        
        # Send to dashboard API
        send_to_dashboard(dashboard_data)
        
    except Exception as e:
        print(f"[{datetime.now().isoformat()}] Error processing for dashboard: {e}")

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

def check_offline_panels():
    """Periodically check for offline panels and generate alerts"""
    while True:
        try:
            now = datetime.now()
            threshold_minutes = OFFLINE_THRESHOLD
            
            for panel_id, state in list(panel_states.items()):
                if state['online']:
                    last_seen_str = state.get('last_seen')
                    if last_seen_str:
                        try:
                            last_seen = datetime.fromisoformat(last_seen_str.replace('Z', '+00:00'))
                            time_diff = (now - last_seen.replace(tzinfo=None)).total_seconds() / 60
                            
                            if time_diff > threshold_minutes:
                                state['online'] = False
                                alerts.append({
                                    'type': 'offline',
                                    'panel_id': panel_id,
                                    'timestamp': now.isoformat(),
                                    'last_seen': last_seen_str,
                                    'severity': 'warning'
                                })
                                print(f"[{datetime.now().isoformat()}] ALERT: Panel {panel_id} offline for {time_diff:.1f} minutes")
                        except (ValueError, AttributeError) as e:
                            print(f"[{datetime.now().isoformat()}] Error parsing timestamp for {panel_id}: {e}")
            
            time.sleep(60)  # Check every minute
            
        except Exception as e:
            print(f"[{datetime.now().isoformat()}] Error in offline check: {e}")
            time.sleep(60)

def print_status():
    """Print current status periodically"""
    while True:
        time.sleep(300)  # Every 5 minutes
        print(f"\n[{datetime.now().isoformat()}] Status Report:")
        print(f"  Connected panels: {len([s for s in panel_states.values() if s['online']])}")
        print(f"  Total tracked: {len(panel_states)}")
        print(f"  Active alerts: {len(alerts)}")
        
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
    print(f"Offline threshold: {OFFLINE_THRESHOLD} minutes")
    print("="*60)

    if not DASHBOARD_TOKEN:
        print(f"[{datetime.now().isoformat()}] Failed to start bridge: DASHBOARD_TOKEN is required")
        exit(1)
    
    # Create MQTT client
    client = mqtt.Client(
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
        print(f"[{datetime.now().isoformat()}] Failed to connect to MQTT broker: {e}")
        print("Make sure Mosquitto is running: sudo systemctl start mosquitto")
        exit(1)
    
    # Start offline checker thread
    offline_thread = threading.Thread(target=check_offline_panels, daemon=True)
    offline_thread.start()
    
    # Start status reporter thread
    status_thread = threading.Thread(target=print_status, daemon=True)
    status_thread.start()
    
    # Start MQTT loop (blocking)
    try:
        client.loop_forever()
    except KeyboardInterrupt:
        print(f"\n[{datetime.now().isoformat()}] Shutting down bridge...")
        client.disconnect()
        print("Bridge stopped.")
