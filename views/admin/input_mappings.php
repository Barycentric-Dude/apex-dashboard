<?php
/**
 * Input Mappings Admin View
 */
?>

<section class="hero" style="padding: 20px 24px; margin-bottom: 20px;">
    <h2 style="margin: 0 0 8px;">Input Mappings</h2>
    <p class="muted">Map DI_1 - DI_8 inputs to friendly names for each company.</p>
</section>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="flash success"><?= h($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="flash error"><?= h($_SESSION['flash_error']) ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<section class="card" style="padding: 24px; margin-bottom: 24px;">
    <div class="label" style="margin-bottom: 16px;">Add/Update Mapping</div>
    <form method="post" action="/admin/input-mappings" class="stack">
        <?= csrf_field() ?>
        <div class="form-row">
            <label>Company</label>
            <select name="company_id" required>
                <option value="">Select company...</option>
                <?php foreach ($grouped as $companyId => $data): ?>
                    <option value="<?= h($companyId) ?>"><?= h($data['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label>Panel Input</label>
            <select name="panel_input" required>
                <option value="">Select input...</option>
                <option value="DI_1">DI_1 - Sprinkler pump</option>
                <option value="DI_2">DI_2 - Hydrant pump</option>
                <option value="DI_3">DI_3 - Main pump</option>
                <option value="DI_4">DI_4 - Fire Panel</option>
                <option value="DI_5">DI_5 - Jockey pump</option>
                <option value="DI_6">DI_6 - Diesel Tank level</option>
                <option value="DI_7">DI_7 - Fire Tank level</option>
                <option value="DI_8">DI_8 - Pressure sensor</option>
            </select>
        </div>
        <div class="form-row">
            <label>Custom Friendly Name</label>
            <input type="text" name="friendly_name" placeholder="e.g., Sprinkler pump" required>
            <div class="form-hint">Leave as-is to use default names</div>
        </div>
        <button class="button" type="submit">Save Mapping</button>
    </form>
</section>

<section class="card" style="padding: 24px;">
    <div class="label" style="margin-bottom: 16px;">Current Mappings by Company</div>

    <?php if (empty($all_mappings)): ?>
        <div class="empty-state-mini">No mappings configured yet.</div>
    <?php else: ?>
        <?php foreach ($grouped as $companyId => $data): ?>
            <?php if (!empty($data['mappings'])): ?>
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 1rem; margin: 0 0 12px; color: var(--text);"><?= h($data['name']) ?></h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Input</th>
                                    <th>Friendly Name</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['mappings'] as $mapping): ?>
                                    <tr>
                                        <td><code style="font-size: 0.85rem;"><?= h($mapping['panel_input']) ?></code></td>
                                        <td><?= h($mapping['friendly_name']) ?></td>
                                        <td style="font-size: 0.85rem;" class="muted"><?= format_datetime($mapping['updated_at'] ?? null) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
