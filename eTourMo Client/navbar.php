<?php
// Check if the session has already been started to avoid the error
if (session_status() == PHP_SESSION_NONE) {
    session_start();  // Start the session if not already started
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

// Navbar links as an array
$navLinks = [
    'Packages' => 'package.php',
    'Transportation' => 'transportation.php',
    'Trip Planner' => 'trip_planner.php',
    'Testimonials' => '#',
    'About Us' => 'about.php'
];

// Check if user is logged in by checking for session variables
$isLoggedIn = isset($_SESSION['user_id']); // You can check for either user_id or user_email

// If user is logged in, add the User Information and Logout links
if ($isLoggedIn) {
    // Check if 'user_email' is set to avoid undefined index error
    $userEmail = $_SESSION["user_email"];
    
    // Add the Welcome message as a clickable link to the customer profile page
    $navLinks['Welcome back, ' . $userEmail] = 'customer.php'; // Display welcome message with email
    
    // Add the Logout link
    $navLinks['Logout'] = 'logout.php';

} else {
    // If not logged in, show Login/Signup link
    $navLinks['Login / Signup'] = 'loginsignup.php';
}
?>

<style>
/* General reset */
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Align navbar to top */
    min-height: 100vh; /* Ensure body takes up full height */
    margin-top: 30px; /* Optional space below navbar */
    padding-top: 40px; /* Adjust this value to prevent content from hiding under navbar and add 40px space */
}

/* Navbar container */
.navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 80%; /* Make navbar full width */
    max-width: 1200px; /* Optional: maximum width for larger screens */
    padding: 15px 30px; /* Adjust padding for height */
    background-color: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 15px; /* No border radius if you want it flush with screen */
    position: fixed; /* Fix the navbar to the top */
    top: 40px; /* Add a 40px margin from the top of the screen */
    z-index: 10; /* Ensure navbar is above other content */
}

/* Logo styling */
.navbar .logo img {
    height: 40px; /* Adjust height to match */
}

/* Nav links */
.navbar ul {
    display: flex;
    list-style-type: none;
}

.navbar ul li {
    margin: 0 20px;
}

.navbar ul li a {
    text-decoration: none;
    color: #333;
    font-size: 16px;
    font-weight: 500;
}

/* Hover effect for links */
.navbar ul li a:hover {
    color: #0073e6;
}
</style>

<nav class="navbar">
    <!-- Logo -->
    <div class="logo">
        <a href="#">
            <img src="logo.png" alt="Logo"> <!-- Replace "logo.png" with the path to your logo image -->
        </a>
    </div>
    <!-- Navigation Links -->
    <ul>
        <?php
            foreach ($navLinks as $name => $link) {
                // If the name is 'Welcome back' or 'Logout', make it a clickable link
                if (strpos($name, 'Welcome back') !== false) {
                    // Wrap the welcome message with a link
                    echo "<li><a href='$link'>$name</a></li>";
                } elseif (strpos($name, 'Logout') !== false) {
                    // Add a logout link
                    echo "<li><a href='$link'>$name</a></li>";
                } else {
                    // Regular navigation links
                    echo "<li><a href='$link'>$name</a></li>";
                }
            }
        ?>
    </ul>
</nav>
