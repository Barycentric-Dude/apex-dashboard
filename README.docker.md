# Apex IoT Dashboard - Docker Deployment

## Quick Start

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop all services
docker-compose down
```

## Services

1. **mosquitto** - MQTT broker (port 1883)
2. **dashboard** - PHP web server (port 8080)
3. **bridge** - MQTT-to-HTTP bridge service

## Configuration

### Environment Variables (bridge)

| Variable | Default | Description |
|----------|---------|-------------|
| MQTT_BROKER | mosquitto | MQTT broker hostname |
| MQTT_PORT | 1883 | MQTT broker port |
| MQTT_TOPICS | sensors/#,panels/# | Topics to subscribe |
| DASHBOARD_URL | http://dashboard:8080 | Dashboard API URL |
| DASHBOARD_TOKEN | (empty) | Bearer token for API auth |

Edit `docker-compose.yml` to configure.

### Panel Configuration

Add panels to `data/panels.json`:

```json
[
  {
    "id": "APX-HQ-001",
    "company_id": "demo",
    "device_id": "APX-HQ-001",
    "token": "panel_token_here",
    "location": "HQ Building"
  }
]
```

## Testing

```bash
# Publish test MQTT message
docker exec apex-mosquitto mosquitto_pub -t sensors/APX-HQ-001/data -m '{"device_id":"APX-HQ-001","panel_input":"DI_1","event_type":"NORMAL","current":5.12,"device_status":1,"water_level":85,"mains_status":1,"batt_status":1}'

# View dashboard
open http://localhost:8080
```

## Production Deployment

For production, use a reverse proxy (nginx/caddy) with HTTPS and update the bridge `DASHBOARD_URL`.

## Data Persistence

Data is stored in `./data/` directory on the host. Backup this folder regularly.