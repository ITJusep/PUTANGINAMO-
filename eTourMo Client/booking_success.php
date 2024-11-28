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

$bookingDetails = null;
if (isset($_GET['booking_id'])) {
    $booking_id = (int)$_GET['booking_id'];
    $bookingDetailsQuery = "SELECT 
    b.booking_id,
    b.booking_date,
    b.booking_total_price,
    b.booking_start,
    b.booking_pax,
    b.add_ons
FROM bookings b
WHERE b.booking_id = $booking_id";
    $bookingDetailsResult = $conn->query($bookingDetailsQuery);
    if ($bookingDetailsResult->num_rows > 0) {
        $bookingDetails = $bookingDetailsResult->fetch_assoc();
    }
}

$imageData = base64_encode($packageDetails['image_data']);
$imageType = $packageDetails['image_type'];
$addOns = json_decode($bookingDetails['add_ons'], true);

$addOnsListName = [];
$addOnsListPrice = [];

foreach ($addOns as $addOn) {
    $addOnDetailsQuery = "SELECT 
    a.addon_id,
    a.package_id,
    a.addon_name,
    a.price
FROM add_ons a
WHERE a.addon_id = $addOn";
    $addOnDetailsResult = $conn->query($addOnDetailsQuery);
    if ($addOnDetailsResult->num_rows > 0) {
        $addOnDetails = $addOnDetailsResult->fetch_assoc();
        array_push($addOnsListName, $addOnDetails["addon_name"]);
        array_push($addOnsListPrice, $addOnDetails["price"]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" 
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="
    https://cdn.jsdelivr.net/npm/confetti-js@0.0.18/dist/index.min.js
    "></script>
    <link href="
    https://cdn.jsdelivr.net/npm/confetti-js@0.0.18/site/site.min.css
    " rel="stylesheet">
</head>
<body>
    <style>
        body {
            background-image: url('data:image/<?php echo $imageType; ?>;base64,<?php echo $imageData; ?>');
        }
    </style>
    <canvas id="confetti-holder" class="absolute top-0 left-0 pointer-events-none"></canvas>
    <main class="w-screen h-screen flex justify-center items-center">
        <section class="w-3/4 shadow-md p-6 bg-white text-black">
            <header class="mb-6 w-1/2 mx-auto text-center">
                <i class="fa-solid fa-circle-check fa-6x text-green-400"></i>
                <h1 class="text-4xl font-semibold my-4">Your booking has been successfully confirmed!</h1>
                <p>Thank you for booking your tour with us! Your booking is confirmed, and we are looking forward to seeing you on the tour. A confirmation email has been sent with all the details.</p>
            </header>

            <div class="flex justify-between">
                <div>
                    <h2 class="text-lg font-semibold mb-2">Booking Information</h2>
                    <p><strong>Booking ID:</strong> <?php echo nl2br($bookingDetails['booking_id']); ?></p>
                    <p><strong>Booking Date:</strong> <?php echo nl2br($bookingDetails['booking_date']); ?></p>
                    <p><strong>Booking Start:</strong> <?php echo nl2br($bookingDetails['booking_start']); ?></p>
                    <p><strong>Number of Passengers:</strong> <?php echo nl2br($bookingDetails['booking_pax']); ?></p>
                    <p><strong>Add Ons:</strong></p>
                    <ul>

                        <?php
                            for ($i = 0; $i < count($addOnsListPrice); $i++) {
                                echo "<li>" . $addOnsListName[$i] . " - " . $addOnsListPrice[$i] . "</li>";
                            }
                        ?>
                    </ul>
                    <p><strong>Total Price:</strong> <?php echo nl2br($bookingDetails['booking_total_price']); ?></p>
                </div>

                <div class="divider divider-horizontal divider-info"></div>

                <div>
                    <h2 class="text-lg font-semibold mb-2">Package Information</h2>
                    <p><strong>Name:</strong> <?php echo nl2br($packageDetails['package_name']); ?></p>
                    <p><strong>Price:</strong> <?php echo $packageDetails['package_price']; ?></p>
                    <p><strong>Description:</strong> <?php echo nl2br($packageDetails['package_description']); ?></p>
                    <p><strong>Location:</strong> <?php echo $packageDetails['package_location']; ?></p>
                    <p><strong>Category:</strong> <?php echo $packageDetails['package_category']; ?></p>
                </div>
                
                <div class="divider divider-horizontal divider-info"></div>

                <div>
                    <h2 class="text-lg font-semibold mb-2">Tour Date</h2>
                    <p><strong>Package Start:</strong> <?php echo nl2br($packageDetails['package_start']); ?></p>
                    <p><strong>Package End:</strong> <?php echo nl2br($packageDetails['package_ends']); ?></p>
                </div>

                <div class="divider divider-horizontal divider-info"></div>

                <div>
                    <h2 class="text-lg font-semibold mb-2">Other Information</h2>
                    <p><strong>Inclusions:</strong> <?php echo nl2br($packageDetails['package_inclusion']); ?></p>
                    <p><strong>Requirements:</strong> <?php echo nl2br($packageDetails['package_requirements']); ?></p>
                    <p><strong>Duration:</strong> <?php echo $packageDetails['package_duration']; ?></p>
                    <p><strong>Cancellation Policy:</strong> <?php echo nl2br($packageDetails['package_cancellation_policy']); ?></p>
                    <p><strong>Itinerary:</strong> <?php echo nl2br($packageDetails['package_itinerary']); ?></p>
                </div>
            </div>
            
            <div class="flex justify-center">
                <a href="/?package.php" class="btn btn-info mt-6">Back To Page</a>
            </div>
        </section>
    </main>
</body>
</html>



<?php
// Close the database connection
$conn->close();
?>


<script>
    var confettiSettings = {"target":"confetti-holder","max":"500","size":"1","animate":true,"props":["circle","square","triangle","line"],"colors":[[165,104,246],[230,61,135],[0,199,228],[253,214,126]],"clock":"25","rotate":false,"width":"1904","height":"1040","start_from_edge":true,"respawn":false};
    var confetti = new ConfettiGenerator(confettiSettings);
    confetti.render();

</script>