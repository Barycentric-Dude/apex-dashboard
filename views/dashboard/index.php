<section class="hero" style="padding: 16px 24px; margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div class="label">Monitoring overview</div>
            <h1 style="font-size: 1.5rem; margin: 4px 0 0;">Operational health</h1>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="muted" style="font-size: 0.85rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Updated <?= format_datetime($stats['last_updated'] ?? null) ?>
            </div>
            <button class="button secondary" onclick="location.reload()" style="padding: 8px 12px; min-height: 36px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/>
                    <polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
            </button>
        </div>
    </div>
</section>

<section style="margin-bottom: 16px;">
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <input type="search" id="panel-search" placeholder="Search panels..." style="width: 100%;" onkeyup="filterPanels(this.value)">
        </div>
        <div>
            <select id="panel-filter" onchange="filterStatus(this.value)" style="min-width: 140px;">
                <option value="">All statuses</option>
                <option value="online">Online only</option>
                <option value="offline">Offline only</option>
                <option value="alerts">With alerts</option>
            </select>
        </div>
    </div>
</section>

<section class="grid kpi-grid">
    <?php
    $fireClass = ($stats['fire_events'] ?? 0) > 0 ? 'kpi-critical' : 'kpi-healthy';
    $offlineClass = ($stats['offline_panels'] ?? 0) > 0 ? 'kpi-danger' : 'kpi-healthy';
    $alertsClass = ($stats['open_alerts'] ?? 0) > 0 ? 'kpi-warning' : 'kpi-healthy';
    ?>

    <div class="card kpi-card <?= $fireClass ?>">
        <?php if (($stats['fire_events'] ?? 0) > 0): ?>
            <div class="kpi-alert-badge">ACTION REQUIRED</div>
        <?php endif; ?>
        <div class="kpi-icon fire">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
            </svg>
        </div>
        <div class="label">Fire Events</div>
        <div class="metric"><?= h((string) ($stats['fire_events'] ?? 0)) ?></div>
    </div>

    <div class="card kpi-card <?= $offlineClass ?>">
        <div class="kpi-icon offline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="1" y1="1" x2="23" y2="23"/>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </div>
        <div class="label">Offline Panels</div>
        <div class="metric"><?= h((string) ($stats['offline_panels'] ?? 0)) ?></div>
    </div>

    <div class="card kpi-card <?= $alertsClass ?>">
        <div class="kpi-icon alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div class="label">Open Alerts</div>
        <div class="metric"><?= h((string) ($stats['open_alerts'] ?? 0)) ?></div>
    </div>

    <div class="card kpi-card kpi-healthy">
        <div class="kpi-icon online">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="label">Online Panels</div>
        <div class="metric"><?= h((string) ($stats['online_panels'] ?? 0)) ?></div>
    </div>

    <div class="card kpi-card">
        <div class="kpi-icon total">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
        </div>
        <div class="label">Total Panels</div>
        <div class="metric"><?= h((string) ($stats['total_panels'] ?? 0)) ?></div>
    </div>
</section>

<section class="panel-list" aria-label="Panel list">
    <?php if (empty($panelCards)): ?>
        <div class="empty-state">
            <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
            </svg>
            <h3>No panels configured</h3>
            <p>Contact your administrator to subscribe fire panels to your account.</p>
        </div>
    <?php else: ?>
        <?php foreach ($panelCards as $item): ?>
            <a class="panel-item <?= $item['is_offline'] ? 'offline' : '' ?>" href="/panels/<?= h($item['panel']['id']) ?>">
                <div>
                    <div class="label"><?= h($item['company']['name'] ?? 'Unknown company') ?></div>
                    <h3 style="margin: 8px 0 6px;"><?= h($item['panel']['name']) ?></h3>
                    <div class="muted">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?= h($item['panel']['site_name'] ?: 'No site assigned') ?>
                        <span style="margin: 0 8px;">·</span>
                        <code style="font-size: 0.8rem;"><?= h($item['panel']['device_id']) ?></code>
                    </div>
                </div>
                <div>
                    <div class="label">Status</div>
                    <div class="status-pill <?= $item['is_offline'] ? 'status-danger' : 'status-ok' ?>">
                        <?php if ($item['is_offline']): ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/></svg>
                            Offline / stale
                        <?php else: ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            Online
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="label">Inputs</div>
                    <div class="metric" style="font-size: 1.4rem;"><?= h((string) $item['state_count']) ?></div>
                </div>
                <div>
                    <div class="label">Open alerts</div>
                    <div class="metric" style="font-size: 1.4rem; color: <?= $item['open_alerts'] > 0 ? 'var(--warning)' : 'inherit' ?>;"><?= h((string) $item['open_alerts']) ?></div>
                    <div class="muted">Last: <?= format_datetime($item['last_reported_at'] ?? null) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</section>