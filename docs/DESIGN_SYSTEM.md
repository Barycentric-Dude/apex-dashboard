# Apex Fire Monitoring - Design System

## Product Profile

- **Type:** Monitoring Dashboard / IoT Platform
- **Industry:** Fire Safety / Industrial IoT
- **Audience:** Facility managers, safety officers, administrators
- **Tone:** Professional, operational, trustworthy

---

## Color System

### Status Colors (Semantic)

| State | Color | Hex | Usage |
|-------|-------|-----|-------|
| **Critical/Fire** | Red | `#c53030` | Fire events, urgent faults |
| **Danger/Offline** | Orange | `#dd6b20` | Offline panels, stale data |
| **Warning/Alert** | Amber | `#b7791f` | Open alerts, warnings |
| **Success/Online** | Green | `#2f855a` | Online status, healthy |
| **Neutral** | Gray | `#a0aec0` | Total counts, informational |

### Brand Palette

| Token | Light Mode | Usage |
|-------|------------|-------|
| `--bg` | `#f4f1e8` | Page background |
| `--surface` | `#fffdf7` | Cards, panels |
| `--text` | `#14213d` | Body text |
| `--muted` | `#5c677d` | Secondary text |
| `--border` | `#d8d1c2` | Borders, dividers |
| `--accent` | `#cf5c36` | Primary actions, links |
| `--accent-soft` | `#ffe3d8` | Hover states, badges |

### Focus & Interaction

| Token | Value |
|-------|-------|
| `--focus-ring` | `0 0 0 3px rgba(207, 92, 54, 0.4)` |
| `--shadow` | `0 10px 24px rgba(20, 33, 61, 0.08)` |
| `--shadow-hover` | `0 14px 28px rgba(20, 33, 61, 0.12)` |

---

## Typography

### Font Stack

```css
font-family: Georgia, "Times New Roman", serif;
```

For code/monospace:
```css
font-family: ui-monospace, "SF Mono", Monaco, monospace;
```

### Scale

| Element | Size | Weight | Letter Spacing |
|---------|------|--------|----------------|
| H1 | 2rem | 700 | - |
| H2 | 1.5rem | 600 | - |
| H3 | 1.25rem | 600 | - |
| Body | 1rem | 400 | - |
| Label | 0.75rem | 500 | 0.08em |
| Metric | 2rem | 700 | - |
| Metric Small | 1.4rem | 700 | - |

### Numeric Values

```css
.metric, .summary-value {
    font-variant-numeric: tabular-nums;
}
```

---

## Spacing Scale

| Token | Value | Usage |
|-------|-------|-------|
| `xs` | 4px | Icon gaps, inline spacing |
| `sm` | 8px | Tight spacing |
| `md` | 12px | Default gaps |
| `lg` | 16px | Section internal |
| `xl` | 24px | Section margins |
| `2xl` | 32px | Page sections |

---

## Components

### KPI Card

```
┌──────────────────────────┐
│ [icon] │ Label           │
│        │ METRIC          │
│        │ (status badge)  │
└──────────────────────────┘
```

**States:**
- `.kpi-critical` - Red left border, fire/urgent
- `.kpi-danger` - Orange left border, offline
- `.kpi-warning` - Amber left border, alerts
- `.kpi-healthy` - Green left border, online
- `.kpi-neutral` - Gray left border, total

### Panel Card

```
┌─────────────────────────────────────────────────────────────┐
│ COMPANY NAME                                                │
│ Panel Name                                                  │
│ Site · Device ID         │ [Status] │ Inputs │ Alerts │ Time │
└─────────────────────────────────────────────────────────────┘
```

**States:**
- Default: `cursor: pointer`, hover lift
- `.offline`: Red-tinted background, red border

### Status Pill

```html
<span class="status-pill status-ok">Online</span>
<span class="status-pill status-warn">Warning</span>
<span class="status-pill status-danger">Offline</span>
```

### Empty State

```
┌──────────────────────────────┐
│         [icon]              │
│      Title                  │
│  Description text           │
│  (optional action)          │
└──────────────────────────────┘
```

---

## Accessibility Requirements

### Focus Management

```css
/* All interactive elements */
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
[tabindex]:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}
```

### Color Contrast

| Combination | Ratio | Status |
|-------------|-------|--------|
| `--text` on `--surface` | 12.5:1 | ✅ AAA |
| `--muted` on `--surface` | 5.2:1 | ✅ AA |
| `--accent` on `white` | 4.6:1 | ✅ AA |
| `#c53030` on `white` | 5.5:1 | ✅ AA |
| `#2f855a` on `white` | 4.8:1 | ✅ AA |

### Touch Targets

Minimum 44x44px for all interactive elements:
```css
button, a, input, select {
    min-height: 44px;
    padding: 10px 14px;
}
```

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

---

## Animation

| Element | Duration | Easing |
|---------|----------|--------|
| Hover transitions | 150ms | ease-out |
| Focus transitions | 200ms | ease-out |
| Modal open | 200ms | ease-out |
| Toast notification | 300ms | ease-out |

```css
.panel-item {
    transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
}
```

---

## Icons

Use SVG icons from:
- **Heroicons** (outline variant) - Primary choice
- **Lucide** - Alternative

**Common icons needed:**
- Fire/flame
- Alert triangle
- Check circle
- X circle
- Clock
- Wifi (online)
- Wifi-off (offline)
- Battery
- Drop (water)
- Zap (mains)
- Eye (toggle visibility)
- Copy
- Shield
- Chevron

---

## Responsive Breakpoints

| Breakpoint | Width | Usage |
|------------|-------|-------|
| Mobile | < 640px | Single column |
| Tablet | 640-1024px | Two columns |
| Desktop | > 1024px | Full grid |

```css
.grid {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.kpi-grid {
    grid-template-columns: repeat(5, 1fr);
}

@media (max-width: 1024px) {
    .kpi-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 640px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .two-col { grid-template-columns: 1fr; }
}
```

---

## Z-Index Scale

| Level | Value | Usage |
|-------|-------|-------|
| Base | 0 | Normal content |
| Sticky | 10 | Nav, filters |
| Dropdown | 20 | Menus, selects |
| Modal | 30 | Dialogs |
| Toast | 40 | Notifications |
| Skip link | 100 | Accessibility |

---

## Anti-Patterns to Avoid

| Don't | Do Instead |
|-------|------------|
| Use emojis as icons | Use SVG icons |
| Placeholder-only form labels | Persistent labels above inputs |
| Raw ISO timestamps | Human-readable with relative age |
| Exposed tokens/secrets | Mask with reveal toggle |
| Equal visual weight for all KPIs | Hierarchy by criticality |
| Blank empty states | Guidance with icon and action |
| Hover-only interactions | Visible focus states |
| Scale transforms on hover | Subtle lift or shadow |