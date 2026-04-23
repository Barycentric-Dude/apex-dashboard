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

$router->get('/admin', [$adminController, 'index']);
$router->post('/admin/companies', [$adminController, 'createCompany']);
$router->post('/admin/users', [$adminController, 'createUser']);
$router->post('/admin/panels', [$adminController, 'createPanel']);

$router->post('/api/panel-ingest', [$ingestController, 'ingest']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
