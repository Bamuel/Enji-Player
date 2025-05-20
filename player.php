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
    'song' => "No Song Found",
    'is_playing' => false,
    'progress' => 0,
    'duration' => 0,
    'last_played' => false,
    'played_at' => null,];

$keys = GetKeys($_GET['id']);
$nowPlayingData = json_decode(getNowPlaying($keys), true);

if (!empty($nowPlayingData)) {
    if (!empty($nowPlayingData['item'])) {
        $initialData['image'] = $nowPlayingData['item']['album']['images'][0]['url'];
        $artists = array_map(function ($artist) {
            return $artist['name'];
        }, $nowPlayingData['item']['artists']);
        $initialData['artist'] = implode(', ', $artists);
        $initialData['song'] = $nowPlayingData['item']['name'];
    }
    if (isset($nowPlayingData['is_playing'])) {
        $initialData['is_playing'] = $nowPlayingData['is_playing'];
    }
    if (isset($nowPlayingData['progress_ms'])) {
        $initialData['progress'] = $nowPlayingData['progress_ms'];
    }
    if (!empty($nowPlayingData['item']) && isset($nowPlayingData['item']['duration_ms'])) {
        $initialData['duration'] = $nowPlayingData['item']['duration_ms'];
    }
}
else {
    $nowPlayingData = json_decode(getLastPlayed($keys), true);
    if (!empty($nowPlayingData)) {
        if (!empty($nowPlayingData['items'][0])) {
            $initialData['image'] = $nowPlayingData['items'][0]['track']['album']['images'][0]['url'];
            $artists = array_map(function ($artist) {
                return $artist['name'];
            }, $nowPlayingData['items'][0]['track']['artists']);
            $initialData['artist'] = implode(', ', $artists);
            $initialData['song'] = $nowPlayingData['items'][0]['track']['name'];
            $initialData['last_played'] = true;
            //change to Australian Sydney timezone
            $initialData['played_at'] = formatDate($nowPlayingData['items'][0]['played_at']);
        }
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

        .hide-in-iframe {
            display: none !important;
        }

        .progress-container {
            width: 100%;
            height: 4px;
            background-color: rgba(200, 162, 200, 0.6);
            position: absolute;
            bottom: 0;
            left: 0;
            z-index: 10;
        }

        .progress-bar {
            height: 100%;
            background-color: #C8A2C8;
            width: <?php echo $initialData['duration'] > 0 ? ($initialData['progress'] / $initialData['duration'] * 100) : 0; ?>%;
            transition: width 2.5s linear;
        }
    </style>
</head>
<body>
<!-- Back arrow added here -->
<a href="index.php" class="back-arrow hide-in-iframe"><i class="fa-solid fa-arrow-left"></i></a>

<div class="wrapper">
    <div id="container" class="raise active">
        <?php
        //Last played check
        if ($initialData['last_played'] && $initialData['played_at']) {
            echo '<div id="last-played">';
            echo '<div style="position: absolute; top: 10px; right: 10px; color: #C8A2C8; font-size: 12px;">Last Played</div>';
            echo '<div style="position: absolute; top: 20px; right: 10px; color: #C8A2C8; font-size: 12px;">' . $initialData['played_at'] . '</div>';
            echo '</div>';
        }
        ?>
        <div class="cover">
            <img id="album-current" alt="Current album cover" class="active" src="<?php echo htmlspecialchars($initialData['image']); ?>">
        </div>
        <div class="main">
            <div class="artists-height-fix"><h4 id="artists" class="active"><?php echo htmlspecialchars($initialData['artist']); ?></h4></div>
            <h2 id="name" class="active scrolling"><?php echo htmlspecialchars($initialData['song']); ?></h2>
        </div>
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
    </div>
</div>

<script src="vendor/components/jquery/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        // Not in an iframe, show the back arrow
        if (window.self === window.top) {
            $('.back-arrow').removeClass('hide-in-iframe');
        }

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
                    //last-played hide
                    $('#last-played').remove();
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
                        // Update progress in your AJAX callback
                        if (data.progress_ms && data.item.duration_ms) {
                            const progress = (data.progress_ms / data.item.duration_ms) * 100;
                            $('.progress-bar').css('width', progress + '%');
                        }
                    } else {
                        // No song playing but returned 200
                        if (currentSong !== "No Song Found") {
                            currentSong = "No Song Found";
                            currentArtist = "Enji Player";
                            currentImage = "images/EnjiPlayer.png";

                            $('#name').text(currentSong);
                            $('#artists').text(currentArtist);
                            $('#album-current').attr('src', currentImage);
                            $('.progress-bar').css('width', '0%');
                        }
                    }
                },
                error: function () {
                    //returned 4xx
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