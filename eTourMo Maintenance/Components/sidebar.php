<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Start the session if not already started
}
// database connection setup (assuming you are using MySQL)
$host = 'localhost';  // Database host
$dbname = 'etourmodb'; // Your database name
$username = 'root'; // Database username
$password = ''; // Database password

// Create PDO instance
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection error
    die("Connection failed: " . $e->getMessage());
}
// Get the current URL path
$current_page = basename($_SERVER['REQUEST_URI'], ".php");

// Check if admin_id is set
$logged_in_user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'Guest';
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Administrator';

// Fetch first_name from admin_accounts database table
if ($logged_in_user_id !== 'Guest') {
    // Assuming you have a database connection $db
    $query = "SELECT first_name FROM admin_accounts WHERE admin_id = :admin_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':admin_id', $logged_in_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get the first name
    $first_name = $user ? $user['first_name'] : 'Guest';
} else {
    $first_name = 'Guest';
}
?>
<style>
/* Sidebar Styles */
.sidebar {
    max-width: 220px;
    background-color: #133E87;
    padding: 20px;
    position: fixed;
    height: 100%;
    left: 0;
    top: 0;
    color: #fff;
    font-family: Arial, sans-serif;
}

.nav a {
    display: block;
    color: #fff;
    padding: 10px;
    margin: 5px 0;
    text-decoration: none;
}

.nav a:hover, .nav a.active {
    background-color: #003253;
    border-radius: 4px;
}

.logout-link {
    color: red;
    font-weight: bold;
}

/* Welcome message styles */
.welcome-message {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 20px;
}

/* Dropdown styles */
.submenu {
    display: none;
    margin-left: 20px;
}

.nav .has-submenu:hover .submenu {
    display: block;
}
</style>

<!-- Navigation Bar Start -->
<aside class="sidebar">
    <!-- Welcome Message -->
    <div class="welcome-message">
        Welcome, <?= htmlspecialchars($first_name) ?>!
    </div>
    <nav class="nav" role="navigation">
        <!-- Dashboard Submenu -->
        <a href="/eTourMo Maintenance/Bookings/bookings.php" class="<?= ($current_page == 'bookings') ? 'active' : '' ?>">Bookings</a>

        <a href="/eTourMo Maintenance/Dashboard/reports.php" class="<?= ($current_page == 'reports') ? 'active' : '' ?>">Reports</a>
        
        <!-- Web Contents Submenu -->
        <div class="has-submenu">
            <a href="javascript:void(0);" class="<?= ($current_page == 'homepage' || $current_page == 'aboutus' || $current_page == 'privacy_and_terms') ? 'active' : '' ?>">Web Contents</a>
            <div class="submenu">
                <a href="/eTourMo Maintenance/Web Contents/About Us/aboutus.php" class="<?= ($current_page == 'aboutus') ? 'active' : '' ?>">About Us</a>
                <!-- <a href="/eTourMo Maintenance/Web Contents/Page Backdrop/page_backdrop.php" class="<?= ($current_page == 'page_backdrop') ? 'active' : '' ?>">Page Backdrop</a> -->
                <a href="/eTourMo Maintenance/Web Contents/Privacy and Terms/privacy_and_terms.php" class="<?= ($current_page == 'privacy_and_terms') ? 'active' : '' ?>">Privacy & Terms</a>
            </div>
        </div>

        <!-- Postings Submenu -->
        <div class="has-submenu">
            <a href="javascript:void(0);" class="<?= ($current_page == 'travel_packages' || $current_page == 'rental_packages' || $current_page == 'educational_tour') ? 'active' : '' ?>">Postings</a>
            <div class="submenu">
                <a href="/eTourMo Maintenance/Postings/Travel Packages/travel_packages.php" class="<?= ($current_page == 'travel_packages') ? 'active' : '' ?>">Travel Packages</a>
                <a href="/eTourMo Maintenance/Postings/Rental Packages/rental.php" class="<?= ($current_page == 'rental') ? 'active' : '' ?>">Rental Packages</a>
                <a href="/eTourMo Maintenance/Postings/Add On/addon.php" class="<?= ($current_page == 'addon.php') ? 'active' : '' ?>">Add Ons</a>
            </div>
        </div>

        <!-- Accounts Submenu -->
        <div class="has-submenu">
            <a href="javascript:void(0);" class="<?= ($current_page == 'customer' || $current_page == 'employee' || $current_page == 'activity_logs') ? 'active' : '' ?>">Accounts</a>
            <div class="submenu">
                <a href="/eTourMo Maintenance/Accounts/Customer/customer.php" class="<?= ($current_page == 'customer') ? 'active' : '' ?>">Customer</a>
                <?php if ($is_admin): ?>
                    <a href="/eTourMo Maintenance/Accounts/Employee/employee.php" class="<?= ($current_page == 'employee') ? 'active' : '' ?>">Employee</a>
                    <a href="/eTourMo Maintenance/Accounts/Employee_Activity_Logs/activity_logs.php" class="<?= ($current_page == 'activity_logs') ? 'active' : '' ?>">Activity Logs</a>
                <?php endif; ?>
            </div>
        </div>

        <a href="javascript:void(0);" onclick="logout()" class="logout-link">Logout</a>
    </nav>
</aside>
<!-- Navigation Bar End -->


<script>
function logout() {
    fetch('/eTourMo Maintenance/logout/index.php', {
        method: 'POST',
        credentials: 'include'
    })
    .then(response => {
        if (response.ok) {
            window.location.href = '/eTourMo Maintenance/login/index.php?logout=success';
        } else {
            alert('Logout failed. Please try again.');
        }
    })
    .catch(error => {
        alert('An error occurred while logging out. Please try again.');
    });
}
</script>


<style>
/* Styles for restricted access notice */
.restricted-access {
    color: #f56565;
    font-weight: bold;
    margin-top: 10px;
    display: block; /* Ensure it stays visible */
}
</style>
