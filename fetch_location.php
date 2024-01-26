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

// Fetch User B's latest location
$query = "SELECT user_b_latitude, user_b_longitude FROM tracking_sessions WHERE session_id = '$sessionId' AND user_b_accepted = 1";
$result = $db->query($query);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(array("error" => "No location found or User B has not accepted the tracking."));
}

// Close database connection
$db->close();
?>
