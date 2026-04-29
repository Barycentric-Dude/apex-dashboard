<section class="hero">
    <a href="/admin" style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--muted); text-decoration:none; margin-bottom:12px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Admin
    </a>
    <div class="label">Edit user</div>
    <h1><?= h($record['name']) ?></h1>
    <p class="muted"><?= h($record['email']) ?> &middot; <span class="status-pill <?= $record['role'] === 'super_admin' ? 'status-warn' : 'status-ok' ?>" style="padding: 2px 8px; font-size: 0.8rem;"><?= h($record['role']) ?></span></p>
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
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <div class="label">User details</div>
        </div>
        <form class="stack" method="post" action="/admin/users/<?= h($record['id']) ?>/edit" style="margin-top: 16px;">
            <div class="form-row">
                <label for="user-name">Full name</label>
                <input type="text" id="user-name" name="name" value="<?= h($record['name']) ?>" required autocomplete="off">
            </div>
            <div class="form-row">
                <label for="user-email">Email address</label>
                <input type="email" id="user-email" name="email" value="<?= h($record['email']) ?>" required autocomplete="off">
            </div>
            <div class="form-row">
                <label for="user-company">Company</label>
                <select id="user-company" name="company_id" required>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= h($company['id']) ?>" <?= $company['id'] === $record['company_id'] ? 'selected' : '' ?>>
                            <?= h($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="user-role">Role</label>
                <select id="user-role" name="role">
                    <option value="client_admin" <?= $record['role'] === 'client_admin' ? 'selected' : '' ?>>Client Admin</option>
                    <option value="super_admin" <?= $record['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                </select>
            </div>
            <div class="form-row">
                <label for="user-password">New password</label>
                <input type="password" id="user-password" name="password" placeholder="Leave blank to keep current" autocomplete="new-password">
                <span class="form-hint">Only fill in to change the password</span>
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
        <p class="muted" style="margin: 12px 0 16px;">This will permanently remove the user account. The user will no longer be able to log in.</p>
        <form method="post" action="/admin/users/<?= h($record['id']) ?>/delete" onsubmit="return confirm('Permanently delete <?= h(addslashes($record['name'])) ?>?')">
            <button type="submit" style="background: var(--danger); border-color: var(--danger); color: #fff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                Delete user
            </button>
        </form>
    </div>
</div>
