<?php

include 'config.php'; // Include the database configuration


// Check if all data is provided
if (!isset($_POST['session']) || !isset($_POST['latitude']) || !isset($_POST['longitude'])) {
    die("Incomplete data provided.");
}

$sessionId = $_POST['session'];
$latitude = (float)$_POST['latitude'];
$longitude = (float)$_POST['longitude'];

// Create database connection
$db = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Update location in database
$query = $db->prepare("UPDATE tracking_sessions SET user_b_accepted = 1, user_b_latitude = ?, user_b_longitude = ? WHERE session_id = ?");
$query->bind_param("dds", $latitude, $longitude, $sessionId);

if (!$query->execute()) {
    die("Error: " . $query->error);
}

// Close database connection
$query->close();
$db->close();

echo "Location updated.";
?>
