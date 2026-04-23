#!/usr/bin/env python3
"""Test script to simulate MQTT messages from IoT panels"""

import json
import socket
import time
import sys

def send_mqtt_message(host, port, topic, payload):
    """Send a simple MQTT PUBLISH message"""
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.connect((host, port))
        
        # MQTT CONNECT packet (simplified)
        connect = b'\x10\x0a\x00\x04MQTT\x04\xc0\x00\x00'
        sock.send(connect)
        time.sleep(0.1)
        sock.recv(1024)  # CONNACK
        
        # MQTT PUBLISH packet
        message = json.dumps(payload).encode('utf-8')
        topic_bytes = topic.encode('utf-8')
        
        # Calculate remaining length
        remaining = len(topic_bytes) + len(message) + 2
        remaining_bytes = bytearray()
        while remaining > 0:
            byte = remaining % 128
            remaining //= 128
            if remaining > 0:
                byte |= 128
            remaining_bytes.append(byte)
        
        # Build PUBLISH packet
        packet = bytearray([0x30 | 0])  # PUBLISH, QoS 0, dup 0, retain 0
        packet.extend(remaining_bytes)
        packet.extend(topic_bytes)
        packet.extend(message)
        
        sock.send(packet)
        sock.close()
        return True
    except Exception as e:
        print(f"Error sending message: {e}")
        return False

def test_messages():
    """Send test MQTT messages"""
    messages = [
        {
            "device_id": "APX-HQ-001",
            "panel_input": "DI_1",
            "event_type": "NORMAL",
            "current": 5.12,
            "device_status": 1,
            "water_level": 85,
            "mains_status": 1,
            "batt_status": 1,
            "reported_at": "2026-04-22T20:00:00+05:30"
        },
        {
            "device_id": "APX-HQ-001",
            "panel_input": "DI_2",
            "event_type": "FIRE",
            "current": 4.50,
            "device_status": 1,
            "water_level": 65,
            "mains_status": 1,
            "batt_status": 1,
            "reported_at": "2026-04-22T20:01:00+05:30"
        },
        {
            "device_id": "APX-WH-001",
            "panel_input": "DI_1",
            "event_type": "NORMAL",
            "current": 3.20,
            "device_status": 1,
            "water_level": 70,
            "mains_status": 1,
            "batt_status": 1,
            "reported_at": "2026-04-22T20:02:00+05:30"
        }
    ]
    
    for msg in messages:
        print(f"Sending: {msg['device_id']} - {msg['event_type']}")
        success = send_mqtt_message('127.0.0.1', 1883, 'sensors/data', msg)
        if success:
            print("  ✓ Message sent successfully")
        else:
            print("  ✗ Failed to send message")
        time.sleep(2)

if __name__ == "__main__":
    print("Testing MQTT message simulation...")
    test_messages()
    print("\nTest complete!")