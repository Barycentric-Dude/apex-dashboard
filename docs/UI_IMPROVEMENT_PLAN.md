# Apex Fire Monitoring - UI Improvement Plan

## Executive Summary

This plan addresses critical accessibility issues, visual hierarchy problems, and UX gaps identified in the Apex Fire IoT monitoring dashboard. The improvements are prioritized by impact and organized into actionable phases.

---

## Priority Matrix

| Priority | Issue | Impact | Effort |
|----------|-------|--------|--------|
| **P0** | Accessibility: focus states, labels, landmarks | CRITICAL - Legal compliance, screen reader support | Low |
| **P0** | Token exposure in admin tables | CRITICAL - Security risk | Low |
| **P1** | Visual hierarchy: critical states prominence | HIGH - Operational efficiency | Medium |
| **P1** | Date/time formatting | HIGH - Readability | Low |
| **P2** | Empty states | MEDIUM - User guidance | Low |
| **P2** | Form accessibility | MEDIUM - Usability | Low |
| **P3** | Panel detail redesign | MEDIUM - Information density | Medium |

---

## Phase 1: Critical Fixes (P0)

### 1.1 Accessibility Shell Improvements

**File:** `views/layout.php`

#### Add Skip Link
```html
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="shell">
```

```css
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: var(--accent);
    color: white;
    padding: 8px 16px;
    z-index: 100;
    transition: top 0.2s;
}
.skip-link:focus {
    top: 0;
}
```

#### Add Main Landmark
```html
<main id="main-content" role="main">
    <?php require $templatePath; ?>
</main>
```

#### Add Focus-Visible Styles
```css
/* Add to :root */
:root {
    --focus-ring: 0 0 0 3px rgba(207, 92, 54, 0.4);
}

/* Global focus styles */
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

/* Stronger nav link hover */
.nav a {
    padding: 4px 8px;
    border-radius: 6px;
    transition: background 0.15s, color 0.15s;
}
.nav a:hover {
    background: var(--accent-soft);
    color: var(--accent);
}
.nav a[aria-current="page"] {
    background: var(--accent);
    color: white;
}
```

#### Add Active Nav State
```php
<nav class="nav">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/dashboard" <?= ($_SERVER['REQUEST_URI'] === '/dashboard') ? 'aria-current="page"' : '' ?>>Dashboard</a>
        <?php if (($user['role'] ?? null) === 'super_admin'): ?>
            <a href="/admin" <?= strpos($_SERVER['REQUEST_URI'], '/admin') === 0 ? 'aria-current="page"' : '' ?>>Admin</a>
        <?php endif; ?>
        <!-- ... -->
    <?php endif; ?>
</nav>
```

### 1.2 Token Masking in Admin Panel

**File:** `views/admin/index.php`

#### Replace Raw Token Display
```php
<td>
    <code class="token-mask" data-token="<?= h($panel['token']) ?>">
        ••••••••••••
    </code>
    <button type="button" class="toggle-token" data-target="<?= h($panel['id']) ?>">
        <svg class="icon-eye"><!-- eye icon --></svg>
    </button>
    <button type="button" class="copy-token" data-token="<?= h($panel['token']) ?>">
        <svg class="icon-copy"><!-- copy icon --></svg>
    </button>
</td>
```

```css
.token-mask {
    font-family: monospace;
    background: var(--surface-strong);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
}
.toggle-token, .copy-token {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    margin-left: 4px;
}
.toggle-token:hover, .copy-token:hover {
    background: var(--accent-soft);
    border-radius: 4px;
}
```

```javascript
// Add inline script or external file
document.querySelectorAll('.toggle-token').forEach(btn => {
    btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        const mask = row.querySelector('.token-mask');
        const isHidden = mask.textContent.includes('•');
        mask.textContent = isHidden ? mask.dataset.token : '••••••••••••';
    });
});
```

---

## Phase 2: High Priority (P1)

### 2.1 Visual Hierarchy for Critical States

**File:** `views/dashboard/index.php`

#### Redesign KPI Cards with State Awareness
```php
<section class="grid kpi-grid">
    <!-- Critical: Fire Events - Prominent -->
    <div class="card kpi-card kpi-critical">
        <div class="kpi-icon">
            <svg><!-- fire icon --></svg>
        </div>
        <div class="kpi-content">
            <div class="label">Fire Events</div>
            <div class="metric"><?= h((string) $stats['fire_events']) ?></div>
        </div>
        <?php if ($stats['fire_events'] > 0): ?>
            <div class="kpi-alert-badge">ACTION REQUIRED</div>
        <?php endif; ?>
    </div>

    <!-- Critical: Offline Panels - Prominent -->
    <div class="card kpi-card kpi-danger">
        <div class="kpi-icon">
            <svg><!-- offline icon --></svg>
        </div>
        <div class="kpi-content">
            <div class="label">Offline Panels</div>
            <div class="metric"><?= h((string) $stats['offline_panels']) ?></div>
        </div>
    </div>

    <!-- Warning: Open Alerts -->
    <div class="card kpi-card kpi-warning">
        <div class="kpi-icon">
            <svg><!-- alert icon --></svg>
        </div>
        <div class="kpi-content">
            <div class="label">Open Alerts</div>
            <div class="metric"><?= h((string) $stats['open_alerts']) ?></div>
        </div>
    </div>

    <!-- Healthy: Online Panels - Visually quieter -->
    <div class="card kpi-card kpi-healthy">
        <div class="kpi-icon">
            <svg><!-- check icon --></svg>
        </div>
        <div class="kpi-content">
            <div class="label">Online Panels</div>
            <div class="metric"><?= h((string) $stats['online_panels']) ?></div>
        </div>
    </div>

    <!-- Neutral: Total Panels -->
    <div class="card kpi-card kpi-neutral">
        <div class="kpi-content">
            <div class="label">Total Panels</div>
            <div class="metric"><?= h((string) $stats['total_panels']) ?></div>
        </div>
    </div>
</section>
```

```css
/* KPI Card Styling */
.kpi-grid {
    grid-template-columns: repeat(5, 1fr);
}

.kpi-card {
    position: relative;
    padding: 20px;
    border-left: 4px solid transparent;
}

.kpi-card.kpi-critical {
    border-left-color: #c53030;
    background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
}
.kpi-card.kpi-critical .metric { color: #c53030; }

.kpi-card.kpi-danger {
    border-left-color: #dd6b20;
    background: linear-gradient(135deg, #fffaf0 0%, #fff 100%);
}
.kpi-card.kpi-danger .metric { color: #dd6b20; }

.kpi-card.kpi-warning {
    border-left-color: #b7791f;
    background: linear-gradient(135deg, #fffff0 0%, #fff 100%);
}
.kpi-card.kpi-warning .metric { color: #b7791f; }

.kpi-card.kpi-healthy {
    border-left-color: #2f855a;
    background: linear-gradient(135deg, #f0fff4 0%, #fff 100%);
}
.kpi-card.kpi-healthy .metric { color: #2f855a; }

.kpi-card.kpi-neutral {
    border-left-color: #a0aec0;
}

.kpi-alert-badge {
    position: absolute;
    top: -8px;
    right: 12px;
    background: #c53030;
    color: white;
    font-size: 0.65rem;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 4px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.kpi-icon svg {
    width: 24px;
    height: 24px;
    opacity: 0.6;
}

/* Tabular numbers for metrics */
.metric {
    font-variant-numeric: tabular-nums;
}
```

### 2.2 Date/Time Formatting

**File:** `src/Support/helpers.php`

Add a helper function:
```php
function format_datetime(?string $iso, bool $relative = true): string {
    if (!$iso) return 'Never';

    $dt = new DateTime($iso);
    $now = new DateTime();
    $diff = $now->diff($dt);

    if ($relative) {
        if ($diff->days === 0) {
            if ($diff->h === 0) {
                return $diff->i . ' min ago';
            }
            return $diff->h . ' hr ' . $diff->i . ' min ago';
        }
        if ($diff->days === 1) return 'Yesterday';
        if ($diff->days < 7) return $diff->days . ' days ago';
    }

    return $dt->format('M j, Y g:i A');
}
```

Apply in views:
```php
<!-- Before -->
<div class="muted">Last report: <?= h($item['last_reported_at'] ?? 'Never') ?></div>

<!-- After -->
<div class="muted">Last report: <?= format_datetime($item['last_reported_at'] ?? null) ?></div>
```

---

## Phase 3: Medium Priority (P2)

### 3.1 Empty States

**File:** `views/dashboard/index.php`

```php
<section class="panel-list">
    <?php if (empty($panelCards)): ?>
        <div class="empty-state">
            <svg class="empty-icon"><!-- panel icon --></svg>
            <h3>No panels configured</h3>
            <p>Contact your administrator to subscribe fire panels to your account.</p>
        </div>
    <?php else: ?>
        <?php foreach ($panelCards as $item): ?>
            <!-- existing panel items -->
        <?php endforeach; ?>
    <?php endif; ?>
</section>
```

```css
.empty-state {
    text-align: center;
    padding: 48px 24px;
    background: var(--surface);
    border: 2px dashed var(--border);
    border-radius: 18px;
}
.empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    opacity: 0.4;
}
.empty-state h3 {
    margin: 0 0 8px;
    color: var(--text);
}
.empty-state p {
    color: var(--muted);
    max-width: 320px;
    margin: 0 auto;
}
```

### 3.2 Form Accessibility

**File:** `views/admin/index.php`

```php
<!-- Before -->
<input type="text" name="name" placeholder="Company name" required>

<!-- After -->
<div class="form-row">
    <label for="company-name">Company name</label>
    <input type="text" id="company-name" name="name" placeholder="e.g., Acme Corp..." autocomplete="off" required>
    <span class="form-hint">The legal business name</span>
</div>
```

```css
.form-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.form-row label {
    font-weight: 500;
    font-size: 0.9rem;
}
.form-hint {
    font-size: 0.8rem;
    color: var(--muted);
}
```

### 3.3 Panel Card Hover States

**File:** `views/dashboard/index.php`

```css
.panel-item {
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}
.panel-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(20, 33, 61, 0.12);
    border-color: var(--accent);
}
.panel-item:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring), var(--shadow);
}
```

---

## Phase 4: Panel Detail Redesign (P3)

### 4.1 Status Summary Header

**File:** `views/dashboard/panel.php`

Add a summary row before tables:

```php
<section class="panel-summary">
    <div class="summary-grid">
        <div class="summary-card <?= $mainsStatus ? 'summary-ok' : 'summary-warn' ?>">
            <div class="summary-label">Mains</div>
            <div class="summary-value"><?= $mainsStatus ? 'ON' : 'OFF' ?></div>
        </div>
        <div class="summary-card <?= $battStatus ? 'summary-ok' : 'summary-warn' ?>">
            <div class="summary-label">Battery</div>
            <div class="summary-value"><?= $battStatus ? 'OK' : 'LOW' ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Water Level</div>
            <div class="summary-value"><?= h($waterLevel) ?>%</div>
            <?php if ($waterLevel < 30): ?>
                <div class="summary-alert">Below threshold</div>
            <?php endif; ?>
        </div>
        <div class="summary-card <?= $openFaults > 0 ? 'summary-danger' : 'summary-ok' ?>">
            <div class="summary-label">Open Faults</div>
            <div class="summary-value"><?= h($openFaults) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Last Report</div>
            <div class="summary-value"><?= format_datetime($lastReportedAt) ?></div>
            <div class="summary-freshness <?= $isOffline ? 'stale' : 'fresh' ?>">
                <?= $isOffline ? 'Stale data' : 'Fresh' ?>
            </div>
        </div>
    </div>
</section>
```

```css
.panel-summary {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}
.summary-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}
.summary-card {
    text-align: center;
    padding: 16px;
    border-radius: 12px;
    background: var(--bg);
}
.summary-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted);
}
.summary-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 8px 0 4px;
    font-variant-numeric: tabular-nums;
}
.summary-ok .summary-value { color: var(--success); }
.summary-warn .summary-value { color: var(--warning); }
.summary-danger .summary-value { color: var(--danger); }
.summary-alert {
    font-size: 0.75rem;
    color: var(--danger);
}
```

### 4.2 Split Alerts Section

```php
<div class="two-col">
    <div class="card">
        <div class="label">Open Alerts</div>
        <?php if (empty($openAlerts)): ?>
            <div class="empty-state-mini">No open alerts</div>
        <?php else: ?>
            <table><!-- open alerts --></table>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="label">Alert History (Last 7 days)</div>
        <?php if (empty($alertHistory)): ?>
            <div class="empty-state-mini">No recent alerts</div>
        <?php else: ?>
            <table><!-- alert history --></table>
        <?php endif; ?>
    </div>
</div>
```

### 4.3 JSON Payload Handling

```php
<td class="payload-cell">
    <button class="toggle-payload" data-expanded="false">View</button>
    <div class="payload-preview"><?= h(substr(json_encode($log['payload']), 0, 50)) ?>...</div>
    <pre class="payload-full hidden"><?= h(json_encode($log['payload'], JSON_PRETTY_PRINT)) ?></pre>
</td>
```

```css
.payload-cell { max-width: 200px; }
.payload-preview {
    font-family: monospace;
    font-size: 0.8rem;
    color: var(--muted);
}
.payload-full {
    font-family: monospace;
    font-size: 0.75rem;
    background: var(--bg);
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    margin-top: 8px;
}
.payload-full.hidden { display: none; }
```

---

## Phase 5: Login Enhancement

**File:** `views/auth/login.php`

### 5.1 Autocomplete Attributes
```php
<input type="email" name="email" autocomplete="username" required>
<input type="password" name="password" autocomplete="current-password" required>
```

### 5.2 Stronger Action Button
```php
<button type="submit">Sign In to Dashboard</button>
```

### 5.3 Trust Signals
```php
<section class="hero login-hero">
    <div class="label">Client and Apex access</div>
    <h1>Monitor fire panel health across subscribed sites.</h1>

    <div class="trust-badges">
        <div class="trust-badge">
            <svg><!-- shield icon --></svg>
            <span>End-to-end encrypted</span>
        </div>
        <div class="trust-badge">
            <svg><!-- clock icon --></svg>
            <span>Real-time updates</span>
        </div>
        <div class="trust-badge">
            <svg><!-- check icon --></svg>
            <span>99.9% uptime SLA</span>
        </div>
    </div>

    <!-- form -->
</section>
```

---

## Implementation Order

1. **Week 1:** Phase 1 (Critical Fixes)
   - Skip link, main landmark, focus states
   - Token masking
   - Active nav state

2. **Week 2:** Phase 2 (High Priority)
   - KPI card redesign with hierarchy
   - Date/time formatting helper
   - Apply formatting to all views

3. **Week 3:** Phase 3 (Medium Priority)
   - Empty states for all lists
   - Form accessibility improvements
   - Panel card hover states

4. **Week 4:** Phase 4 (Panel Detail)
   - Summary header redesign
   - Split alerts section
   - JSON payload handling

---

## Files to Modify

| File | Changes |
|------|---------|
| `views/layout.php` | Skip link, main landmark, focus styles, nav active state |
| `views/dashboard/index.php` | KPI hierarchy, empty states, hover states |
| `views/dashboard/panel.php` | Summary header, split alerts, payload handling |
| `views/admin/index.php` | Token masking, form labels, empty states |
| `views/auth/login.php` | Autocomplete, trust signals |
| `src/Support/helpers.php` | `format_datetime()` helper |

---

## Testing Checklist

- [ ] Keyboard navigation: Tab order matches visual order
- [ ] Screen reader: All landmarks announced correctly
- [ ] Focus visible: All interactive elements show focus ring
- [ ] Color contrast: 4.5:1 minimum for all text
- [ ] Empty states: All lists show guidance when empty
- [ ] Mobile: All views usable at 375px width
- [ ] Reduced motion: Animations respect `prefers-reduced-motion`