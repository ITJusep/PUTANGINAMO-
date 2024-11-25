<?php
// Database connection (adjust parameters as needed)
$host = 'localhost';
$db = 'etourmodb';
$user = 'root';
$pass = '';
$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Log visitor IP
$ip_address = $_SERVER['REMOTE_ADDR'];
$date = date('Y-m-d');
$query = "SELECT id FROM ip_visits WHERE ip_address = ? AND DATE(visit_time) = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ss", $ip_address, $date);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    // Insert new visit record if the IP has not visited today
    $stmt = $mysqli->prepare("INSERT INTO ip_visits (ip_address) VALUES (?)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
}

$stmt->close();

// Fetch total bookings, done bookings, total price, pax, email, and admin_id for each package
$doneBookingsResult = $mysqli->query("
    SELECT 
        p.package_name, 
        COUNT(b.booking_id) AS total_bookings,
        COUNT(CASE WHEN b.booking_status = 'done' THEN 1 END) AS done_bookings,
        SUM(CASE WHEN b.booking_status = 'done' THEN b.booking_total_price ELSE 0 END) AS total_price,
        GROUP_CONCAT(CASE WHEN b.booking_status = 'done' THEN DATE(b.booking_date) END) AS done_booking_dates,
        GROUP_CONCAT(CASE WHEN b.booking_status = 'done' THEN b.booking_pax END) AS done_booking_pax,
        GROUP_CONCAT(CASE WHEN b.booking_status = 'done' THEN u.email END) AS done_booking_emails,
        GROUP_CONCAT(CASE WHEN b.booking_status = 'done' THEN b.admin_id END) AS done_admin_ids  -- Fetch admin_id
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    JOIN user_profiles u ON b.user_id = u.user_id
    WHERE b.booking_status = 'done'
    GROUP BY p.package_name
");

// Initialize total price for all done bookings
$totalPrice = 0;
$packageBookings = [];
while ($row = $doneBookingsResult->fetch_assoc()) {
    $packageBookings[] = $row;
    $totalPrice += $row['total_price'];
}

$mysqli->close();
?>

<?php include('../Components/header.php'); ?>
<div class="content text-black">
<!-- Booking Report Table -->
<h2 class="text-6xl font-bold mb-4">Reports</h2>

<!-- Total Price from All Done Bookings -->
<div class="total-earned">
    <h3 class="font-bold text-2xl mb-4">Total Earned From Bookings: <span class="text-green-500">₱<?= number_format($totalPrice, 2) ?></span></h3>
</div>
<div class="">
    <div class="table-wrapper">
        <table class="table table-lg">
            <thead>
                <tr class="text-white bg-[#608BC1]">
                    <th>Package Name</th>
                    <th>Total Bookings</th>
                    <th>Status</th>
                    <th>Earned</th>
                    <th>Booking Dates</th>
                    <th>Booking Pax</th>
                    <th>Customer Emails</th>
                    <th>Admin ID</th> <!-- New column for Admin ID -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packageBookings as $package): ?>
                    <tr class="">
                        <td><?= htmlspecialchars($package['package_name']) ?></td>
                        <td><?= $package['total_bookings'] ?></td>
                        <td><?= $package['done_bookings'] > 0 ? "Done" : "Not Done" ?></td>
                        <td>₱<?= number_format($package['total_price'], 2) ?></td>
                        <td>
                            <?php
                            if ($package['done_booking_dates']) {
                                $dates = explode(",", $package['done_booking_dates']);
                                echo implode("<br>", $dates); 
                            } else {
                                echo "No done bookings";
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($package['done_booking_pax']) {
                                $pax = explode(",", $package['done_booking_pax']);
                                echo implode("<br>", $pax); 
                            } else {
                                echo "No done bookings";
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($package['done_booking_emails']) {
                                $emails = explode(",", $package['done_booking_emails']);
                                echo implode("<br>", $emails); 
                            } else {
                                echo "No done bookings";
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($package['done_admin_ids']) {
                                $admin_ids = explode(",", $package['done_admin_ids']);
                                echo implode("<br>", $admin_ids);  // Display admin IDs
                            } else {
                                echo "No done bookings";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php include('../Components/footer.php'); ?>

<!-- Plain CSS Styles -->
<style>
body {
    background-color: #F3F3E0;
}


/* Responsive Styles */

/* For Tablets and Smaller Screens */
@media (max-width: 768px) {
    .booking-report-container {
        margin-left: 20px; /* Reduce left margin on smaller screens */
        padding: 15px;
    }

    .booking-report-table th,
    .booking-report-table td {
        padding: 8px;
        font-size: 14px;
    }

    .total-earned {
        font-size: 16px;
        padding: 10px;
    }
}

/* For Mobile Phones */
@media (max-width: 480px) {
    .booking-report-container {
        margin-left: 10px; /* Reduce left margin on very small screens */
        padding: 10px;
    }

    .booking-report-table th,
    .booking-report-table td {
        padding: 6px;
        font-size: 12px;
    }

    .total-earned {
        font-size: 14px;
        padding: 8px;
    }
}
</style>
