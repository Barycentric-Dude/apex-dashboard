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

        app_view('admin/index', [
            'title' => 'Apex Admin',
            'user' => $user,
            'companies' => $store->all('companies'),
            'users' => $store->all('users'),
            'panels' => $store->all('panels'),
            'flash' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function createCompany(): void
    {
        $this->requireAdmin();
        verify_csrf();
        $store = $this->app['store'];

        $company = [
            'id' => $store->nextId('company'),
            'name' => form_value('name'),
            'slug' => strtolower(preg_replace('/[^a-z0-9]+/', '-', form_value('name')) ?? ''),
            'subscription_status' => form_value('subscription_status', 'active'),
            'subscription_ends_at' => form_value('subscription_ends_at'),
            'panel_limit' => (int) form_value('panel_limit', '1'),
            'created_at' => now_iso(),
        ];

        if ($company['name'] === '') {
            $_SESSION['flash_error'] = 'Company name is required.';
            redirect_to('/admin');
        }

        $store->append('companies', $company);
        $_SESSION['flash_success'] = 'Company created.';
        redirect_to('/admin');
    }

    public function createUser(): void
    {
        $this->requireAdmin();
        verify_csrf();
        $store = $this->app['store'];

        $password = form_value('password');
        $user = [
            'id' => $store->nextId('user'),
            'company_id' => form_value('company_id'),
            'name' => form_value('name'),
            'email' => strtolower(form_value('email')),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => form_value('role', 'client_admin'),
            'created_at' => now_iso(),
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
        verify_csrf();
        $store = $this->app['store'];

        $token = form_value('token');
        if ($token === '') {
            $token = bin2hex(random_bytes(16));
        }

        $panel = [
            'id' => $store->nextId('panel'),
            'company_id' => form_value('company_id'),
            'name' => form_value('name'),
            'site_name' => form_value('site_name'),
            'device_id' => form_value('device_id'),
            'token' => $token,
            // water_level removed
            'reporting_interval_minutes' => (int) form_value('reporting_interval_minutes', '12'),
            'created_at' => now_iso(),
        ];

        if ($panel['company_id'] === '' || $panel['name'] === '' || $panel['device_id'] === '') {
            $_SESSION['flash_error'] = 'Panel form is incomplete.';
            redirect_to('/admin');
        }

        $store->append('panels', $panel);
        $_SESSION['flash_success'] = 'Panel created.';
        redirect_to('/admin');
    }

    public function deleteCompany(string $id): void
    {
        $this->requireAdmin();
        verify_csrf();
        $store = $this->app['store'];
        $store->replace('companies', fn(array $items): array => array_values(array_filter($items, fn($c) => $c['id'] !== $id)));
        $_SESSION['flash_success'] = 'Company deleted.';
        redirect_to('/admin');
    }

    public function deleteUser(string $id): void
    {
        $this->requireAdmin();
        verify_csrf();
        $store = $this->app['store'];
        $store->replace('users', fn(array $items): array => array_values(array_filter($items, fn($u) => $u['id'] !== $id)));
        $_SESSION['flash_success'] = 'User deleted.';
        redirect_to('/admin');
    }

    public function deletePanel(string $id): void
    {
        $this->requireAdmin();
        verify_csrf();
        $store = $this->app['store'];
        $store->replace('panels', fn(array $items): array => array_values(array_filter($items, fn($p) => $p['id'] !== $id)));
        $_SESSION['flash_success'] = 'Panel deleted.';
        redirect_to('/admin');
    }

    public function inputMappings(): void
    {
        $user = $this->requireAdmin();
        $store = $this->app['store'];

        $mappings = $store->all('input_mappings');
        $companies = $store->all('companies');

        // Group by company
        $grouped = [];
        foreach ($companies as $company) {
            $grouped[$company['id']] = [
                'name' => $company['name'],
                'mappings' => []
            ];
        }

        foreach ($mappings as $m) {
            if (isset($grouped[$m['company_id']])) {
                $grouped[$m['company_id']]['mappings'][] = $m;
            }
        }

        app_view('admin/input_mappings', [
            'title' => 'Input Mappings',
            'user' => $user,
            'grouped' => $grouped,
            'all_mappings' => $mappings,
        ]);
    }

    public function saveInputMappings(): void
    {
        $this->requireAdmin();
        verify_csrf();
        $store = $this->app['store'];

        $companyId = form_value('company_id');
        $input = form_value('panel_input');
        $friendlyName = form_value('friendly_name');

        if ($input === '' || $friendlyName === '') {
            $_SESSION['flash_error'] = 'Input and friendly name are required.';
            redirect_to('/admin/input-mappings');
        }

        // Check if mapping exists
        $existing = $store->find('input_mappings', fn($m) =>
            $m['company_id'] === $companyId && $m['panel_input'] === $input
        );

        if ($existing) {
            // Update
            $store->replace('input_mappings', function($items) use ($companyId, $input, $friendlyName) {
                foreach ($items as &$item) {
                    if ($item['company_id'] === $companyId && $item['panel_input'] === $input) {
                        $item['friendly_name'] = $friendlyName;
                        $item['updated_at'] = now_iso();
                    }
                }
                return $items;
            });
        } else {
            // Create
            $store->append('input_mappings', [
                'id' => $store->nextId('mapping'),
                'company_id' => $companyId,
                'panel_input' => $input,
                'friendly_name' => $friendlyName,
                'sort_order' => 0,
                'created_at' => now_iso(),
                'updated_at' => now_iso(),
            ]);
        }

        $_SESSION['flash_success'] = 'Input mapping saved.';
        redirect_to('/admin/input-mappings');
    }

    private function requireAdmin(): array
    {
        $userId = $_SESSION['user_id'] ?? null;
        $user = is_string($userId)
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
