<style>
.alert-type-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.alert-type-fire { background: #fee2e2; color: #991b1b; }
.alert-type-device_offline { background: #fef3c7; color: #92400e; }
.alert-type-mains_power_lost { background: #fef3c7; color: #92400e; }
.alert-type-battery_fault { background: #fef3c7; color: #92400e; }
.alert-type-low_water { background: #dbeafe; color: #1e40af; }
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.filter-btn { padding: 6px 14px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-size: 0.85rem; font-weight: 500; text-decoration: none; cursor: pointer; }
.filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.filter-btn:hover:not(.active) { background: var(--accent-soft); border-color: var(--accent); color: var(--accent); }
</style>

<section class="hero">
    <a href="/admin" style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--muted); text-decoration:none; margin-bottom:12px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Admin
    </a>
    <div class="label">Alert management</div>
    <h1>All Alerts</h1>
    <p class="muted">View, filter, and manually resolve alerts across all panels.</p>
</section>

<?php if (!empty($flash)): ?>
    <div class="flash success" role="alert"><?= h($flash) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="flash error" role="alert"><?= h($error) ?></div>
<?php endif; ?>

<div class="filter-bar">
    <a href="/admin/alerts?status=all" class="filter-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">All (<?= count($alerts) ?>)</a>
    <a href="/admin/alerts?status=open" class="filter-btn <?= $statusFilter === 'open' ? 'active' : '' ?>">
        Open (<?= $statusFilter === 'open' ? count($alerts) : $totalOpen ?>)
    </a>
    <a href="/admin/alerts?status=resolved" class="filter-btn <?= $statusFilter === 'resolved' ? 'active' : '' ?>">Resolved</a>

    <?php if ($totalOpen > 0): ?>
        <form method="post" action="/admin/alerts/resolve-all" style="margin-left: auto;"
              onsubmit="return confirm('Mark all <?= $totalOpen ?> open alert(s) as resolved?')">
            <button type="submit" style="padding: 6px 16px; border-radius: 8px; border: 1px solid var(--success); background: #f0fff4; color: var(--success); font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
                Resolve all open (<?= $totalOpen ?>)
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <?php if (empty($alerts)): ?>
            <div class="empty-state-mini" style="padding: 40px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.3;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <div>No alerts found<?= $statusFilter !== 'all' ? ' for this filter' : '' ?></div>
            </div>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Panel</th>
                    <th>Input</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Reported</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td>
                            <span class="alert-type-badge alert-type-<?= h($alert['type']) ?>">
                                <?= h(str_replace('_', ' ', $alert['type'])) ?>
                            </span>
                        </td>
                        <td><?= h($panelNames[$alert['panel_id']] ?? $alert['panel_id']) ?></td>
                        <td><code style="font-size: 0.8rem; background: var(--bg); padding: 2px 5px; border-radius: 4px;"><?= h($alert['panel_input'] ?? '—') ?></code></td>
                        <td style="max-width: 280px; font-size: 0.85rem; color: var(--muted);"><?= h($alert['message'] ?? '') ?></td>
                        <td>
                            <span class="status-pill <?= $alert['status'] === 'open' ? 'status-danger' : 'status-ok' ?>" style="padding: 2px 8px; font-size: 0.8rem;">
                                <?= h($alert['status']) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.82rem; font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--muted);">
                            <?= h(format_datetime($alert['reported_at'] ?? null)) ?>
                        </td>
                        <td>
                            <?php if ($alert['status'] === 'open'): ?>
                                <form method="post" action="/admin/alerts/<?= h($alert['id']) ?>/resolve" style="display: inline;">
                                    <input type="hidden" name="return_filter" value="<?= h($statusFilter) ?>">
                                    <button type="submit" style="padding: 4px 10px; font-size: 0.8rem; border-radius: 6px; border: 1px solid var(--success); background: #f0fff4; color: var(--success); cursor: pointer; font-weight: 500;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                                        Resolve
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--muted);">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
