<?php
include 'config.php'; // Include the database configuration

// Function to check if the setup is complete
function isSetupComplete() {
    global $dbHost, $dbUsername, $dbPassword, $dbName;

    // Attempt to connect to the database
    $conn = @new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check for connection error
    if ($conn->connect_error) {
        return false; // Setup is not complete
    }

    $conn->close();
    return true; // Setup is complete
}

// Redirect to setup.php if setup is not complete
if (!isSetupComplete()) {
    header("Location: setup.php");
    exit;
}
// Function to update configuration
function updateConfig($key, $value, $db) {
    $query = $db->prepare("INSERT INTO configuration (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
    $query->bind_param("ss", $key, $value);
    $query->execute();
}

// Handling form submission for configuration update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateConfig'])) {
    $dbConfig = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    if ($dbConfig->connect_error) {
        die("Connection failed: " . $dbConfig->connect_error);
    }

    updateConfig('mapbox_access_token', $_POST['mapboxAccessToken'], $dbConfig);
    $dbConfig->close();
}

$sessionId = '';

    // Check if a session ID is provided in the GET request
    if (isset($_GET['session']) && !empty($_GET['session'])) {
        $sessionId = $_GET['session'];
    } elseif (isset($_GET['newSession'])) {
        $sessionId = bin2hex(random_bytes(16));
    }
    
// Generate a new session ID if requested
if (isset($_GET['newSession'])) {
    $sessionId = bin2hex(random_bytes(16));
} elseif (isset($_GET['session'])) {
    $sessionId = $_GET['session'];
}

// Database operations for session management
$dbSession = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($dbSession->connect_error) {
    die("Connection failed: " . $dbSession->connect_error);
}

if (!empty($sessionId)) {
    $query = "INSERT INTO tracking_sessions (session_id) VALUES ('$sessionId') ON DUPLICATE KEY UPDATE session_id = session_id";
    if (!$dbSession->query($query)) {
        die("Error: " . $dbSession->error);
    }
}
$dbSession->close();

// Fetch updated Mapbox access token from the database
$dbConfig = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($dbConfig->connect_error) {
    die("Connection failed: " . $dbConfig->connect_error);
}
$query = "SELECT config_value FROM configuration WHERE config_key = 'mapbox_access_token'";
$result = $dbConfig->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $mapboxAccessToken = $row['config_value'];
} else {
    $mapboxAccessToken = ''; // Default value if not found
}
$dbConfig->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Real-Time Location Tracker</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.3.1/mapbox-gl.css" rel="stylesheet" />
    <link href="css/style1.css" rel="stylesheet" />

</head>
<body>
<header>
    <div class="container">
        <h1><i class="fas fa-map-marker-alt header-icon"></i> Real-Time Location Tracker</h1>
    </div>
</header>


    <div class="container">
        <div class="content-section">
<p>Share this link with your friend: 
    <a href='map.php?session=<?php echo $sessionId; ?>' id="sessionLink">map.php?session=<?php echo $sessionId; ?></a>
    <button onclick="copySessionLink()">Copy Link</button>
</p>
<div class="content-section">
    <h2>Track a Session</h2>
    <form action="index.php" method="get">
        <label for="sessionIdInput">Session ID:</label>
        <input type="text" id="sessionIdInput" name="session" required>
        <input type="submit" value="Track Session">
    </form>
</div>

                        <form action="index.php?newSession=true" method="post">
                <input class="button" type="submit" value="Generate New Session">
            </form>
        </div>


<div id="altitudeDisplay" class="content-section">Altitude: Not available</div>

        <div class="content-section">
    <label for="mapStyle">Map Style:</label>
    <select id="mapStyle" onchange="changeMapStyle()">
        <option value="mapbox://styles/mapbox/streets-v11">Streets</option>
        <option value="mapbox://styles/mapbox/satellite-v9">Satellite</option>
        <option value="mapbox://styles/mapbox/satellite-streets-v11">Satellite Streets</option>
    </select>
    
        <label for="mapHeight">Map Height:</label>
    <input type="range" id="mapHeight" min="300" max="1200" value="800" onchange="changeMapHeight()">
    
            <div id="locationInfo"></div>

</div>

        <div id="map"></div>
        
                    <form class="content-section" method="post" action="index.php?session=<?php echo $sessionId; ?>">
                <label>Mapbox Access Token:</label>
                <input type="text" name="mapboxAccessToken" value="<?php echo $mapboxAccessToken; ?>">
                <input type="submit" name="updateConfig" value="Update Configuration">
            </form>

        <div class="content-section">
            <h2>About This Tracker</h2>
            <p>Our Real-Time Location Tracker offers live location tracking for enhanced user experience and safety. Stay connected with your team, wherever they are.</p>
        </div>
        

        
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 Real-Time Tracker, Inc. All rights reserved.</p>
        </div>
    </footer>
    <script>
        mapboxgl.accessToken = '<?php echo $mapboxAccessToken; ?>';
        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [0, 0],
            zoom: 9
        });
        var marker = new mapboxgl.Marker().setLngLat([0, 0]).addTo(map);

function updateLocation() {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText);
            if (response.error) {
                console.error(response.error);
            } else {
                var userBLocation = [response.user_b_longitude, response.user_b_latitude];
                var altitude = response.user_b_altitude || 'Not available';
                marker.setLngLat(userBLocation);

                // Check if the map needs to be re-centered
                var currentCenter = map.getCenter();
                var distance = Math.sqrt(
                    Math.pow(currentCenter.lng - userBLocation[0], 2) +
                    Math.pow(currentCenter.lat - userBLocation[1], 2)
                );

                if (distance > 0.01) { // Threshold for re-centering the map
                    map.flyTo({center: userBLocation, zoom: 15});
                }

                // Displaying latitude, longitude, and altitude
                document.getElementById('locationInfo').innerText = 
                    'Latitude: ' + response.user_b_latitude + 
                    ', Longitude: ' + response.user_b_longitude + 
                    ', Altitude: ' + altitude;
            }
        }
    };
    xhr.open("GET", "fetch_location.php?session=<?php echo $sessionId; ?>", true);
    xhr.send();
}


        setInterval(updateLocation, 2000); // Update location every 2 seconds
    </script>
    
    <script>
    
    
function copySessionLink() {
    var sessionId = '<?php echo $sessionId; ?>';
    var fullPath = window.location.href.substring(0, window.location.href.lastIndexOf('/')) + '/map.php?session=' + sessionId;

    var tempInput = document.createElement('input');
    document.body.appendChild(tempInput);
    tempInput.value = fullPath;
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // For mobile devices

    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Full link copy was ' + msg);
    } catch (err) {
        console.log('Oops, unable to copy');
    }

    document.body.removeChild(tempInput);
}

function changeMapStyle() {
    var selectedStyle = document.getElementById('mapStyle').value;
    map.setStyle(selectedStyle);
}

function changeMapHeight() {
    var height = document.getElementById('mapHeight').value;
    var mapElement = document.getElementById('map');
    mapElement.style.height = height + 'px';

    // Trigger the resize event on the map
    map.resize();
}



</script>
</body>
</html>
