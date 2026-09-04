<?php

declare(strict_types=1);

// Встроенный сервер PHP отдаёт существующие статические файлы напрямую.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

// Остальные запросы передаются единой точке входа Symfony.
require __DIR__ . '/index.php';
