<?php
// src/Controllers/TelemetryController.php

declare(strict_types=1);

namespace App\Controllers;

final class TelemetryController
{
    public function __construct(private array $app) {}
    
    public function ingest(): void
    {
        $data = request_json();
        
        // Validate required fields
        $required = ['device_id', 'panel_input', 'event_type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                json_response(['error' => "Missing $field"], 400);
                return;
            }
        }
        
        $store = $this->app['store'];
        
        // Find panel by device_id
        $panel = $store->find('panels', fn($p) => $p['device_id'] === $data['device_id']);
        if (!$panel) {
            json_response(['error' => 'Panel not registered'], 404);
            return;
        }
        
        // Store telemetry
        $store->append('telemetry_logs', [
            'id' => $store->nextId('log'),
            'panel_id' => $panel['id'],
            'panel_input' => $data['panel_input'],
            'event_type' => $data['event_type'],
            'current' => $data['current'] ?? null,
            'device_status' => $data['device_status'] ?? 0,
            'water_level' => $data['water_level'] ?? null,
            'mains_status' => $data['mains_status'] ?? 0,
            'batt_status' => $data['batt_status'] ?? 0,
            'reported_at' => $data['reported_at'] ?? now_iso(),
            'received_at' => now_iso(),
        ]);
        
        // Update latest state
        $this->upsertLatestState($panel, $data);
        
        // Sync alerts
        $this->syncAlerts($panel, $data);
        
        json_response(['status' => 'ok'], 200);
    }
    
    private function upsertLatestState(array $panel, array $data): void
    {
        $store = $this->app['store'];
        $states = $store->all('latest_states');
        $matched = false;
        
        foreach ($states as &$state) {
            if ($state['panel_id'] === $panel['id'] && $state['panel_input'] === $data['panel_input']) {
                $state = array_merge($state, [
                    'event_type' => $data['event_type'],
                    'current' => $data['current'],
                    'device_status' => $data['device_status'],
                    'water_level' => $data['water_level'],
                    'mains_status' => $data['mains_status'],
                    'batt_status' => $data['batt_status'],
                    'reported_at' => $data['reported_at'],
                    'updated_at' => now_iso()
                ]);
                $matched = true;
                break;
            }
        }
        
        if (!$matched) {
            $states[] = [
                'id' => $store->nextId('state'),
                'panel_id' => $panel['id'],
                'panel_input' => $data['panel_input'],
                'event_type' => $data['event_type'],
                'current' => $data['current'],
                'device_status' => $data['device_status'],
                'water_level' => $data['water_level'],
                'mains_status' => $data['mains_status'],
                'batt_status' => $data['batt_status'],
                'reported_at' => $data['reported_at'],
                'updated_at' => now_iso()
            ];
        }
        
        $store->write('latest_states', $states);
    }
    
    private function syncAlerts(array $panel, array $data): void
    {
        $store = $this->app['store'];
        $alerts = $store->all('alerts');
        
        // Auto-resolve alerts when condition clears
        foreach ($alerts as &$alert) {
            if ($alert['panel_id'] === $panel['id'] && $alert['panel_input'] === $data['panel_input']) {
                if ($data['event_type'] !== 'FIRE' && $data['device_status'] != 0) {
                    $alert['status'] = 'resolved';
                    $alert['resolved_at'] = now_iso();
                }
            }
        }
        unset($alert);
        
        // Create new alerts
        if ($data['event_type'] === 'FIRE') {
            $alerts[] = [
                'id' => $store->nextId('alert'),
                'panel_id' => $panel['id'],
                'panel_input' => $data['panel_input'],
                'type' => 'fire',
                'status' => 'open',
                'message' => 'Fire event reported',
                'reported_at' => $data['reported_at'],
                'created_at' => now_iso()
            ];
        }
        
        if ($data['device_status'] == 0) {
            $alerts[] = [
                'id' => $store->nextId('alert'),
                'panel_id' => $panel['id'],
                'panel_input' => $data['panel_input'],
                'type' => 'device_offline',
                'status' => 'open',
                'message' => 'Device status is offline',
                'reported_at' => $data['reported_at'],
                'created_at' => now_iso()
            ];
        }
        
        $store->write('alerts', $alerts);
    }
}