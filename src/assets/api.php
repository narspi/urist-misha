<?php
$config = require __DIR__ . '/config.php';

$host = $config['db_host'];
$dbname = $config['db_name'];
$username = $config['db_user'];
$password = $config['db_pass'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Ошибка подключения к БД: " . $e->getMessage()]));
}

// --- 1. Функция получения IP пользователя ---
function getUserIP(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // если прокси — берем первый IP
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

// --- 2. Проверка, есть ли IP в базе данных ---
function checkIPExists(string $ip, PDO $pdo): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ip_address = :ip");
    $stmt->execute(['ip' => $ip]);
    return $stmt->fetchColumn() > 0;
}

// --- 3. Сохранение пользователя в базу ---
function saveUser(string $ip, PDO $pdo): bool {
    $stmt = $pdo->prepare("INSERT INTO users (ip_address, created_at) VALUES (:ip, NOW())");
    return $stmt->execute(['ip' => $ip]);
}
