<?php
include_once ('vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$clientID = $_ENV['SPOTIFY_CLIENT_ID'];
$secret = $_ENV['SPOTIFY_CLIENT_SECRET'];
$redirectUri = $_ENV['SPOTIFY_REDIRECT_URI'];

$scope = [
    "user-read-currently-playing",
    "user-read-playback-state",
    "user-read-recently-played",
    "user-read-email"];

$queryParams = [
    "client_id" => $clientID,
    "response_type" => "code",
    "redirect_uri" => $redirectUri,
    "scope" => implode(" ", $scope)];

$url = "https://accounts.spotify.com/authorize?" . http_build_query($queryParams);

header("Location: " . $url);
exit();