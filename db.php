<?php
function getPDO() {
    $dsn = 'mysql:host=' . $_ENV['MYSQL_HOST'] . ';port=' . $_ENV['MYSQL_PORT'] . ';dbname=' . $_ENV['MYSQL_DATABASE'];
    $username = $_ENV['MYSQL_USER'];
    $password = $_ENV['MYSQL_PASSWORD'];
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage();
        exit();
    }
}