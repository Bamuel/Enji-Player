<?php
include_once ('vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once 'db.php';
require_once 'functions.php';

$pdo = getPDO();
$stmt = $pdo->query('SELECT * FROM SpotifyKeys ORDER BY id');
$users = $stmt->fetchAll();

foreach ($users as $key => $user) {
    $userdeets = getSpotifyDetails($user['user_uri']);
    $users[$key]['spotify_id'] = $userdeets['id'];
    if (is_array($userdeets['images']) && !empty($userdeets['images'])) {
        $lastImage = end($userdeets['images']);
        $users[$key]['profile_picture'] = $lastImage['url'] ?? null; // Use null coalescing for safety
    }
    else {
        $users[$key]['profile_picture'] = null; // Default value if no images exist
    }
    $users[$key]['display_name'] = $userdeets['display_name'];
    $users[$key]['type'] = $userdeets['type'];
    $users[$key]['external_urls'] = $userdeets['external_urls']['spotify'] ?? null; // Use null coalescing for safety
    $users[$key]['followers'] = $userdeets['followers']['total'] ?? 0; // Use null coalescing for safety
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enji Player</title>
    <link rel="stylesheet" href="main.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">
    <link rel="stylesheet" href="vendor/fortawesome/font-awesome/css/all.min.css">
</head>
<body>
<div id="Header">
    <h1>Enji Player</h1>
    <p>A music playing software for streamers</p>
    <hr>
    <div class="add-account-container">
        <a href="/install.php" class="btn-spotify">
            <i class="fab fa-spotify"></i> Add your Spotify account
        </a>
    </div>
    <p style="font-size: small"><i class="fas fa-exclamation-circle"></i> *Our Spotify key is currently in trial phase with limited availability. Request access <a href="mailto:me@bamuel.com?subject=Enji%20Player%20Spotify%20Access%20Request&body=Hi%20there,%0D%0A%0D%0AI'd%20like%20to%20request%20access%20to%20the%20Enji%20Player%20Spotify%20integration.%0D%0A%0D%0AMy%20Spotify%20email%3A%20%0D%0A%0D%0AThanks!">here</a>.</p>
</div>
<div>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Followers</th>
            <th>Player</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td class="user-cell">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile" class="profile-pic">
                    <?php else: ?>
                        <img src="https://placehold.co/30" alt="No profile pic" class="profile-pic">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($user['user']); ?>
                </td>
                <td><?php echo number_format($user['followers'] ?? 0); ?></td>
                <td><a href="/player.php?id=<?php echo urlencode($user['user_uri']); ?>">View Player</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>