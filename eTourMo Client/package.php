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

// Fetch packages and images from the database with pagination
$sql = "
    SELECT 
        p.package_id,
        p.package_name,
        p.package_location,
        p.package_price,
        p.package_ends,
        p.package_category,
        i.image_data,
        i.image_type
    FROM packages p
    LEFT JOIN package_images i ON p.package_id = i.package_id
    WHERE p.package_ends >= CURDATE()  -- Ensure the package end date is today or in the future
    GROUP BY p.package_id
    LIMIT $offset, $itemsPerPage
";
$result = $conn->query($sql);


// Count total packages for pagination
$totalPackagesQuery = "SELECT COUNT(*) AS total FROM packages";
$totalPackagesResult = $conn->query($totalPackagesQuery);
$totalPackages = $totalPackagesResult->fetch_assoc()['total'];
$totalPages = ceil($totalPackages / $itemsPerPage);

// If package_id is set in the URL, fetch the package details to show in modal
$packageDetails = null;
if (isset($_GET['package_id'])) {
    $package_id = (int)$_GET['package_id'];
    $packageDetailsQuery = "SELECT 
    p.package_name,
    p.package_pax,
    p.package_location,
    p.package_price,
    p.package_category,
    p.package_description,
    p.package_start,
    p.package_ends,
    p.package_inclusion,
    p.package_requirements,
    p.package_duration,
    p.package_cancellation_policy,
    p.package_itinerary,
    p.package_minimum,
    i.image_data,
    i.image_type
FROM packages p
LEFT JOIN package_images i ON p.package_id = i.package_id
WHERE p.package_id = $package_id";
    $packageDetailsResult = $conn->query($packageDetailsQuery);
    if ($packageDetailsResult->num_rows > 0) {
        $packageDetails = $packageDetailsResult->fetch_assoc();
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
        }

        /* Package layout */
        .container { max-width: 1200px; width: 100%; padding: 20px; }
        .package-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; height: 300px; }

        /* Package card styling */
        .package-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s, background-color 0.3s;
            background-color: #fff;
        }

        .package-card:hover {
            transform: scale(1.05);
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            background-color: #f9f9f9;
        }

        /* Image and text styling within the package card */
        .package-image { max-width: 100%; border-radius: 8px; transition: transform 0.3s; max-height: 110px; }
        .package-card:hover .package-image {
            transform: scale(1.1);
        }

        .package-title { font-size: 18px; font-weight: bold; margin: 10px 0; }
        .package-location { font-size: 14px; color: #555; }
        .package-price { font-size: 16px; color: #ff5722; font-weight: bold; margin-top: 10px; margin-bottom: 30px; }

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

        /* Modal-like styling for package details */
        /* Modal-like styling for package details */
        .modal-container {
    display: <?php echo $packageDetails ? 'flex' : 'none'; ?>;
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
    padding: 16px; /* Reduced padding for a smaller modal */
    border-radius: 8px;
    width: 60%;  /* Keep the width smaller */
    max-width: 500px;  /* Restrict the max width */
    max-height: 80%;  /* Limit the height of the modal */
    overflow-y: auto;  /* Allow scrolling if content is too long */
    text-align: left;  /* Align text to the left for readability */
    box-sizing: border-box; /* Prevent padding from expanding the width */
}

.modal-content img {
    max-width: 100%;
    border-radius: 8px;
    margin-bottom: 16px;  /* Slightly reduced space below the image */
}

.modal-content h3 {
    font-size: 20px;  /* Slightly smaller font size */
    font-weight: bold;
    margin-bottom: 8px;  /* Reduced margin for h3 */
}

.modal-content p {
    font-size: 14px;
    margin-bottom: 8px;  /* Reduced margin for paragraphs */
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
        <h2>Featured Packages</h2>
        <div class="package-grid">
            <?php
            // Display each package
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="package-card">';
                    if ($row['image_data']) {
                        $imageData = base64_encode($row['image_data']);
                        $imageType = $row['image_type'];
                        echo "<img src='data:image/$imageType;base64,$imageData' class='package-image' alt='{$row['package_name']}'>";
                    } else {
                        echo "<img src='default-image.jpg' class='package-image' alt='Default Image'>";
                    }
                    echo "<div class='package-title'>{$row['package_name']}</div>";
                    echo "Package Available Until <div class='package_ends'>{$row['package_ends']}</div>";
                    echo "<div class='package-location'>{$row['package_location']}</div>";
                    echo "<div class='package-price'>₱" . number_format($row['package_price'], 2) . "</div>";
                    echo "<a href='?package_id={$row['package_id']}' class='book-now-btn'>Book Now</a>";
                    echo "</div>";
                }
            } else {
                echo "<p>No packages available.</p>";
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

    <?php if ($packageDetails && $isLoggedIn): ?>
<div class="modal-container">
    <div class="modal-content">
        <?php
        // Display package image
        if ($packageDetails['image_data']) {
            $imageData = base64_encode($packageDetails['image_data']);
            $imageType = $packageDetails['image_type'];
            echo "<img src='data:image/$imageType;base64,$imageData' class='package-image' alt='{$packageDetails['package_name']}'>";
        } else {
            echo "<img src='default-image.jpg' class='package-image' alt='Default Image'>";
        }
        ?>
        <h3><?php echo $packageDetails['package_name']; ?></h3>
        <p><strong>Location:</strong> <?php echo $packageDetails['package_location']; ?></p>
        <p><strong>Price:</strong> ₱<?php echo number_format($packageDetails['package_price'], 2); ?></p>
        <p><strong>Category:</strong> <?php echo $packageDetails['package_category']; ?></p>

        <!-- New package details -->
        <p><strong>Package Start:</strong> <?php echo nl2br($packageDetails['package_start']); ?></p>
        <p><strong>Package End:</strong> <?php echo nl2br($packageDetails['package_ends']); ?></p>
        <p><strong>Description:</strong> <?php echo nl2br($packageDetails['package_description']); ?></p>
        <p><strong>Inclusions:</strong> <?php echo nl2br($packageDetails['package_inclusion']); ?></p>
        <p><strong>Requirements:</strong> <?php echo nl2br($packageDetails['package_requirements']); ?></p>
        <p><strong>Duration:</strong> <?php echo $packageDetails['package_duration']; ?></p>
        <p><strong>Cancellation Policy:</strong> <?php echo nl2br($packageDetails['package_cancellation_policy']); ?></p>
        <p><strong>Itinerary:</strong> <?php echo nl2br($packageDetails['package_itinerary']); ?></p>

        <!-- Success message display -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
            <div class="booking-success-message">
                <p style="color: green; font-size: 18px; font-weight: bold;">Booking successful! Thank you for your reservation.</p>
            </div>
        <?php endif; ?>

        <!-- Booking Form with Start Date -->
        <form action="process_package_booking.php" method="POST">
            <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">

            <p><strong>Number of Passengers:</strong> 
                <input type="number" name="booking_pax" min="<?php echo $packageDetails['package_minimum'] ?>" max="<?php echo $packageDetails['package_pax'] ?>" required value="<?php echo $packageDetails['package_minimum'] ?>">
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
