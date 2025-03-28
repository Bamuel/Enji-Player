<?php

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

require_once 'load_env.php';
require_once 'db.php';

if (isset($_GET['id']) && ($_GET['func'] ?? "") == 'getSong') {
    $keys = GetKeys($_GET['id']);
    $nowPlayingData = json_decode(getNowPlaying($keys), true);
    echo json_encode($nowPlayingData['item']['name'] ?? "");
    exit();
}
$pdo = getPDO();

$sql = "SELECT * FROM SpotifyKeys WHERE user_uri = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();



if (isset($user['user_uri']) && $_GET['id'] == $user['user_uri']) {
    $keys = GetKeys($_GET['id']);
    $nowPlayingData = json_decode(getNowPlaying($keys), true);
    if (empty($nowPlayingData)) {
        $artist = "Enji Player";
        $image = "https://i.imgur.com/OIpECt6.jpg";
        $song = "No Song Found";
    }
    else {
        $image = $nowPlayingData['item']['album']['images'][0]['url'];
        $artist = array();
        foreach ($nowPlayingData['item']['artists'] as $artists) {
            $artist[] = $artists['name'];
        }
        $artistFirst = $artist[0];
        $artist = rtrim(implode(', ', $artist), ', ');
        $song = $nowPlayingData['item']['name'];
    }
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

function getNowPlaying($keys) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.spotify.com/v1/me/player/currently-playing",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer " . $keys['access_token'],
            "Content-Type: application/json"],]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    }
    else {
        return $response;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv='cache-control' content='no-cache'>
    <meta http-equiv='expires' content='0'>
    <meta http-equiv='pragma' content='no-cache'>
    <!--    <meta http-equiv="refresh" content="10" />-->
    <title>Enji Player</title>
    <style>
        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        html, body {
            font-family: 'proxima-nova', sans-serif;
            margin: 0;
            overflow: hidden;
            padding: 0;
        }

        .wrapper {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            width: 100vw;
            height: 100vh;
        }

        #container {
            width: 70px;
            height: 70px;
            position: relative;
            background: #1b1d20;
            margin: auto;
            transition: opacity .4s ease, transform .4s ease, width .4s ease;
            opacity: 0;
            transform: translateY(300px);
        }

        #container.raise {
            opacity: 1;
            transform: translateY(0);
        }

        #container.active {
            width: 350px;
        }

        img {
            width: 70px;
            height: 70px;
            transition: opacity .4s ease;
            opacity: 0;
            position: absolute;
            left: 0;
            top: 0;
        }

        img.active {
            opacity: 1;
        }

        .cover {
            width: 70px;
            height: 70px;
            position: relative;
            z-index: 9;
        }

        .main {
            position: absolute;
            padding-left: 70px;
            padding-top: 10px;
            top: 0;
            height: 70px;
            width: 100%;
            max-width: 350px;
            overflow: hidden;
        }

        #artists {
            color: #C8A2C8;
            font-weight: 600;
            margin: 0;
            font-size: 15px;
            text-transform: uppercase;
        }

        #requested {
            color: #C8A2C8;
            font-weight: 600;
            margin: 0;
            font-size: 15px;
            text-transform: uppercase;
        }

        #name {
            color: #fff;
            margin: 4px 0 0;
            font-size: 24px;
        }

        h4, h2 {
            opacity: 0;
            white-space: nowrap;
            padding-left: 10px;
            transform: translateX(-50px);
            transition: opacity .4s ease, transform .4s ease;
            display: inline-block;
        }

        h4.active, h2.active {
            opacity: 1;
            transform: translateX(0);
        }

        h4.drop, h2.drop {
            transform: translateY(100px);
        }

        .artists-height-fix {
            min-height: 20px;
        }

        h2.scrolling, h4.scrolling {
            /* -webkit-animation: slide 30s linear infinite; */
            position: absolute;
            animation: slide 30s linear infinite;
        }

        @keyframes slide {
            from {
                /* left: 100%; */
                transform: translateX(100%);
            }
            to {
                /* left: -100%; */
                transform: translateX(-100%);
            }
        }

        .theSnap {
            animation: MrStarkIDontFeelSoGood 7s linear 1;
        }

        @keyframes MrStarkIDontFeelSoGood {
            0% {
                opacity: 1
            }
            50% {
                opacity: 0.9
            }
            80% {
                opacity: 0.6
            }
            100% {
                opacity: 0
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php
    if (isset($gif)) {
        echo "<img src=\"" . $gif . "\"
         style=\"z-index: 1000; width: 350px; height: 70px; object-fit: cover; opacity: 0; position: absolute;
left: 0;right: 0; top:0 ; bottom: 0 ; margin: auto\" class=\"active theSnap\">";
    }

    ?>
    <div id="container" class="raise active">
        <div class="cover">
            <img id="album-current" alt="Current album cover" class="active" src="<?php echo $image; ?>">
        </div>
        <div class="main">
            <div class="artists-height-fix"><h4 id="artists" class="active"><?php echo $artist; ?></h4></div>
            <h2 id="name" class="active scrolling ">
                <?php
                echo $song;
                ?></h2>
        </div>
    </div>
</div>

</body>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    songChange();

    function songChange() {
        $.ajax({
            url: "/index.php?id=<?php echo $_GET['id'] ?>&func=getSong",
            type: 'GET',
            dataType: 'json',
            success: function (result) {
                //console.log(result);
                if (result !== null) {
                    if (result !== "") {
                        if (result !== "<?php echo str_replace('"', '\"', $song); ?>") {
                            console.log('The song has changed so refresh')
                            console.log(result);
                            console.log('<?php echo str_replace('"', '\"', $song); ?>');
                            location.reload();
                        }
                    }
                }
            }
        });
        setTimeout(songChange, 2500);
    }
</script>
</html>
