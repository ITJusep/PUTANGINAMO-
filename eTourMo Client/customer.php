<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Connect to the database (example using MySQL)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "etourmodb"; // Replace with your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user profile
$sql = "SELECT firstname, lastname, email, contact_information, created_at FROM user_profiles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($firstname, $lastname, $email, $contact_information, $created_at);
$stmt->fetch();
$stmt->close();

// Fetch booking history
$bookingSql = "SELECT b.booking_id, p.package_name, b.booking_date, b.booking_start, b.booking_pax, b.booking_total_price, b.booking_status 
               FROM bookings b
               JOIN packages p ON b.package_id = p.package_id
               WHERE b.user_id = ?";
$bookingStmt = $conn->prepare($bookingSql);
$bookingStmt->bind_param("i", $userId);
$bookingStmt->execute();
$bookingStmt->store_result();
$bookingStmt->bind_result($booking_id, $package_name, $booking_date, $booking_start, $booking_pax, $booking_total_price, $booking_status);
$bookings = [];
while ($bookingStmt->fetch()) {
    $bookings[] = [
        'booking_id' => $booking_id,
        'package_name' => $package_name,
        'booking_date' => $booking_date,
        'booking_start' => $booking_start,
        'booking_pax' => $booking_pax,
        'booking_total_price' => $booking_total_price,
        'booking_status' => $booking_status
    ];
}
$bookingStmt->close();

// Handle profile update, password change, account deletion, and booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update user information
    if (isset($_POST['update_profile'])) {
        $newFirstName = !empty($_POST['firstname']) ? $_POST['firstname'] : $firstname;
        $newLastName = !empty($_POST['lastname']) ? $_POST['lastname'] : $lastname;
        $newEmail = !empty($_POST['email']) ? $_POST['email'] : $email;
        $newContactInfo = !empty($_POST['contact_information']) ? $_POST['contact_information'] : $contact_information;

        $updateSql = "UPDATE user_profiles SET firstname = ?, lastname = ?, email = ?, contact_information = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("ssssi", $newFirstName, $newLastName, $newEmail, $newContactInfo, $userId);
        if ($stmt->execute()) {
            header("Location: customer.php"); // Redirect to reload the updated profile
            exit();
        } else {
            echo "Error updating profile: " . $stmt->error;
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword !== $confirmPassword) {
            echo "Passwords do not match!";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updatePasswordSql = "UPDATE user_profiles SET password_hash = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updatePasswordSql);
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) {
                // echo "Password changed successfully!";
            } else {
                echo "Error updating password: " . $stmt->error;
            }
        }
    }

    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        $deleteSql = "DELETE FROM user_profiles WHERE user_id = ?";
        $stmt = $conn->prepare($deleteSql);
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            session_destroy();
            header("Location: loginsignup.php");
            exit();
        } else {
            echo "Error deleting account: " . $stmt->error;
        }
    }

    // Handle booking cancellation
    if (isset($_POST['cancel_booking'])) {
        $bookingId = $_POST['booking_id'];
        $cancelSql = "UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = ? AND booking_status NOT IN ('Confirmed', 'Done', 'Declined')";
        $stmt = $conn->prepare($cancelSql);
        $stmt->bind_param("i", $bookingId);
        if ($stmt->execute()) {
            header("Location: customer.php"); // Redirect to reload the page with updated booking status
            exit();
        } else {
            echo "Error canceling booking: " . $stmt->error;
        }
    }

    if (isset($_POST['paynow_booking'])) {
            header("Location: paynow.php"); // Redirect to reload the page with updated booking status
            exit();
    }
}

$conn->close();
?>

<style>
html, body {
  height: 100%;
  margin: 0;
  overflow: hidden;
}

.content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-top: 50px;
}

.UserProfile-container {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.UserProfile-main {
  width: 50%;
  padding: 10px;
}

.UserProfile-info-container {
  text-align: center;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-top: 20px;
  margin-left: 10px;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

table, th, td {
  border: 1px solid #ddd;
  padding: 8px;
}

th {
  background-color: #f4f4f4;
}

.booking-history-container {
  width: 45%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-left: 20px;
  text-align: center;
}

.booking-history-container table {
  margin-top: 20px;
  width: 100%;
  border-collapse: collapse;
}
</style>

<?php include('header.php'); ?>
<div class="content">
    <div class="UserProfile-container">
        <!-- User Profile Section -->
        <div class="UserProfile-main">
            <div class="UserProfile-info-container">
                <form method="POST" action="customer.php">
                    <div class="UserProfile-info-row">
                        <div class="UserProfile-info-item">
                            <input type="text" name="firstname" value="<?= htmlspecialchars($firstname) ?>" placeholder="Update Firstname">
                            <input type="text" name="lastname" value="<?= htmlspecialchars($lastname) ?>" placeholder="Update Lastname">
                        </div>

                        <div class="UserProfile-info-item">
                            <input type="text" name="contact_information" value="<?= htmlspecialchars($contact_information) ?>" placeholder="Update Contact Information">
                        </div>

                        <div class="UserProfile-info-item">
                            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Update Email">
                        </div>

                        <div class="UserProfile-info-item">
                            <button type="submit" name="update_profile" class="update-profile-button">Update Profile</button>
                        </div>
                    </div>
                </form>

                <h3>Change Password</h3>
                <form method="POST" action="customer.php">
                    <div class="UserProfile-info-item">
                        <label for="new_password">New Password</label>
                        <input type="password" name="new_password" required placeholder="Enter new password">
                    </div>

                    <div class="UserProfile-info-item">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm new password">
                    </div>

                    <div class="UserProfile-info-item">
                        <button type="submit" name="change_password" class="change-password-button">Change Password</button>
                    </div>
                </form>

                <h3>Delete Account</h3>
                <form method="POST" action="customer.php">
                    <div class="UserProfile-info-item">
                        <button type="submit" name="delete_account" class="delete-account-button">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Booking History Section -->
        <div class="booking-history-container">
            <h3>Your Booking History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Package Name</th>
                        <th>Booking Date</th>
                        <th>Booking Start</th>
                        <th>Pax</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking) : ?>
                    <tr>
                        <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                        <td><?= htmlspecialchars($booking['package_name']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_date']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_start']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_pax']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_total_price']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_status']) ?></td>
                        <td>
                            <?php if ($booking['booking_status'] !== 'Confirmed' && $booking['booking_status'] !== 'Done' && $booking['booking_status'] !== 'Declined') : ?>
                                <form method="POST" action="customer.php">
                                    <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                    <button type="submit" name="cancel_booking">Cancel</button> |
                                    <button type="submit" name="paynow_booking">Pay Now</button> 

                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include('footer.php'); ?>
