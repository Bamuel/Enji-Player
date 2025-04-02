<?php
require_once 'load_env.php';
require_once 'db.php';

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

function getSpotifyDetails($userid) {
    $spotifyKeys = getKeys($userid);
    $access_token = $spotifyKeys['access_token'];
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.spotify.com/v1/me",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $access_token],]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    }
    else {
        return json_decode($response, true);
    }
    return false;
}

function getKeys($id) {
    try {
        include_once 'db.php';
        // Get PDO connection
        $pdo = getPDO();

        // Query with prepared statement
        $sqlquery = "SELECT * FROM SpotifyKeys WHERE user_uri = :id";
        $stmt = $pdo->prepare($sqlquery);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        if ($row && date("Y-m-d H:i:s") > $row['expires_in']) {
            // Refresh the token if expired
            //echo "Token expired, refreshing...";
            refreshKeys($row, $pdo);
            // Fetch updated record after refresh
            $stmt->execute();
            $row = $stmt->fetch();
        }

        return $row;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}
function refreshKeys($data, $pdo) {
    $clientId = getenv('SPOTIFY_CLIENT_ID');
    $clientSecret = getenv('SPOTIFY_CLIENT_SECRET');

    // Prepare POST data
    $postData = [
        "grant_type" => "refresh_token",
        "refresh_token" => $data['refresh_token']];

    // Initialize cURL
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://accounts.spotify.com/api/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_USERPWD => "$clientId:$clientSecret",
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded"],]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    }
    else {
        // Decode response
        $accesstoken = json_decode($response);

        if (isset($accesstoken->access_token)) {
            $accesstoken->expires_in = date("Y-m-d H:i:s", strtotime("+" . $accesstoken->expires_in . " seconds"));

            // Update token in the database
            $sql = "UPDATE SpotifyKeys 
                    SET access_token = :access_token, expires_in = :expires_in
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':access_token', $accesstoken->access_token);
            $stmt->bindParam(':expires_in', $accesstoken->expires_in);
            $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enji Player</title>
    <link rel="stylesheet" href="main.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        #Header {
            text-align: center;
            padding: 20px 10px;
        }

        #Header h1 {
            margin: 0;
            font-size: clamp(1.5rem, 5vw, 2.5rem);
        }

        #Header p {
            margin: 10px 0 0;
            font-size: clamp(0.9rem, 3vw, 1.2rem);
        }

        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            font-size: clamp(0.8rem, 2.5vw, 1rem);
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
        }

        .profile-pic {
            width: 30px;
            height: 30px;
            object-fit: cover;
            margin-right: 5px;
            vertical-align: middle;
        }

        a {
            text-decoration: none;
            color: #1DB954;
            white-space: nowrap;
        }

        a:hover {
            text-decoration: underline;
        }

        hr {
            width: 80%;
            margin: 10px auto;
            border: 0;
            height: 1px;
            background-color: #ddd;
        }

        /* Responsive table */
        @media screen and (max-width: 600px) {
            table {
                width: 80%;
                display: block;
                margin: 20px auto;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            td:nth-child(2), th:nth-child(2) {
                display: none;
            }
        }

        /* For very small screens */
        @media screen and (max-width: 400px) {
            table {
                width: 100%;
                margin: 20px auto;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .profile-pic {
                width: 25px;
                height: 25px;
            }
        }
    </style>
</head>
<body>
<div id="Header">
    <h1>Enji Player</h1>
    <p>A music playing software for streamers</p>
    <hr>
</div>
<div>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>URI</th>
            <th>Type</th>
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
                <td><?php echo htmlspecialchars($user['user_uri']); ?></td>
                <td><?php echo htmlspecialchars($user['type']); ?></td>
                <td><?php echo number_format($user['followers'] ?? 0); ?></td>
                <td><a href="/player.php?id=<?php echo urlencode($user['user_uri']); ?>">View Player</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>