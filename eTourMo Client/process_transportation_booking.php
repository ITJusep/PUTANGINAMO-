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
        $rentalId = (int)$_POST['rental_id'];
        $userId = (int)$_POST['user_id'];
        $pax = (int)$_POST['booking_pax'];
        $bookingStartDate = $_POST['booking_start']; // Date selected by the user

        // Fetch rental price from the database
        $sql = "SELECT rental_price FROM rentals WHERE rental_id = $rentalId";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $rental = $result->fetch_assoc();
            $rentalPrice = $rental['rental_price'];

            // Calculate total price
            $totalPrice = $pax * $rentalPrice;

            // Insert booking details into the rental_bookings table
            $insertBookingSql = "INSERT INTO rental_bookings (user_id, rental_id, booking_pax, booking_total_price, booking_date, booking_start) 
                                 VALUES ($userId, $rentalId, $pax, $totalPrice, NOW(), '$bookingStartDate')";
            
            // After processing booking
            if ($conn->query($insertBookingSql) === TRUE) {
                // Redirect back to the rental details page with success message
                header("Location: transportation.php?rental_id=$rentalId&success=true");
                exit;
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "Rental not found.";
        }
    } else {
        echo "Booking start date is missing.";
    }
}

$conn->close();
?>
