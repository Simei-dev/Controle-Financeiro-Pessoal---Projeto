<?php

$env = parse_ini_file(__DIR__ . '/../.env');

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
