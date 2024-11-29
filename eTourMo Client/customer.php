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
body {
  height: 1040px;
  margin: 0;
  font-family: Arial, sans-serif;
  background-color: #f9f9f9;
  overflow:hidden;
}

/* Container for both elements */
.container {
  display: grid;
  grid-template-columns: 1fr 1fr; /* Two equal-width columns */
}

.UserProfile-container {
  display: flex;
  flex-direction: column;
  width: 500px;
  margin-top:20px;
  margin-left:20px;
  overflow:scroll;
  height:300px
}

.UserProfile-main {
  width: 60%; /* Increased width for profile section */
  padding: 20px;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  height: 100px
}

.UserProfile-info-container {
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #fafafa;
  margin-bottom: 20px;
}

.UserProfile-info-container h3 {
  margin-bottom: 10px;
  font-size: 1.2em;
}

.UserProfile-info-item {
  margin-bottom: 15px;
}

.UserProfile-info-item input,
.UserProfile-info-item button {
  width: 100%;
  padding: 12px;
  margin: 8px 0;
  font-size: 1em;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.UserProfile-info-item input:focus,
.UserProfile-info-item button:focus {
  border-color: #007bff;
  outline: none;
}

.update-profile-button,
.change-password-button,
.delete-account-button {
  background-color: #007bff;
  color: #fff;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.update-profile-button:hover,
.change-password-button:hover,
.delete-account-button:hover {
  background-color: #0056b3;
}

.booking-history-container {
  width: 700px; /* Adjusted width for better layout */
  padding: 20px;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  margin-left:-90px;
  overflow:scroll;
  height: 300px;
  margin-top:20px;
}

.booking-history-container h3 {
  margin-bottom: 15px;
  font-size: 1.2em;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px;
  text-align: center;
  border: 1px solid #ddd;
}

th {
  background-color: #f4f4f4;
  font-weight: bold;
}

td {
  font-size: 0.9em;
}

td button {
  padding: 8px 15px;
  background-color: #dc3545;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

td button:hover {
  background-color: #c82333;
}

/* Responsive Design */
@media (max-width: 768px) {
  .content {
    flex-direction: column;
    align-items: center;
  }

  .UserProfile-main, .booking-history-container {
    width: 100%;
    margin-bottom: 20px;
  }
}

</style>

<?php include('header.php'); ?>
<div class="content">
<?php include('./carousel/carousel.php'); ?>
<div class="container">
    <div class="UserProfile-container">
        <!-- User Profile Section -->
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
