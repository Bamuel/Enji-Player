<?php
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db.php';
require_once 'functions.php';

$initialData = [
    'image' => "images/EnjiPlayer.png",
    'artist' => "Enji Player",
    'song' => "No Song Found"];

$keys = GetKeys($_GET['id']);
$nowPlayingData = json_decode(getNowPlaying($keys), true);

if (!empty($nowPlayingData) && !empty($nowPlayingData['item'])) {
    $initialData['image'] = $nowPlayingData['item']['album']['images'][0]['url'];
    $artists = array_map(function ($artist) {
        return $artist['name'];
    }, $nowPlayingData['item']['artists']);
    $initialData['artist'] = implode(', ', $artists);
    $initialData['song'] = $nowPlayingData['item']['name'];
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
    <title>Enji Player</title>
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="stylesheet" href="vendor/fortawesome/font-awesome/css/all.min.css">
    <style>
        @font-face {
            font-family: proxima-nova;
            src: url('fonts/proximanova_regular.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        html, body {
            background-image: url("images/noise.png");
            background-repeat: repeat;
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

        .artists-height-fix {
            min-height: 20px;
        }

        h2.scrolling, h4.scrolling {
            position: absolute;
            animation: slide 30s linear infinite;
        }

        @keyframes slide {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(-100%);
            }
        }

        /* Back arrow styles */
        .back-arrow {
            position: absolute;
            top: 20px;
            left: 20px;
            color: black;
            font-size: 24px;
            text-decoration: none;
            z-index: 100;
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body>
<!-- Back arrow added here -->
<a href="index.php" class="back-arrow"><i class="fa-solid fa-arrow-left"></i></a>
<div class="wrapper">
    <div id="container" class="raise active">
        <div class="cover">
            <img id="album-current" alt="Current album cover" class="active" src="<?php echo htmlspecialchars($initialData['image']); ?>">
        </div>
        <div class="main">
            <div class="artists-height-fix"><h4 id="artists" class="active"><?php echo htmlspecialchars($initialData['artist']); ?></h4></div>
            <h2 id="name" class="active scrolling"><?php echo htmlspecialchars($initialData['song']); ?></h2>
        </div>
    </div>
</div>

<script src="vendor/components/jquery/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        // Initialize with default values
        let currentSong = "";
        let currentImage = "images/EnjiPlayer.png";
        let currentArtist = "Enji Player";

        fetchNowPlaying();

        function fetchNowPlaying() {
            $.ajax({
                url: "get_now_playing.php",
                type: 'GET',
                data: {id: "<?php echo $_GET['id']; ?>"},
                dataType: 'json',
                success: function (data) {
                    if (data && data.item) {
                        // Update song if changed
                        if (data.item.name !== currentSong) {
                            currentSong = data.item.name;
                            $('#name').text(currentSong);

                            // Update artist
                            let artists = data.item.artists.map(artist => artist.name);
                            currentArtist = artists.join(', ');
                            $('#artists').text(currentArtist);

                            // Update image if changed
                            if (data.item.album.images[0].url !== currentImage) {
                                currentImage = data.item.album.images[0].url;
                                $('#album-current').attr('src', currentImage);
                            }
                        }
                    } else {
                        // No song playing
                        if (currentSong !== "No Song Found") {
                            currentSong = "No Song Found";
                            currentArtist = "Enji Player";
                            currentImage = "images/EnjiPlayer.png";

                            $('#name').text(currentSong);
                            $('#artists').text(currentArtist);
                            $('#album-current').attr('src', currentImage);
                        }
                    }
                },
                error: function () {
                    //console.log("Error fetching now playing data");
                },
                complete: function () {
                    // Schedule next update
                    setTimeout(fetchNowPlaying, 2500);
                }
            });
        }
    });
</script>
</body>
</html>