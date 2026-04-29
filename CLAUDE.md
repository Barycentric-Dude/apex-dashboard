# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Running the App

```bash
# Local dev — PHP built-in server
./run.sh                          # http://127.0.0.1:8080
HOST=0.0.0.0 PORT=9000 ./run.sh  # custom bind

# Bridge (separate terminal, needs paho-mqtt + requests)
pip3 install paho-mqtt requests
MQTT_BROKER=43.204.150.164 MQTT_PORT=1883 \
MQTT_USERNAME=apexusr MQTT_PASSWORD=jaijaishreeram \
MQTT_TOPICS='sensors/#,panels/#' \
DASHBOARD_URL=http://127.0.0.1:8080/api \
python3 bridge.py

# Reset demo data (delete JSON files — auto-reseeded on next request)
rm data/*.json
```

**Demo accounts:** `admin@apex.local` / `ChangeMe123!` (super_admin), `ops@demo-industries.local` / `ChangeMe123!` (client_admin)

**Generate a password hash:**
```bash
php -r "echo password_hash('NewPassword', PASSWORD_BCRYPT);"
```

## Deployment

```bash
sudo ./deploy.sh   # installs nginx, PHP-FPM, python3, paho-mqtt; deploys and starts apex-bridge systemd service
sudo journalctl -u apex-bridge -f   # live bridge logs
sudo ./debug.sh                     # full health check
```

## Architecture

```
MQTT Broker (43.204.150.164:1883)
  → bridge.py [systemd: apex-bridge]
    → POST /api/panel-ingest
      → IngestController → JsonStore → data/*.json
                                     ↑
DashboardController ←────────────────┘
  GET /dashboard, /panels/{id}

AdminController ← GET/POST /admin/**
AuthController  ← GET/POST /login, /logout

website/ ← static marketing site (nginx, separate vhost)
```

**No Composer, no framework, no database.** The app is intentionally dependency-free; `src/bootstrap.php` wires everything together manually.

## Request Lifecycle

`public/index.php` is the sole entry point. It instantiates controllers, registers routes with `src/Support/Router.php` (regex-based, supports `{param}` placeholders), then calls `dispatch()`. The router calls the matched controller method with URL params as positional arguments.

Views are rendered via `app_view('template/name', $data)` which extracts `$data` into scope and `require`s `views/layout.php`, which in turn `require`s the template at `$templatePath`. Views are plain PHP — any `<style>` blocks go directly in the template body.

## Storage Layer

`JsonStore` (`src/Storage/JsonStore.php`) is the only persistence mechanism. All reads are full-file loads; all writes use `LOCK_EX`. There is no transaction support — concurrent writes can corrupt data. Key methods:

- `all($collection)` — returns full array
- `find($collection, $predicate)` — first match or null
- `filter($collection, $predicate)` — all matches
- `replace($collection, $mutator)` — read → mutate → write (use for edits and cascade deletes)
- `append($collection, $record)` — adds one record
- `nextId($prefix)` — generates `{prefix}_{8hex}`

Collections: `companies`, `users`, `panels`, `latest_states`, `alerts`, `telemetry_logs`

## Role System

- `super_admin` — full access including `/admin/**`
- `client_admin` — scoped to their `company_id`; subscription must be `active` or `internal` and not expired

`AdminController::requireAdmin()` enforces super_admin. `DashboardController::requireUser()` enforces auth + active subscription.

## Alert Rules (IngestController)

Alerts are auto-triggered and auto-resolved on every ingest. On each payload:
1. All existing `open` alerts for `(panel_id, panel_input)` are resolved
2. New alerts are created for any of: `event_type=FIRE`, `device_status=0`, `mains_status=0`, `batt_status=0`, `water_level < panel.water_level_threshold`

## Ingest API

```
POST /api/panel-ingest
Authorization: Bearer <panel-token>   (or X-Panel-Token header)
Content-Type: application/json

[{"device_id":"APX-001","panel_input":"DI_1","event_type":"NORMAL","current":5.12,
  "device_status":1,"water_level":73,"mains_status":1,"batt_status":1}]
```

Accepts single object or array. Panel is resolved by matching `device_id` against the token's registered panel. `reported_at` timestamps must be within 24 minutes or the panel shows as offline on the dashboard.

## Key Conventions

- All controller methods must call `requireAdmin()` or `requireUser()` as their first line
- Cascade deletes: when removing a company or panel, also remove its dependent records from `latest_states`, `alerts`, `telemetry_logs` using `JsonStore::replace()`
- Flash messages: set `$_SESSION['flash_success']` or `$_SESSION['flash_error']`, then `redirect_to()`. The receiving action reads and `unset()`s them
- Helpers in `src/Support/helpers.php`: `h()`, `form_value()`, `redirect_to()`, `now_iso()`, `format_datetime()`, `json_response()`, `request_json()`
- No CSRF tokens are implemented; all destructive actions use POST with `onsubmit="return confirm()"` as the only guard
