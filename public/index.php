<?php
// Front controller: bootstraps the app and routes to the correct page.

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../config/routes.php';

$page = $_GET['page'] ?? 'event_feed';

// Resolve the view file from the routes map; fall back to 404.
$viewFile = $routes[$page] ?? null;

if ($viewFile === null) {
    http_response_code(404);
    $viewFile = __DIR__ . '/../views/pages/404.php';
}

// Shared layout: header → page content → footer.
require __DIR__ . '/../views/layout/header.php';
require __DIR__ . '/../views/partials/flash_messages.php';
require $viewFile;
require __DIR__ . '/../views/layout/footer.php';

