<?php
session_start();

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "etourmodb";

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    exit("Error: Database connection failed.");
}
// Fetch the backdrop image from the database
$sql = "SELECT backdrop_image FROM backdrops ORDER BY created_at DESC LIMIT 1";  // Get the most recent image
$result = $conn->query($sql);
$backgroundImage = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $backgroundImage = $row['backdrop_image'];
} else {
    // If no backdrop found, fallback to a default background image
    // $backgroundImage = file_get_contents('default-background.jpg'); // Provide a default image
}
// Encode the image in base64 for embedding in CSS
$backgroundImageBase64 = base64_encode($backgroundImage);

// Determine the action based on the form submission
$action = $_POST['action'] ?? '';
$error_message = ""; // Variable to store error messages

// Handle different actions (login, logout, sign-up)
switch ($action) {
    case 'login':
        $error_message = login($conn);
        break;
        
    case 'signup':
        $error_message = signup($conn);
        break;

    case 'forgot_password':
        $error_message = forgotPassword($conn);
        break;

    case 'reset_password':
        $error_message = resetPassword($conn);
        break;

    default:
        break;
}

// Close the database connection
$conn->close();

// Forgot Password function
function forgotPassword($conn) {
    $email = $_POST['email'] ?? '';
    $firstName = $_POST['firstname'] ?? '';
    $lastName = $_POST['lastname'] ?? '';
    $error_message = "";

    if (!empty($email) && !empty($firstName) && !empty($lastName)) {
        $email = $conn->real_escape_string($email);
        $firstName = $conn->real_escape_string($firstName);
        $lastName = $conn->real_escape_string($lastName);

        $sql = "SELECT * FROM user_profiles WHERE email = ? AND firstname = ? AND lastname = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $firstName, $lastName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $_SESSION['reset_user'] = $result->fetch_assoc(); // Store user data in session
        } else {
            $error_message = "No matching user found. Please check your details.";
        }
        $stmt->close();
    } else {
        $error_message = "Please provide your email, first name, and last name.";
    }

    return $error_message;
}

// Reset Password function
function resetPassword($conn) {
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $error_message = "";

    if (!empty($newPassword) && !empty($confirmPassword) && $newPassword === $confirmPassword) {
        if (isset($_SESSION['reset_user'])) {
            $user = $_SESSION['reset_user'];
            $userId = $user['user_id'];

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $sql = "UPDATE user_profiles SET password_hash = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashedPassword, $userId);

            if ($stmt->execute()) {
                $error_message = "Password reset successfully. You can now log in with your new password.";
                unset($_SESSION['reset_user']); // Clear session data
            } else {
                $error_message = "Failed to reset password. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "Session expired. Please try again.";
        }
    } else {
        $error_message = "Passwords do not match or are empty.";
    }

    return $error_message;
}

// Login function
function login($conn)
{
    // Retrieve POST data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $error_message = "";  // Variable to store error messages

    // Check if both email and password are provided
    if (!empty($email) && !empty($password)) {
        $email = $conn->real_escape_string($email);
        $password = $conn->real_escape_string($password);

        // Query to fetch the user profile based on email
        $sql = "SELECT user_id, lastname, firstname, email, password_hash, contact_information, created_at FROM user_profiles WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password against the hashed password in the database
            if (password_verify($password, $user['password_hash'])) {
                // Remove password field from the response
                unset($user['password_hash']); // Remove the hashed password from the response

                // Store user data in the session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];

                // Redirect to a success page or the dashboard
                header('Location: package.php');
                exit();
            } else {
                $error_message = "Invalid credentials.";
            }
        } else {
            $error_message = "User not found.";
        }
        $stmt->close();
    } else {
        $error_message = "Please provide both email and password.";
    }

    return $error_message;
}

// Sign-up function
function signup($conn)
{
    // Retrieve POST data for sign-up
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';  // Password input
    $confirmPassword = $_POST['confirmPassword'] ?? ''; // Confirm Password
    $firstName = $_POST['firstname'] ?? '';
    $lastName = $_POST['lastname'] ?? '';
    $contactInformation = $_POST['contactInformation'] ?? '';

    $error_message = ""; // Variable to store error messages

    // Sign-up functionality
    if (!empty($email) && !empty($password) && !empty($firstName) && !empty($lastName) && !empty($contactInformation) && !empty($confirmPassword)) {
        
        // Check if password and confirm password match
        if ($password !== $confirmPassword) {
            $error_message = "Passwords do not match.";
            return $error_message;
        }

        // Sanitize inputs to prevent SQL injection
        $email = $conn->real_escape_string($email);
        $password = $conn->real_escape_string($password);
        $firstName = $conn->real_escape_string($firstName);
        $lastName = $conn->real_escape_string($lastName);
        $contactInformation = $conn->real_escape_string($contactInformation);

        // Hash the password before storing it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if the email already exists in the database
        $sql = "SELECT * FROM user_profiles WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $error_message = "Email already exists.";
        } else {
            // Insert the new user into the database
            $sql = "INSERT INTO user_profiles (email, password_hash, firstname, lastname, contact_information) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $hashedPassword, $firstName, $lastName, $contactInformation);

            if (!$stmt->execute()) {
                $error_message = "Error creating user: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $error_message = "All fields are required.";
    }

    return $error_message;
}
?>
<style>
/* General body styling to ensure the page takes full height */
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;            
    background-image: url('data:image/jpeg;base64,<?php echo $backgroundImageBase64; ?>'); /* Background image from DB */
    background-size: cover; /* Ensure the background image covers the entire screen */
    background-position: center; /* Center the background image */
    overflow: hidden; /* Prevent page scrolling */
}

/* Form container that holds all the forms */
.form-container {
    width: 500px;
    max-width: 800px;  /* Increased max-width for wider forms */
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    max-height: 90vh; /* Ensure the form container does not exceed 90% of viewport height */
    overflow: hidden;  /* Prevent any internal scrolling */
    margin-top: 0; /* Remove margin to ensure it stays centered */
    box-sizing: border-box;
    margin-top: 100px;
    height: 450px;
    overflow: auto; /* Prevent page scrolling */
}

/* Header style for each form */
.form-container h2 {
    text-align: center;
    margin-bottom: 20px;
}

/* General styles for form elements */
.form-container div {
    margin-bottom: 15px;
    width: 100%;  /* Ensure inputs stretch to form width */
}

/* Label styles */
.form-container label {
    display: block;
    margin-bottom: 5px;
}

/* Input fields styling */
.form-container input {
    width: 100%;
    padding: 10px;
    margin: 5px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* Submit button styling */
.form-container button {
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.form-container button:hover {
    background-color: #45a049;
}

/* Toggle button (Sign-up/Login) styling */
.toggle-btn {
    text-align: center;
    margin-top: 20px;
}

/* Error message styling */
.error-message {
    color: red;
    font-size: 14px;
    margin-top: 10px;
    text-align: center;
}

/* Style to hide elements */
.hidden {
    display: none;
}

/* Responsive Styles */
@media screen and (max-width: 600px) {
    .form-container {
        padding: 15px;
        width: 90%;  /* Ensure the form takes up 90% of the screen width on small devices */
    }
    .form-container h2 {
        font-size: 1.5em;
    }
}
</style>

<?php include('header.php'); ?>

<div class="form-container">
    <!-- Login Form -->
    <div id="loginForm">
        <h2>Login</h2>
        <form action="loginsignup.php" method="POST">
            <input type="hidden" name="action" value="login">
            
            <div>
                <label for="email">Email:</label>
                <input type="email" id="loginEmail" name="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="loginPassword" name="password" required>
            </div>
            <div>
                <button type="submit">Login</button>
            </div>
        </form>
        <div class="toggle-btn">
            <p>Don't have an account? <a href="javascript:void(0)" onclick="toggleForms()">Sign up here</a></p>
        </div>
        <div class="toggle-btn">
            <p><a href="javascript:void(0)" onclick="toggleForgotPasswordForm()">Forgot Password?</a></p>
        </div>
        <div id="errorMessage" class="error-message"></div>
    </div>

    <!-- Sign-Up Form -->
    <div id="signupForm" class="hidden">
        <h2>Sign Up</h2>
        <form action="loginsignup.php" method="POST">
            <input type="hidden" name="action" value="signup">

            <div>
                <label for="email">Email:</label>
                <input type="email" id="signupEmail" name="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="signupPassword" name="password" required>
            </div>
            <div>
                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
            </div>
            <div>
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>
            <div>
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>
            <div>
                <label for="contactInformation">Contact Information:</label>
                <input type="text" id="contactInformation" name="contactInformation" required>
            </div>
            <div>
                <button type="submit">Sign Up</button>
            </div>
        </form>
        <div class="toggle-btn">
            <p>Already have an account? <a href="javascript:void(0)" onclick="toggleForms()">Login here</a></p>
        </div>
        <div id="errorMessage" class="error-message"></div>
    </div>
</div>

<script>
    // Function to toggle between login and signup forms
    function toggleForms() {
        var loginForm = document.getElementById('loginForm');
        var signupForm = document.getElementById('signupForm');
        
        loginForm.classList.toggle('hidden');
        signupForm.classList.toggle('hidden');
    }

    // Function to toggle between forgot password and reset password forms
    function toggleForgotPasswordForm() {
        var forgotPasswordForm = document.getElementById('forgotPasswordForm');
        
        forgotPasswordForm.classList.toggle('hidden');
    }

    // Display error message if available
    <?php if (!empty($error_message)): ?>
        document.getElementById('errorMessage').textContent = "<?php echo $error_message; ?>";
        setTimeout(function() {
            document.getElementById('errorMessage').textContent = '';
        }, 2000);  // Hide the error message after 2 seconds
    <?php endif; ?>
</script>
<?php include('footer.php'); ?>