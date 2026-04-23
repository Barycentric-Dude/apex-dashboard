#!/bin/bash
# Demo workflow for Apex IoT Bridge

echo "=========================================="
echo "Apex IoT Bridge - Demo Workflow"
echo "=========================================="
echo ""

echo "Step 1: Verify data files exist"
echo "--------------------------------"
for file in data/panels.json data/latest_states.json data/alerts.json; do
    if [ -f "$file" ]; then
        echo "✓ $file"
    else
        echo "✗ $file MISSING"
    fi
done
echo ""

echo "Step 2: Verify bridge script"
echo "--------------------------------"
if [ -f "bridge.py" ] && [ -x "bridge.py" ]; then
    echo "✓ bridge.py exists and is executable"
else
    echo "✗ bridge.py missing or not executable"
fi
echo ""

echo "Step 3: Verify PHP dashboard"
echo "--------------------------------"
if [ -f "public/index.php" ]; then
    echo "✓ Dashboard entry point exists"
fi
if [ -f "src/Controllers/TelemetryController.php" ]; then
    echo "✓ TelemetryController exists"
fi
echo ""

echo "Step 4: Sample MQTT message format"
echo "--------------------------------"
python3 << 'PYEOF'
import json
sample = {
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
print(json.dumps(sample, indent=2))
PYEOF
echo ""

echo "Step 5: Dashboard statistics"
echo "--------------------------------"
python3 << 'PYEOF'
import json

with open('data/panels.json') as f:
    panels = json.load(f)
with open('data/latest_states.json') as f:
    states = json.load(f)
with open('data/alerts.json') as f:
    alerts = json.load(f)

print(f"Panels: {len(panels)}")
print(f"Latest States: {len(states)}")
print(f"Alerts: {len(alerts)}")
PYEOF
echo ""

echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "To start the bridge:"
echo "  $ python3 bridge.py"
echo ""
echo "To start the dashboard:"
echo "  $ php -S 127.0.0.1:8080 -t public"
echo ""
echo "The bridge will:"
echo "  • Connect to MQTT broker on port 1883"
echo "  • Subscribe to all panel sensor topics"
echo "  • Forward data to dashboard API"
echo "  • Detect offline panels (>24 min)"
echo "  • Generate alerts for fire events"
