<?php
include 'config.php'; // Include the database configuration

// Check if session ID is set
if (!isset($_GET['session'])) {
    die("No session ID provided.");
}

$sessionId = $_GET['session'];

// Create database connection
$db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Fetch Mapbox access token from the database
$query = "SELECT config_value FROM configuration WHERE config_key = 'mapbox_access_token'";
$result = $db->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $mapboxAccessToken = $row['config_value'];
} else {
    die("Error: Mapbox access token not found in the database.");
}

// Close database connection
$db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Location Sharing</title>
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.css" rel="stylesheet" />
<style>
    body {
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
        color: #333;
        text-align: center;
    }
    header, footer {
        background-color: #333;
        color: white;
        padding: 1em 0;
        text-align: center;
    }
    .container {
        width: 80%;
        margin: auto;
        overflow: hidden;
    }
    #map {
        width: 100%;
        height: 400px;
        margin: 20px 0;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    #acceptButton {
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin-bottom: 20px;
    }
    #statusMessage {
        margin-top: 10px;
        font-style: italic;
        color: #676;
    }
</style>

</head>

<header>
    <div class="container">
        <h1>Location Sharing Service</h1>
    </div>
</header>

<body>
    
    
<div class="container">
    <button id="acceptButton" onclick="acceptTracking()">Accept and Share My Location</button>
    <p id="statusMessage"></p>
    <div id="map"></div>
</div>



<footer>
    <div class="container">
        <p>&copy; 2024 Location Sharing, Inc. All rights reserved.</p>
    </div>
</footer>


    <script>
        var lastLatitude, lastLongitude;
        var statusMessage = document.getElementById('statusMessage');
        var map, marker;

        function acceptTracking() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            navigator.geolocation.watchPosition(function(position) {
                var latitude = position.coords.latitude;
                var longitude = position.coords.longitude;
                var altitude = position.coords.altitude ? position.coords.altitude.toFixed(2) : 'Not Available';

                updateLocation(latitude, longitude);
                if (!map) {
                    initializeMap(latitude, longitude);
                }
                updateMarker(latitude, longitude);

                statusMessage.innerHTML = 'Your current GPS altitude is: ' + altitude + ' meters. <br>Location sharing is live!';
            }, function() {
                alert('Unable to retrieve your location');
            }, { 
                maximumAge: 2000, // Accept a cached position within 2 seconds old
                timeout: 3000 // Set timeout to 5 seconds
            });
        }

        function initializeMap(lat, lng) {
            mapboxgl.accessToken = '<?php echo $mapboxAccessToken; ?>';
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [lng, lat],
                zoom: 15
            });
            marker = new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map);
        }

        function updateMarker(lat, lng) {
            marker.setLngLat([lng, lat]);
        }

        function updateLocation(latitude, longitude) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_location.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.send("session=<?php echo $sessionId; ?>&latitude=" + latitude + "&longitude=" + longitude);
        }
    </script>
</body>
</html>
