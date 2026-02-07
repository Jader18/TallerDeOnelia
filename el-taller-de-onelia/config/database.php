<?php
// config/database.php

define('DB_HOST',     'localhost');
define('DB_NAME',     'u923582435_taller_onelia');
define('DB_USER',     'u923582435_oneliauser');
define('DB_PASS',     'Onelia1_eltallerdeoneliawebsite');

define('DB_CHARSET',  'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}

function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}