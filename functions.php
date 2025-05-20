<?php
include_once ('vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
    $clientId = $_ENV['SPOTIFY_CLIENT_ID'];
    $clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];

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

function getLastPlayed($keys) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.spotify.com/v1/me/player/recently-played?limit=1",
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
            "Content-Type: application/json"],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
        return false;
    } else {
        return $response;
    }
}

function formatDate($dateString): string {
    $date = new DateTime($dateString, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Australia/Sydney'));
    return $date->format('d/m/Y h:ia');
}