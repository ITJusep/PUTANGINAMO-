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
    die("Could not connect to the database: " . $e->getMessage());
}

// Initialize variables
$privacy_policy = '';
$terms_conditions = '';
$selected_policy = null;

// Fetch the current policies (only one row should exist)
$stmt = $pdo->query("SELECT * FROM policies LIMIT 1");
$selected_policy = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Handle Privacy Policy Update
    if (isset($_POST['update_privacy_policy']) && !empty($_POST['privacy_policy']) && !empty($selected_policy)) {
        $privacy_policy = $_POST['privacy_policy'];

        if (!empty($privacy_policy)) {
            $stmt = $pdo->prepare("UPDATE policies SET privacy_policy = ?, updated_at = CURRENT_TIMESTAMP WHERE policy_id = ?");
            $stmt->execute([$privacy_policy, $selected_policy['policy_id']]);
            echo "<script>window.location.replace(window.location.href);</script>"; // Refresh without scroll reset
            exit();
        } else {
            echo "Privacy Policy cannot be empty!";
        }
    }

    // Handle Terms and Conditions Update
    if (isset($_POST['update_terms_conditions']) && !empty($_POST['terms_conditions']) && !empty($selected_policy)) {
        $terms_conditions = $_POST['terms_conditions'];

        if (!empty($terms_conditions)) {
            $stmt = $pdo->prepare("UPDATE policies SET terms_condition = ?, updated_at = CURRENT_TIMESTAMP WHERE policy_id = ?");
            $stmt->execute([$terms_conditions, $selected_policy['policy_id']]);
            echo "<script>window.location.replace(window.location.href);</script>"; // Refresh without scroll reset
            exit();
        } else {
            echo "Terms and Conditions cannot be empty!";
        }
    }

    // Handle Privacy Policy Upload (Insert new policy if none exists)
    if (isset($_POST['upload_privacy_policy']) && empty($selected_policy)) {
        $privacy_policy = $_POST['privacy_policy'];

        if (!empty($privacy_policy)) {
            $stmt = $pdo->prepare("INSERT INTO policies (privacy_policy, terms_condition) VALUES (?, ?)");
            $stmt->execute([$privacy_policy, '']);
            echo "<script>window.location.replace(window.location.href);</script>"; // Refresh without scroll reset
            exit();
        } else {
            echo "Privacy Policy cannot be empty!";
        }
    }

    // Handle Terms and Conditions Upload (Insert new policy if none exists)
    if (isset($_POST['upload_terms_conditions']) && empty($selected_policy)) {
        $terms_conditions = $_POST['terms_conditions'];

        if (!empty($terms_conditions)) {
            $stmt = $pdo->prepare("INSERT INTO policies (privacy_policy, terms_condition) VALUES (?, ?)");
            $stmt->execute(['', $terms_conditions]);
            echo "<script>window.location.replace(window.location.href);</script>"; // Refresh without scroll reset
            exit();
        } else {
            echo "Terms and Conditions cannot be empty!";
        }
    }

    // Handle Privacy Policy Deletion
    if (isset($_POST['delete_privacy_policy']) && !empty($selected_policy['policy_id'])) {
        $stmt = $pdo->prepare("UPDATE policies SET privacy_policy = '', updated_at = CURRENT_TIMESTAMP WHERE policy_id = ?");
        $stmt->execute([$selected_policy['policy_id']]);
        echo "<script>window.location.replace(window.location.href);</script>"; // Refresh without scroll reset
        exit();
    }

    // Handle Terms and Conditions Deletion
    if (isset($_POST['delete_terms_conditions']) && !empty($selected_policy['policy_id'])) {
        $stmt = $pdo->prepare("UPDATE policies SET terms_condition = '', updated_at = CURRENT_TIMESTAMP WHERE policy_id = ?");
        $stmt->execute([$selected_policy['policy_id']]);
        echo "<script>window.location.replace(window.location.href);</script>"; // Refresh without scroll reset
        exit();
    }
}
?>

<?php include('../../Components/header.php'); ?>
<div class="content">
<!-- Privacy Policy Section -->
<div id="privacyPolicyForm" class="agreement-section">
    <h2>Privacy Policy</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="privacy_policy" class="form-label">Privacy Policy:</label>
            <textarea id="privacy_policy" name="privacy_policy" class="form-textarea" required><?php echo isset($selected_policy['privacy_policy']) ? htmlspecialchars($selected_policy['privacy_policy']) : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <?php if ($selected_policy): ?>
                <input type="submit" id="update_privacy_policy_btn" name="update_privacy_policy" value="Update Privacy Policy" class="form-button">
                <input type="submit" id="delete_privacy_policy_btn" name="delete_privacy_policy" value="Delete Privacy Policy" class="form-button">
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Terms and Conditions Section -->
<div id="termsConditionsForm" class="agreement-section">
    <h2>Terms and Conditions</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="terms_conditions" class="form-label">Terms and Conditions:</label>
            <textarea id="terms_conditions" name="terms_conditions" class="form-textarea" required><?php echo isset($selected_policy['terms_condition']) ? htmlspecialchars($selected_policy['terms_condition']) : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <?php if ($selected_policy): ?>
                <input type="submit" id="update_terms_conditions_btn" name="update_terms_conditions" value="Update Terms and Conditions" class="form-button">
                <input type="submit" id="delete_terms_conditions_btn" name="delete_terms_conditions" value="Delete Terms and Conditions" class="form-button">
            <?php endif; ?>
        </div>
    </form>
</div>
</div>
<?php include('../../Components/footer.php'); ?>

<!-- JavaScript to Preserve Scroll Position -->
<script>
    // Save scroll position before page unload
    window.onbeforeunload = function () {
        sessionStorage.setItem('scrollPos', window.scrollY);
    };

    // Restore scroll position after page load
    window.onload = function () {
        if (sessionStorage.getItem('scrollPos')) {
            window.scrollTo(0, sessionStorage.getItem('scrollPos'));
        }
    };
</script>

<!-- Styles -->
<style>
    .content{
        margin-left: 250px;
    }
/* General Agreement Section Styles */
.agreement-section {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: flex-start;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #e5e5e5;
    background-color: #f7fafc;
    border-radius: 8px;
    max-width: 920px;
}

/* Form Group */
.form-group {
    width: 100%;
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
}

/* Label Style */
.form-label {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

/* Form Inputs */
.form-select, .form-input, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    background-color: white;
    box-sizing: border-box;
}

/* Textarea Style */
.form-textarea {
    height: 120px;
    resize: none;
}

/* Buttons Styling */
.form-button {
    background-color: #4299e1;
    color: white;
    font-weight: bold;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    margin: 0.5rem 0;
    width: 100%;
    cursor: pointer;
    border: none;
    text-align: center;
    transition: background-color 0.3s ease;
}

/* Button Hover Effects */
.form-button:hover {
    background-color: #2b6cb0;
}

/* Focus Styles for Inputs */
.form-select:focus, .form-input:focus, .form-textarea:focus {
    outline: none;
    border-color: #3182ce;
}

/* Aligning Form Action Buttons */
.form-actions {
    display: flex;
    flex-direction: row;
    justify-content: flex-start;
    width: 100%;
}

.form-actions input {
    width: auto;
    margin-right: 1rem;
}

/* Max width for the Agreement section */
.agreement-section {
    max-width: 920px;
    margin: 20px auto;
    display: flex;
    flex-direction: column;
}

form {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
</style>
