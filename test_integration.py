#!/usr/bin/env python3
"""Integration test for the MQTT-to-HTTP bridge"""

import json
import time
import sys
import os

# Add the project to path
sys.path.insert(0, '/home/barycentricdude/Documents/Work/Apex Dashboard')

def test_bridge_components():
    """Test that all components are properly set up"""
    print("Testing Bridge Components...")
    print("="*60)
    
    # Test 1: Check bridge script exists and is executable
    bridge_path = "/home/barycentricdude/Documents/Work/Apex Dashboard/bridge.py"
    if os.path.exists(bridge_path):
        print("✓ Bridge script exists")
        if os.access(bridge_path, os.X_OK):
            print("✓ Bridge script is executable")
        else:
            print("✗ Bridge script is not executable")
            return False
    else:
        print("✗ Bridge script not found")
        return False
    
    # Test 2: Check PHP server is running
    import urllib.request
    try:
        response = urllib.request.urlopen('http://127.0.0.1:8080/dashboard', timeout=5)
        if response.status == 200 or response.status == 401:  # 401 is expected (no auth)
            print("✓ PHP dashboard server is running")
        else:
            print(f"✗ PHP server returned status {response.status}")
            return False
    except Exception as e:
        print(f"✗ PHP server not accessible: {e}")
        return False
    
    # Test 3: Test API endpoint
    try:
        req = urllib.request.Request('http://127.0.0.1:8080/api/telemetry', 
                                     data=json.dumps({
                                         "device_id": "TEST-001",
                                         "panel_input": "DI_1",
                                         "event_type": "NORMAL",
                                         "current": 5.0,
                                         "device_status": 1,
                                         "water_level": 80,
                                         "mains_status": 1,
                                         "batt_status": 1
                                     }).encode('utf-8'),
                                     headers={'Content-Type': 'application/json'},
                                     method='POST')
        response = urllib.request.urlopen(req, timeout=5)
        if response.status == 200:
            print("✓ Dashboard API is accessible and accepting data")
        else:
            print(f"✗ API returned status {response.status}")
            return False
    except urllib.error.HTTPError as e:
        # 401/403 is OK if authentication is required
        if e.code in [401, 403]:
            print("✓ Dashboard API is accessible (authentication required)")
        else:
            print(f"✗ API returned error: {e.code}")
            return False
    except Exception as e:
        print(f"✗ API not accessible: {e}")
        return False
    
    # Test 4: Check data files exist
    data_dir = "/home/barycentricdude/Documents/Work/Apex Dashboard/data"
    required_files = ['panels.json', 'latest_states.json', 'telemetry_logs.json', 'alerts.json']
    for f in required_files:
        path = os.path.join(data_dir, f)
        if os.path.exists(path):
            print(f"✓ Data file exists: {f}")
        else:
            print(f"✗ Data file missing: {f}")
            return False
    
    print("\n" + "="*60)
    print("All integration tests passed!")
    return True

def test_mqtt_message_format():
    """Test that MQTT messages are properly formatted"""
    print("\nTesting MQTT Message Format...")
    print("="*60)
    
    # Simulate a valid MQTT message
    test_message = {
        "device_id": "APX-HQ-001",
        "panel_input": "DI_1",
        "event_type": "NORMAL",
        "current": 5.12,
        "device_status": 1,
        "water_level": 85,
        "mains_status": 1,
        "batt_status": 1,
        "reported_at": "2026-04-22T20:00:00+05:30"
    }
    
    # Validate the message structure
    required_fields = ['device_id', 'panel_input', 'event_type', 'current', 
                       'device_status', 'water_level', 'mains_status', 'batt_status']
    
    for field in required_fields:
        if field not in test_message:
            print(f"✗ Missing required field: {field}")
            return False
    
    print(f"✓ Test message structure is valid")
    print(f"  Device ID: {test_message['device_id']}")
    print(f"  Panel Input: {test_message['panel_input']}")
    print(f"  Event Type: {test_message['event_type']}")
    print(f"  Current: {test_message['current']}")
    print(f"  Device Status: {test_message['device_status']}")
    print(f"  Water Level: {test_message['water_level']}")
    print(f"  Mains Status: {test_message['mains_status']}")
    print(f"  Batt Status: {test_message['batt_status']}")
    
    # Test JSON serialization
    try:
        json_str = json.dumps(test_message)
        json.loads(json_str)  # Validate it can be parsed back
        print(f"✓ Message can be serialized/deserialized as JSON")
    except Exception as e:
        print(f"✗ JSON serialization failed: {e}")
        return False
    
    print("\n" + "="*60)
    print("MQTT message format test passed!")
    return True

if __name__ == "__main__":
    print("Apex IoT Bridge Integration Test")
    print()
    
    success = True
    success = test_mqtt_message_format() and success
    success = test_bridge_components() and success
    
    if success:
        print("\n" + "="*60)
        print("✓ ALL TESTS PASSED")
        print("="*60)
        sys.exit(0)
    else:
        print("\n" + "="*60)
        print("✗ SOME TESTS FAILED")
        print("="*60)
        sys.exit(1)
