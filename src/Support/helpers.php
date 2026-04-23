<?php

declare(strict_types=1);

function app_view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $templatePath = dirname(__DIR__, 2) . '/views/' . $template . '.php';
    require dirname(__DIR__, 2) . '/views/layout.php';
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function now_iso(): string
{
    return gmdate('c');
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid JSON body.');
    }

    return $decoded;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}

function form_value(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function format_datetime(?string $iso): string
{
    if ($iso === null) {
        return 'Never';
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return 'Unknown';
    }
    return date('M d, H:i', $ts);
}
