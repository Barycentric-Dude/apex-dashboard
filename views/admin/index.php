<section class="hero">
    <div class="label">Apex admin</div>
    <h1>Provision companies, users, and panels</h1>
    <p class="muted">Internal area for subscription setup and ingest credential management.</p>
</section>

<?php if (!empty($flash)): ?>
    <div class="flash success" role="alert"><?= h($flash) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="flash error" role="alert"><?= h($error) ?></div>
<?php endif; ?>

<section class="two-col">
    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <div class="label">Create company</div>
        </div>
        <form class="stack" method="post" action="/admin/companies" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <div class="form-row">
                <label for="company-name">Company name</label>
                <input type="text" id="company-name" name="name" placeholder="e.g., Acme Corporation" autocomplete="off" required>
                <span class="form-hint">The legal business name</span>
            </div>
            <div class="form-row">
                <label for="company-status">Subscription status</label>
                <select id="company-status" name="subscription_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="form-row">
                <label for="company-ends">Subscription end date</label>
                <input type="date" id="company-ends" name="subscription_ends_at" required>
            </div>
            <div class="form-row">
                <label for="company-limit">Panel limit</label>
                <input type="number" id="company-limit" name="panel_limit" min="1" value="1" required>
            </div>
            <button type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create company
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <div class="label">Create customer user</div>
        </div>
        <form class="stack" method="post" action="/admin/users" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <div class="form-row">
                <label for="user-company">Assign company</label>
                <select id="user-company" name="company_id" required>
                    <option value="">Select company</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= h($company['id']) ?>"><?= h($company['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="user-name">User name</label>
                <input type="text" id="user-name" name="name" placeholder="e.g., John Smith" autocomplete="off" required>
            </div>
            <div class="form-row">
                <label for="user-email">Email address</label>
                <input type="email" id="user-email" name="email" placeholder="john@company.com" autocomplete="off" required>
            </div>
            <div class="form-row">
                <label for="user-password">Temporary password</label>
                <input type="password" id="user-password" name="password" placeholder="Min 8 characters" autocomplete="new-password" required>
                <span class="form-hint">User will be prompted to change on first login</span>
            </div>
            <div class="form-row">
                <label for="user-role">Role</label>
                <select id="user-role" name="role">
                    <option value="client_admin">Client Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <button type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create user
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <div class="label">Register panel</div>
        </div>
        <form class="stack" method="post" action="/admin/panels" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <div class="form-row">
                <label for="panel-company">Assign company</label>
                <select id="panel-company" name="company_id" required>
                    <option value="">Select company</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= h($company['id']) ?>"><?= h($company['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="panel-name">Panel name</label>
                <input type="text" id="panel-name" name="name" placeholder="e.g., Building A - Main" autocomplete="off" required>
            </div>
            <div class="form-row">
                <label for="panel-site">Site name</label>
                <input type="text" id="panel-site" name="site_name" placeholder="e.g., Downtown Campus" autocomplete="off">
                <span class="form-hint">Optional location identifier</span>
            </div>
            <div class="form-row">
                <label for="panel-device">Device ID</label>
                <input type="text" id="panel-device" name="device_id" placeholder="Unique hardware identifier" autocomplete="off" required>
            </div>
            <div class="form-row">
                <label for="panel-token">Panel token</label>
                <input type="text" id="panel-token" name="token" placeholder="Leave blank to auto-generate" autocomplete="off">
                <span class="form-hint">Secret token for MQTT authentication</span>
            </div>
            <div class="form-row">
                <label for="panel-interval">Reporting interval (minutes)</label>
                <input type="number" id="panel-interval" name="reporting_interval_minutes" min="1" value="12">
            </div>
            <button type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Register panel
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <div class="label">Companies</div>
        </div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($companies)): ?>
                <div class="empty-state-mini">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.4;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    <div>No companies configured</div>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Ends</th>
                        <th>Limit</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?= h($company['name']) ?></td>
                            <td>
                                <span class="status-pill <?= $company['subscription_status'] === 'active' ? 'status-ok' : 'status-danger' ?>" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <?= h($company['subscription_status']) ?>
                                </span>
                            </td>
                            <td style="font-variant-numeric: tabular-nums;"><?= format_datetime($company['subscription_ends_at'] ?? null, false) ?></td>
                            <td style="font-variant-numeric: tabular-nums;"><?= h((string) $company['panel_limit']) ?></td>
                            <td>
                                <form method="post" action="/admin/companies/<?= h($company['id']) ?>/delete" onsubmit="return confirm('Delete this company?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="token-btn" style="color: var(--danger);">Delete</button>
                                </form>
                            </td>
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
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <div class="label">Users</div>
        </div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($users)): ?>
                <div class="empty-state-mini">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.4;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>No users configured</div>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Company</th>
                        <th>Role</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $record): ?>
                        <tr>
                            <td><?= h($record['name']) ?></td>
                            <td><?= h($record['email']) ?></td>
                            <td>
                                <?php
                                $companyName = '';
                                foreach ($companies as $c) {
                                    if ($c['id'] === $record['company_id']) {
                                        $companyName = $c['name'];
                                        break;
                                    }
                                }
                                ?>
                                <?= h($companyName ?: $record['company_id']) ?>
                            </td>
                            <td>
                                <span class="status-pill <?= $record['role'] === 'super_admin' ? 'status-warn' : 'status-ok' ?>" style="padding: 2px 8px; font-size: 0.8rem;">
                                    <?= h($record['role']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="/admin/users/<?= h($record['id']) ?>/delete" onsubmit="return confirm('Delete this user?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="token-btn" style="color: var(--danger);">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <div class="label">Panels</div>
        </div>
        <div class="table-wrap" style="margin-top: 12px;">
            <?php if (empty($panels)): ?>
                <div class="empty-state-mini">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; opacity: 0.4;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <div>No panels registered</div>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Device</th>
                        <th>Token</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($panels as $panel): ?>
                        <tr>
                            <td><?= h($panel['name']) ?></td>
                            <td>
                                <?php
                                $companyName = '';
                                foreach ($companies as $c) {
                                    if ($c['id'] === $panel['company_id']) {
                                        $companyName = $c['name'];
                                        break;
                                    }
                                }
                                ?>
                                <?= h($companyName ?: $panel['company_id']) ?>
                            </td>
                            <td><code style="font-family: ui-monospace, monospace; font-size: 0.85rem; background: var(--bg); padding: 2px 6px; border-radius: 4px;"><?= h($panel['device_id']) ?></code></td>
                            <td>
                                <code class="token-mask" data-token="<?= h($panel['token']) ?>">••••••••••••</code>
                                <span class="token-actions">
                                    <button type="button" class="token-btn toggle-token" aria-label="Show token">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button type="button" class="token-btn copy-token" data-token="<?= h($panel['token']) ?>" aria-label="Copy token">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    </button>
                                </span>
                            </td>
                            <td>
                                <form method="post" action="/admin/panels/<?= h($panel['id']) ?>/delete" onsubmit="return confirm('Delete this panel?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="token-btn" style="color: var(--danger);">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>