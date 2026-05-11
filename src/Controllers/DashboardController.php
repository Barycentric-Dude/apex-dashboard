<?php

declare(strict_types=1);

namespace App\Controllers;

final class DashboardController
{
    public function __construct(private array $app)
    {
    }

    public function home(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!is_string($userId)) {
            redirect_to('/login');
        }

        redirect_to('/dashboard');
    }

    public function index(): void
    {
        $user = $this->requireUser();
        $store = $this->app['store'];

        $companies = $store->all('companies');
        $companyIndex = [];
        foreach ($companies as $company) {
            $companyIndex[$company['id']] = $company;
        }

        $panels = $store->all('panels');
        if ($user['role'] !== 'super_admin') {
            $panels = array_values(array_filter($panels, fn (array $panel): bool => $panel['company_id'] === $user['company_id']));
        }

        $latestStates = $store->all('latest_states');
        $alerts = $store->all('alerts');

        $panelCards = [];
        $onlineCount = 0;
        $offlineCount = 0;
        $openAlertCount = 0;
        $fireCount = 0;

        foreach ($panels as $panel) {
            $panelStates = array_values(array_filter($latestStates, fn (array $state): bool => $state['panel_id'] === $panel['id']));
            $panelAlerts = array_values(array_filter($alerts, fn (array $alert): bool => $alert['panel_id'] === $panel['id'] && $alert['status'] === 'open'));

            $lastReportedAt = null;
            foreach ($panelStates as $state) {
                if ($lastReportedAt === null || strtotime($state['reported_at']) > strtotime($lastReportedAt)) {
                    $lastReportedAt = $state['reported_at'];
                }
            }

            $isOffline = $this->isOffline($lastReportedAt);
            if ($isOffline) {
                $offlineCount++;
            } else {
                $onlineCount++;
            }

            $openAlertCount += count($panelAlerts);
            foreach ($panelAlerts as $alert) {
                if ($alert['type'] === 'fire') {
                    $fireCount++;
                }
            }

            $panelCards[] = [
                'panel' => $panel,
                'company' => $companyIndex[$panel['company_id']] ?? null,
                'last_reported_at' => $lastReportedAt,
                'is_offline' => $isOffline,
                'state_count' => count($panelStates),
                'open_alerts' => count($panelAlerts),
            ];
        }

        usort($panelCards, static fn (array $a, array $b): int => strcmp($a['panel']['name'], $b['panel']['name']));

        app_view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => [
                'total_panels' => count($panelCards),
                'online_panels' => $onlineCount,
                'offline_panels' => $offlineCount,
                'open_alerts' => $openAlertCount,
                'fire_events' => $fireCount,
            ],
            'panelCards' => $panelCards,
        ]);
    }

    public function panel(string $id): void
    {
        $user = $this->requireUser();
        $store = $this->app['store'];

        $panel = $store->find('panels', fn (array $record): bool => $record['id'] === $id);
        if ($panel === null) {
            http_response_code(404);
            echo 'Panel not found';
            return;
        }

        if ($user['role'] !== 'super_admin' && $panel['company_id'] !== $user['company_id']) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $states = array_values(array_filter(
            $store->all('latest_states'),
            fn (array $state): bool => $state['panel_id'] === $panel['id']
        ));
        usort($states, static fn (array $a, array $b): int => strcmp($a['panel_input'], $b['panel_input']));

        $alerts = array_values(array_filter(
            $store->all('alerts'),
            fn (array $alert): bool => $alert['panel_id'] === $panel['id']
        ));
        usort($alerts, static fn (array $a, array $b): int => strtotime($b['reported_at']) <=> strtotime($a['reported_at']));

        $logs = array_values(array_filter(
            $store->all('telemetry_logs'),
            fn (array $log): bool => $log['panel_id'] === $panel['id']
        ));
        usort($logs, static fn (array $a, array $b): int => strtotime($b['received_at']) <=> strtotime($a['received_at']));

        $lastReportedAt = $states[0]['reported_at'] ?? null;
        foreach ($states as $state) {
            if ($lastReportedAt === null || strtotime($state['reported_at']) > strtotime($lastReportedAt)) {
                $lastReportedAt = $state['reported_at'];
            }
        }

        // Get input mappings for this panel's company
        $mappings = $store->filter('input_mappings', fn($m) => $m['company_id'] === $panel['company_id']);
        $mappingIndex = [];
        foreach ($mappings as $m) {
            $mappingIndex[$m['panel_input']] = $m['friendly_name'];
        }

        app_view('dashboard/panel', [
            'title' => $panel['name'],
            'user' => $user,
            'panel' => $panel,
            'states' => $states,
            'alerts' => $alerts,
            'logs' => array_slice($logs, 0, 10),
            'isOffline' => $this->isOffline($lastReportedAt),
            'lastReportedAt' => $lastReportedAt,
            'input_mappings' => $mappingIndex,
            'app' => $this->app,
        ]);
    }

    private function requireUser(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!is_string($userId)) {
            redirect_to('/login');
        }

        $user = $this->app['store']->find('users', static fn (array $record): bool => $record['id'] === $userId);
        if ($user === null) {
            session_destroy();
            redirect_to('/login');
        }

        $company = $this->app['store']->find('companies', static fn (array $record): bool => $record['id'] === $user['company_id']);
        if ($user['role'] !== 'super_admin' && !$this->isSubscriptionActive($company)) {
            session_destroy();
            echo 'Subscription inactive or expired.';
            exit;
        }

        return $user;
    }

    private function isSubscriptionActive(?array $company): bool
    {
        if ($company === null) {
            return false;
        }

        if (($company['subscription_status'] ?? 'inactive') === 'internal') {
            return true;
        }

        if (($company['subscription_status'] ?? 'inactive') !== 'active') {
            return false;
        }

        $endsAt = $company['subscription_ends_at'] ?? null;
        return $endsAt === null || strtotime($endsAt) >= strtotime(gmdate('Y-m-d'));
    }

    public function telemetry(string $id): void
    {
        $user = $this->requireUser();
        $store = $this->app['store'];

        $panel = $store->find('panels', fn (array $record): bool => $record['id'] === $id);
        if ($panel === null) {
            http_response_code(404);
            echo 'Panel not found';
            return;
        }

        if ($user['role'] !== 'super_admin' && $panel['company_id'] !== $user['company_id']) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $mappings = $store->filter('input_mappings', fn($m) => $m['company_id'] === $panel['company_id']);
        $mappingIndex = [];
        foreach ($mappings as $m) {
            $mappingIndex[$m['panel_input']] = $m['friendly_name'];
        }

        app_view('dashboard/telemetry', [
            'title' => $panel['name'] . ' - Telemetry',
            'user' => $user,
            'panel' => $panel,
            'input_mappings' => $mappingIndex,
        ]);
    }

    public function apiTelemetry(string $id): void
    {
        header('Content-Type: application/json');
        $user = $this->requireUser();
        $store = $this->app['store'];

        $panel = $store->find('panels', fn (array $record): bool => $record['id'] === $id);
        if ($panel === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Panel not found']);
            return;
        }

        if ($user['role'] !== 'super_admin' && $panel['company_id'] !== $user['company_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $inputs = !empty($_GET['input']) ? explode(',', $_GET['input']) : [];
        $eventTypes = !empty($_GET['event_type']) ? explode(',', $_GET['event_type']) : [];
        $from = $_GET['from'] ?? gmdate('c', strtotime('-24 hours'));
        $to = $_GET['to'] ?? gmdate('c');
        $aggregate = $_GET['aggregate'] ?? 'hour';
        $limit = min((int) ($_GET['limit'] ?? 200), 1000);

        $allLogs = $store->all('telemetry_logs');
        $panelLogs = array_values(array_filter(
            $allLogs,
            fn (array $log): bool => $log['panel_id'] === $panel['id']
        ));

        $filtered = array_values(array_filter($panelLogs, function (array $log) use ($inputs, $eventTypes, $from, $to): bool {
            $p = $log['payload'];
            $ts = strtotime($log['received_at']);
            if ($ts < strtotime($from)) return false;
            if ($ts > strtotime($to)) return false;
            if (!empty($inputs) && !in_array($p['panel_input'], $inputs)) return false;
            if (!empty($eventTypes) && !in_array($p['event_type'], $eventTypes)) return false;
            return true;
        }));

        $perInput = [];
        $perEventType = [];
        foreach ($filtered as $log) {
            $p = $log['payload'];
            $perInput[$p['panel_input']] = ($perInput[$p['panel_input']] ?? 0) + 1;
            $perEventType[$p['event_type']] = ($perEventType[$p['event_type']] ?? 0) + 1;
        }

        $timeSeries = $this->buildTimeSeries($filtered, $aggregate);

        usort($filtered, static fn (array $a, array $b): int => strtotime($b['received_at']) <=> strtotime($a['received_at']));
        $logs = array_slice($filtered, 0, $limit);

        echo json_encode([
            'panel' => $panel,
            'stats' => [
                'total' => count($filtered),
                'per_input' => $perInput,
                'per_event_type' => $perEventType,
            ],
            'time_series' => $timeSeries,
            'logs' => $logs,
        ]);
    }

    private function buildTimeSeries(array $logs, string $aggregate): array
    {
        $buckets = [];
        foreach ($logs as $log) {
            $ts = strtotime($log['received_at']);
            $key = match ($aggregate) {
                'day' => gmdate('Y-m-d', $ts),
                'hour' => gmdate('Y-m-d H:00', $ts),
                default => gmdate('Y-m-d H:i', $ts),
            };
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['bucket' => $key];
            }
            $et = $log['payload']['event_type'];
            $buckets[$key][$et] = ($buckets[$key][$et] ?? 0) + 1;
        }
        ksort($buckets);
        return array_values($buckets);
    }

    private function isOffline(?string $reportedAt): bool
    {
        if ($reportedAt === null) {
            return true;
        }

        $cutoff = strtotime('-' . (int) $this->app['config']['offline_after_minutes'] . ' minutes');
        return strtotime($reportedAt) < $cutoff;
    }

    public function apiPanelDetail(string $id): void
    {
        header('Content-Type: application/json');
        $user = $this->requireUser();
        $store = $this->app['store'];

        $panel = $store->find('panels', fn (array $record): bool => $record['id'] === $id);
        if ($panel === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Panel not found']);
            return;
        }

        if ($user['role'] !== 'super_admin' && $panel['company_id'] !== $user['company_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $states = array_values(array_filter(
            $store->all('latest_states'),
            fn (array $state): bool => $state['panel_id'] === $panel['id']
        ));
        usort($states, static fn (array $a, array $b): int => strcmp($a['panel_input'], $b['panel_input']));

        $alerts = array_values(array_filter(
            $store->all('alerts'),
            fn (array $alert): bool => $alert['panel_id'] === $panel['id']
        ));
        usort($alerts, static fn (array $a, array $b): int => strtotime($b['reported_at']) <=> strtotime($a['reported_at']));

        $mainsOk = true;
        $battOk = true;
        $openFaults = 0;
        foreach ($states as $state) {
            if ((int) $state['mains_status'] === 0) $mainsOk = false;
            if ((int) $state['batt_status'] === 0) $battOk = false;
        }
        foreach ($alerts as $alert) {
            if ($alert['status'] === 'open') $openFaults++;
        }

        $lastReportedAt = null;
        foreach ($states as $state) {
            if ($lastReportedAt === null || strtotime($state['reported_at']) > strtotime($lastReportedAt)) {
                $lastReportedAt = $state['reported_at'];
            }
        }

        $mappings = $store->filter('input_mappings', fn($m) => $m['company_id'] === $panel['company_id']);
        $mappingIndex = [];
        foreach ($mappings as $m) {
            $mappingIndex[$m['panel_input']] = $m['friendly_name'];
        }

        $logs = array_values(array_filter(
            $store->all('telemetry_logs'),
            fn (array $log): bool => $log['panel_id'] === $panel['id']
        ));
        usort($logs, static fn (array $a, array $b): int => strtotime($b['received_at']) <=> strtotime($a['received_at']));

        echo json_encode([
            'panel' => $panel,
            'states' => $states,
            'alerts' => $alerts,
            'logs' => array_slice($logs, 0, 10),
            'isOffline' => $this->isOffline($lastReportedAt),
            'lastReportedAt' => $lastReportedAt,
            'mainsOk' => $mainsOk,
            'battOk' => $battOk,
            'openFaults' => $openFaults,
            'input_mappings' => $mappingIndex,
        ]);
    }

    public function apiPanels(): void
    {
        header('Content-Type: application/json');
        $store = $this->app['store'];
        $panels = $store->all('panels');
        echo json_encode($panels);
    }

    public function apiPanel(): void
    {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? $_POST['id'] ?? $_REQUEST['id'] ?? null;
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Panel ID required']);
            return;
        }
        $store = $this->app['store'];
        $panel = $store->find('panels', $id);
        if ($panel === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Panel not found']);
            return;
        }
        echo json_encode($panel);
    }

    public function apiAlerts(): void
    {
        header('Content-Type: application/json');
        $store = $this->app['store'];
        $alerts = $store->all('alerts');
        echo json_encode($alerts);
    }

    public function apiDashboard(): void
    {
        header('Content-Type: application/json');
        $user = $this->requireUser();
        $store = $this->app['store'];

        $companies = $store->all('companies');
        $companyIndex = [];
        foreach ($companies as $company) {
            $companyIndex[$company['id']] = $company;
        }

        $panels = $store->all('panels');
        if ($user['role'] !== 'super_admin') {
            $panels = array_values(array_filter($panels, fn (array $panel): bool => $panel['company_id'] === $user['company_id']));
        }

        $latestStates = $store->all('latest_states');
        $alerts = $store->all('alerts');

        $panelCards = [];
        $onlineCount = 0;
        $offlineCount = 0;
        $openAlertCount = 0;
        $fireCount = 0;
        $lastUpdated = null;

        foreach ($panels as $panel) {
            $panelStates = array_values(array_filter($latestStates, fn (array $state): bool => $state['panel_id'] === $panel['id']));
            $panelAlerts = array_values(array_filter($alerts, fn (array $alert): bool => $alert['panel_id'] === $panel['id'] && $alert['status'] === 'open'));

            $lastReportedAt = null;
            foreach ($panelStates as $state) {
                if ($lastReportedAt === null || strtotime($state['reported_at']) > strtotime($lastReportedAt)) {
                    $lastReportedAt = $state['reported_at'];
                }
            }

            if ($lastUpdated === null || ($lastReportedAt !== null && strtotime($lastReportedAt) > strtotime($lastUpdated))) {
                $lastUpdated = $lastReportedAt;
            }

            $isOffline = $this->isOffline($lastReportedAt);
            if ($isOffline) {
                $offlineCount++;
            } else {
                $onlineCount++;
            }

            $openAlertCount += count($panelAlerts);
            foreach ($panelAlerts as $alert) {
                if ($alert['type'] === 'fire') {
                    $fireCount++;
                }
            }

            $panelCards[] = [
                'panel' => $panel,
                'company' => $companyIndex[$panel['company_id']] ?? null,
                'last_reported_at' => $lastReportedAt,
                'is_offline' => $isOffline,
                'state_count' => count($panelStates),
                'open_alerts' => count($panelAlerts),
            ];
        }

        usort($panelCards, static fn (array $a, array $b): int => strcmp($a['panel']['name'], $b['panel']['name']));

        echo json_encode([
            'stats' => [
                'total_panels' => count($panelCards),
                'online_panels' => $onlineCount,
                'offline_panels' => $offlineCount,
                'open_alerts' => $openAlertCount,
                'fire_events' => $fireCount,
                'last_updated' => $lastUpdated,
            ],
            'panelCards' => $panelCards,
        ]);
    }
}
