<?php
session_start(); // Start the session to access session variables

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_id'])) {
    die("You must be logged in to manage bookings.");
}

// Database connection
$host = 'localhost';
$dbname = 'etourmodb';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to the database: " . $e->getMessage());
}

// Fetch all booking information along with related user and package information
$sql = "SELECT 
    b.booking_id, 
    b.booking_status, 
    b.booking_date, 
    CONCAT(u.firstname, ' ', u.lastname) AS username,  -- Concatenate first and last name as username
    u.contact_information,
    p.package_name, 
    b.admin_id -- Include the profile picture if needed
FROM 
    bookings AS b
JOIN 
    user_profiles AS u ON b.user_id = u.user_id
JOIN 
    packages AS p ON b.package_id = p.package_id;";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$booking_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the admin's ID from the session
$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['booking_id']) && isset($_GET['action'])) {
    $booking_id = $_GET['booking_id'];
    $action = $_GET['action'];

    if ($action === 'confirm') {
        // Confirm the booking
        $sql = "UPDATE bookings SET booking_status = 'confirmed', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);

        // Redirect back to the booking management page
        header("Location: /eTourMo Maintenance/Bookings/bookings.php");
        exit();

    } elseif ($action === 'done') {
        // Set the booking status to 'done'
        $sql = "UPDATE bookings SET booking_status = 'done', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);

        // Redirect back to the booking management page
        header("Location: /eTourMo Maintenance/Bookings/bookings.php");
        exit();

    } elseif ($action === 'decline') {
        // Decline the booking
        $sql = "UPDATE bookings SET booking_status = 'declined', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);

        // Check how many rows were affected
        if ($stmt->rowCount() > 0) {
            // Optionally, you can set a success message in the session or handle it here
        } else {
            // Log an error or display a message if no booking was found
            echo "<script>alert('No booking found with that ID.');</script>";
        }

        // Redirect back to the booking management page
        header("Location: /eTourMo Maintenance/Bookings/bookings.php");
        exit();
    }
}
?>

<?php include('../Components/header.php'); ?>
<div class="content">
<!-- Check if there are any bookings -->
<?php if ($booking_data): ?>
    <table class="booking-table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Booking ID</th>
                <th>Username</th>
                <th>Contact</th>
                <th>Package Name</th>
                <th>Status</th>
                <th>Admin ID</th>
            </tr>
        </thead>
        <tbody> 
        <?php foreach ($booking_data as $booked): ?>
            <tr>
                <td>
                    <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=confirm" class="confirm-link">Confirm</a>
                    <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=decline" class="decline-link">Decline</a>
                    <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=done" class="done-link">Done</a>
                </td>
                <td><?php echo htmlspecialchars($booked['booking_id']); ?></td>
                <td><?php echo htmlspecialchars($booked['username']); ?></td>
                <td><?php echo htmlspecialchars($booked['contact_information']); ?></td>
                <td><?php echo htmlspecialchars($booked['package_name']); ?></td>
                <td><?php echo htmlspecialchars($booked['booking_status']); ?></td>
                <td><?php echo htmlspecialchars($booked['admin_id']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No bookings available.</p>
<?php endif; ?>
</div>
<?php include('../Components/footer.php'); ?>

<style>
.content {
display: flex;
justify-content: center; /* Centers horizontally */
align-items: center;    /* Centers vertically */
min-height: 100vh;      /* Ensures it takes full viewport height */
padding: 0;
box-sizing: border-box; /* Includes padding in width/height calculations */
margin: 0;
}
/* Table Styles */
.booking-table {
    width: 1000px;
    border-collapse: collapse;
    background-color: #fff;
    border: 1px solid #ddd;
}

.booking-table th, .booking-table td {
    padding: 12px 15px;
    text-align: left;
    border: 1px solid #ddd;
}

.booking-table th {
    background-color: #f4f4f4;
    font-weight: bold;
}

.booking-table tbody tr:hover {
    background-color: #f9f9f9;
}

/* Action Links - Now arranged vertically */
td a {
    display: block; /* Makes each action link appear on a new line */
    text-decoration: none;
    padding: 5px 20px;
    border-radius: 4px;
    margin-bottom: 5px; /* Adds vertical space below each button */
}

/* Style for "Confirm" button */
.confirm-link {
    color: blue;
}

/* Style for "Decline" button */
.decline-link {
    color: red;
}

/* Style for "Done" button */
.done-link {
    color: darkblue;
}

/* General Text Styling */
h1 {
    font-size: 1.5rem;
    margin-bottom: 20px;
}

p {
    font-size: 1rem;
    color: #555;
}

</style>