# Payload Specification

## Endpoint

`POST /api/panel-ingest`

## Headers

- `Authorization: Bearer <panel-token>`
- or `X-Panel-Token: <panel-token>`

## Supported body shapes

### Single record

```json
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
```

### Batch

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
  },
  {
    "device_id": "APX-DEMO-001",
    "panel_input": "DI_2",
    "event_type": "FIRE",
    "current": 5.26,
    "device_status": 1,
    "water_level": 71,
    "mains_status": 1,
    "batt_status": 1,
    "reported_at": "2026-04-12T10:00:00Z"
  }
]
```

## Field rules

- `device_id`: required string, must match the registered panel
- `panel_input`: required string, typically `DI_1` through `DI_8`
- `event_type`: required string, examples: `NORMAL`, `FAULT`, `FIRE`
- `current`: optional decimal
- `device_status`: required `1` or `0`
- `water_level`: optional decimal
- `mains_status`: required `1` or `0`
- `batt_status`: required `1` or `0`
- `reported_at`: required ISO 8601 timestamp, defaults to current server time if omitted

## Derived alerts

- `fire` when `event_type = FIRE`
- `device_offline` when `device_status = 0`
- `mains_power_lost` when `mains_status = 0`
- `battery_fault` when `batt_status = 0`
- `low_water` when `water_level` is below the panel threshold
- panel stale/offline in the dashboard when no update is received within 24 minutes
