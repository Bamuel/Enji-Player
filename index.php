<?php
require_once 'load_env.php';
require_once 'db.php';

$pdo = getPDO();
$stmt = $pdo->query('SELECT * FROM SpotifyKeys ORDER BY id');
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enji Player</title>
    <link rel="stylesheet" href="main.css">
</head>
<body>
<div id="Header"><h1>Enji Player</h1>
    <p>A music playing software for streamers</p><br>
    <hr style="width: 80%">
    <br></div>
<div>
    <?php
    foreach ($users as $user) {
        echo "<a href='/player.php?id=" . $user['user_uri'] . "'>" . $user['user'] . "</a><br>";
    }
    ?>
</div>
</body>
</html>