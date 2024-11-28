<?php
session_start(); // Start the session

// Database connection
$host = 'localhost';
$dbname = 'etourmodb';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Handle account creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (empty($_POST['admin_id']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['password']) || empty($_POST['role'])) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: admin.php"); // Redirect to the same page
        exit;
    }

    $admin_id = trim($_POST['admin_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact_information = trim($_POST['contact_number']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Hash the password before storing
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into the database
    $stmt = $pdo->prepare("INSERT INTO admin_accounts (admin_id, first_name, last_name, contact_number, user_type, admin_password) 
                           VALUES (:admin_id, :first_name, :last_name, :contact_number, :role, :password_hash)");
    try {
        $stmt->execute([
            'admin_id' => $admin_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'contact_number' => $contact_information,
            'role' => $role,
            'password_hash' => $password_hash
        ]);
        $_SESSION['success_message'] = "Account created successfully!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Account creation failed: " . $e->getMessage();
    }
    header("Location: employee.php"); // Redirect to the same page
    exit;
}

// Handle deactivate/activate actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && isset($_POST['admin_id'])) {
    $admin_id = trim($_POST['admin_id']);

    if ($_POST['action'] === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE admin_accounts SET status = 'deactivated' WHERE admin_id = :admin_id");
        $stmt->execute(['admin_id' => $admin_id]);
        $_SESSION['success_message'] = "Admin ID $admin_id has been deactivated.";
    } elseif ($_POST['action'] === 'activate') {
        $stmt = $pdo->prepare("UPDATE admin_accounts SET status = 'active' WHERE admin_id = :admin_id");
        $stmt->execute(['admin_id' => $admin_id]);
        $_SESSION['success_message'] = "Admin ID $admin_id has been reactivated.";
    }

    header("Location: employee.php");
    exit;
}

// Fetch admin accounts
$stmt = $pdo->query("SELECT admin_id, first_name, last_name, contact_number, user_type, status FROM admin_accounts");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include('../../Components/header.php'); ?>
<h2 class="text-6xl font-bold mb-4 text-black text-center">Manage Employee Accounts</h2>

<!-- Account Management Section -->
<div class="account-section text-black">
    <!-- Create Account Form -->
    <form action="employee.php" method="POST">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
            <label for="admin_id" class="form-label">Admin ID:</label>
            <input type="number" id="admin_id" name="admin_id" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>

            <label for="first_name" class="form-label">First Name:</label>
            <input type="text" id="first_name" name="first_name" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>

            <label for="last_name" class="form-label">Last Name:</label>
            <input type="text" id="last_name" name="last_name" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>

            <form id="contactForm">
    <label class="form-label" for="contactInformation">Contact Information:</label>
    <input 
        type="text" 
        id="contactInformation" 
        name="contact_number" 
        required 
        placeholder="+63xxxxxxxxxx"
        class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
    <span id="error-message" style="color: red; display: none;">Please enter a valid phone number.</span>
</form>

            <label for="password" class="form-label">Password:</label>
            <input type="password" id="password" name="password" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>

            <label for="role" class="form-label">Role:</label>
            <select id="role" name="role" class="select select-bordered w-full max-full bg-[#CBDCEB] text-black" required>
                <option value="">Select Role</option>
                <option value="Administrator">Administrator</option>
                <option value="Employee">Employee</option>
            </select>

            <button type="submit" class="form-submit-button">Create Account</button>
        </div>
    </form>

    <!-- Dropdown for Deactivate/Reactivate -->
    <div class="admin-dropdown">
        <form action="employee.php" method="POST">
            <label for="admin_select" class="form-label">Select Admin:</label>
            <select id="admin_select" name="admin_id" class="select select-bordered w-full max-full bg-[#CBDCEB] text-black" required>
                <option value="">Select Admin</option>
                <?php foreach ($admins as $admin): ?>
                    <option value="<?php echo htmlspecialchars($admin['admin_id']); ?>">
                        <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?> (<?php echo htmlspecialchars($admin['status']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="action_select" class="form-label">Action:</label>
            <select id="action_select" name="action" class="select select-bordered w-full max-full bg-[#CBDCEB] text-black" required>
                <option value="deactivate">Deactivate</option>
                <option value="activate">Reactivate</option>
            </select>

            <button type="submit" class="form-submit-button">Submit</button>
        </form>
    </div>
</div>

<?php include('../../Components/footer.php'); ?>
<style>
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 150px;
    background-color: #F3F3E0;
    height: 1040px;
    padding: 100px;
}
/* General Styles for Account Management Section */
.account-section {
    background-color: #f7fafc;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin: 20px auto;
    max-width: 900px;
    width: 625px;
}

/* Form Group */
.form-group {
    margin-bottom: 10px;
}

/* Form Labels */
.form-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #333;
    display: block;
    margin-bottom: 5px;
}

/* Form Inputs */
.form-input, .form-select {
    width: 595px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    color: #333;
    box-sizing: border-box;
    margin-bottom: 10px;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 5px rgba(51, 130, 206, 0.5);
}

/* Submit Button (Create Account) */
.form-submit-button {
    width: 100%;
    background-color: #4299e1;
    color: white;
    font-weight: bold;
    padding: 10px;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    transition: background-color 0.3s;
}

.form-submit-button:hover {
    background-color: #3182ce;
}

/* Delete Button */
.form-delete-button {
    width: 100%;
    background-color: #f56565;
    color: white;
    font-weight: bold;
    padding: 10px;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    transition: background-color 0.3s;
}

.form-delete-button:hover {
    background-color: #e53e3e;
}

/* Styling the Select Dropdown */
.form-select {
    padding: 8px;
    font-size: 1rem;
    color: #333;
    border-radius: 4px;
    border: 1px solid #ccc;
    width: 595px;
}

.form-select:focus {
    outline: none;
    border-color: #3182ce;
    box-shadow: 0 0 5px rgba(51, 130, 206, 0.5);
}
</style>
<script>
 // Get the input field and the error message element
 const contactInput = document.getElementById('contactInformation');
    const errorMessage = document.getElementById('error-message');

    // Event listener for input validation
    contactInput.addEventListener('input', function() {
        // Regular expression for Philippine phone numbers (+63 or 09 followed by 9 digits)
        const phonePattern = /^(09|\+63)\d{9}$/;

        // Validate input value
        if (!phonePattern.test(contactInput.value)) {
            // Show error message if the phone number is invalid
            errorMessage.style.display = 'inline';
        } else {
            // Hide error message if the phone number is valid
            errorMessage.style.display = 'none';
        }
    });

    // Optional: Check the phone number when the form is submitted
    document.getElementById('contactForm').addEventListener('submit', function(event) {
        // Check if the input value matches the pattern before form submission
        if (!phonePattern.test(contactInput.value)) {
            event.preventDefault();  // Prevent form submission
            errorMessage.style.display = 'inline';  // Show error message
        }
    });
</script>