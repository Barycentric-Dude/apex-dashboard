<?php

declare(strict_types=1);

function is_nav_active(string $path): string {
    $current = $_SERVER['REQUEST_URI'] ?? '/';
    $isActive = $path === '/dashboard'
        ? $current === '/dashboard' || $current === '/'
        : strpos($current, $path) === 0;
    return $isActive ? 'aria-current="page"' : '';
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(($title ?? 'Apex Dashboard')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f6fa;
            --surface: #ffffff;
            --surface-strong: #1a1a2e;
            --text: #222222;
            --muted: #848484;
            --border: #e8e8e8;
            --accent: #6377ee;
            --accent-soft: #eef0fd;
            --success: #2f855a;
            --warning: #b7791f;
            --danger: #c53030;
            --link: #6377ee;
            --shadow: 0 10px 24px rgba(99, 119, 238, 0.08);
            --focus-ring: 0 0 0 3px rgba(99, 119, 238, 0.3);
        }

        .skip-link {
            position: absolute;
            top: -100px;
            left: 16px;
            background: var(--accent);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            z-index: 1000;
            text-decoration: none;
            font-weight: 500;
            transition: top 0.2s ease-out;
        }
        .skip-link:focus {
            top: 16px;
            outline: none;
            box-shadow: var(--focus-ring);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        a { color: var(--link); text-decoration: none; }
        .shell { max-width: 1200px; margin: 0 auto; padding: 24px; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Inter', sans-serif; }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 16px 24px;
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        .brand {
            font-size: 1.2rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text);
        }

        .nav {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav a {
            padding: 8px 14px;
            border-radius: 8px;
            transition: background 0.15s ease-out, color 0.15s ease-out;
            text-decoration: none;
        }
        .nav a:hover {
            background: var(--accent-soft);
            color: var(--accent);
        }
        .nav a[aria-current="page"] {
            background: var(--accent);
            color: white;
            font-weight: 500;
        }

        .button, button {
            border: 0;
            background: var(--accent);
            color: white;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font: inherit;
            transition: background 0.15s ease-out, transform 0.1s ease-out;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .button:hover, button:hover {
            background: #4f63d2;
        }
        .button:active, button:active {
            transform: scale(0.98);
        }
        .button.secondary {
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .button.secondary:hover {
            background: var(--bg);
            border-color: var(--accent);
        }

        .hero, .card, .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }
        .hero { padding: 24px; margin-bottom: 24px; }

        .grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .card { padding: 18px; }

        .kpi-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(5, 1fr);
        }
        @media (max-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 640px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .metric {
            font-size: 2rem;
            font-weight: bold;
            margin: 8px 0 4px;
            font-variant-numeric: tabular-nums;
        }
        .label {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.8rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.9rem;
            background: var(--accent-soft);
        }
        .status-ok { color: var(--success); }
        .status-warn { color: var(--warning); }
        .status-danger { color: var(--danger); }

        .panel-list { display: grid; gap: 16px; margin-top: 24px; }
        .panel-item {
            display: grid;
            gap: 12px;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            align-items: center;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: var(--surface);
            box-shadow: var(--shadow);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: transform 0.15s ease-out, box-shadow 0.15s ease-out, border-color 0.15s ease-out;
        }
        .panel-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(20, 33, 61, 0.12);
            border-color: var(--accent);
        }
        .panel-item.offline {
            border-color: rgba(197, 48, 48, 0.5);
            background: #fff6f6;
        }

        .table-wrap { overflow-x: auto; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border); }
        th {
            font-weight: 600;
            color: var(--text);
            background: var(--bg);
        }
        tbody tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.02);
        }
        tbody tr:hover {
            background: var(--accent-soft);
        }

        form.stack { display: grid; gap: 12px; }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: white;
            font: inherit;
            min-height: 44px;
            transition: border-color 0.15s ease-out, box-shadow 0.15s ease-out;
        }
        input:hover, select:hover {
            border-color: var(--accent);
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: var(--focus-ring);
        }

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

        .two-col {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }
        .card-header svg {
            color: var(--accent);
        }

        .button svg, button[type="submit"] svg {
            vertical-align: middle;
            margin-right: 6px;
        }

        .flash {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            border: 1px solid var(--border);
            background: white;
        }
        .flash.error { border-color: rgba(197, 48, 48, 0.45); background: #fff6f6; }
        .flash.success { border-color: rgba(47, 133, 90, 0.45); background: #f0fff4; }

        .muted { color: var(--muted); }

        .user-menu {
            position: relative;
        }
        .user-menu-trigger {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font: inherit;
            color: var(--text);
            transition: border-color 0.15s ease-out;
        }
        .user-menu-trigger:hover {
            border-color: var(--accent);
        }
        .user-avatar {
            width: 24px;
            height: 24px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: var(--text);
        }
        .mobile-menu-toggle svg {
            width: 24px;
            height: 24px;
        }

        .login-page {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #eef0fd 0%, #f5f6fa 100%);
            border-radius: 18px;
        }
        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-top: 4px solid var(--accent);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(99, 119, 238, 0.12);
        }
        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 24px;
        }
        .login-brand-name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text);
        }
        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 8px;
            text-align: center;
            color: var(--text);
        }
        .login-subtitle {
            color: var(--muted);
            text-align: center;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .trust-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            font-size: 0.8rem;
        }
        .trust-badge svg {
            color: var(--success);
        }

        .flash.error {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .flash.error svg {
            flex-shrink: 0;
            color: var(--danger);
        }

        @media (max-width: 640px) {
            .topbar {
                flex-wrap: wrap;
            }
            .nav {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                padding-top: 12px;
            }
            .nav.open {
                display: flex;
            }
            .nav a, .nav form {
                width: 100%;
            }
            .nav a {
                padding: 12px 8px;
                border-bottom: 1px solid var(--border);
            }
            .mobile-menu-toggle {
                display: block;
            }
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            background: var(--surface);
            border: 2px dashed var(--border);
            border-radius: 18px;
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
        .empty-state-mini {
            text-align: center;
            padding: 24px;
            color: var(--muted);
            font-style: italic;
        }

        .token-mask {
            font-family: ui-monospace, monospace;
            background: var(--surface-strong);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .token-actions {
            display: inline-flex;
            gap: 4px;
            margin-left: 8px;
        }
        .token-btn {
            background: transparent;
            border: 1px solid var(--border);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: var(--muted);
            transition: background 0.15s, color 0.15s;
            min-height: auto;
        }
        .token-btn:hover {
            background: var(--accent-soft);
            color: var(--accent);
            border-color: var(--accent);
        }

        .kpi-card {
            position: relative;
            border-left: 4px solid var(--border);
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

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .kpi-icon svg {
            width: 22px;
            height: 22px;
        }
        .kpi-icon.fire { background: #fee2e2; color: #c53030; }
        .kpi-icon.offline { background: #ffedd5; color: #dd6b20; }
        .kpi-icon.alert { background: #fef3c7; color: #b7791f; }
        .kpi-icon.online { background: #d1fae5; color: #2f855a; }
        .kpi-icon.total { background: var(--bg); color: var(--muted); }

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
            position: relative;
        }
        .summary-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            background: var(--surface);
        }
        .summary-card.summary-ok .summary-icon { background: #d1fae5; color: #2f855a; }
        .summary-card.summary-warn .summary-icon { background: #fef3c7; color: #b7791f; }
        .summary-card.summary-danger .summary-icon { background: #fee2e2; color: #c53030; }
        .summary-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 4px 0;
            font-variant-numeric: tabular-nums;
        }
        .summary-value.text-danger { color: var(--danger); }
        .summary-alert {
            font-size: 0.7rem;
            color: var(--danger);
            font-weight: 500;
        }
        .summary-freshness {
            font-size: 0.7rem;
            font-weight: 500;
        }
        .summary-freshness.fresh { color: var(--success); }
        .summary-freshness.stale { color: var(--danger); }

        .input-indicator-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .input-indicator {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s ease-out;
        }

        .input-indicator.active {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .input-indicator.fire {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .input-indicator-label {
            font-size: 0.75rem;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .input-indicator-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .input-indicator-status {
            font-size: 2rem;
            margin: 8px 0;
        }

        .input-indicator-details {
            font-size: 0.75rem;
            color: var(--muted);
        }

        @media (max-width: 860px) {
            .summary-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 540px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
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

        @media (max-width: 860px) {
            .panel-item { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
        .tab-bar {
            display: flex;
            gap: 4px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 16px;
        }
        .tab {
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: color 0.15s, border-color 0.15s;
        }
        .tab:hover { color: var(--text); }
        .tab-active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .filter-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            font-size: 0.78rem;
            font-weight: 500;
            font-family: inherit;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--surface);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.15s ease-out;
            line-height: 1.4;
        }
        .filter-pill:hover {
            border-color: var(--accent);
            color: var(--text);
        }
        .filter-pill.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<div class="shell">
    <header class="topbar">
        <a href="/dashboard" class="brand" aria-label="Apex Fire IoT - Home" style="gap: 12px;">
            <img src="/ACEP%20logo%20new%201.png" alt="Apex" style="height: 36px; width: auto;">
            <span>Apex</span>
        </a>
        <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false" onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'); document.querySelector('.nav').classList.toggle('open');">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <nav class="nav" aria-label="Main navigation">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/dashboard" <?= is_nav_active('/dashboard') ?>>Dashboard</a>
                <?php if (($user['role'] ?? null) === 'super_admin'): ?>
                    <a href="/admin" <?= is_nav_active('/admin') ?>>Admin</a>
                    <a href="/admin/input-mappings" <?= is_nav_active('/admin/input-mappings') ?>>Input Mappings</a>
                <?php endif; ?>
                <div class="user-menu">
                    <button class="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
                        <span class="user-avatar"><?= h(substr($user['name'] ?? 'U', 0, 1)) ?></span>
                        <span><?= h($user['name'] ?? 'User') ?></span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                </div>
                <form method="post" action="/logout">
                    <button class="button secondary" type="submit" style="width: 100%;">Logout</button>
                </form>
            <?php else: ?>
                <a class="button" href="/login">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main id="main-content" role="main">
        <?php require $templatePath; ?>
    </main>
</div>

<script>
document.querySelectorAll('.toggle-token').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var mask = btn.closest('td').querySelector('.token-mask');
        var isHidden = mask.textContent.includes('\u2022');
        mask.textContent = isHidden ? mask.dataset.token : '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022';
        btn.textContent = isHidden ? 'Hide' : 'Show';
    });
});

document.querySelectorAll('.copy-token').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var token = btn.dataset.token;
        navigator.clipboard.writeText(token).then(function() {
            var original = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = original; }, 1500);
        });
    });
});

function filterPanels(searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    document.querySelectorAll('.panel-item').forEach(function(item) {
        var text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function filterStatus(status) {
    document.querySelectorAll('.panel-item').forEach(function(item) {
        if (!status) {
            item.style.display = '';
            return;
        }
        var isOffline = item.classList.contains('offline');
        var hasAlerts = item.querySelector('.metric') && item.querySelector('.metric').textContent.trim() !== '0';
        
        if (status === 'online' && !isOffline) item.style.display = '';
        else if (status === 'offline' && isOffline) item.style.display = '';
        else if (status === 'alerts' && hasAlerts) item.style.display = '';
        else item.style.display = 'none';
    });
}

(function() {
    if (!window.location.pathname.endsWith('/dashboard')) return;

    function formatDateTime(iso) {
        if (!iso) return 'Never';
        var d = new Date(iso);
        return d.toLocaleString();
    }

    function updateDashboard() {
        fetch('/api/dashboard')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var stats = data.stats;

                var lastUpdEl = document.getElementById('last-updated');
                if (lastUpdEl) lastUpdEl.innerHTML =
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">' +
                    '<circle cx="12" cy="12" r="10"/>' +
                    '<polyline points="12 6 12 12 16 14"/>' +
                    '</svg> Updated ' + formatDateTime(stats.last_updated);

                var kpiFire = document.getElementById('kpi-fire-count');
                if (kpiFire) kpiFire.textContent = stats.fire_events;
                var kpiOffline = document.getElementById('kpi-offline-count');
                if (kpiOffline) kpiOffline.textContent = stats.offline_panels;
                var kpiAlerts = document.getElementById('kpi-alerts-count');
                if (kpiAlerts) kpiAlerts.textContent = stats.open_alerts;
                var kpiOnline = document.getElementById('kpi-online-count');
                if (kpiOnline) kpiOnline.textContent = stats.online_panels;
                var kpiTotal = document.getElementById('kpi-total-count');
                if (kpiTotal) kpiTotal.textContent = stats.total_panels;

                var fireCard = document.getElementById('kpi-fire');
                var offlineCard = document.getElementById('kpi-offline');
                var alertsCard = document.getElementById('kpi-alerts');

                if (fireCard) {
                    fireCard.className = 'card kpi-card ' + (stats.fire_events > 0 ? 'kpi-critical' : 'kpi-healthy');
                    if (stats.fire_events > 0 && !fireCard.querySelector('.kpi-alert-badge')) {
                        var badge = document.createElement('div');
                        badge.className = 'kpi-alert-badge';
                        badge.textContent = 'ACTION REQUIRED';
                        fireCard.insertBefore(badge, fireCard.firstChild);
                    }
                    if (stats.fire_events === 0) {
                        var existingBadge = fireCard.querySelector('.kpi-alert-badge');
                        if (existingBadge) existingBadge.remove();
                    }
                }
                if (offlineCard) offlineCard.className = 'card kpi-card ' + (stats.offline_panels > 0 ? 'kpi-danger' : 'kpi-healthy');
                if (alertsCard) alertsCard.className = 'card kpi-card ' + (stats.open_alerts > 0 ? 'kpi-warning' : 'kpi-healthy');

                var panelList = document.getElementById('panel-list');
                if (panelList && data.panelCards) {
                    panelList.innerHTML = '';
                    if (data.panelCards.length === 0) {
                        panelList.innerHTML =
                            '<div class="empty-state">' +
                            '<svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
                            '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>' +
                            '<rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>' +
                            '</svg><h3>No panels configured</h3><p>Contact your administrator to subscribe fire panels to your account.</p></div>';
                    } else {
                        data.panelCards.forEach(function(item) {
                            var isOffline = item.is_offline;
                            var card = document.createElement('a');
                            card.className = 'panel-item' + (isOffline ? ' offline' : '');
                            card.href = '/panels/' + encodeURIComponent(item.panel.id);
                            card.innerHTML =
                                '<div>' +
                                    '<div class="label">' + esc(item.company ? item.company.name : 'Unknown company') + '</div>' +
                                    '<h3 style="margin: 8px 0 6px;">' + esc(item.panel.name) + '</h3>' +
                                    '<div class="muted">' +
                                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">' +
                                        '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>' +
                                        '<circle cx="12" cy="10" r="3"/></svg>' +
                                        esc(item.panel.site_name || 'No site assigned') +
                                        '<span style="margin: 0 8px;">\u00b7</span>' +
                                        '<code style="font-size: 0.8rem;">' + esc(item.panel.device_id) + '</code>' +
                                    '</div>' +
                                '</div>' +
                                '<div>' +
                                    '<div class="label">Status</div>' +
                                    '<div class="status-pill ' + (isOffline ? 'status-danger' : 'status-ok') + '">' +
                                        (isOffline
                                            ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/></svg> Offline / stale'
                                            : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Online'
                                        ) +
                                    '</div>' +
                                '</div>' +
                                '<div>' +
                                    '<div class="label">Inputs</div>' +
                                    '<div class="metric" style="font-size: 1.4rem;">' + item.state_count + '</div>' +
                                '</div>' +
                                '<div>' +
                                    '<div class="label">Open alerts</div>' +
                                    '<div class="metric" style="font-size: 1.4rem; color: ' + (item.open_alerts > 0 ? 'var(--warning)' : 'inherit') + ';">' + item.open_alerts + '</div>' +
                                    '<div class="muted">Last: ' + formatDateTime(item.last_reported_at) + '</div>' +
                                '</div>';
                            panelList.appendChild(card);
                        });
                    }

                    var activeSearch = document.getElementById('panel-search');
                    if (activeSearch && activeSearch.value) filterPanels(activeSearch.value);
                    var activeFilter = document.getElementById('panel-filter');
                    if (activeFilter && activeFilter.value) filterStatus(activeFilter.value);
                }
            })
            .catch(function() {});

    function esc(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    setInterval(updateDashboard, 2000);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</body>
</html>