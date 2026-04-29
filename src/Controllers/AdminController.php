<?php

declare(strict_types=1);

namespace App\Controllers;

final class AdminController
{
    public function __construct(private array $app)
    {
    }

    public function index(): void
    {
        $user = $this->requireAdmin();
        $store = $this->app['store'];

        $allAlerts = $store->all('alerts');
        $openAlerts = array_filter($allAlerts, static fn ($a) => $a['status'] === 'open');

        app_view('admin/index', [
            'title'     => 'Apex Admin',
            'user'      => $user,
            'companies' => $store->all('companies'),
            'users'     => $store->all('users'),
            'panels'    => $store->all('panels'),
            'stats'     => [
                'companies'  => count($store->all('companies')),
                'users'      => count($store->all('users')),
                'panels'     => count($store->all('panels')),
                'open_alerts'=> count($openAlerts),
                'fire_events'=> count(array_filter($openAlerts, static fn ($a) => $a['type'] === 'fire')),
            ],
            'flash' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    // =========================================================
    // CREATE
    // =========================================================

    public function createCompany(): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $name = form_value('name');
        if ($name === '') {
            $_SESSION['flash_error'] = 'Company name is required.';
            redirect_to('/admin');
        }

        $store->append('companies', [
            'id'                  => $store->nextId('company'),
            'name'                => $name,
            'slug'                => strtolower(preg_replace('/[^a-z0-9]+/', '-', $name) ?? ''),
            'subscription_status' => form_value('subscription_status', 'active'),
            'subscription_ends_at'=> form_value('subscription_ends_at') ?: null,
            'panel_limit'         => (int) form_value('panel_limit', '1'),
            'created_at'          => now_iso(),
        ]);

        $_SESSION['flash_success'] = 'Company created.';
        redirect_to('/admin');
    }

    public function createUser(): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $password = form_value('password');
        $user = [
            'id'            => $store->nextId('user'),
            'company_id'    => form_value('company_id'),
            'name'          => form_value('name'),
            'email'         => strtolower(form_value('email')),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => form_value('role', 'client_admin'),
            'created_at'    => now_iso(),
        ];

        if ($user['company_id'] === '' || $user['name'] === '' || $user['email'] === '' || $password === '') {
            $_SESSION['flash_error'] = 'User form is incomplete.';
            redirect_to('/admin');
        }

        $store->append('users', $user);
        $_SESSION['flash_success'] = 'User created.';
        redirect_to('/admin');
    }

    public function createPanel(): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $token = form_value('token');
        if ($token === '') {
            $token = bin2hex(random_bytes(16));
        }

        $panel = [
            'id'                        => $store->nextId('panel'),
            'company_id'                => form_value('company_id'),
            'name'                      => form_value('name'),
            'site_name'                 => form_value('site_name'),
            'device_id'                 => form_value('device_id'),
            'token'                     => $token,
            'water_level_threshold'     => (float) form_value('water_level_threshold', '30'),
            'reporting_interval_minutes'=> (int) form_value('reporting_interval_minutes', '12'),
            'created_at'                => now_iso(),
        ];

        if ($panel['company_id'] === '' || $panel['name'] === '' || $panel['device_id'] === '') {
            $_SESSION['flash_error'] = 'Panel form is incomplete.';
            redirect_to('/admin');
        }

        $store->append('panels', $panel);
        $_SESSION['flash_success'] = 'Panel created.';
        redirect_to('/admin');
    }

    // =========================================================
    // COMPANY EDIT / DELETE
    // =========================================================

    public function editCompanyForm(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];
        $company = $store->find('companies', static fn ($c) => $c['id'] === $id);

        if ($company === null) {
            http_response_code(404);
            echo 'Company not found';
            exit;
        }

        app_view('admin/edit_company', [
            'title'   => 'Edit Company',
            'company' => $company,
            'flash'   => $_SESSION['flash_success'] ?? null,
            'error'   => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function editCompany(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $name = form_value('name');
        if ($name === '') {
            $_SESSION['flash_error'] = 'Company name is required.';
            redirect_to('/admin/companies/' . $id . '/edit');
        }

        $updates = [
            'name'                => $name,
            'slug'                => strtolower(preg_replace('/[^a-z0-9]+/', '-', $name) ?? ''),
            'subscription_status' => form_value('subscription_status'),
            'subscription_ends_at'=> form_value('subscription_ends_at') ?: null,
            'panel_limit'         => (int) form_value('panel_limit', '1'),
        ];

        $store->replace('companies', static function (array $records) use ($id, $updates): array {
            return array_map(
                static fn (array $c) => $c['id'] === $id ? array_merge($c, $updates) : $c,
                $records
            );
        });

        $_SESSION['flash_success'] = 'Company updated.';
        redirect_to('/admin');
    }

    public function deleteCompany(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $panelIds = array_column(
            array_filter($store->all('panels'), static fn ($p) => $p['company_id'] === $id),
            'id'
        );

        $store->replace('companies', static fn ($r) => array_values(array_filter($r, static fn ($c) => $c['id'] !== $id)));
        $store->replace('users', static fn ($r) => array_values(array_filter($r, static fn ($u) => $u['company_id'] !== $id)));
        $store->replace('panels', static fn ($r) => array_values(array_filter($r, static fn ($p) => $p['company_id'] !== $id)));

        if (!empty($panelIds)) {
            $store->replace('latest_states', static fn ($r) => array_values(array_filter($r, static fn ($s) => !in_array($s['panel_id'], $panelIds, true))));
            $store->replace('alerts', static fn ($r) => array_values(array_filter($r, static fn ($a) => !in_array($a['panel_id'], $panelIds, true))));
            $store->replace('telemetry_logs', static fn ($r) => array_values(array_filter($r, static fn ($l) => !in_array($l['panel_id'], $panelIds, true))));
        }

        $_SESSION['flash_success'] = 'Company and all associated data deleted.';
        redirect_to('/admin');
    }

    // =========================================================
    // USER EDIT / DELETE
    // =========================================================

    public function editUserForm(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];
        $record = $store->find('users', static fn ($u) => $u['id'] === $id);

        if ($record === null) {
            http_response_code(404);
            echo 'User not found';
            exit;
        }

        app_view('admin/edit_user', [
            'title'     => 'Edit User',
            'record'    => $record,
            'companies' => $store->all('companies'),
            'flash'     => $_SESSION['flash_success'] ?? null,
            'error'     => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function editUser(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $name  = form_value('name');
        $email = strtolower(form_value('email'));

        if ($name === '' || $email === '') {
            $_SESSION['flash_error'] = 'Name and email are required.';
            redirect_to('/admin/users/' . $id . '/edit');
        }

        $newPassword = form_value('password');

        $store->replace('users', static function (array $records) use ($id, $name, $email, $newPassword): array {
            return array_map(static function (array $u) use ($id, $name, $email, $newPassword): array {
                if ($u['id'] !== $id) {
                    return $u;
                }
                $updated = array_merge($u, [
                    'name'       => $name,
                    'email'      => $email,
                    'role'       => form_value('role', $u['role']),
                    'company_id' => form_value('company_id', $u['company_id']),
                ]);
                if ($newPassword !== '') {
                    $updated['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                return $updated;
            }, $records);
        });

        $_SESSION['flash_success'] = 'User updated.';
        redirect_to('/admin');
    }

    public function deleteUser(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];
        $store->replace('users', static fn ($r) => array_values(array_filter($r, static fn ($u) => $u['id'] !== $id)));
        $_SESSION['flash_success'] = 'User deleted.';
        redirect_to('/admin');
    }

    // =========================================================
    // PANEL EDIT / DELETE
    // =========================================================

    public function editPanelForm(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];
        $panel = $store->find('panels', static fn ($p) => $p['id'] === $id);

        if ($panel === null) {
            http_response_code(404);
            echo 'Panel not found';
            exit;
        }

        app_view('admin/edit_panel', [
            'title'     => 'Edit Panel',
            'panel'     => $panel,
            'companies' => $store->all('companies'),
            'flash'     => $_SESSION['flash_success'] ?? null,
            'error'     => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function editPanel(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        if (form_value('name') === '' || form_value('device_id') === '') {
            $_SESSION['flash_error'] = 'Panel name and Device ID are required.';
            redirect_to('/admin/panels/' . $id . '/edit');
        }

        $store->replace('panels', static function (array $records) use ($id): array {
            return array_map(static function (array $p) use ($id): array {
                if ($p['id'] !== $id) {
                    return $p;
                }
                $token = form_value('token');
                return array_merge($p, [
                    'company_id'                 => form_value('company_id', $p['company_id']),
                    'name'                       => form_value('name'),
                    'site_name'                  => form_value('site_name'),
                    'device_id'                  => form_value('device_id'),
                    'token'                      => $token !== '' ? $token : $p['token'],
                    'water_level_threshold'      => (float) form_value('water_level_threshold', (string) $p['water_level_threshold']),
                    'reporting_interval_minutes' => (int) form_value('reporting_interval_minutes', (string) $p['reporting_interval_minutes']),
                ]);
            }, $records);
        });

        $_SESSION['flash_success'] = 'Panel updated.';
        redirect_to('/admin');
    }

    public function deletePanel(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $store->replace('panels', static fn ($r) => array_values(array_filter($r, static fn ($p) => $p['id'] !== $id)));
        $store->replace('latest_states', static fn ($r) => array_values(array_filter($r, static fn ($s) => $s['panel_id'] !== $id)));
        $store->replace('alerts', static fn ($r) => array_values(array_filter($r, static fn ($a) => $a['panel_id'] !== $id)));
        $store->replace('telemetry_logs', static fn ($r) => array_values(array_filter($r, static fn ($l) => $l['panel_id'] !== $id)));

        $_SESSION['flash_success'] = 'Panel and all associated data deleted.';
        redirect_to('/admin');
    }

    // =========================================================
    // ALERT MANAGEMENT
    // =========================================================

    public function alerts(): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $statusFilter = $_GET['status'] ?? 'all';
        $allAlerts    = $store->all('alerts');

        $filtered = match ($statusFilter) {
            'open'     => array_filter($allAlerts, static fn ($a) => $a['status'] === 'open'),
            'resolved' => array_filter($allAlerts, static fn ($a) => $a['status'] === 'resolved'),
            default    => $allAlerts,
        };

        usort($filtered, static fn ($a, $b) => strcmp($b['reported_at'] ?? '', $a['reported_at'] ?? ''));

        $panelNames = array_column($store->all('panels'), 'name', 'id');

        app_view('admin/alerts', [
            'title'        => 'Alert Management',
            'alerts'       => array_values($filtered),
            'panelNames'   => $panelNames,
            'statusFilter' => $statusFilter,
            'totalOpen'    => count(array_filter($allAlerts, static fn ($a) => $a['status'] === 'open')),
            'flash'        => $_SESSION['flash_success'] ?? null,
            'error'        => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function resolveAlert(string $id): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $store->replace('alerts', static function (array $records) use ($id): array {
            return array_map(static function (array $a) use ($id): array {
                return $a['id'] === $id
                    ? array_merge($a, ['status' => 'resolved', 'updated_at' => now_iso()])
                    : $a;
            }, $records);
        });

        $_SESSION['flash_success'] = 'Alert resolved.';
        redirect_to('/admin/alerts?' . http_build_query(['status' => $_POST['return_filter'] ?? 'all']));
    }

    public function resolveAllAlerts(): void
    {
        $this->requireAdmin();
        $store = $this->app['store'];

        $store->replace('alerts', static function (array $records): array {
            return array_map(static function (array $a): array {
                return $a['status'] === 'open'
                    ? array_merge($a, ['status' => 'resolved', 'updated_at' => now_iso()])
                    : $a;
            }, $records);
        });

        $_SESSION['flash_success'] = 'All open alerts resolved.';
        redirect_to('/admin/alerts');
    }

    // =========================================================
    // GUARD
    // =========================================================

    private function requireAdmin(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        $user   = is_string($userId)
            ? $this->app['store']->find('users', static fn (array $record): bool => $record['id'] === $userId)
            : null;

        if ($user === null || $user['role'] !== 'super_admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        return $user;
    }
}
