# Apex Fire IoT Dashboard

## Architecture

```
External MQTT Broker (43.204.150.164:1883)
        |
        v
bridge.py  [systemd: apex-bridge, runs as www-data]
  paho-mqtt + requests installed via pip3
        |
        v (HTTP POST to http://127.0.0.1/api)
nginx + PHP-FPM  [host, ports 80/443]
  root: /var/www/html/dashboard.apexenergycontrol/public
        |
        v
data/*.json  [file-based storage]

Marketing website
  nginx + PHP-FPM  [host, ports 80/443]
  root: /var/www/html/apexenergycontrol
```

- **Dashboard** served by nginx + PHP-FPM directly on the host — no containers
- **Bridge** runs as a systemd service (`apex-bridge`), subscribes to external MQTT, forwards to dashboard API
- **Website** served by nginx on the host
- **MQTT Broker** is external (43.204.150.164:1883), never local

## Quick Start (Local Dev)

```bash
# Terminal 1 — PHP dev server
HOST=127.0.0.1 PORT=8080 ./run.sh
# Dashboard: http://127.0.0.1:8080

# Terminal 2 — Bridge (needs paho-mqtt and requests installed)
pip3 install paho-mqtt requests
MQTT_BROKER=43.204.150.164 MQTT_PORT=1883 \
MQTT_USERNAME=apexusr MQTT_PASSWORD=jaijaishreeram \
MQTT_TOPICS='sensors/#,panels/#' \
DASHBOARD_URL=http://127.0.0.1:8080/api \
python3 bridge.py
```

## Server Deployment

```bash
curl -sL https://raw.githubusercontent.com/Barycentric-Dude/apex-dashboard/master/deploy.sh -o deploy.sh
chmod +x deploy.sh
sudo ./deploy.sh
```

## MQTT Credentials

| Env Var | Value |
|---------|-------|
| MQTT_BROKER | 43.204.150.164 |
| MQTT_PORT | 1883 |
| MQTT_USERNAME | apexusr |
| MQTT_PASSWORD | jaijaishreeram |

Credentials are injected via `Environment=` directives in `/etc/systemd/system/apex-bridge.service`.

## Data Files (JSON as database)

Must have these helpers in `src/Support/helpers.php`: `format_datetime()`, `h()`, `now_iso()`

| File | Purpose |
|------|---------|
| `panels.json` | `device_id`, `token`, `company_id` |
| `latest_states.json` | Current state per `panel_id` + `panel_input` |
| `alerts.json` | Open/resolved alerts |
| `users.json` | `role`: `super_admin` or `client_admin` |

## API: Panel Ingest

```bash
curl -X POST http://dashboard.apexenergycontrol.com/api/panel-ingest \
  -H "Authorization: Bearer <panel-token>" \
  -H "Content-Type: application/json" \
  -d '[{"device_id":"APX-HQ-001","panel_input":"DI_1","event_type":"NORMAL","current":5.12}]'
```

## Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Entry point + routes |
| `src/Controllers/IngestController.php` | Panel data ingest |
| `src/Controllers/DashboardController.php` | Dashboard + offline detection |
| `views/layout.php` | Global CSS (variables, components) |
| `bridge.py` | MQTT subscriber → dashboard API forwarder |

## Gotchas

- `reported_at` timestamps must be within 24 min or panel shows as offline
- Client admins only see their company's panels; `super_admin` sees all
- Bridge connects to external MQTT (43.204.150.164), not localhost
- Bridge sends data to `http://127.0.0.1/api` (nginx local loopback, avoids SSL dependency at startup)
- Website runs on host, needs PHP-FPM socket at `unix:/var/run/php/php-fpm.sock`

## Troubleshooting

```bash
# Watch bridge logs (replaces: docker logs -f apex-bridge)
sudo journalctl -u apex-bridge -f

# Restart bridge
sudo systemctl restart apex-bridge

# Bridge service status
sudo systemctl status apex-bridge

# MQTT subscribe test (requires mosquitto-clients)
mosquitto_sub -h 43.204.150.164 -p 1883 -u apexusr -P jaijaishreeram -t 'sensors/#' -v

# nginx logs
sudo tail -f /var/log/nginx/error.log

# Full debug check
./debug.sh
```

## User Management

No admin UI — edit `data/users.json` directly. Generate password hash:
```bash
php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
```
