# Apex Fire IoT Dashboard - Project Brief

## Overview

Apex is a multi-tenant fire panel monitoring dashboard for IoT-connected fire safety systems. It provides real-time monitoring of fire panels, sensor states, water levels, power status, and automated alert management across multiple client companies.

---

## Credentials

### Web Interface

| Role | Email | Password |
|------|-------|----------|
| Apex Admin (super_admin) | `admin@apex.local` | `ChangeMe123!` |
| Client Admin | `ops@demo-industries.local` | `ChangeMe123!` |

### API Authentication

Panels authenticate via Bearer token in the `Authorization` header or `X-Panel-Token` header.

| Panel | Token |
|-------|-------|
| APX-HQ-001 | `panel_token_hq001` |
| APX-HQ-002 | `panel_token_hq002` |
| APX-PLANT-001 | `panel_token_plant001` |

---

## Feature Set

### Core Functionality

- [x] Multi-tenant architecture with company-based data isolation
- [x] Role-based access control (super_admin / client_admin)
- [x] Bearer token authentication for panel API
- [x] Session-based authentication for web interface
- [x] Real-time telemetry ingestion from IoT fire panels
- [x] Automated alert generation and resolution
- [x] Offline panel detection (24-minute threshold)
- [x] Subscription management with panel limits
- [x] Telemetry logging and historical tracking

### Dashboard Features

- [x] KPI cards: Fire Events, Offline Panels, Open Alerts, Online Panels, Total Panels
- [x] Panel list with search and status filtering
- [x] Panel detail view with full sensor state
- [x] Mains power status monitoring
- [x] Battery status monitoring
- [x] Water level monitoring with threshold warnings
- [x] Input states table (DI_1, DI_2, DI_3)
- [x] Open alerts display
- [x] Alert history
- [x] Recent telemetry logs
- [x] Token management (show/hide/copy)

### Admin Features

- [x] Create companies with subscription management
- [x] Create users with role assignment
- [x] Register panels with device ID and token
- [x] View all companies, users, and panels

### API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Redirect to dashboard |
| GET | `/login` | Display login form |
| POST | `/login` | Process login |
| POST | `/logout` | Destroy session |
| GET | `/dashboard` | Main dashboard |
| GET | `/panels/{id}` | Panel detail view |
| GET | `/admin` | Admin dashboard (super_admin only) |
| POST | `/admin/companies` | Create company |
| POST | `/admin/users` | Create user |
| POST | `/admin/panels` | Register panel |
| POST | `/api/panel-ingest` | Panel telemetry ingestion |

---

## Data Models

### Companies
- id, name, slug, subscription_status, subscription_ends_at, panel_limit, created_at

### Users
- id, company_id, name, email, password_hash, role, created_at

### Panels
- id, company_id, name, site_name, device_id, token, water_level_threshold, reporting_interval_minutes, created_at

### Latest States
- id, panel_id, panel_input, event_type, current, device_status, water_level, mains_status, batt_status, reported_at, updated_at

### Alerts
- id, panel_id, panel_input, type, status, message, reported_at, updated_at

### Telemetry Logs
- id, panel_id, payload, received_at

---

## Technology Stack

- **Backend:** PHP 8.x (native, no framework)
- **Database:** JSON file storage (development) / MySQL (production schema provided)
- **Frontend:** Vanilla HTML/CSS/JS with CSS custom properties
- **Authentication:** bcrypt passwords, session-based web auth, Bearer tokens for API

---

## Next Build Phases

### Phase 1: Notifications & Escalation
- [ ] Email alert notifications via SMTP
- [ ] Push notifications (web push API)
- [ ] SMS alerts via Twilio/MessageBird
- [ ] Configurable alert escalation rules
- [ ] Alert acknowledgment workflow

### Phase 2: Analytics & Reporting
- [ ] Historical trends charts (water level, mains failures)
- [ ] Alert frequency reports per company/panel
- [ ] Panel uptime statistics
- [ ] Export reports to PDF/CSV
- [ ] Scheduled email reports

### Phase 3: Enhanced Security
- [ ] Two-factor authentication (TOTP)
- [ ] API key management for clients
- [ ] Audit logging for admin actions
- [ ] IP allowlisting for panel API
- [ ] Rate limiting on ingest endpoint

### Phase 4: Mobile & UI Polish
- [ ] PWA with offline support
- [ ] Native mobile app (React Native)
- [ ] Dark mode toggle
- [ ] Dashboard customization (drag-drop widgets)
- [ ] Real-time WebSocket updates

### Phase 5: Advanced Features
- [ ] Panel firmware update notifications
- [ ] Predictive maintenance alerts (ML-based)
- [ ] Integration APIs for third-party systems
- [ ] Multi-language support (i18n)
- [ ] SAML/OIDC SSO for enterprise clients

---

## Testing Requirements

### Unit Tests

| Test Suite | Coverage |
|------------|----------|
| JsonStore CRUD operations | Create, read, update, delete for all entities |
| Router parameter extraction | Path matching, parameter parsing |
| Alert generation logic | All alert types and resolution conditions |
| Authentication flow | Login validation, session handling |
| Authorization checks | Role-based access enforcement |

### Integration Tests

| Test | Expected Behavior |
|------|-------------------|
| Panel ingest flow | Token validation, state update, alert sync |
| Dashboard data loading | Correct filtering by company for client_admin |
| Admin CRUD operations | Company/user/panel creation with validation |
| Alert auto-resolution | Alert resolves when condition clears |

### Manual Testing Checklist

**Authentication:**
- [ ] Login with valid credentials succeeds
- [ ] Login with invalid credentials shows error
- [ ] Logout destroys session
- [ ] Client admin cannot access /admin
- [ ] Session expires after inactivity

**Panel Monitoring:**
- [ ] New panel ingest creates latest_state record
- [ ] Duplicate ingest updates existing state
- [ ] Panel shows offline after 24 minutes without data
- [ ] Water level warning at threshold

**Alerting:**
- [ ] FIRE event type generates open alert
- [ ] Low water generates open alert
- [ ] Mains power loss generates open alert
- [ ] Alert auto-resolves on condition clear
- [ ] Alert history persists after resolution

**Admin:**
- [ ] Create company with valid data
- [ ] Create user with valid data
- [ ] Register panel with valid data
- [ ] Panel limit enforced

---

## API Testing (curl)

```bash
# Ingest panel data
curl -X POST http://127.0.0.1:8080/api/panel-ingest \
  -H "Authorization: Bearer panel_token_hq001" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "APX-HQ-001",
    "panel_input": "DI_1",
    "event_type": "NORMAL",
    "current": 5.12,
    "device_status": 1,
    "water_level": 85,
    "mains_status": 1,
    "batt_status": 1
  }'
```

---

## Local Development

```bash
# Start server
php -S 127.0.0.1:8080 -t public

# URL
http://127.0.0.1:8080
```

---

*Document Version: 1.0*  
*Last Updated: April 2026*
