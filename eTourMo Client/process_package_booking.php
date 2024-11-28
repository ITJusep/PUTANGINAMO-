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
        $packageId = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $pax = filter_input(INPUT_POST, 'booking_pax', FILTER_VALIDATE_INT);
        $bookingStartDate = filter_input(INPUT_POST, 'booking_start', FILTER_SANITIZE_STRING);

        // Validate inputs
        if ($packageId === false || $userId === false || $pax === false || !$bookingStartDate) {
            die("Invalid input data.");
        }

        // Fetch package price from the database
        $sql = "SELECT package_price FROM packages WHERE package_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $packageId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $package = $result->fetch_assoc();
            $packagePrice = $package['package_price'];

            // Calculate base total price
            $totalPrice = $pax * $packagePrice;


            // Initialize the add-ons array for storing add-on names
            $addOnsSelected = [];

            // Check if any add-ons were selected
            if (isset($_POST['addons']) && is_array($_POST['addons'])) {
                // Loop through selected add-ons and add their prices to the total
                foreach ($_POST['addons'] as $addonId) {
                    $addonId = filter_var($addonId, FILTER_VALIDATE_INT);
                    if ($addonId === false) continue;

                    // Fetch add-on details (name and price)
                    $addonSql = "SELECT addon_name, price FROM add_ons WHERE addon_id = ?";
                    $addonStmt = $conn->prepare($addonSql);
                    $addonStmt->bind_param("i", $addonId);
                    $addonStmt->execute();
                    $addonResult = $addonStmt->get_result();
                    
                    if ($addonResult->num_rows > 0) {
                        $addon = $addonResult->fetch_assoc();
                        $addonName = $addon['addon_name'];
                        $addonPrice = $addon['price'];

                        // Add add-on price to the total
                        $totalPrice += $addonPrice;

                        // Add the add-on name to the addOns array
                        $addOnsSelected[] = $addonId;
                    }
                    $addonStmt->close();
                }
            }

            // Encode the selected add-ons as a JSON array of names
            $addOnsJson = json_encode($addOnsSelected);

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert booking details into the database
                $insertBookingSql = "INSERT INTO bookings (user_id, package_id, booking_pax, booking_total_price, booking_date, booking_start, add_ons) 
                                     VALUES (?, ?, ?, ?, NOW(), ?, ?)";
                $insertStmt = $conn->prepare($insertBookingSql);
                $insertStmt->bind_param("iiidss", $userId, $packageId, $pax, $totalPrice, $bookingStartDate, $addOnsJson);
                
                if ($insertStmt->execute()) {
                    $bookingId = $conn->insert_id;
                    $conn->commit();
                    // Redirect back to the package details page with success message
                    header("Location: booking_success.php?package_id=$packageId&success=true&booking_id=$bookingId");
                    exit;
                } else {
                    throw new Exception("Error inserting booking: " . $insertStmt->error);
                }
            } catch (Exception $e) {
                $conn->rollback();
                $insertStmt->close();
        }} else {
            echo "Package not found.";
        }
        $stmt->close();
        } else {
            echo "Booking start date is missing.";
        }
    }

$conn->close();
?>
