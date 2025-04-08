<?php
include_once ('vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


if (!isset($_REQUEST['code'])) {
    echo 'An error has occurred';
    exit();
}

$getToken = json_decode(GetToken($_REQUEST['code']));
$getUser = json_decode(GetUser($getToken->access_token));

if (isset($getToken->access_token)) {
    SaveToken($getUser, $getToken);
    //echo 'Success';

    // Get the base URL of the site dynamically
    //$redirect_url = $_SERVER['HTTP_REFERER'] ?? '/';

    //header("Location: $redirect_url");
    header("Location: index.php");

    exit();
}
else {
    echo 'An error has occurred';
    exit();
}


function GetToken($code) {
    $clientId = $_ENV['SPOTIFY_CLIENT_ID'];
    $clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];
    $redirectUri = $_ENV['SPOTIFY_REDIRECT_URI'];

    // Prepare POST data
    $postData = [
        "grant_type" => "authorization_code",
        "code" => $code,
        "redirect_uri" => $redirectUri];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://accounts.spotify.com/api/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
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
        return false;
    }
    else {
        return $response;
    }
}

function GetUser($access_token) {
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
        return false;
    }
    else {
        return $response;
    }
}

function SaveToken($getUser, $getToken) {
    // Calculate expiration date
    include_once 'db.php';
    $expires_in = date("Y-m-d H:i:s", strtotime("+" . $getToken->expires_in . " seconds"));

    try {
        $pdo = getPDO();

        $sql = "INSERT INTO SpotifyKeys (user, user_uri, access_token, refresh_token, expires_in)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE access_token = ?, refresh_token = ?, expires_in = ?";
        $stmt = $pdo->prepare($sql);

        // Bind parameters positionally
        $stmt->bindParam(1, $getUser->display_name);
        $stmt->bindParam(2, $getUser->uri);
        $stmt->bindParam(3, $getToken->access_token);
        $stmt->bindParam(4, $getToken->refresh_token);
        $stmt->bindParam(5, $expires_in);

        // For the ON DUPLICATE KEY UPDATE part
        $stmt->bindParam(6, $getToken->access_token);
        $stmt->bindParam(7, $getToken->refresh_token);
        $stmt->bindParam(8, $expires_in);

        $stmt->execute();


    } catch (PDOException $e) {
        // Handle connection or query errors
        echo "Error: " . $e->getMessage();
    }
}
