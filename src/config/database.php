<?php

$rootPath = dirname(__DIR__, 2);
$envPath = $rootPath . DIRECTORY_SEPARATOR . '.env';
$envExamplePath = $rootPath . DIRECTORY_SEPARATOR . '.env.example';

$config = [];
$sourcePath = file_exists($envPath) ? $envPath : $envExamplePath;

if (file_exists($sourcePath)) {
    $lines = file($sourcePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $config[$key] = $value;
        }
    }
}

$dbHost = $config['DB_HOST'] ?? 'localhost';
$dbName = $config['DB_NAME'] ?? 'recloth';
$dbUser = $config['DB_USER'] ?? 'root';
$dbPass = $config['DB_PASSWORD'] ?? '';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}
