<?php
$mainsOk = true;
$battOk = true;
$waterLevel = 0;
$openFaults = 0;

foreach ($states ?? [] as $state) {
    if ((int) $state['mains_status'] === 0) $mainsOk = false;
    if ((int) $state['batt_status'] === 0) $battOk = false;
    if (!empty($state['water_level'])) $waterLevel = max($waterLevel, (float) $state['water_level']);
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
        <div class="status-pill <?= $isOffline ? 'status-danger' : 'status-ok' ?>" style="font-size: 1rem; padding: 8px 14px;">
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

<section class="panel-summary" aria-label="Panel status summary">
    <div class="summary-grid">
        <div class="summary-card <?= $mainsOk ? 'summary-ok' : 'summary-warn' ?>">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
            </div>
            <div class="label">Mains</div>
            <div class="summary-value <?= $mainsOk ? '' : 'text-danger' ?>"><?= $mainsOk ? 'ON' : 'OFF' ?></div>
        </div>
        <div class="summary-card <?= $battOk ? 'summary-ok' : 'summary-warn' ?>">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="6" width="18" height="12" rx="2" ry="2"/>
                    <line x1="23" y1="13" x2="23" y2="11"/>
                </svg>
            </div>
            <div class="label">Battery</div>
            <div class="summary-value <?= $battOk ? '' : 'text-danger' ?>"><?= $battOk ? 'OK' : 'LOW' ?></div>
        </div>
        <div class="summary-card <?= $waterLevel < 30 ? 'summary-warn' : '' ?>">
            <div class="summary-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
            </div>
            <div class="label">Water Level</div>
            <div class="summary-value"><?= h(number_format($waterLevel, 1)) ?>%</div>
            <?php if ($waterLevel < 30): ?>
                <div class="summary-alert">Below threshold</div>
            <?php endif; ?>
        </div>
        <div class="summary-card <?= $openFaults > 0 ? 'summary-danger' : 'summary-ok' ?>">
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
        <div class="summary-card <?= $isOffline ? 'summary-warn' : 'summary-ok' ?>">
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
    </div>
</section>

<section class="two-col">
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
                        <th>Water</th>
                        <th>Mains</th>
                        <th>Battery</th>
                        <th>Reported</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($states as $state): ?>
                        <tr>
                            <td><?= h($state['panel_input']) ?></td>
                            <td><?= h($state['event_type']) ?></td>
                            <td style="font-variant-numeric: tabular-nums;"><?= h((string) $state['current']) ?></td>
                            <td style="font-variant-numeric: tabular-nums;"><?= h(number_format((float) $state['water_level'], 1)) ?>%</td>
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
                    <tbody>
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

<section class="two-col" style="margin-top: 24px;">
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

    <div class="card">
        <div class="label">Recent telemetry</div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($logs)): ?>
                <div class="empty-state-mini">No telemetry data</div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Received</th>
                        <th>Input</th>
                        <th>Event</th>
                        <th>Payload</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($logs, 0, 10) as $log): ?>
                        <tr>
                            <td style="font-size: 0.85rem;"><?= format_datetime($log['received_at'] ?? null) ?></td>
                            <td><?= h($log['payload']['panel_input'] ?? '-') ?></td>
                            <td><?= h($log['payload']['event_type'] ?? '-') ?></td>
                            <td style="max-width: 200px;">
                                <details>
                                    <summary style="cursor: pointer; color: var(--link); font-size: 0.85rem;">View JSON</summary>
                                    <pre style="margin: 8px 0 0; padding: 8px; background: var(--bg); border-radius: 6px; font-size: 0.75rem; overflow-x: auto; font-family: ui-monospace, monospace;"><?= h(json_encode($log['payload'], JSON_PRETTY_PRINT)) ?></pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>