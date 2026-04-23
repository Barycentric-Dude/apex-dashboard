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

        app_view('dashboard/panel', [
            'title' => $panel['name'],
            'user' => $user,
            'panel' => $panel,
            'states' => $states,
            'alerts' => $alerts,
            'logs' => array_slice($logs, 0, 10),
            'isOffline' => $this->isOffline($lastReportedAt),
            'lastReportedAt' => $lastReportedAt,
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

    private function isOffline(?string $reportedAt): bool
    {
        if ($reportedAt === null) {
            return true;
        }

        $cutoff = strtotime('-' . (int) $this->app['config']['offline_after_minutes'] . ' minutes');
        return strtotime($reportedAt) < $cutoff;
    }
}
