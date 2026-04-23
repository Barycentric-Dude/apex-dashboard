# Apex Fire IoT Dashboard

## Architecture

```
MQTT Broker (43.204.150.164:1883) → bridge.py → Dashboard API (Docker) → JSON files
                                              ↳ website/ (nginx on host)
```

- **Dashboard** runs in Docker container (`apex-dashboard`), port 8081 internally
- **Bridge** runs in Docker container (`apex-bridge`), subscribes to MQTT, forwards to API
- **Website** runs on host via nginx (not in Docker)
- **MQTT Broker** is external, not containerized

## Quick Start (Local Dev)

```bash
docker-compose up -d --build
```

- Dashboard: http://127.0.0.1:8081
- API: http://127.0.0.1:8081/api/panel-ingest

## Server Deployment

```bash
# Use deploy.sh (auto-deploy with checks)
curl -sL https://raw.githubusercontent.com/Barycentric-Dude/apex-dashboard/master/deploy.sh -o deploy.sh
chmod +x deploy.sh
sudo ./deploy.sh

# Verify after deploy
./debug.sh
```

## MQTT Credentials

| Env Var | Value |
|---------|-------|
| MQTT_BROKER | 43.204.150.164 |
| MQTT_PORT | 1883 |
| MQTT_USERNAME | apexusr |
| MQTT_PASSWORD | jaijaishreeram |

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
curl -X POST http://127.0.0.1:8081/api/panel-ingest \
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

## Gotchas

- `reported_at` timestamps must be within 24 min or panel shows as offline
- Client admins only see their company's panels; `super_admin` sees all
- Bridge connects to external MQTT (not localhost)
- Website runs on host, needs PHP-FPM installed on server

## Troubleshooting

```bash
# Watch bridge logs
sudo docker logs -f apex-bridge

# MQTT subscribe test
mosquitto_sub -h 43.204.150.164 -p 1883 -u apexusr -P jaijaishreeram -t sensors/# -v

# Full debug check
./debug.sh
```

## User Management

No admin UI - edit `data/users.json` directly. Generate password hash:
```bash
php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
```