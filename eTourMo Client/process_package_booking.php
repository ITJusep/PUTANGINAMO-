<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "etourmodb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginsignup.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the 'booking_start' exists in the POST request
    if (isset($_POST['booking_start'])) {
        // Get form details
        $packageId = (int)$_POST['package_id'];
        $userId = (int)$_POST['user_id'];
        $pax = (int)$_POST['booking_pax'];
        $bookingStartDate = $_POST['booking_start']; // Date selected by the user

        // Fetch package price from the database
        $sql = "SELECT package_price FROM packages WHERE package_id = $packageId";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $package = $result->fetch_assoc();
            $packagePrice = $package['package_price'];

            // Calculate total price
            $totalPrice = $pax * $packagePrice;

            // Insert booking details into the database
            $insertBookingSql = "INSERT INTO bookings (user_id, package_id, booking_pax, booking_total_price, booking_date, booking_start) 
                                 VALUES ($userId, $packageId, $pax, $totalPrice, NOW(), '$bookingStartDate')";
           // After processing booking
           if ($conn->query($insertBookingSql) === TRUE) {
               // Redirect back to the package details page with success message
               $bookingId = $conn->insert_id;
               
                header("Location: booking_success.php?package_id=$packageId&success=true&booking_id=$bookingId");
                exit;
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Package not found.";
        }
    } else {
        echo "Booking start date is missing.";
    }
}

$conn->close();
?>
