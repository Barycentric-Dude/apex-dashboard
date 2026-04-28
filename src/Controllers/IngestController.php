<?php

declare(strict_types=1);

namespace App\Controllers;

use RuntimeException;

final class IngestController
{
    public function __construct(private array $app)
    {
    }

    public function ingest(): void
    {
        try {
            $token = $this->resolveToken();
            if ($token === null) {
                json_response(['error' => 'Missing panel token.'], 401);
            }

            $payload = request_json();
            if ($payload === []) {
                json_response(['error' => 'Empty JSON body.'], 422);
            }

            $records = $this->normalizePayload($payload);
            $store = $this->app['store'];
            $processed = [];

            foreach ($records as $record) {
                $panel = $store->find('panels', fn (array $panelRow): bool => $panelRow['token'] === $token && $panelRow['device_id'] === $record['device_id']);
                if ($panel === null) {
                    json_response(['error' => 'Panel not found for supplied token and device_id.'], 403);
                }

                $store->append('telemetry_logs', [
                    'id' => $store->nextId('log'),
                    'panel_id' => $panel['id'],
                    'payload' => $record,
                    'received_at' => now_iso(),
                ]);

                $this->upsertLatestState($panel, $record);
                $this->syncAlerts($panel, $record);

                $processed[] = [
                    'panel_id' => $panel['id'],
                    'panel_input' => $record['panel_input'],
                    'reported_at' => $record['reported_at'],
                ];
            }

            json_response([
                'status' => 'ok',
                'processed' => $processed,
            ], 202);
        } catch (RuntimeException $exception) {
            json_response(['error' => $exception->getMessage()], 422);
        }
    }

    private function resolveToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        $panelHeader = $_SERVER['HTTP_X_PANEL_TOKEN'] ?? '';
        return $panelHeader !== '' ? trim($panelHeader) : null;
    }

    private function normalizePayload(array $payload): array
    {
        $records = array_is_list($payload) ? $payload : [$payload];
        $normalized = [];

        foreach ($records as $index => $record) {
            if (!is_array($record)) {
                throw new RuntimeException('Each payload record must be an object.');
            }

            $recordNumber = $index + 1;
            $panelInput = trim((string) ($record['panel_input'] ?? $record['di'] ?? ''));
            $deviceId = trim((string) ($record['device_id'] ?? ''));
            $eventType = strtoupper(trim((string) ($record['event_type'] ?? '')));
            $reportedAtRaw = isset($record['reported_at']) ? trim((string) $record['reported_at']) : now_iso();

            if ($panelInput === '' || $deviceId === '') {
                throw new RuntimeException("Record {$recordNumber}: device_id and panel_input are required.");
            }
            if ($eventType === '') {
                throw new RuntimeException("Record {$recordNumber}: event_type is required.");
            }

            $deviceStatus = $this->normalizeBinaryField($record, 'device_status', $recordNumber);
            $mainsStatus = $this->normalizeBinaryField($record, 'mains_status', $recordNumber);
            $battStatus = $this->normalizeBinaryField($record, 'batt_status', $recordNumber);
            $reportedAt = strtotime($reportedAtRaw);
            if ($reportedAt === false) {
                throw new RuntimeException("Record {$recordNumber}: reported_at must be a valid date/time.");
            }

            $normalized[] = [
                'device_id' => $deviceId,
                'panel_input' => $panelInput,
                'event_type' => $eventType,
                'current' => isset($record['current']) ? (float) $record['current'] : null,
                'device_status' => $deviceStatus,
                'water_level' => isset($record['water_level']) ? (float) $record['water_level'] : null,
                'mains_status' => $mainsStatus,
                'batt_status' => $battStatus,
                'reported_at' => gmdate('c', $reportedAt),
            ];
        }

        return $normalized;
    }

    private function normalizeBinaryField(array $record, string $field, int $recordNumber): int
    {
        if (!array_key_exists($field, $record)) {
            throw new RuntimeException("Record {$recordNumber}: {$field} is required.");
        }

        $value = (string) $record[$field];
        if ($value !== '0' && $value !== '1') {
            throw new RuntimeException("Record {$recordNumber}: {$field} must be 0 or 1.");
        }

        return (int) $value;
    }

    private function upsertLatestState(array $panel, array $record): void
    {
        $store = $this->app['store'];
        $states = $store->all('latest_states');
        $matched = false;

        foreach ($states as &$state) {
            if ($state['panel_id'] === $panel['id'] && $state['panel_input'] === $record['panel_input']) {
                $state['event_type'] = $record['event_type'];
                $state['current'] = $record['current'];
                $state['device_status'] = $record['device_status'];
                $state['water_level'] = $record['water_level'];
                $state['mains_status'] = $record['mains_status'];
                $state['batt_status'] = $record['batt_status'];
                $state['reported_at'] = $record['reported_at'];
                $state['updated_at'] = now_iso();
                $matched = true;
                break;
            }
        }
        unset($state);

        if (!$matched) {
            $states[] = [
                'id' => $store->nextId('state'),
                'panel_id' => $panel['id'],
                'panel_input' => $record['panel_input'],
                'event_type' => $record['event_type'],
                'current' => $record['current'],
                'device_status' => $record['device_status'],
                'water_level' => $record['water_level'],
                'mains_status' => $record['mains_status'],
                'batt_status' => $record['batt_status'],
                'reported_at' => $record['reported_at'],
                'updated_at' => now_iso(),
            ];
        }

        $store->write('latest_states', $states);
    }

    private function syncAlerts(array $panel, array $record): void
    {
        $rules = [];

        if ($record['event_type'] === 'FIRE') {
            $rules[] = ['type' => 'fire', 'message' => 'Fire event reported.'];
        }
        if ($record['device_status'] === 0) {
            $rules[] = ['type' => 'device_offline', 'message' => 'Device status is offline.'];
        }
        if ($record['mains_status'] === 0) {
            $rules[] = ['type' => 'mains_power_lost', 'message' => 'Mains power is off.'];
        }
        if ($record['batt_status'] === 0) {
            $rules[] = ['type' => 'battery_fault', 'message' => 'Battery status is off.'];
        }
        if ($record['water_level'] !== null && $record['water_level'] < (float) $panel['water_level_threshold']) {
            $rules[] = ['type' => 'low_water', 'message' => 'Water level is below threshold.'];
        }

        $store = $this->app['store'];
        $alerts = $store->all('alerts');

        foreach ($alerts as &$alert) {
            if ($alert['panel_id'] === $panel['id'] && $alert['panel_input'] === $record['panel_input']) {
                $alert['status'] = 'resolved';
                $alert['updated_at'] = now_iso();
            }
        }
        unset($alert);

        foreach ($rules as $rule) {
            $alerts[] = [
                'id' => $store->nextId('alert'),
                'panel_id' => $panel['id'],
                'panel_input' => $record['panel_input'],
                'type' => $rule['type'],
                'status' => 'open',
                'message' => $rule['message'],
                'reported_at' => $record['reported_at'],
                'updated_at' => now_iso(),
            ];
        }

        $store->write('alerts', $alerts);
    }
}
