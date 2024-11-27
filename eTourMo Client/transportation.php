<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in by checking for session variables
$isLoggedIn = isset($_SESSION['user_id']);

// If user is logged in, add the User Information and Logout links
if ($isLoggedIn) {
    $userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'User';
    $navLinks['Welcome back, ' . $userEmail] = 'customer.php';
    $navLinks['Logout'] = 'logout.php';
} else {
    $navLinks['Login / Signup'] = 'loginsignup.php';
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

// Pagination variables
$itemsPerPage = 12; // 4 columns x 3 rows
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Fetch rentals and images from the database with pagination
$sql = "
    SELECT 
        r.rental_id,
        r.rental_pax,
        r.rental_price,
        r.rental_type,
        r.rental_description,
        r.rental_not_included,
        r.rental_duration,
        r.rental_cancellation_policy,
        i.image_data,
        i.image_type
    FROM rentals r
    LEFT JOIN rental_images i ON r.rental_id = i.rental_id
    GROUP BY r.rental_id
    LIMIT $offset, $itemsPerPage
";
$result = $conn->query($sql);

// Count total rentals for pagination
$totalRentalsQuery = "SELECT COUNT(*) AS total FROM rentals";
$totalRentalsResult = $conn->query($totalRentalsQuery);
$totalRentals = $totalRentalsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRentals / $itemsPerPage);

// If rental_id is set in the URL, fetch the rental details to show in modal
$rentalDetails = null;
if (isset($_GET['rental_id'])) {
    $rental_id = (int)$_GET['rental_id'];
    $rentalDetailsQuery = "SELECT 
    r.rental_pax,
    r.rental_price,
    r.rental_type,
    r.rental_description,
    r.rental_not_included,
    r.rental_duration,
    r.rental_cancellation_policy,
    i.image_data,
    i.image_type
    FROM rentals r
    LEFT JOIN rental_images i ON r.rental_id = i.rental_id
    WHERE r.rental_id = $rental_id";
    $rentalDetailsResult = $conn->query($rentalDetailsQuery);
    if ($rentalDetailsResult->num_rows > 0) {
        $rentalDetails = $rentalDetailsResult->fetch_assoc();
    }
}

?>
<style>
.content {
    display: flex;
    justify-content: center;  /* Centers content horizontally */
    align-items: center;      /* Centers content vertically */
}
/* General reset */
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    padding-top: 80px; /* Space for fixed navbar */
    overflow:hidden;
}

/* Rental layout */
.container { max-width: 1200px; width: 100%; padding: 20px; }
.rental-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

/* Rental card styling */
.rental-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s, background-color 0.3s;
    background-color: #fff;
}

.rental-card:hover {
    transform: scale(1.05);
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
    background-color: #f9f9f9;
}

/* Image and text styling within the rental card */
.rental-image { max-width: 100%; border-radius: 8px; transition: transform 0.3s; }
.rental-card:hover .rental-image {
    transform: scale(1.1);
}

.rental-title { font-size: 18px; font-weight: bold; margin: 10px 0; }
.rental-type { font-size: 14px; color: #555; }
.rental-price { font-size: 16px; color: #ff5722; font-weight: bold; margin-top: 10px; }

/* Button styling */
.book-now-btn {
    margin-top: 15px;
    padding: 10px 20px;
    background-color: #0073e6;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.book-now-btn:hover {
    background-color: #005bb5;
}

/* Pagination */
.pagination {
    text-align: center;
    margin-top: 20px;
}
.pagination a {
    text-decoration: none;
    color: #333;
    margin: 0 5px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.pagination a.active, .pagination a:hover {
    background-color: #0073e6;
    color: white;
}

/* Modal-like styling for rental details */
.modal-container {
    display: <?php echo $rentalDetails ? 'flex' : 'none'; ?>;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 100;
}

.modal-content {
    background-color: white;
    padding: 16px;
    border-radius: 8px;
    width: 60%;
    max-width: 500px;
    max-height: 80%;
    overflow-y: auto;
    text-align: left;
    box-sizing: border-box;
}

.modal-content img {
    max-width: 100%;
    border-radius: 8px;
    margin-bottom: 16px;
}

.modal-content h3 {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 8px;
}

.modal-content p {
    font-size: 14px;
    margin-bottom: 8px;
}

.modal-content p strong {
    font-weight: bold;
}

.confirm-booking-btn, .back-btn {
    margin-top: 15px;
    padding: 10px 20px;
    background-color: #0073e6;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

.confirm-booking-btn:hover, .back-btn:hover {
    background-color: #005bb5;
}

/* Styling for the Back button */
.back-btn {
    background-color:blue;
}

.back-btn:hover {
    background-color: #999;
}
</style>
<?php include('header.php'); ?>
<div class="content">
    <div class="container">
            <h2>Featured Rentals</h2>
        <div class="rental-grid">
            <?php
            // Display each rental
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="rental-card">';
                    if ($row['image_data']) {
                        $imageData = base64_encode($row['image_data']);
                        $imageType = $row['image_type'];
                        echo "<img src='data:image/$imageType;base64,$imageData' class='rental-image' alt='{$row['rental_type']}'>";
                    } else {
                        echo "<img src='default-image.jpg' class='rental-image' alt='Default Image'>";
                    }
                    echo "<div class='rental-title'>{$row['rental_type']}</div>";
                    echo "<div class='rental-type'>{$row['rental_type']}</div>";
                    echo "<div class='rental-price'>₱" . number_format($row['rental_price'], 2) . "</div>";
                    echo "<a href='?rental_id={$row['rental_id']}' class='book-now-btn'>Book Now</a>";
                    echo "</div>";
                }
            } else {
                echo "<p>No rentals available.</p>";
            }
            ?>
        </div>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php
            for ($i = 1; $i <= $totalPages; $i++) {
                $activeClass = $i == $page ? 'active' : '';
                echo "<a href='?page=$i' class='$activeClass'>$i</a>";
            }
            ?>
        </div>
    </div>

    <?php if ($rentalDetails && $isLoggedIn): ?>
<div class="modal-container">
    <div class="modal-content">
        <?php
        // Display rental image
        if ($rentalDetails['image_data']) {
            $imageData = base64_encode($rentalDetails['image_data']);
            $imageType = $rentalDetails['image_type'];
            echo "<img src='data:image/$imageType;base64,$imageData' class='rental-image' alt='{$rentalDetails['rental_type']}'>";
        } else {
            echo "<img src='default-image.jpg' class='rental-image' alt='Default Image'>";
        }
        ?>
        <h3><?php echo $rentalDetails['rental_type']; ?></h3>
        <p><strong>Price:</strong> ₱<?php echo number_format($rentalDetails['rental_price'], 2); ?></p>
        <p><strong>Description:</strong> <?php echo nl2br($rentalDetails['rental_description']); ?></p>
        <p><strong>Not Included:</strong> <?php echo nl2br($rentalDetails['rental_not_included']); ?></p>
        <p><strong>Duration:</strong> <?php echo $rentalDetails['rental_duration']; ?></p>
        <p><strong>Cancellation Policy:</strong> <?php echo nl2br($rentalDetails['rental_cancellation_policy']); ?></p>

        <!-- Booking Form with Start Date -->
        <form action="process_transportation_booking.php" method="POST">
            <input type="hidden" name="rental_id" value="<?php echo $rental_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">

            <p><strong>Number of Passengers:</strong> 
                <input type="number" name="booking_pax" min="1" required value="1">
            </p>

            <p><strong>Booking Start Date:</strong>
                <input type="date" name="booking_start" id="booking_start" required>
            </p>

            <button class="back-btn" type="button" onclick="window.history.back();">Back</button>
            <button class="confirm-booking-btn" type="submit">Confirm Booking</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    // JavaScript to enforce the start date to be at least one week ahead
    const bookingStartDateInput = document.getElementById('booking_start');
    const today = new Date();
    const nextWeek = new Date(today);
    nextWeek.setDate(today.getDate() + 7); // Set to 7 days ahead of today

    const year = nextWeek.getFullYear();
    const month = (nextWeek.getMonth() + 1).toString().padStart(2, '0'); // months are 0-indexed
    const day = nextWeek.getDate().toString().padStart(2, '0'); 

    // Set the minimum date for the input field
    bookingStartDateInput.min = `${year}-${month}-${day}`;
</script>
    </div>
<?php include('footer.php'); ?>

<?php
// Close the database connection
$conn->close();
?>
