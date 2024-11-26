<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "etourmodb"; // Replace with your actual database name

try {
    // Set up PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Fetch all add-ons and their associated package names
$sql = "SELECT a.addon_id, a.addon_name, a.price, a.created_at, p.package_name
        FROM add_ons a
        JOIN packages p ON a.package_id = p.package_id";
$stmt = $conn->prepare($sql);
$stmt->execute();
$addons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all packages from the packages table to display in the dropdown
$package_sql = "SELECT package_id, package_name FROM packages";
$stmt = $conn->prepare($package_sql);
$stmt->execute();
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission (Add New Add-On)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addon_name'])) {
    // Get the form data
    $package_id = $_POST['package_id'];
    $addon_name = $_POST['addon_name'];
    $price = $_POST['price'];

    // Validate the form data
    if (empty($package_id) || empty($addon_name) || empty($price)) {
        $message = "All fields are required!";
        $message_type = "error";
    } else {
        // Insert the new add-on into the database
        $insert_sql = "INSERT INTO add_ons (package_id, addon_name, price) 
                       VALUES (:package_id, :addon_name, :price)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bindParam(':package_id', $package_id);
        $stmt->bindParam(':addon_name', $addon_name);
        $stmt->bindParam(':price', $price);

        if ($stmt->execute()) {
            // Redirect back to the same page with a success message
            header("Location: /eTourMo Maintenance/Postings/Add On/addon.php?message=success");
            exit;
        } else {
            $message = "Error: Could not add add-on";
            $message_type = "error";
        }
    }
}

// Handle the deletion of an add-on
if (isset($_GET['delete_addon'])) {
    $addon_id = $_GET['delete_addon'];

    // Prepare the DELETE SQL query
    $delete_sql = "DELETE FROM add_ons WHERE addon_id = :addon_id";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bindParam(':addon_id', $addon_id);

    if ($stmt->execute()) {
        echo "<p class='success'>Add-On deleted successfully!</p>";
    } else {
        echo "<p class='error'>Error: Could not delete add-on</p>";
    }
}
?>

<?php include('../../Components/header.php'); ?>

    <!-- Message Display -->
    <?php if (isset($_GET['message'])): ?>
        <?php if ($_GET['message'] == 'success'): ?>
            <div class="message success">New add-on added successfully!</div>
        <?php else: ?>
            <div class="message error">There was an error. Please try again.</div>
        <?php endif; ?>
    <?php endif; ?>

    <h2>Existing Add-Ons</h2>

    <!-- Table to Display Add-Ons -->
    <table class="table">
        <thead>
            <tr>
                <th>Addon ID</th>
                <th>Addon Name</th>
                <th>Package Name</th>
                <th>Price</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Display the fetched add-ons
            foreach ($addons as $row) {
                echo "<tr>
                        <td>" . htmlspecialchars($row["addon_id"]) . "</td>
                        <td>" . htmlspecialchars($row["addon_name"]) . "</td>
                        <td>" . htmlspecialchars($row["package_name"]) . "</td>
                        <td>" . htmlspecialchars($row["price"]) . "</td>
                        <td>" . htmlspecialchars($row["created_at"]) . "</td>
                        <td>
                            <a href='?delete_addon=" . $row["addon_id"] . "' onclick='return confirm(\"Are you sure you want to delete this add-on?\")'>Delete</a>
                        </td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>

    <h2>Add New Add-On</h2>

    <!-- Form to Add a New Add-On -->
    <form action="addon.php" method="POST">
        <label for="package_id">Select Package:</label>
        <select name="package_id" required>
            <option value="">-- Select Package --</option>
            <?php
            // Populate the dropdown with package options
            foreach ($packages as $package) {
                echo "<option value='" . htmlspecialchars($package["package_id"]) . "'>" . htmlspecialchars($package["package_name"]) . "</option>";
            }
            ?>
        </select><br><br>

        <label for="addon_name">Addon Name:</label>
        <input type="text" name="addon_name" required><br><br>

        <label for="price">Price:</label>
        <input type="number" step="0.01" name="price" required><br><br>

        <input type="submit" value="New Add-On">
    </form>


<?php include('../../Components/footer.php'); ?>

<!-- Simple Styles for the Page -->
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f9f9f9;
        width: 80%;
        margin: 30px auto;
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    h2 {
        color: #333;
        margin-bottom: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .table th, .table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    .table th {
        background-color: #f4f4f4;
    }

    .table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .table tr:hover {
        background-color: #f1f1f1;
    }

    .table a {
        color: #ff6f61;
        text-decoration: none;
    }

    .table a:hover {
        text-decoration: underline;
    }

    .message {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
    }

    form {
        margin-top: 30px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }

    input[type="text"], input[type="number"], select {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    input[type="submit"] {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
    }

    input[type="submit"]:hover {
        background-color: #0056b3;
    }
</style>
