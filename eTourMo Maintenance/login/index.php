<?php
// Set timezone
date_default_timezone_set('Asia/Manila');

// Database connection details
$host = 'localhost';
$dbname = 'etourmodb';
$db_username = 'root';
$db_password = '';

try {
    // Create a PDO connection to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Start the session to store log_id
session_start();

// Initialize an error message variable
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if admin_id and password are provided
    if (empty($_POST['admin_id']) || empty($_POST['admin_password'])) {
        $error_message = "Admin ID and password must be provided.";
    } else {
        // Sanitize and retrieve POST data
        $admin_id = trim($_POST['admin_id']);
        $password = trim($_POST['admin_password']);

        // Query the database for the admin account with the given admin_id
        $stmt = $pdo->prepare("SELECT * FROM admin_accounts WHERE admin_id = :admin_id");
        $stmt->execute(['admin_id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the admin account exists
        if ($admin) {
            // Check if the account status is 'deactivated'
            if ($admin['status'] === 'deactivated') {
                $error_message = "Your account is deactivated. Please contact the administrator.";
            } elseif (password_verify($password, $admin['admin_password'])) {
                // Insert the login record into the activity_logs table
                $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, login_time) VALUES (:admin_id, NOW())");
                $stmt->execute(['admin_id' => $admin_id]);

                // Store log ID for logout reference
                $_SESSION['log_id'] = $pdo->lastInsertId();

                // Store the admin_id and user_type in the session for later use
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['user_type'] = $admin['user_type'];  // Store user_type session variable

                // Redirect to the landing page or dashboard after successful login
                header("Location: /eTourMo Maintenance/Dashboard/reports.php");
                exit;
            } else {
                // Invalid password
                $error_message = "Invalid password.";
            }
        } else {
            // Invalid admin ID
            $error_message = "Invalid admin ID.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        /* ... Your existing styles ... */
        .error-message {
            color: #FF5733;
            font-size: 14px;
            text-align: center;
            margin-top: -10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <h2>Admin Login</h2>
        <form action="index.php" method="POST">
            <div>
                <label for="admin_id">Enter Admin ID:</label>
                <input type="text" id="admin_id" name="admin_id" required>
            </div>
            <div>
                <label for="admin_password">Password:</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <button type="submit">Login</button>
        </form>

        <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <p id="logout-message">You have successfully logged out.</p>
        <?php endif; ?>
    </div>

    <script>
        // Hide the logout success message after 2 seconds
        window.onload = function() {
            const logoutMessage = document.getElementById("logout-message");
            if (logoutMessage) {
                setTimeout(() => {
                    logoutMessage.style.display = 'none';
                }, 2000);  // Hide the message after 2 seconds
            }
        };
    </script>
</body>
</html>

<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .overlay {
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 320px;
        }

        h2 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        label {
            font-size: 14px;
            color: #333;
            display: block;
            margin-bottom: 0.5rem;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 5px rgba(74, 144, 226, 0.6);
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #4A90E2;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #357ABD;
        }

        .message {
            margin-top: 1rem;
            text-align: center;
            font-size: 14px;
        }

        .message.success {
            color: #4CAF50;
        }

        .message.error {
            color: #FF5733;
        }
    </style>