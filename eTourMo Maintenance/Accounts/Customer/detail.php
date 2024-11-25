<?php
// Database connection
$host = 'localhost';
$dbname = 'etourmodb';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to the database: " . $e->getMessage());
}

// Initialize a variable to hold the success message
$successMessage = ""; 

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $user_id = $_POST['user_id']; // Get user ID from the form
    $sql = "DELETE FROM user_profiles WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        // Set the success message
        $successMessage = "Account deleted successfully.";
    } else {
        $successMessage = "Error deleting account.";
    }
}

// Fetch user profile data by ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Convert to integer for safety
$sql = "SELECT * FROM user_profiles WHERE user_id = $user_id LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php include('../../Components/header.php'); ?>
<div class="content">
<div class="back-button-container">
    <button type="button" class="back-button" onclick="window.location.href='customer.php';">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 5l-7 7 7 7"></path>
        </svg>
    </button>
</div>
<!-- Check if there are any user accounts -->
<?php if ($user_data): ?>
    <!-- Display success message if any -->
    <?php if ($successMessage): ?>
        <p class="success-message"><?= htmlspecialchars($successMessage); ?></p>
    <?php endif; ?>

    <div class="user-profile">
        <form action="" method="POST" class="form">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_data['user_id']); ?>">

            <div class="form-group">
                <label for="firstname" class="label">First Name:</label>
                <input type="text" name="firstname" id="firstname" value="<?= htmlspecialchars($user_data['firstname']); ?>" readonly class="input">
            </div>

            <div class="form-group">
                <label for="lastname" class="label">Last Name:</label>
                <input type="text" name="lastname" id="lastname" value="<?= htmlspecialchars($user_data['lastname']); ?>" readonly class="input">
            </div>

            <div class="form-group">
                <label for="email" class="label">Email:</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($user_data['email']); ?>" readonly class="input">
            </div>

            <div class="form-group">
                <label for="contact_information" class="label">Contact Information:</label>
                <input type="text" name="contact_information" id="contact_information" value="<?= htmlspecialchars($user_data['contact_information']); ?>" readonly class="input">
            </div>

            <div class="account-buttons">
                <input type="submit" name="delete" value="Delete Account" onclick="return confirm('Are you sure you want to delete your account?');" class="delete-button">
            </div>
        </form>
    </div>
<?php else: ?>
    <p class="no-accounts">No accounts available.</p>
<?php endif; ?>
</div>
<?php include('../../Components/footer.php'); ?>

<!-- Inline CSS -->
<style>
/* Global Styles */

.content{
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 250px; 
}

/* User Profile Section */
.user-profile {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-picture {
    margin-bottom: 20px;
}

.label {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
}

.profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
}

.no-picture {
    color: #7d7d7d;
    font-size: 14px;
}

.form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.input {
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    width: 100%;
    background-color: #f0f0f0;
    cursor: not-allowed;
}

.input:focus {
    border-color: #5d5d5d;
    outline: none;
}

.account-buttons {
    display: flex;
    justify-content: flex-start;
}

.delete-button {
    background-color: #e74c3c;
    color: white;
    font-weight: bold;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.delete-button:hover {
    background-color: #c0392b;
}

.delete-button:focus {
    outline: none;
}

/* Responsive Design */
@media (max-width: 600px) {
    .user-profile {
        padding: 15px;
    }
}
/* General button styles for both regular and back buttons */
.form-group button, .back-button-container .back-button, .button-group button {
    padding: 12px 24px;                 /* Increased padding for a more balanced button */
    font-size: 16px;                    /* Maintain legible font size */
    font-weight: 600;                   /* Slightly bolder text */
    text-align: center;                 /* Center text inside the button */
    border: none;                       /* Remove default border */
    border-radius: 8px;                 /* Rounded corners for smoother appearance */
    cursor: pointer;                   /* Pointer cursor to indicate clickability */
    transition: all 0.3s ease-in-out;   /* Smooth transition for hover and active states */
    text-transform: uppercase;          /* Uppercase text for better readability */
    display: inline-block;              /* Ensure buttons align properly with the content */
    letter-spacing: 1px;                /* Slight spacing for better readability */
}

.back-button{
    margin-left: 10px;
}

/* Default state for all buttons (background gradient, padding, and text styles) */
.form-group button, .back-button-container .back-button, .button-group button {
    background: linear-gradient(145deg, #007bff, #0056b3); /* Subtle gradient background */
    color: white;                         /* White text color */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Soft shadow for a lifted effect */
}

/* Hover effect for all buttons */
.form-group button:hover, .back-button-container .back-button:hover, .button-group button:hover {
    background: linear-gradient(145deg, #0056b3, #00428a); /* Darker gradient on hover */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);   /* More pronounced shadow for hover effect */
}

/* Active effect for all buttons (when clicked) */
.form-group button:active, .back-button-container .back-button:active,.button-group button:active {
    background: linear-gradient(145deg, #003c7a, #00284f); /* Even darker gradient when clicked */
    box-shadow: none; /* Remove shadow on active state */
    transform: translateY(2px); /* Slight move down for a "pressed" effect */
}

/* Focus effect for all buttons (to ensure accessibility) */
.form-group button:focus, .back-button-container .back-button:focus, .button-group button:focus {
    outline: none; /* Remove default outline */
    border: 2px solid #0056b3; /* Border on focus to indicate the active element */
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Light shadow to indicate focus */
}

/* Back button container and its styles */
.back-button-container {
    text-align: left; /* Align the back button to the left */
    margin-top: 20px;    /* Space at the top of the button container */
}

.back-button-container .back-button {
    background-color: #007bff; /* Matching the main button's color */
    color: white;
    padding: 12px 24px;
    font-size: 16px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Same lifted shadow effect */
}

/* Hover effect for back button */
.back-button-container .back-button:hover {
    background-color: #0056b3; /* Darker blue on hover */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

/* Active effect for back button */
.back-button-container .back-button:active {
    background-color: #003c7a;
    box-shadow: none;
    transform: translateY(2px); /* "Pressed" effect */
}

/* Focus effect for back button */
.back-button-container .back-button:focus {
    outline: none; 
    border: 2px solid #0056b3; /* Border and shadow on focus */
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Light focus shadow */
}
</style>
