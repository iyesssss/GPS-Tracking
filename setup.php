<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dbHost = $_POST['dbHost'];
    $dbUsername = $_POST['dbUsername'];
    $dbPassword = $_POST['dbPassword'];
    $dbName = $_POST['dbName'];
    $mapboxAccessToken = $_POST['mapboxAccessToken'];

    // Create connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbName";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully<br>";
    } else {
        echo "Error creating database: " . $conn->error;
    }

    // Select database
    $conn->select_db($dbName);

    // SQL to create tables
    $sql = "CREATE TABLE IF NOT EXISTS tracking_sessions (
                session_id VARCHAR(255) PRIMARY KEY,
                user_b_accepted TINYINT(1) DEFAULT 0,
                user_b_latitude DOUBLE,
                user_b_longitude DOUBLE,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS configuration (
                config_key VARCHAR(50) PRIMARY KEY,
                config_value TEXT NOT NULL
            );

            INSERT INTO configuration (config_key, config_value) VALUES 
            ('mapbox_access_token', '$mapboxAccessToken')
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);";

    // Execute query
// After all operations are successful
if ($conn->multi_query($sql) === TRUE) {
    echo "Setup complete. Redirecting...<script>setTimeout(function() { window.location = 'index.php'; }, 3000);</script>";

    // Schedule deletion of this script
    file_put_contents('delete_setup.php', "<?php unlink('setup.php'); unlink('delete_setup.php'); ?>");
} else {
        echo "Error creating tables: " . $conn->error;
    }

    // Write database configuration to config.php
    $configData = "<?php\n";
    $configData .= "\$dbHost = '$dbHost';\n";
    $configData .= "\$dbUsername = '$dbUsername';\n";
    $configData .= "\$dbPassword = '$dbPassword';\n";
    $configData .= "\$dbName = '$dbName';\n";
    file_put_contents('config.php', $configData);

    $conn->close();
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Database</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            color: #333;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 400px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #4CAF50;
        }

        input[type=text], input[type=password] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .button:hover {
            background-color: #45a049;
        }

        .form-item {
            text-align: left;
            margin-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        .message {
            color: #0066cc;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Setup Database for Location Tracker</h2>
        <form id="setupForm" method="post" action="setup.php">
            <div class="form-item">
                <label>Database Host:</label>
                <input type="text" name="dbHost" value="localhost">
            </div>
            <div class="form-item">
                <label>Database Username:</label>
                <input type="text" name="dbUsername">
            </div>
            <div class="form-item">
                <label>Database Password:</label>
                <input type="password" name="dbPassword">
            </div>
            <div class="form-item">
                <label>Database Name:</label>
                <input type="text" name="dbName">
            </div>
            <div class="form-item">
                <label>Mapbox Access Token: <a href="https://www.mapbox.com/" target="_blank">Get your API key</a></label>
                <input type="text" name="mapboxAccessToken">
            </div>
            <input class="button" type="submit" value="Complete Setup">
        </form>
        <div class="message"></div>
    </div>

    <script>
        $(document).ready(function() {
            $('#setupForm').on('submit', function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                $.post('setup.php', formData, function(response) {
                    $('.message').html(response);
                });
            });
        });
    </script>
    
    
    <script>
    $(document).ready(function() {
        $('#setupForm').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $.post('setup.php', formData, function(response) {
                $('.message').html(response);
                // Check if setup is complete and delete 'setup.php'
                if (response.includes('Setup complete')) {
                    $.get('delete_setup.php'); // This will trigger the deletion
                }
            });
        });
    });
</script>


</body>
</html>