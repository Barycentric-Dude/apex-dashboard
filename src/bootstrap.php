<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/Support/helpers.php';

session_start();

date_default_timezone_set('UTC');

$config = [
    'app_name' => 'Apex Fire IoT Dashboard',
    'storage_path' => dirname(__DIR__) . '/data',
    'offline_after_minutes' => 24,
];

$store = new App\Storage\JsonStore($config['storage_path']);
$store->bootstrap();

$app = [
    'config' => $config,
    'store' => $store,
];
