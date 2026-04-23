<?php

declare(strict_types=1);

namespace App\Storage;

use RuntimeException;

final class JsonStore
{
    private string $basePath;

    private array $files = [
        'companies' => 'companies.json',
        'users' => 'users.json',
        'panels' => 'panels.json',
        'telemetry_logs' => 'telemetry_logs.json',
        'latest_states' => 'latest_states.json',
        'alerts' => 'alerts.json',
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function bootstrap(): void
    {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }

        foreach ($this->files as $key => $file) {
            $path = $this->path($key);
            if (!is_file($path)) {
                $this->write($key, []);
            }
        }

        $this->seedIfEmpty();
    }

    public function all(string $collection): array
    {
        $path = $this->path($collection);
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function write(string $collection, array $records): void
    {
        $path = $this->path($collection);
        $encoded = json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode JSON.');
        }

        file_put_contents($path, $encoded . PHP_EOL, LOCK_EX);
    }

    public function append(string $collection, array $record): array
    {
        $records = $this->all($collection);
        $records[] = $record;
        $this->write($collection, $records);
        return $record;
    }

    public function find(string $collection, callable $predicate): ?array
    {
        foreach ($this->all($collection) as $record) {
            if ($predicate($record)) {
                return $record;
            }
        }

        return null;
    }

    public function filter(string $collection, callable $predicate): array
    {
        return array_values(array_filter($this->all($collection), $predicate));
    }

    public function replace(string $collection, callable $mutator): void
    {
        $records = $this->all($collection);
        $mutated = $mutator($records);
        $this->write($collection, is_array($mutated) ? $mutated : $records);
    }

    public function nextId(string $prefix): string
    {
        return $prefix . '_' . bin2hex(random_bytes(4));
    }

    private function path(string $collection): string
    {
        if (!isset($this->files[$collection])) {
            throw new RuntimeException('Unknown collection: ' . $collection);
        }

        return $this->basePath . '/' . $this->files[$collection];
    }

    private function seedIfEmpty(): void
    {
        if ($this->all('users') !== []) {
            return;
        }

        $companies = [
            [
                'id' => 'company_apex',
                'name' => 'Apex Internal',
                'slug' => 'apex-internal',
                'subscription_status' => 'internal',
                'subscription_ends_at' => null,
                'panel_limit' => 999,
                'created_at' => now_iso(),
            ],
            [
                'id' => 'company_demo',
                'name' => 'Demo Industries',
                'slug' => 'demo-industries',
                'subscription_status' => 'active',
                'subscription_ends_at' => gmdate('Y-m-d', strtotime('+1 year')),
                'panel_limit' => 10,
                'created_at' => now_iso(),
            ],
        ];

        $users = [
            [
                'id' => 'user_admin',
                'company_id' => 'company_apex',
                'name' => 'Apex Admin',
                'email' => 'admin@apex.local',
                'password_hash' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
                'role' => 'super_admin',
                'created_at' => now_iso(),
            ],
            [
                'id' => 'user_demo',
                'company_id' => 'company_demo',
                'name' => 'Demo Ops',
                'email' => 'ops@demo-industries.local',
                'password_hash' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
                'role' => 'client_admin',
                'created_at' => now_iso(),
            ],
        ];

        $panels = [
            [
                'id' => 'panel_demo_1',
                'company_id' => 'company_demo',
                'name' => 'Mumbai Plant Fire Panel',
                'site_name' => 'Mumbai Plant',
                'device_id' => 'APX-DEMO-001',
                'token' => 'demo-panel-token',
                'water_level_threshold' => 30,
                'reporting_interval_minutes' => 12,
                'created_at' => now_iso(),
            ],
        ];

        $latestStates = [
            [
                'id' => 'state_demo_1',
                'panel_id' => 'panel_demo_1',
                'panel_input' => 'DI_1',
                'event_type' => 'NORMAL',
                'current' => 5.12,
                'device_status' => 1,
                'water_level' => 73,
                'mains_status' => 1,
                'batt_status' => 1,
                'reported_at' => gmdate('c', strtotime('-8 minutes')),
                'updated_at' => now_iso(),
            ],
            [
                'id' => 'state_demo_2',
                'panel_id' => 'panel_demo_1',
                'panel_input' => 'DI_2',
                'event_type' => 'FAULT',
                'current' => 4.96,
                'device_status' => 1,
                'water_level' => 28,
                'mains_status' => 1,
                'batt_status' => 1,
                'reported_at' => gmdate('c', strtotime('-8 minutes')),
                'updated_at' => now_iso(),
            ],
        ];

        $alerts = [
            [
                'id' => 'alert_demo_low_water',
                'panel_id' => 'panel_demo_1',
                'panel_input' => 'DI_2',
                'type' => 'low_water',
                'status' => 'open',
                'message' => 'Water level is below threshold.',
                'reported_at' => gmdate('c', strtotime('-8 minutes')),
                'updated_at' => now_iso(),
            ],
        ];

        $telemetryLogs = [
            [
                'id' => 'log_demo_1',
                'panel_id' => 'panel_demo_1',
                'payload' => [
                    'device_id' => 'APX-DEMO-001',
                    'panel_input' => 'DI_1',
                    'event_type' => 'NORMAL',
                    'current' => 5.12,
                    'device_status' => 1,
                    'water_level' => 73,
                    'mains_status' => 1,
                    'batt_status' => 1,
                    'reported_at' => gmdate('c', strtotime('-8 minutes')),
                ],
                'received_at' => now_iso(),
            ],
            [
                'id' => 'log_demo_2',
                'panel_id' => 'panel_demo_1',
                'payload' => [
                    'device_id' => 'APX-DEMO-001',
                    'panel_input' => 'DI_2',
                    'event_type' => 'FAULT',
                    'current' => 4.96,
                    'device_status' => 1,
                    'water_level' => 28,
                    'mains_status' => 1,
                    'batt_status' => 1,
                    'reported_at' => gmdate('c', strtotime('-8 minutes')),
                ],
                'received_at' => now_iso(),
            ],
        ];

        $this->write('companies', $companies);
        $this->write('users', $users);
        $this->write('panels', $panels);
        $this->write('latest_states', $latestStates);
        $this->write('alerts', $alerts);
        $this->write('telemetry_logs', $telemetryLogs);
    }
}
