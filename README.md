# Apex Fire IoT Dashboard

Standalone PHP implementation of the Apex Fire IoT monitoring dashboard.

This version is designed to run in the current local environment without Composer
or database drivers. It uses JSON file storage for local development while
keeping the application structure aligned with a later MySQL deployment.

## Features

- Multi-tenant login for Apex admins and client admins
- Panel ingest API for periodic JSON payloads
- Customer dashboard with panel health, alert visibility, and latest sensor state
- Apex admin area for company, panel, user, and token setup
- Production MySQL schema included in `schema/mysql.sql`

## Local Run

1. Start the built-in PHP server from the project root:

```bash
php -S 127.0.0.1:8080 -t public
```

2. Open `http://127.0.0.1:8080`

Default demo accounts:

- Apex admin: `admin@apex.local` / `ChangeMe123!`
- Client admin: `ops@demo-industries.local` / `ChangeMe123!`

## Ingest API

Endpoint:

```text
POST /api/panel-ingest
```

Authentication:

- `Authorization: Bearer <panel-token>`
- or `X-Panel-Token: <panel-token>`

Accepted payloads:

- Single object
- Array of objects

Sample payload:

```json
[
  {
    "device_id": "APX-DEMO-001",
    "panel_input": "DI_1",
    "event_type": "NORMAL",
    "current": 5.12,
    "device_status": 1,
    "water_level": 73,
    "mains_status": 1,
    "batt_status": 1,
    "reported_at": "2026-04-12T10:00:00Z"
  }
]
```

## Notes

- Local persistence lives under `data/`
- Offline is derived when a panel has not reported within 24 minutes
- Subscription status is manually managed by Apex admins
