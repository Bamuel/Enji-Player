<?php
require_once 'load_env.php';
require_once 'db.php';
require_once 'functions.php';

if (!isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['error' => 'Missing ID parameter']);
    exit();
}

$keys = GetKeys($_GET['id']);
$nowPlayingData = json_decode(getNowPlaying($keys), true);

if (empty($nowPlayingData) || empty($nowPlayingData['item'])) {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(['error' => 'No song currently playing']);
    exit();
}

header('Content-Type: application/json');
echo json_encode($nowPlayingData);
