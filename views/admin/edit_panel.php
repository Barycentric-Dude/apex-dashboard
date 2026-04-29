<section class="hero">
    <a href="/admin" style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--muted); text-decoration:none; margin-bottom:12px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Admin
    </a>
    <div class="label">Edit panel</div>
    <h1><?= h($panel['name']) ?></h1>
    <p class="muted"><code style="font-family: ui-monospace, monospace; background: var(--bg); padding: 2px 6px; border-radius: 4px;"><?= h($panel['device_id']) ?></code></p>
</section>

<?php if (!empty($flash)): ?>
    <div class="flash success" role="alert"><?= h($flash) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="flash error" role="alert"><?= h($error) ?></div>
<?php endif; ?>

<div style="max-width: 560px;">
    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <div class="label">Panel configuration</div>
        </div>
        <form class="stack" method="post" action="/admin/panels/<?= h($panel['id']) ?>/edit" style="margin-top: 16px;">
            <div class="form-row">
                <label for="panel-company">Company</label>
                <select id="panel-company" name="company_id" required>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= h($company['id']) ?>" <?= $company['id'] === $panel['company_id'] ? 'selected' : '' ?>>
                            <?= h($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="panel-name">Panel name</label>
                <input type="text" id="panel-name" name="name" value="<?= h($panel['name']) ?>" required autocomplete="off">
            </div>
            <div class="form-row">
                <label for="panel-site">Site name</label>
                <input type="text" id="panel-site" name="site_name" value="<?= h($panel['site_name'] ?? '') ?>" autocomplete="off">
                <span class="form-hint">Optional physical location label</span>
            </div>
            <div class="form-row">
                <label for="panel-device">Device ID</label>
                <input type="text" id="panel-device" name="device_id" value="<?= h($panel['device_id']) ?>" required autocomplete="off">
                <span class="form-hint">Unique hardware identifier sent in MQTT payloads</span>
            </div>
            <div class="form-row">
                <label for="panel-token">Panel token</label>
                <input type="text" id="panel-token" name="token" placeholder="Leave blank to keep current token" autocomplete="off">
                <span class="form-hint">
                    Current: <code style="font-family: ui-monospace, monospace; font-size: 0.8rem; background: var(--surface-strong); color: #fff; padding: 1px 6px; border-radius: 4px;"><?= h(substr($panel['token'], 0, 8)) ?>••••••••</code>
                    — Fill in only to replace
                </span>
            </div>
            <div class="form-row">
                <label for="panel-water">Water level threshold (%)</label>
                <input type="number" id="panel-water" name="water_level_threshold" step="0.01" value="<?= h((string) $panel['water_level_threshold']) ?>">
                <span class="form-hint">Alert triggers when water level falls below this percentage</span>
            </div>
            <div class="form-row">
                <label for="panel-interval">Reporting interval (minutes)</label>
                <input type="number" id="panel-interval" name="reporting_interval_minutes" min="1" value="<?= h((string) $panel['reporting_interval_minutes']) ?>">
                <span class="form-hint">Expected frequency of MQTT messages from this panel</span>
            </div>
            <div style="display:flex; gap:10px; margin-top:4px;">
                <button type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save changes
                </button>
                <a href="/admin" style="display:inline-flex; align-items:center; padding:0 18px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; color:var(--text); text-decoration:none; background:var(--surface);">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card" style="margin-top: 16px; border-color: #fca5a5;">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div class="label" style="color: var(--danger);">Danger zone</div>
        </div>
        <p class="muted" style="margin: 12px 0 16px;">Deleting this panel will permanently remove all its telemetry logs, latest states, and alert history.</p>
        <form method="post" action="/admin/panels/<?= h($panel['id']) ?>/delete" onsubmit="return confirm('Permanently delete panel <?= h(addslashes($panel['name'])) ?> and all its data?')">
            <button type="submit" style="background: var(--danger); border-color: var(--danger); color: #fff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                Delete panel
            </button>
        </form>
    </div>
</div>
