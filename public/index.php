<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Router;

$basePath = (string) ($GLOBALS['config']['app']['base_path'] ?? '');
$router = new Router($basePath);

require BASE_PATH . '/routes/web.php';

$router->dispatch();
