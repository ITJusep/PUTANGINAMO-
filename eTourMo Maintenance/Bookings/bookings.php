<?php
session_start();
require 'vendor/autoload.php';

$env = parse_ini_file('.env');


$sid = $env["TWILIO_SID"];
$token = $env["TWILIO_TOKEN"];
$client = new Twilio\Rest\Client($sid, $token);

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
        $sql = "UPDATE bookings SET booking_status = 'confirmed', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);

        // Use the Client to make requests to the Twilio REST API
        $client->messages->create(
            // The number you'd like to send the message to
            '+639218576738',
            [
                // A Twilio phone number you purchased at https://console.twilio.com
                'from' => '+13302497241',
                // The body of the text message you'd like to send
                'body' => "Your booking payment has been confirmed. - eTourMo"
            ]);

    } elseif ($action === 'done') {
        $sql = "UPDATE bookings SET booking_status = 'done', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);
    } elseif ($action === 'decline') {
        $sql = "UPDATE bookings SET booking_status = 'declined', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);

        // Use the Client to make requests to the Twilio REST API
        $client->messages->create(
            // The number you'd like to send the message to
            '+639218576738',
            [
                // A Twilio phone number you purchased at https://console.twilio.com
                'from' => '+13302497241',
                // The body of the text message you'd like to send
                'body' => "Your booking has been declined. Please check your account for more details. - eTourMo"
            ]);
    } elseif ($action === 'undo') {
        $sql = "UPDATE bookings SET booking_status = 'pending', admin_id = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_id, $booking_id]);
    }

    // Redirect back to the booking management page with existing filters
    $queryString = http_build_query(array_diff_key($_GET, array_flip(['booking_id', 'action'])));
    header("Location: /eTourMo Maintenance/Bookings/bookings.php" . ($queryString ? "?$queryString" : ""));
    exit();
}

// Get filter values from GET parameters
$emailSearch = isset($_GET['email']) ? $_GET['email'] : '';
$packageFilter = isset($_GET['package']) ? $_GET['package'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$startDateFilter = isset($_GET['booking_start']) ? $_GET['booking_start'] : '';

// Fetch all packages for the dropdown
$packagesStmt = $pdo->query("SELECT DISTINCT package_id, package_name FROM packages ORDER BY package_name");
$packages = $packagesStmt->fetchAll(PDO::FETCH_ASSOC);

// Build the main query with filters
$query = "
    SELECT b.*, u.email, u.contact_information, p.package_name 
    FROM bookings b
    JOIN user_profiles u ON b.user_id = u.user_id
    JOIN packages p ON b.package_id = p.package_id
    WHERE 1=1
";

$params = array();

if (!empty($emailSearch)) {
    $query .= " AND u.email LIKE :email";
    $params[':email'] = "%$emailSearch%";
}

if (!empty($packageFilter)) {
    $query .= " AND b.package_id = :package_id";
    $params[':package_id'] = $packageFilter;
}

if (!empty($statusFilter)) {
    $query .= " AND b.booking_status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($startDateFilter)) {
    $query .= " AND DATE(b.booking_start) = :booking_start";
    $params[':booking_start'] = $startDateFilter;
}

$query .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$booking_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include('../Components/header.php'); ?>

<div class="min-h-screen bg-[#F3F3E0] p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <h2 class="text-6xl font-bold mb-4 text-black">Your Bookings</h2>

<!-- Filters Form -->
<form class="search-form mb-6 w-full" method="GET" action="">
    <!-- Search Controls Container -->
    <div class="flex flex-col md:flex-row justify-between gap-4 w-full">
        <!-- Email Search -->
        <input type="search" name="email" 
            class="input input-bordered bg-[#CBDCEB] text-black placeholder-black w-full md:w-auto"
            placeholder="Search by Email"
            value="<?php echo htmlspecialchars($emailSearch); ?>" />

        <!-- Package Filter -->
        <select name="package" class="select select-bordered bg-[#CBDCEB] text-black w-full md:w-auto">
            <option value="">All Packages</option>
            <?php foreach ($packages as $package): ?>
                <option value="<?php echo htmlspecialchars($package['package_id']); ?>"
                        <?php echo $packageFilter == $package['package_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($package['package_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Status Filter -->
        <select name="status" class="select select-bordered bg-[#CBDCEB] text-black w-full md:w-auto">
            <option value="">All Statuses</option>
            <?php foreach (['pending', 'confirmed', 'declined', 'cancelled', 'done'] as $status): ?>
                <option value="<?php echo $status; ?>"
                        <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                    <?php echo ucfirst($status); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Date Filter -->
        <input type="date" name="booking_start" 
            class="input input-bordered bg-[#CBDCEB] text-black w-full md:w-auto"
            value="<?php echo htmlspecialchars($startDateFilter); ?>" />

        <button type="submit" class="btn btn-info flex-1">Apply Filters</button>
        <a href="?" class="btn btn-warning flex-1">Reset</a>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-4 mt-4 w-full">
        
    </div>
</form>

<!-- Bookings Table -->
<div class="content" >
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

.table {
    border-spacing: 0;
    width: 100%;
}
</style>
