<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;

$env = parse_ini_file('.env');

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
$dbname = "etourmodb";  // Update the database name if needed

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination variables
$itemsPerPage = 4; // Limit to 4 items per page (adjust if necessary)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Fetch data with limit for rental listings
$sql = "SELECT * FROM rentals LIMIT $offset, $itemsPerPage"; // Updated table name to 'rentals'
$result = mysqli_query($conn, $sql);

// Calculate total pages
$totalItemsQuery = "SELECT COUNT(*) AS total FROM rentals"; // Updated table name to 'rentals'
$totalItemsResult = mysqli_query($conn, $totalItemsQuery);
$totalItems = mysqli_fetch_assoc($totalItemsResult)['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch rental data and images from the database with pagination
$sql = "
    SELECT 
        r.rental_id,
        r.rental_type,
        r.rental_pax,
        r.rental_price,
        r.rental_description,
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
$totalRentalsQuery = "SELECT COUNT(*) AS total FROM rentals";  // Updated table name to 'rentals'
$totalRentalsResult = $conn->query($totalRentalsQuery);
$totalRentals = $totalRentalsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRentals / $itemsPerPage);

// If rental_id is set in the URL, fetch the rental details to show in modal
$rentalDetails = null;
if (isset($_GET['rental_id'])) {  // Changed from 'package_id' to 'rental_id'
    $rental_id = (int)$_GET['rental_id'];
    $rentalDetailsQuery = "
    SELECT 
        r.rental_type,
        r.rental_description,
        r.rental_price,
        r.rental_duration,
        r.rental_cancellation_policy,
        i.image_data,
        i.image_type
    FROM rentals r
    LEFT JOIN rental_images i ON r.rental_id = i.rental_id
    WHERE r.rental_id = $rental_id";  // Changed from 'package_id' to 'rental_id'
    $rentalDetailsResult = $conn->query($rentalDetailsQuery);
    if ($rentalDetailsResult->num_rows > 0) {
        $rentalDetails = $rentalDetailsResult->fetch_assoc();
    }
}
?>

<style>
    /* Your existing CSS styles */

    /* General reset */
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        height: 1040px;
        padding-top: 80px; /* Space for fixed navbar */
    }

    /* Rental layout */
    .container { max-width: 1200px; width: 100%; height:10px}

    .rental-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); /* 4 columns */
        grid-template-rows: 1fr; /* Single row */
        gap: 10px; /* Space between grid items */
        width: 100%;
    }
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
    .rental-image { max-width: 100%; border-radius: 8px; transition: transform 0.3s; max-height: 110px; }
    .rental-card:hover .rental-image {
        transform: scale(1.1);
    }

    .rental-title { font-size: 18px; font-weight: bold; margin: 10px 0; }
    .rental-location { font-size: 14px; color: #555; }
    .rental-price { font-size: 16px; color: #ff5722; font-weight: bold; margin-top: 10px; margin-bottom: 30px; }

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
        margin-top: 50px;
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
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 60%;
        box-sizing: border-box;
        border-radius: 10px;
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
        transition: background-color 0.3s;
    }

    .confirm-booking-btn:hover, .back-btn:hover {
        background-color: #005bb5;
    }
</style>

<!-- Rental Display and Pagination -->
<?php include('header.php'); ?>
<div class="content">
<?php include('./carousel/carousel.php'); ?>  
<div class="container">
    <h2 class="text-4xl my-2 font-semibold text-center">Featured Rentals</h2>
    <div class="rental-grid">
<?php
// Display each rental package
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="rental-card">';

        // Display the rental image
        if ($row['image_data']) {
            $imageData = base64_encode($row['image_data']);
            $imageType = $row['image_type'];
            echo "<img class='rental-image' src='data:image/$imageType;base64,$imageData' alt='Rental Image'>";
        } else {
            echo "<img class='rental-image' src='default-image.jpg' alt='Default Image'>";
        }

        // Rental details
        echo "<div class='rental-title'>{$row['rental_type']}</div>";
        echo "<div class='rental-price'>PHP " . number_format($row['rental_price'], 2) . " / night</div>";

        // View details button
        echo "<button class='book-now-btn' onclick=\"window.location.href='rental_details.php?rental_id={$row['rental_id']}'\">Book</button>";

        echo '</div>';
    }
} else {
    echo "<p>No rentals available.</p>";
}
?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</div>
    </div>

<!-- Modal Display for Rental Details -->
<div class="modal-container">
    <div class="modal-content">
        <img src="data:image/<?php echo $rentalDetails['image_type']; ?>;base64,<?php echo base64_encode($rentalDetails['image_data']); ?>" alt="Rental Image">
        <h3><?php echo $rentalDetails['rental_name']; ?></h3>
        <p><strong>Description:</strong> <?php echo $rentalDetails['rental_description']; ?></p>
        <p><strong>Location:</strong> <?php echo $rentalDetails['rental_location']; ?></p>
        <p><strong>Price:</strong> PHP <?php echo number_format($rentalDetails['rental_price'], 2); ?> / night</p>
        <p><strong>Duration:</strong> <?php echo $rentalDetails['rental_duration']; ?> days</p>
        <p><strong>Cancellation Policy:</strong> <?php echo $rentalDetails['rental_cancellation_policy']; ?></p>
        <button class="confirm-booking-btn">Book Now</button>
        <button class="back-btn" onclick="window.location.href='customer.php'">Go Back</button>
    </div>
</div>
<?php include('footer.php'); ?>

<?php
// Close the database connection
$conn->close();
?>

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
