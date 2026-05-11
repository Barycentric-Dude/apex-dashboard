<?php
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
$eventTypes = ['NORMAL', 'ACTIVE', 'FIRE', 'FAULT'];
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$defaultFrom = $now->modify('-24 hours')->format('Y-m-d\TH:i');
$defaultTo = $now->format('Y-m-d\TH:i');
?>
<nav aria-label="Breadcrumb" style="margin-bottom: 16px;">
    <a href="/dashboard" style="display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 0.9rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Dashboard
    </a>
</nav>

<section class="hero" style="padding: 20px 24px; margin-bottom: 0;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div>
            <div class="label"><?= h($panel['site_name'] ?: 'No site assigned') ?></div>
            <h1 style="font-size: 1.75rem; margin: 4px 0 8px;"><?= h($panel['name']) ?></h1>
            <div class="muted" style="display: flex; align-items: center; gap: 8px;">
                <code style="background: var(--bg); padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><?= h($panel['device_id']) ?></code>
            </div>
        </div>
    </div>
</section>

<div class="tab-bar" style="padding-left: 24px; padding-right: 24px;">
    <a href="/panels/<?= h($panel['id']) ?>" class="tab">Panel</a>
    <a href="/panels/<?= h($panel['id']) ?>/telemetry" class="tab tab-active">Telemetry</a>
</div>

<section class="card" style="padding: 20px 24px; margin-bottom: 20px;">
    <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end; margin-bottom: 16px;">
        <div>
            <label class="label" for="filter-from" style="display: block; margin-bottom: 4px;">From</label>
            <input type="datetime-local" id="filter-from" value="<?= $defaultFrom ?>" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font: inherit; font-size: 0.85rem;">
        </div>
        <div>
            <label class="label" for="filter-to" style="display: block; margin-bottom: 4px;">To</label>
            <input type="datetime-local" id="filter-to" value="<?= $defaultTo ?>" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font: inherit; font-size: 0.85rem;">
        </div>
        <button class="button" id="apply-filters" style="padding: 6px 16px;">Apply</button>
        <button class="button secondary" id="reset-filters" style="padding: 6px 16px;">Reset</button>
        <span id="filter-status" style="font-size: 0.85rem; color: var(--muted); margin-left: auto;"></span>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
        <span class="label" style="margin-right: 6px;">Inputs</span>
        <?php foreach ($defaultInputs as $input => $name): ?>
        <button type="button" class="filter-pill filter-input active" data-value="<?= $input ?>"><?= $input ?></button>
        <?php endforeach; ?>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px;">
        <span class="label" style="margin-right: 6px;">Events</span>
        <?php foreach ($eventTypes as $et): ?>
        <button type="button" class="filter-pill filter-event active" data-value="<?= $et ?>"><?= $et ?></button>
        <?php endforeach; ?>
        <span style="flex:1"></span>
        <span class="label" style="margin-right: 6px;">Group by</span>
        <button type="button" class="filter-pill filter-agg active" data-value="hour">Hourly</button>
        <button type="button" class="filter-pill filter-agg" data-value="day">Daily</button>
        <button type="button" class="filter-pill filter-agg" data-value="raw">Raw</button>
    </div>
</section>

<section class="card" style="padding: 24px; margin-bottom: 20px;">
    <div class="label" style="margin-bottom: 4px;">Event Timeline</div>
    <div class="muted" style="font-size: 0.85rem; margin-bottom: 16px;">Events over time, grouped by selected interval</div>
    <div style="position: relative; height: 300px;">
        <canvas id="chart-timeline"></canvas>
    </div>
</section>

<section class="two-col" style="margin-bottom: 20px;">
    <div class="card" style="padding: 24px;">
        <div class="label" style="margin-bottom: 4px;">Per-Input Activity</div>
        <div class="muted" style="font-size: 0.85rem; margin-bottom: 16px;">Total events by input channel</div>
        <div style="position: relative; height: 280px;">
            <canvas id="chart-per-input"></canvas>
        </div>
    </div>
    <div class="card" style="padding: 24px;">
        <div class="label" style="margin-bottom: 4px;">Event Distribution</div>
        <div class="muted" style="font-size: 0.85rem; margin-bottom: 16px;">Breakdown by event type</div>
        <div style="position: relative; height: 280px;">
            <canvas id="chart-distribution"></canvas>
        </div>
    </div>
</section>

<section class="card" style="padding: 24px;">
    <div class="label" style="margin-bottom: 4px;">Telemetry Logs</div>
    <div class="muted" style="font-size: 0.85rem; margin-bottom: 16px;"><span id="log-count"></span></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Received</th>
                    <th>Input</th>
                    <th>Event</th>
                    <th>Device</th>
                    <th>Mains</th>
                    <th>Battery</th>
                    <th>Current</th>
                </tr>
            </thead>
            <tbody id="telemetry-body"></tbody>
        </table>
    </div>
</section>

<script>
(function() {
    var panelId = <?= json_encode($panel['id']) ?>;
    if (!panelId) return;

    var eventColors = {
        NORMAL: '#2f855a',
        ACTIVE: '#b7791f',
        FIRE: '#c53030',
        FAULT: '#6377ee'
    };
    var eventLabels = { NORMAL: 'NORMAL', ACTIVE: 'ACTIVE', FIRE: 'FIRE', FAULT: 'FAULT' };
    var defaultInputs = <?= json_encode($defaultInputs) ?>;

    var chartTimeline = null;
    var chartPerInput = null;
    var chartDist = null;
    var fetchTimer = null;

    function getFilters() {
        var inputs = [];
        document.querySelectorAll('.filter-input.active').forEach(function(p) { inputs.push(p.dataset.value); });
        var events = [];
        document.querySelectorAll('.filter-event.active').forEach(function(p) { events.push(p.dataset.value); });
        var agg = document.querySelector('.filter-agg.active');
        return {
            from: document.getElementById('filter-from').value,
            to: document.getElementById('filter-to').value,
            input: inputs.join(','),
            event_type: events.join(','),
            aggregate: agg ? agg.dataset.value : 'hour'
        };
    }

    function buildQuery(f) {
        var p = [];
        if (f.from) p.push('from=' + encodeURIComponent(f.from) + ':00Z');
        if (f.to) p.push('to=' + encodeURIComponent(f.to) + ':00Z');
        if (f.input) p.push('input=' + encodeURIComponent(f.input));
        if (f.event_type) p.push('event_type=' + encodeURIComponent(f.event_type));
        p.push('aggregate=' + f.aggregate);
        p.push('limit=200');
        return p.join('&');
    }

    function fetchData() {
        var f = getFilters();
        var qs = buildQuery(f);
        var url = '/api/panels/' + encodeURIComponent(panelId) + '/telemetry?' + qs;
        document.getElementById('filter-status').textContent = 'Loading\u2026';
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                updateCharts(d);
                updateTable(d);
                document.getElementById('filter-status').textContent = d.stats.total + ' entries';
            })
            .catch(function() {
                document.getElementById('filter-status').textContent = 'Error loading data';
            });
    }

    function updateCharts(d) {
        var series = d.time_series || [];
        var eventTypes = Object.keys(eventColors);
        var allBuckets = series.map(function(s) { return s.bucket; });

        // --- Timeline stacked bar ---
        var ctx1 = document.getElementById('chart-timeline');
        if (!ctx1) return;
        if (chartTimeline) chartTimeline.destroy();
        var datasets = eventTypes.map(function(et) {
            var data = series.map(function(s) { return s[et] || 0; });
            return {
                label: et,
                data: data,
                backgroundColor: eventColors[et],
                borderWidth: 0,
                borderRadius: 2,
            };
        });
        chartTimeline = new Chart(ctx1, {
            type: 'bar',
            data: { labels: allBuckets, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } }
                },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, padding: 12, font: { size: 11 } } },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });

        // --- Per-Input bar ---
        var ctx2 = document.getElementById('chart-per-input');
        if (!ctx2) return;
        if (chartPerInput) chartPerInput.destroy();
        var inputOrder = ['DI_1','DI_2','DI_3','DI_4','DI_5','DI_6','DI_7','DI_8'];
        var inputLabels = inputOrder.map(function(k) { return k + ' ' + (defaultInputs[k] || ''); });
        var inputData = inputOrder.map(function(k) { return d.stats.per_input[k] || 0; });
        chartPerInput = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: inputLabels,
                datasets: [{ label: 'Events', data: inputData, backgroundColor: '#6377ee', borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                    y: { grid: { display: false }, ticks: { font: { size: 10 } } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // --- Distribution doughnut ---
        var ctx3 = document.getElementById('chart-distribution');
        if (!ctx3) return;
        if (chartDist) chartDist.destroy();
        var distData = eventTypes.map(function(et) { return d.stats.per_event_type[et] || 0; });
        var distColors = eventTypes.map(function(et) { return eventColors[et]; });
        chartDist = new Chart(ctx3, {
            type: 'doughnut',
            data: {
                labels: eventTypes,
                datasets: [{ data: distData, backgroundColor: distColors, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } }
                }
            }
        });
    }

    function updateTable(d) {
        var body = document.getElementById('telemetry-body');
        if (!body) return;
        var count = document.getElementById('log-count');
        if (count) count.textContent = 'Showing last ' + (d.logs ? d.logs.length : 0) + ' of ' + d.stats.total + ' entries';
        if (!d.logs || d.logs.length === 0) {
            body.innerHTML = '<tr><td colspan="7"><div class="empty-state-mini">No telemetry data matching filters</div></td></tr>';
            return;
        }
        var html = '';
        d.logs.forEach(function(l) {
            var p = l.payload;
            html += '<tr>' +
                '<td style="font-size:0.85rem;white-space:nowrap;">' + fmt(l.received_at) + '</td>' +
                '<td>' + esc(p.panel_input) + '</td>' +
                '<td><span class="status-pill ' + (p.event_type === 'NORMAL' ? 'status-ok' : p.event_type === 'FIRE' ? 'status-danger' : 'status-warn') + '" style="padding:2px 8px;font-size:0.8rem;">' + esc(p.event_type) + '</span></td>' +
                '<td><span class="status-pill ' + (parseInt(p.device_status) === 1 ? 'status-ok' : 'status-danger') + '" style="padding:2px 8px;font-size:0.8rem;">' + (parseInt(p.device_status) === 1 ? 'ON' : 'OFF') + '</span></td>' +
                '<td><span class="status-pill ' + (parseInt(p.mains_status) === 1 ? 'status-ok' : 'status-danger') + '" style="padding:2px 8px;font-size:0.8rem;">' + (parseInt(p.mains_status) === 1 ? 'ON' : 'OFF') + '</span></td>' +
                '<td><span class="status-pill ' + (parseInt(p.batt_status) === 1 ? 'status-ok' : 'status-danger') + '" style="padding:2px 8px;font-size:0.8rem;">' + (parseInt(p.batt_status) === 1 ? 'OK' : 'LOW') + '</span></td>' +
                '<td style="font-variant-numeric:tabular-nums;font-size:0.85rem;">' + (p.current !== null && p.current !== undefined ? parseFloat(p.current).toFixed(2) + 'A' : '-') + '</td>' +
                '</tr>';
        });
        body.innerHTML = html;
    }

    function fmt(iso) {
        if (!iso) return 'Never';
        return new Date(iso).toLocaleString();
    }

    function esc(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function debouncedFetch() {
        if (fetchTimer) clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchData, 400);
    }

    document.querySelectorAll('.filter-pill').forEach(function(p) {
        p.addEventListener('click', function() {
            if (p.classList.contains('filter-agg')) {
                document.querySelectorAll('.filter-agg').forEach(function(o) { o.classList.remove('active'); });
                p.classList.add('active');
            } else {
                p.classList.toggle('active');
            }
            debouncedFetch();
        });
    });
    document.getElementById('apply-filters').addEventListener('click', fetchData);
    document.getElementById('reset-filters').addEventListener('click', function() {
        document.getElementById('filter-from').value = '<?= $defaultFrom ?>';
        document.getElementById('filter-to').value = '<?= $defaultTo ?>';
        document.querySelectorAll('.filter-pill').forEach(function(p) { p.classList.add('active'); });
        document.querySelectorAll('.filter-agg').forEach(function(p) { p.classList.remove('active'); });
        document.querySelector('.filter-agg[data-value="hour"]').classList.add('active');
        fetchData();
    });

    fetchData();
    setInterval(fetchData, 30000);
})();
</script>
