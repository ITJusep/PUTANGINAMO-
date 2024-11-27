<?php
    // Start the session
    session_start();

    // Destroy the session to log the user out
    session_unset();  // Clears all session variables
    session_destroy();  // Destroys the session

    // Set a success message to be displayed after logout
    $_SESSION['logout_message'] = "Logged out successfully.";

    // Optionally, you can redirect the user to a login page or home page
    header("Location: loginsignup.php");  // Redirect to login page
    exit();
?>
