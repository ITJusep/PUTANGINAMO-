<?php
// Database connection
$host = 'localhost'; 
$db = 'etourmodb'; 
$user = 'root'; 
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Retrieve rental information and images if rental_id is provided
$rental_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rental_data = null;
$images = [];

if ($rental_id) {
    // Fetch rental data from the database
    $sql = "SELECT * FROM rentals WHERE rental_id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rental_id]);
    $rental_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch images for the rental
    $sql_images = "SELECT * FROM rental_images WHERE rental_id = ?";
    $stmt_images = $pdo->prepare($sql_images);
    $stmt_images->execute([$rental_id]);
    $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission for update or delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the posted values
    $rental_type = isset($_POST['rental_type']) ? $_POST['rental_type'] : $rental_data['rental_type'];
    $rental_pax = isset($_POST['rental_pax']) ? $_POST['rental_pax'] : $rental_data['rental_pax'];
    $rental_price = isset($_POST['rental_price']) ? $_POST['rental_price'] : $rental_data['rental_price'];
    $rental_description = isset($_POST['rental_description']) ? $_POST['rental_description'] : $rental_data['rental_description'];
    $rental_not_included = isset($_POST['rental_not_included']) ? $_POST['rental_not_included'] : $rental_data['rental_not_included'];
    $rental_duration = isset($_POST['rental_duration']) ? $_POST['rental_duration'] : $rental_data['rental_duration'];
    $rental_cancellation_policy = isset($_POST['rental_cancellation_policy']) ? $_POST['rental_cancellation_policy'] : $rental_data['rental_cancellation_policy'];

    // Handle Update Rental action
    if (isset($_POST['update']) && !empty($rental_id)) {
        // Prepare the SQL statement for the fields being updated
        $stmt = $pdo->prepare("UPDATE rentals SET 
            rental_type = ?, 
            rental_pax = ?, 
            rental_price = ?, 
            rental_description = ?, 
            rental_not_included = ?,  
            rental_duration = ?, 
            rental_cancellation_policy = ? 
            WHERE rental_id = ?");

        // Execute and check for errors
        $success = $stmt->execute([
            $rental_type, 
            $rental_pax, 
            $rental_price, 
            $rental_description, 
            $rental_not_included, 
            $rental_duration, 
            $rental_cancellation_policy, 
            $rental_id
        ]);

        // Handle image update
        if (isset($_FILES['rental_images']) && !empty($_FILES['rental_images']['name'][0])) {
            // Delete existing images if new images are uploaded
            $stmt_delete_images = $pdo->prepare("DELETE FROM rental_images WHERE rental_id = ?");
            $stmt_delete_images->execute([$rental_id]);

            // Upload new images
            $files = $_FILES['rental_images'];
            foreach ($files['tmp_name'] as $index => $tmp_name) {
                // Check if file is an image
                if (exif_imagetype($tmp_name)) {
                    $image_data = file_get_contents($tmp_name);
                    $image_type = mime_content_type($tmp_name);

                    // Insert new image into the rental_images table
                    $stmt_image = $pdo->prepare("INSERT INTO rental_images (rental_id, image_data, image_type) VALUES (?, ?, ?)");
                    $stmt_image->execute([$rental_id, $image_data, $image_type]);
                }
            }
        }

        // Handle image deletions
        if (isset($_POST['delete_images'])) {
            $delete_image_ids = $_POST['delete_images'];
            foreach ($delete_image_ids as $image_id) {
                // Delete selected images from the rental_images table
                $stmt_delete_image = $pdo->prepare("DELETE FROM rental_images WHERE image_id = ?");
                $stmt_delete_image->execute([$image_id]);
            }
        }

        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=$rental_id");
            exit();
        } else {
            echo "<div class='error'>Update failed. Please try again.</div>";
        }
    }

    // Handle Delete Rental action
    if (isset($_POST['delete']) && !empty($rental_id)) {
        // First, remove the references to this rental in the bookings table
        $stmt_remove_bookings = $pdo->prepare("DELETE FROM rentals WHERE rental_id = ?");
        $stmt_remove_bookings->execute([$rental_id]);

        // Delete images related to the rental
        $stmt_delete_images = $pdo->prepare("DELETE FROM rental_images WHERE rental_id = ?");
        $stmt_delete_images->execute([$rental_id]);

        // Then, delete the rental itself
        $stmt_delete_rental = $pdo->prepare("DELETE FROM rentals WHERE rental_id = ?");
        $stmt_delete_rental->execute([$rental_id]);

        // Redirect to index.php after deleting the rental
        header("Location: details.php");
        exit();
    }
}
?>

<?php include('../../Components/header.php'); ?>
<div class="content">
<!-- Back Button -->
<div class="back-button-container">
    <button type="button" class="back-button" onclick="window.location.href='rental.php';">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 5l-7 7 7 7"></path>
        </svg>
    </button>
</div>

<!-- Form for uploading/updating/deleting rental -->
<form class="form-container" method="POST" enctype="multipart/form-data">

    <div class="form-group">
        <label for="rental_type">Rental Type:</label>
        <input type="text" name="rental_type" value="<?php echo htmlspecialchars($rental_data['rental_type'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="rental_pax">Rental Pax:</label>
        <input type="text" name="rental_pax" value="<?php echo htmlspecialchars($rental_data['rental_pax'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="rental_price">Rental Price:</label>
        <input type="text" name="rental_price" value="<?php echo htmlspecialchars($rental_data['rental_price'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="rental_description">Rental Description:</label>
        <textarea name="rental_description" required><?php echo htmlspecialchars($rental_data['rental_description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="rental_not_included">Rental Not Included:</label>
        <textarea name="rental_not_included"><?php echo htmlspecialchars($rental_data['rental_not_included'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="rental_duration">Rental Duration:</label>
        <input type="text" name="rental_duration" value="<?php echo htmlspecialchars($rental_data['rental_duration'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="rental_cancellation_policy">Rental Cancellation Policy:</label>
        <textarea name="rental_cancellation_policy"><?php echo htmlspecialchars($rental_data['rental_cancellation_policy'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="rental_images">Upload Images:</label>
        <input type="file" name="rental_images[]" multiple>
    </div>

    <!-- Existing Images Section -->
    <?php if ($images): ?>
    <div class="form-group">
        <label for="delete_images">Delete Images:</label>
        <ul>
            <?php foreach ($images as $image): ?>
            <li>
                <img src="data:<?php echo $image['image_type']; ?>;base64,<?php echo base64_encode($image['image_data']); ?>" width="100" height="100" />
                <input type="checkbox" name="delete_images[]" value="<?php echo $image['image_id']; ?>"> Delete
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <button type="submit" name="update">Update</button>
        <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this rental?');">Delete</button>
    </div>
</form>

<?php include('../../Components/footer.php'); ?>


<!-- Styles applied to the form elements -->
<style>
.content{
font-family: Arial, sans-serif;
margin: 0;
padding: 0;
margin-left: 250px; 
}
/* Form container styles */
.form-container {
    max-width: 920px; /* Maximum width of the form */
    margin: 40px auto; /* Center the form horizontally */
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    display: block; /* Ensure it's block-level to prevent any inline behavior */
}

/* Form group styles */
.form-group {
    display: flex;
    flex-direction: column; /* Stack label and input/textarea vertically */
    margin-bottom: 20px; /* Space between form groups */
}

.form-group label {
    font-weight: bold;
    margin-bottom: 8px; /* Space between label and input */
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea,
.form-group input[type="file"],
.form-group select {
    width: 100%; /* Ensure full width of the form */
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box; /* Ensure padding does not overflow */
}

.form-group textarea {
    height: 100px; /* Set a default height for textareas */
    resize: vertical; /* Allow vertical resizing of the textarea */
}

.form-group input[type="file"] {
    padding: 5px; /* Adjust padding for file input */
}

/* Image gallery styling */
.image-gallery {
    display: flex; /* Use flexbox to display images horizontally */
    flex-wrap: wrap; /* Allow images to wrap to the next line if they don't fit */
    gap: 10px; /* Add space between images */
    margin-bottom: 20px;
}

.image-preview {
    display: flex;
    flex-direction: column; /* Stack the image and delete checkbox vertically */
    align-items: center; /* Center the image and checkbox */
    margin-bottom: 10px;
}

.image-preview img {
    width: 100px; /* Fixed width for all images */
    height: 100px; /* Fixed height for all images */
    object-fit: cover; /* Ensures images fill the space without distortion */
    border-radius: 5px; /* Rounded corners for images */
    margin-bottom: 5px; /* Space between the image and the checkbox */
}

.image-preview input {
    margin-top: 5px; /* Adjust margin between checkbox and image */
}

/* General button styles for both regular and back buttons */
.form-group button, .back-button-container .back-button,
.button-group input[type="submit"], .button-group button {
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
.form-group button, .back-button-container .back-button,
.button-group input[type="submit"], .button-group button {
    background: linear-gradient(145deg, #007bff, #0056b3); /* Subtle gradient background */
    color: white;                         /* White text color */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Soft shadow for a lifted effect */
}

/* Hover effect for all buttons */
.form-group button:hover, .back-button-container .back-button:hover,
.button-group input[type="submit"]:hover, .button-group button:hover {
    background: linear-gradient(145deg, #0056b3, #00428a); /* Darker gradient on hover */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);   /* More pronounced shadow for hover effect */
}

/* Active effect for all buttons (when clicked) */
.form-group button:active, .back-button-container .back-button:active,
.button-group input[type="submit"]:active, .button-group button:active {
    background: linear-gradient(145deg, #003c7a, #00284f); /* Even darker gradient when clicked */
    box-shadow: none; /* Remove shadow on active state */
    transform: translateY(2px); /* Slight move down for a "pressed" effect */
}

/* Focus effect for all buttons (to ensure accessibility) */
.form-group button:focus, .back-button-container .back-button:focus,
.button-group input[type="submit"]:focus, .button-group button:focus {
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

/* Button group container styles */
.button-group {
    text-align: center;                 /* Center align buttons */
    margin-top: 20px;                   /* Space at the top of the button group */
}

/* Additional form controls for better structure */
form {
    display: block; /* Ensure form itself is a block element */
}

form .form-group {
    display: block; /* Ensure all form groups are block-level */
}

/* Styling the category select dropdown */
.form-group select {
    width: 100%; /* Ensure the select takes full width of the form */
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box;
    margin-top: 5px;
}

.form-group select option {
    padding: 10px;
}

/* Adjustments for smaller screens */
@media (max-width: 768px) {
    .form-container {
        width: 90%;
        padding: 15px;
    }
    
    input[type="submit"] {
        padding: 10px 20px;
    }

    .button-group input[type="submit"] {
        margin: 10px 5px;
    }
}
</style>