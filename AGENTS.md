# Apex Fire IoT Dashboard

## Run Locally

```bash
cd /home/bary/Documents/KWS\ OS/projects/Apex
php -S 127.0.0.1:8080 -t public
```

**URL:** http://127.0.0.1:8080

## Docker Deployment

See `docker-compose.yml` for service configuration.

## Auth Credentials

| Role | Email | Password |
|------|-------|----------|
| Apex Admin | `admin@apex.local` | `ChangeMe123!` |
| Client Admin | `ops@demo-industries.local` | `ChangeMe123!` |

## MQTT Credentials

Edit these files to update credentials:

| File | What to Change |
|------|---------------|
| `mosquitto.passwd` | Add user:pass pairs (format: `user:password`) |
| `docker-compose.yml` | `MQTT_USERNAME` and `MQTT_PASSWORD` in bridge service |

Generate password file (outside Docker):
```bash
mosquitto_passwd -U mosquitto.passwd
```

## Data Files (`data/`)

JSON files are the database. Must have these functions in `src/Support/helpers.php`:
- `format_datetime(?string $iso): string` - formats ISO timestamps
- `h(?string $value): string` - HTML escape
- `now_iso(): string` - current UTC timestamp

| File | Purpose |
|------|---------|
| `companies.json` | Tenant companies with subscription status |
| `panels.json` | Fire panels with `company_id`, `device_id`, `token` |
| `latest_states.json` | Current sensor state per `panel_id` + `panel_input` |
| `alerts.json` | Open/resolved alerts with `type`, `status`, `panel_input` |
| `users.json` | Users with `company_id`, `role` (super_admin/client_admin) |
| `telemetry_logs.json` | Ingest history |

## Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Entry point + route registration |
| `src/Support/Router.php` | Simple path-based router |
| `src/Controllers/DashboardController.php` | Dashboard logic + offline detection |
| `src/Controllers/IngestController.php` | Panel data ingest API |
| `views/layout.php` | Global CSS (variables, components, responsive) |

## API: Panel Ingest

```bash
curl -X POST http://127.0.0.1:8080/api/panel-ingest \
  -H "Authorization: Bearer <panel-token>" \
  -H "Content-Type: application/json" \
  -d '[{"device_id":"APX-HQ-001","panel_input":"DI_1","event_type":"NORMAL","current":5.12,"device_status":1,"water_level":85,"mains_status":1,"batt_status":1}]'
```

## Design System

- **Fonts:** Georgia serif (body), ui-monospace (device IDs, tokens)
- **Status colors:** Green=ok, Amber=warn, Orange=danger, Red=critical
- **Icons:** Inline SVG only (no emojis)
- **Breakpoints:** 640px, 860px, 1024px

## Gotchas

- `reported_at` timestamps must be recent (within 24 min) or panels show as offline
- Alerts auto-resolve on next ingest if condition clears
- Client admins only see their company's panels; super_admin sees all

## Docker Deployment

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Test MQTT message
docker exec apex-mosquitto mosquitto_pub \
  -t sensors/APX-HQ-001/data \
  -m '{"device_id":"APX-HQ-001","panel_input":"DI_1","event_type":"NORMAL","current":5.12,"device_status":1,"water_level":85,"mains_status":1,"batt_status":1}'
```

**Services:** `apex-mosquitto` (1883), `apex-dashboard` (8080), `apex-bridge`

## MQTT Bridge (`bridge.py`)

Environment variables:
- `MQTT_BROKER` - default `mosquitto`
- `MQTT_PORT` - default `1883`
- `DASHBOARD_URL` - default `http://dashboard:8080/api`
- `MQTT_TOPICS` - comma-separated, default `sensors/#,panels/#`

Subscribes to MQTT → forwards to `/api/telemetry` endpoint.

## Server Deployment

```bash
# 1. Copy files to server
scp -r . user@your-server:/opt/apex/

# 2. SSH and start
ssh user@your-server
cd /opt/apex
docker-compose up -d --build
```

Optional HTTPS: Use Caddy or nginx as reverse proxy.

## User Management

**Users are added manually** - no admin UI. Edit `data/users.json`:

Generate password hash:
```bash
php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
```

**Roles:** `super_admin` (all panels), `client_admin` (own company only)
