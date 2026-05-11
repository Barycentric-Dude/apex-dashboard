<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\IngestController;
use App\Support\Router;

$router = new Router();

$authController = new AuthController($app);
$dashboardController = new DashboardController($app);
$adminController = new AdminController($app);
$ingestController = new IngestController($app);

$router->get('/', [$dashboardController, 'home']);
$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->post('/logout', [$authController, 'logout']);

$router->get('/dashboard', [$dashboardController, 'index']);
$router->get('/panels/{id}', [$dashboardController, 'panel']);
$router->get('/panels/{id}/telemetry', [$dashboardController, 'telemetry']);

$router->get('/admin', [$adminController, 'index']);
$router->post('/admin/companies', [$adminController, 'createCompany']);
$router->post('/admin/users', [$adminController, 'createUser']);
$router->post('/admin/panels', [$adminController, 'createPanel']);
$router->post('/admin/companies/{id}/delete', [$adminController, 'deleteCompany']);
$router->post('/admin/users/{id}/delete', [$adminController, 'deleteUser']);
$router->post('/admin/panels/{id}/delete', [$adminController, 'deletePanel']);
$router->get('/admin/input-mappings', [$adminController, 'inputMappings']);
$router->post('/admin/input-mappings', [$adminController, 'saveInputMappings']);

$router->post('/api/panel-ingest', [$ingestController, 'ingest']);

$router->get('/api/panels', [$dashboardController, 'apiPanels']);
$router->get('/api/panels/{id}', [$dashboardController, 'apiPanel']);
$router->get('/api/panels/{id}/detail', [$dashboardController, 'apiPanelDetail']);
$router->get('/api/panels/{id}/telemetry', [$dashboardController, 'apiTelemetry']);
$router->get('/api/alerts', [$dashboardController, 'apiAlerts']);
$router->get('/api/dashboard', [$dashboardController, 'apiDashboard']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
