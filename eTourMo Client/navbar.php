<?php
// Check if the session has already been started to avoid the error
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start the session if not already started
}

// Navbar links as an array
$navLinks = [
    'Packages' => 'package.php',
    'Transportation' => 'transportation.php',
    'Testimonials' => '#',
    'About Us' => 'about.php'
];

// Check if user is logged in by checking for session variables
$isLoggedIn = isset($_SESSION['user_id']); // Check for user_id or user_email

if ($isLoggedIn) {
    // If logged in, add user-specific links
    $userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'User';
    $userLinks['Welcome, ' . $userEmail] = 'customer.php'; // Add user profile link
    $userLinks['<a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i></a>'] = 'logout.php'; // Add logout icon with a specific class
} else {
    // If not logged in, add Login/Signup link
    $userLinks['Login / Signup'] = 'loginsignup.php';
}

// Get the current page filename
$currentFile = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link rel="stylesheet" href="./navbar.css"> <!-- Link to external stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <!-- Logo -->
        <div class="logo">
            <a href="#">
                <img src="logo.png" alt="Logo"> <!-- logo path -->
            </a>
        </div>
        <!-- Navigation Links -->
        <ul class="nav-links">
            <?php
            foreach ($navLinks as $name => $link) {
                // Check if the current link matches the current page
                $isActive = (basename($link) == $currentFile) ? 'class="active"' : '';
                echo "<li><a href='$link' $isActive>$name</a></li>";
            }
            ?>
        </ul>
        <!-- User-specific links -->
        <div class="user-links">
            <ul>
                <?php
                foreach ($userLinks as $name => $link) {
                    $isActive = (basename($link) == $currentFile) ? 'class="active"' : '';
                    echo "<li><a href='$link' $isActive>$name</a></li>";
                }
                ?>
            </ul>
        </div>
    </nav>
</body>

</html>