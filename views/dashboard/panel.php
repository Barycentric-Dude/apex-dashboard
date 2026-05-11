<?php
$mainsOk = true;
$battOk = true;
$openFaults = 0;

foreach ($states ?? [] as $state) {
    if ((int) $state['mains_status'] === 0) $mainsOk = false;
    if ((int) $state['batt_status'] === 0) $battOk = false;
}

foreach ($alerts ?? [] as $alert) {
    if ($alert['status'] === 'open') $openFaults++;
}

$openAlerts = array_filter($alerts ?? [], fn($a) => $a['status'] === 'open');
$alertHistory = array_filter($alerts ?? [], fn($a) => $a['status'] !== 'open');
?>

<nav aria-label="Breadcrumb" style="margin-bottom: 16px;">
    <a href="/dashboard" style="display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 0.9rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Dashboard
    </a>
</nav>

<section class="hero" style="padding: 20px 24px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div>
            <div class="label"><?= h($panel['site_name'] ?: 'No site assigned') ?></div>
            <h1 style="font-size: 1.75rem; margin: 4px 0 8px;"><?= h($panel['name']) ?></h1>
            <div class="muted" style="display: flex; align-items: center; gap: 8px;">
                <code style="background: var(--bg); padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><?= h($panel['device_id']) ?></code>
            </div>
        </div>
        <div class="status-pill <?= $isOffline ? 'status-danger' : 'status-ok' ?>" style="font-size: 1rem; padding: 8px 14px;" id="panel-status-pill">
            <?php if (!$isOffline): ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Online
            <?php else: ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"/></svg>
                Offline / stale
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="tab-bar">
    <a href="/panels/<?= h($panel['id']) ?>" class="tab tab-active">Panel</a>
    <a href="/panels/<?= h($panel['id']) ?>/telemetry" class="tab">Telemetry</a>
</div>

<section class="panel-summary" aria-label="Panel status summary">
    <div class="summary-grid">
        <div class="summary-card <?= $mainsOk ? 'summary-ok' : 'summary-warn' ?>" id="card-mains">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
            </div>
            <div class="label">Mains</div>
            <div class="summary-value <?= $mainsOk ? '' : 'text-danger' ?>"><?= $mainsOk ? 'ON' : 'OFF' ?></div>
        </div>
        <div class="summary-card <?= $battOk ? 'summary-ok' : 'summary-warn' ?>" id="card-batt">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="6" width="18" height="12" rx="2" ry="2"/>
                    <line x1="23" y1="13" x2="23" y2="11"/>
                </svg>
            </div>
            <div class="label">Battery</div>
            <div class="summary-value <?= $battOk ? '' : 'text-danger' ?>"><?= $battOk ? 'OK' : 'LOW' ?></div>
        </div>
        <div class="summary-card" id="card-last-report">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="label">Last Report</div>
            <div class="summary-value" style="font-size: 1rem;"><?= format_datetime($lastReportedAt ?? null) ?></div>
            <div class="summary-freshness <?= $isOffline ? 'stale' : 'fresh' ?>">
                <?= $isOffline ? 'Stale data' : 'Fresh' ?>
            </div>
        </div>
        <div class="summary-card <?= $openFaults > 0 ? 'summary-danger' : 'summary-ok' ?>" id="card-faults">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="label">Open Faults</div>
            <div class="summary-value <?= $openFaults > 0 ? 'text-danger' : '' ?>"><?= h((string) $openFaults) ?></div>
        </div>
        <div class="summary-card <?= $isOffline ? 'summary-warn' : 'summary-ok' ?>" id="card-status">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="1" y1="1" x2="23" y2="23"/>
                    <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                    <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                    <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/>
                    <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                </svg>
            </div>
            <div class="label">System Status</div>
            <div class="summary-value" style="font-size: 1.1rem; <?= $isOffline ? 'color: var(--danger);' : 'color: var(--success);' ?>">
                <?= $isOffline ? 'OFFLINE' : 'ONLINE' ?>
            </div>
            <div class="summary-freshness <?= $isOffline ? 'stale' : 'fresh' ?>">
                <?= $isOffline ? 'Not reporting' : 'All systems nominal' ?>
            </div>
        </div>
    </div>
</section>

<section class="card" style="padding: 24px; margin-bottom: 24px;">
    <div class="label" style="margin-bottom: 4px;">Input Indicators</div>
    <div class="muted" style="font-size: 0.85rem; margin-bottom: 16px;">Visual status of active fire panel inputs</div>
    <?php
        // Build state index from states - only show inputs that have data
        $stateIndex = [];
        foreach ($states ?? [] as $s) {
            $stateIndex[$s['panel_input']] = $s;
        }

        // Default input mappings
        $defaultInputs = [
            'DI_1' => 'Sprinkler pump',
            'DI_2' => 'Hydrant pump',
            'DI_3' => 'Main pump',
            'DI_4' => 'Fire Panel',
            'DI_5' => 'Jockey pump',
            'DI_6' => 'Diesel Tank level',
            'DI_7' => 'Fire Tank level',
            'DI_8' => 'Pressure sensor',
        ];

        // Only show inputs that have data
        $activeInputs = [];
        foreach ($defaultInputs as $input => $defaultName) {
            if (isset($stateIndex[$input])) {
                $activeInputs[$input] = $defaultName;
            }
        }
    ?>
    <?php if (empty($activeInputs)): ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            <h3>No sensor data received yet</h3>
            <p>Check panel connection and topic configuration.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;" id="input-indicators">
            <?php foreach ($activeInputs as $input => $defaultName):
                $friendlyName = ($input_mappings[$input] ?? $defaultName);
                $state = $stateIndex[$input] ?? null;
                $isActive = $state !== null && (int)($state['device_status'] ?? 1) === 1;
                $eventType = $state['event_type'] ?? 'NORMAL';
                $isFire = ($eventType === 'FIRE');
                $bgColor = $isFire ? '#fee2e2' : ($isActive ? '#f0fdf4' : '#f9fafb');
                $borderColor = $isFire ? '#fca5a5' : ($isActive ? '#bbf7d0' : '#e5e7eb');
            ?>
                <div style="background: <?= $bgColor ?>; border: 1px solid <?= $borderColor ?>; border-radius: 12px; padding: 16px; text-align: center; position: relative; transition: all 0.2s ease-out;">
                    <?php if ($isFire): ?>
                        <div style="position: absolute; top: 8px; right: 8px; background: #dc2626; color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 4px; font-weight: 600;">FIRE</div>
                    <?php endif; ?>
                    <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 4px;"><?= h($input) ?></div>
                    <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 8px;"><?= h($friendlyName) ?></div>
                    <div style="font-size: 2rem; margin: 8px 0;"><?= $isActive ? '🟢' : '⚪' ?></div>
                    <div style="font-size: 0.75rem; color: #6b7280;">
                        <?= h($eventType) ?> | <?= (float)($state['current'] ?? 0) ?>A
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="two-col" id="panel-tables">
    <div class="card">
        <div class="label">Latest inputs</div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($states)): ?>
                <div class="empty-state-mini">No input data available</div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Input</th>
                        <th>Event</th>
                        <th>Current</th>
                        <th>Mains</th>
                        <th>Battery</th>
                        <th>Reported</th>
                    </tr>
                    </thead>
                    <tbody id="latest-inputs-body">
                    <?php foreach ($states as $state): ?>
                        <tr>
                            <td><?= h($state['panel_input']) ?></td>
                            <td><?= h($state['event_type']) ?></td>
                                <td style="font-variant-numeric: tabular-nums;"><?= h((string) $state['current']) ?></td>
                                <td>
                                <span class="status-pill <?= (int) $state['mains_status'] === 1 ? 'status-ok' : 'status-danger' ?>" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <?= (int) $state['mains_status'] === 1 ? 'ON' : 'OFF' ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-pill <?= (int) $state['batt_status'] === 1 ? 'status-ok' : 'status-danger' ?>" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <?= (int) $state['batt_status'] === 1 ? 'OK' : 'LOW' ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem;"><?= format_datetime($state['reported_at'] ?? null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="label">Open Alerts</div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($openAlerts)): ?>
                <div class="empty-state-mini">No open alerts</div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Input</th>
                        <th>Message</th>
                        <th>Reported</th>
                    </tr>
                    </thead>
                    <tbody id="open-alerts-body">
                    <?php foreach ($openAlerts as $alert): ?>
                        <tr>
                            <td>
                                <span class="status-pill status-danger" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <?= h($alert['type']) ?>
                                </span>
                            </td>
                            <td><?= h($alert['panel_input']) ?></td>
                            <td style="font-size: 0.85rem;"><?= h($alert['message']) ?></td>
                            <td style="font-size: 0.85rem;"><?= format_datetime($alert['reported_at'] ?? null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="two-col" style="margin-top: 24px;" id="panel-history">
    <div class="card">
        <div class="label">Alert History (Last 7 days)</div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($alertHistory)): ?>
                <div class="empty-state-mini">No recent alerts</div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Input</th>
                        <th>Status</th>
                        <th>Reported</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($alertHistory, 0, 10) as $alert): ?>
                        <tr>
                            <td><?= h($alert['type']) ?></td>
                            <td><?= h($alert['panel_input']) ?></td>
                            <td>
                                <span class="status-pill <?= $alert['status'] === 'resolved' ? 'status-ok' : 'status-warn' ?>" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <?= h($alert['status']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem;"><?= format_datetime($alert['reported_at'] ?? null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</section>

<script>
(function() {
    var panelId = <?= json_encode($panel['id']) ?>;
    if (!panelId) return;

    function updatePanel() {
        fetch('/api/panels/' + encodeURIComponent(panelId) + '/detail')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var fmt = function(iso) {
                    if (!iso) return 'Never';
                    return new Date(iso).toLocaleString();
                };

                // --- Summary cards ---
                var mainsCard = document.getElementById('card-mains');
                if (mainsCard) {
                    mainsCard.className = 'summary-card ' + (d.mainsOk ? 'summary-ok' : 'summary-warn');
                    var val = mainsCard.querySelector('.summary-value');
                    if (val) { val.textContent = d.mainsOk ? 'ON' : 'OFF'; val.className = 'summary-value' + (d.mainsOk ? '' : ' text-danger'); }
                }

                var battCard = document.getElementById('card-batt');
                if (battCard) {
                    battCard.className = 'summary-card ' + (d.battOk ? 'summary-ok' : 'summary-warn');
                    var val = battCard.querySelector('.summary-value');
                    if (val) { val.textContent = d.battOk ? 'OK' : 'LOW'; val.className = 'summary-value' + (d.battOk ? '' : ' text-danger'); }
                }

                var lastRpt = document.getElementById('card-last-report');
                if (lastRpt) {
                    var val = lastRpt.querySelector('.summary-value');
                    if (val) val.textContent = fmt(d.lastReportedAt);
                    var fresh = lastRpt.querySelector('.summary-freshness');
                    if (fresh) {
                        fresh.className = 'summary-freshness ' + (d.isOffline ? 'stale' : 'fresh');
                        fresh.textContent = d.isOffline ? 'Stale data' : 'Fresh';
                    }
                }

                var faultsCard = document.getElementById('card-faults');
                if (faultsCard) {
                    faultsCard.className = 'summary-card ' + (d.openFaults > 0 ? 'summary-danger' : 'summary-ok');
                    var val = faultsCard.querySelector('.summary-value');
                    if (val) { val.textContent = d.openFaults; val.className = 'summary-value' + (d.openFaults > 0 ? ' text-danger' : ''); }
                }

                var statusCard = document.getElementById('card-status');
                if (statusCard) {
                    statusCard.className = 'summary-card ' + (d.isOffline ? 'summary-warn' : 'summary-ok');
                    var val = statusCard.querySelector('.summary-value');
                    if (val) {
                        val.textContent = d.isOffline ? 'OFFLINE' : 'ONLINE';
                        val.style.color = d.isOffline ? 'var(--danger)' : 'var(--success)';
                    }
                    var fresh = statusCard.querySelector('.summary-freshness');
                    if (fresh) {
                        fresh.className = 'summary-freshness ' + (d.isOffline ? 'stale' : 'fresh');
                        fresh.textContent = d.isOffline ? 'Not reporting' : 'All systems nominal';
                    }
                }

                var statusPill = document.getElementById('panel-status-pill');
                if (statusPill) {
                    statusPill.className = 'status-pill ' + (d.isOffline ? 'status-danger' : 'status-ok') + ' status-pill';
                    statusPill.style.cssText = 'font-size: 1rem; padding: 8px 14px;';
                    statusPill.innerHTML = d.isOffline
                        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"/></svg> Offline / stale'
                        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Online';
                }

                // --- Input indicators ---
                var indicators = document.getElementById('input-indicators');
                if (indicators && d.states) {
                    var defaults = { DI_1:'Sprinkler pump', DI_2:'Hydrant pump', DI_3:'Main pump', DI_4:'Fire Panel', DI_5:'Jockey pump', DI_6:'Diesel Tank level', DI_7:'Fire Tank level', DI_8:'Pressure sensor' };
                    var html = '';
                    d.states.forEach(function(s) {
                        var input = s.panel_input;
                        var name = (d.input_mappings && d.input_mappings[input]) || defaults[input] || input;
                        var isActive = parseInt(s.device_status) === 1;
                        var isFire = s.event_type === 'FIRE';
                        var bg = isFire ? '#fee2e2' : (isActive ? '#f0fdf4' : '#f9fafb');
                        var bc = isFire ? '#fca5a5' : (isActive ? '#bbf7d0' : '#e5e7eb');
                        html += '<div style="background:' + bg + ';border:1px solid ' + bc + ';border-radius:12px;padding:16px;text-align:center;position:relative;transition:all 0.2s ease-out;">';
                        if (isFire) html += '<div style="position:absolute;top:8px;right:8px;background:#dc2626;color:white;font-size:0.65rem;padding:2px 8px;border-radius:4px;font-weight:600;">FIRE</div>';
                        html += '<div style="font-size:0.75rem;color:#6b7280;margin-bottom:4px;">' + esc(input) + '</div>';
                        html += '<div style="font-weight:600;font-size:0.9rem;margin-bottom:8px;">' + esc(name) + '</div>';
                        html += '<div style="font-size:2rem;margin:8px 0;">' + (isActive ? '\ud83d\udfe2' : '\u26aa') + '</div>';
                        html += '<div style="font-size:0.75rem;color:#6b7280;">' + esc(s.event_type) + ' | ' + parseFloat(s.current || 0).toFixed(2) + 'A</div>';
                        html += '</div>';
                    });
                    indicators.innerHTML = html;
                }

                // --- Latest inputs table ---
                var inputsBody = document.getElementById('latest-inputs-body');
                if (inputsBody && d.states) {
                    var html = '';
                    d.states.forEach(function(s) {
                        html += '<tr>' +
                            '<td>' + esc(s.panel_input) + '</td>' +
                            '<td>' + esc(s.event_type) + '</td>' +
                            '<td style="font-variant-numeric:tabular-nums;">' + esc(String(s.current)) + '</td>' +
                            '<td><span class="status-pill ' + (parseInt(s.mains_status) === 1 ? 'status-ok' : 'status-danger') + '" style="padding:2px 8px;font-size:0.8rem;">' + (parseInt(s.mains_status) === 1 ? 'ON' : 'OFF') + '</span></td>' +
                            '<td><span class="status-pill ' + (parseInt(s.batt_status) === 1 ? 'status-ok' : 'status-danger') + '" style="padding:2px 8px;font-size:0.8rem;">' + (parseInt(s.batt_status) === 1 ? 'OK' : 'LOW') + '</span></td>' +
                            '<td style="font-size:0.85rem;">' + fmt(s.reported_at) + '</td>' +
                            '</tr>';
                    });
                    inputsBody.innerHTML = html;
                }

                // --- Open alerts table ---
                var alertsBody = document.getElementById('open-alerts-body');
                if (alertsBody && d.alerts) {
                    var openAlerts = d.alerts.filter(function(a) { return a.status === 'open'; });
                    if (openAlerts.length === 0) {
                        var container = alertsBody.closest('.table-wrap') || alertsBody.closest('.card');
                        if (container) container.innerHTML = '<div class="empty-state-mini">No open alerts</div>';
                    } else {
                        var parentWrap = alertsBody.closest('.table-wrap');
                        if (parentWrap && !parentWrap.querySelector('table')) {
                            parentWrap.innerHTML = '<table><thead><tr><th>Type</th><th>Input</th><th>Message</th><th>Reported</th></tr></thead><tbody id="open-alerts-body"></tbody></table>';
                            var newBody = document.getElementById('open-alerts-body');
                            if (newBody) alertsBody = newBody;
                        }
                        var html = '';
                        openAlerts.forEach(function(a) {
                            html += '<tr>' +
                                '<td><span class="status-pill status-danger" style="padding:2px 8px;font-size:0.8rem;">' + esc(a.type) + '</span></td>' +
                                '<td>' + esc(a.panel_input) + '</td>' +
                                '<td style="font-size:0.85rem;">' + esc(a.message) + '</td>' +
                                '<td style="font-size:0.85rem;">' + fmt(a.reported_at) + '</td>' +
                                '</tr>';
                        });
                        alertsBody.innerHTML = html;
                    }
                }
            })
            .catch(function() {});
    }

    function esc(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    updatePanel();
    setInterval(updatePanel, 3000);
})();
</script>