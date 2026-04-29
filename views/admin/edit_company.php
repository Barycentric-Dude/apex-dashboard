<section class="hero">
    <a href="/admin" style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--muted); text-decoration:none; margin-bottom:12px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Admin
    </a>
    <div class="label">Edit company</div>
    <h1><?= h($company['name']) ?></h1>
    <p class="muted">Update subscription details and configuration.</p>
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
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <div class="label">Company details</div>
        </div>
        <form class="stack" method="post" action="/admin/companies/<?= h($company['id']) ?>/edit" style="margin-top: 16px;">
            <div class="form-row">
                <label for="company-name">Company name</label>
                <input type="text" id="company-name" name="name" value="<?= h($company['name']) ?>" required autocomplete="off">
            </div>
            <div class="form-row">
                <label for="company-status">Subscription status</label>
                <select id="company-status" name="subscription_status">
                    <option value="active" <?= $company['subscription_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $company['subscription_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="internal" <?= $company['subscription_status'] === 'internal' ? 'selected' : '' ?>>Internal</option>
                </select>
            </div>
            <div class="form-row">
                <label for="company-ends">Subscription end date</label>
                <input type="date" id="company-ends" name="subscription_ends_at"
                    value="<?= $company['subscription_ends_at'] ? h(date('Y-m-d', strtotime($company['subscription_ends_at']))) : '' ?>">
                <span class="form-hint">Leave blank for no expiry (internal accounts)</span>
            </div>
            <div class="form-row">
                <label for="company-limit">Panel limit</label>
                <input type="number" id="company-limit" name="panel_limit" min="1" value="<?= h((string) $company['panel_limit']) ?>" required>
                <span class="form-hint">Maximum number of panels this company can register</span>
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
        <p class="muted" style="margin: 12px 0 16px;">Deleting this company will permanently remove all its users, panels, telemetry data, and alerts.</p>
        <form method="post" action="/admin/companies/<?= h($company['id']) ?>/delete" onsubmit="return confirm('Permanently delete <?= h(addslashes($company['name'])) ?> and all associated data? This cannot be undone.')">
            <button type="submit" style="background: var(--danger); border-color: var(--danger); color: #fff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                Delete company
            </button>
        </form>
    </div>
</div>
