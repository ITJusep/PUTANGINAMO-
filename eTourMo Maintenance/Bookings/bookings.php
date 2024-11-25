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

// Get the admin's ID from the session
$admin_id = $_SESSION['admin_id'];

// Handle booking actions (confirm, decline, done, undo)
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

        // Redirect back to the booking management page
        header("Location: /eTourMo Maintenance/Bookings/bookings.php");
        exit();
    } elseif ($action == 'undo') {
        // Undo the booking status (set to 'pending')
        $sql = "UPDATE bookings SET booking_status = 'pending', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);

        // Redirect back to the booking management page
        header("Location: /eTourMo Maintenance/Bookings/bookings.php");
        exit();
    }
}

// Handle package search functionality (search by username)
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];

    // Use the username directly from the user_profiles table
    $stmt = $pdo->prepare("
        SELECT b.*, u.email, u.contact_information, p.package_name, u.email
        FROM bookings b
        JOIN user_profiles u ON b.user_id = u.user_id
        JOIN packages p ON b.package_id = p.package_id
        WHERE u.email LIKE :searchTerm
    ");
    $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
} else {
    // Fetch all bookings if no search term is entered
    $stmt = $pdo->prepare("
        SELECT b.*, u.email, u.contact_information, p.package_name 
        FROM bookings b
        JOIN user_profiles u ON b.user_id = u.user_id
        JOIN packages p ON b.package_id = p.package_id
    ");
    $stmt->execute();
}

$booking_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include('../Components/header.php'); ?>

<h2 class="text-6xl font-bold mb-4 text-black mb-4">Your Bookings</h2>

<form class="search-form mb-6" method="GET" action="">
    <div class="join">
        <div>
            <div>
                <!-- Search input field, searching by concatenated username -->
                <input type="search" id="default-search" name="search" class="input input-bordered join-item bg-[#CBDCEB] placeholder-black text-black" placeholder="Search by Email" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            </div>
        </div>
        <button type="submit" class="btn btn-info join-item">Search</button>
    </div>
</form>

<div class="content">
<!-- Check if there are any bookings -->
<?php if ($booking_data): ?>
    <table class="table table-lg text-black">
        <thead>
            <tr class="text-white bg-[#608BC1]">
                <th>Action</th>
                <th>Booking ID</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Package Name</th>
                <th>Status</th>
                <th>Admin ID</th>
            </tr>
        </thead>
        <tbody> 
        <?php foreach ($booking_data as $booked): ?>
            <tr>
                <td class="flex">
                    <?php if ($booked['booking_status'] == 'pending'): ?>
                        <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=confirm" class="confirm-link text-green-500 font-bold">Confirm</a>
                        <span class="divider divider-horizontal divider-neutral"></span>
                        <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=decline" class="decline-link text-red-500 font-bold">Decline</a>
                        <span class="divider divider-horizontal divider-neutral"></span>
                        <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=done" class="done-link text-blue-500 font-bold">Done</a>
                    <?php else: ?>
                        <a href="?booking_id=<?php echo $booked['booking_id']; ?>&action=undo" class="done-link text-blue-500 font-bold">Undo</a>
                    <?php endif ?>
                </td>
                <td><?php echo htmlspecialchars($booked['booking_id']); ?></td>
                <td><?php echo htmlspecialchars($booked['email']); ?></td>
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
body {
    background-color: #F3F3E0;
}
</style>
