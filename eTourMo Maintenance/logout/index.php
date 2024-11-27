<?php
session_start(); // Start the session

// Set timezone to Asia/Manila for correct time calculations
date_default_timezone_set('Asia/Manila');

// Ensure the user is logged in and has an active session
if (isset($_SESSION['admin_id'])) {
    // Database connection
    $servername = "localhost"; // Change if necessary
    $username = "root"; // Your database username
    $password = ""; // Your database password
    $dbname = "etourmodb"; // Your database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the database connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the current employee ID and the current time
    $employee_id = $_SESSION['admin_id'];
    $logout_time = date('Y-m-d H:i:s'); // Get the current timestamp

    // Get the login time from the database for the current session
    $sql = "SELECT login_time FROM activity_logs WHERE admin_id = ? AND logout_time IS NULL LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $login_time = $row['login_time'];

        // Calculate the duration by subtracting login_time from logout_time
        $login_timestamp = strtotime($login_time);
        $logout_timestamp = strtotime($logout_time);
        $duration = gmdate("H:i:s", $logout_timestamp - $login_timestamp); // Get duration in H:M:S format

        // Update the logout time and duration in the database for the current session
        $update_sql = "UPDATE activity_logs 
                       SET logout_time = ?, duration = ? 
                       WHERE admin_id = ? AND logout_time IS NULL"; // Ensure we update only the active session
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $logout_time, $duration, $employee_id);
        
        if ($update_stmt->execute()) {
            // Success, logout completed, destroy session
            session_unset();  // Unset all session variables
            session_destroy(); // Destroy the session
        } else {
            // Handle error if logout time update fails
            echo "Error updating logout time: " . $conn->error;
        }
    } else {
        // Handle case where login time isn't found (shouldn't happen if session is valid)
        echo "Error: Login time not found.";
    }

    // Close the database connection
    $update_stmt->close();
    $conn->close();
}

// Optionally, clear the session cookie if necessary
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Redirect to the login page
header("Location: /eTourMo Maintenance/login/index.php?logout=success");
exit();
?>
