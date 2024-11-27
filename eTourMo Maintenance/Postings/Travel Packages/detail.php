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

// Retrieve package information and images if package_id is provided
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$package_data = null;
$images = [];

if ($package_id) {
    // Fetch package data from the database
    $sql = "SELECT * FROM packages WHERE package_id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$package_id]);
    $package_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch images for the package
    $sql_images = "SELECT * FROM package_images WHERE package_id = ?";
    $stmt_images = $pdo->prepare($sql_images);
    $stmt_images->execute([$package_id]);
    $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission for update or delete
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the posted values
    $package_name = isset($_POST['package_name']) ? $_POST['package_name'] : $package_data['package_name'];
    $package_category = isset($_POST['package_category']) ? $_POST['package_category'] : $package_data['package_category']; // Now editable
    $package_pax = isset($_POST['package_pax']) ? $_POST['package_pax'] : $package_data['package_pax'];
    $package_location = isset($_POST['package_location']) ? $_POST['package_location'] : $package_data['package_location'];
    $package_price = isset($_POST['package_price']) ? $_POST['package_price'] : $package_data['package_price'];
    $package_start = isset($_POST['package_start']) ? $_POST['package_start'] : $package_data['package_start'];
    $package_ends = isset($_POST['package_ends']) ? $_POST['package_ends'] : $package_data['package_ends'];
    $package_description = isset($_POST['package_description']) ? $_POST['package_description'] : $package_data['package_description'];
    $package_inclusion = isset($_POST['package_inclusion']) ? $_POST['package_inclusion'] : $package_data['package_inclusion'];
    $package_requirements = isset($_POST['package_requirements']) ? $_POST['package_requirements'] : $package_data['package_requirements'];
    $package_duration = isset($_POST['package_duration']) ? $_POST['package_duration'] : $package_data['package_duration'];
    $package_cancellation_policy = isset($_POST['package_cancellation_policy']) ? $_POST['package_cancellation_policy'] : $package_data['package_cancellation_policy'];
    $package_itinerary = isset($_POST['package_itinerary']) ? $_POST['package_itinerary'] : $package_data['package_itinerary'];
    $package_minimum = isset($_POST['package_minimum']) ? $_POST['package_minimum'] : $package_data['package_minimum'];

    // Handle Update Package action
    if (isset($_POST['update']) && !empty($package_id)) {
        // Prepare the SQL statement for the fields being updated
        $stmt = $pdo->prepare("UPDATE packages SET 
            package_name = ?, 
            package_category = ?, 
            package_pax = ?, 
            package_location = ?, 
            package_price = ?, 
            package_start = ?,
            package_ends = ?,
            package_description = ?, 
            package_inclusion = ?,  
            package_requirements = ?, 
            package_duration = ?, 
            package_cancellation_policy = ?, 
            package_itinerary = ?,
            package_minimum = ?
            WHERE package_id = ?");

        // Execute and check for errors
        $success = $stmt->execute([
            $package_name, 
            $package_category, 
            $package_pax, 
            $package_location, 
            $package_price, 
            $package_start,
            $package_ends,
            $package_description, 
            $package_inclusion, 
            $package_requirements, 
            $package_duration, 
            $package_cancellation_policy, 
            $package_itinerary, 
            $package_minimum,
            $package_id
        ]);

        if (!$success) {
            echo "Update failed: " . implode(", ", $stmt->errorInfo()); 
        }

        // Handle image update
        if (isset($_FILES['package_images']) && !empty($_FILES['package_images']['name'][0])) {
            // Delete existing images if new images are uploaded
            $stmt_delete_images = $pdo->prepare("DELETE FROM package_images WHERE package_id = ?");
            $stmt_delete_images->execute([$package_id]);

            // Upload new images
            $files = $_FILES['package_images'];
            foreach ($files['tmp_name'] as $index => $tmp_name) {
                // Check if file is an image
                if (exif_imagetype($tmp_name)) {
                    $image_data = file_get_contents($tmp_name);
                    $image_type = mime_content_type($tmp_name);

                    // Insert new image into the package_images table
                    $stmt_image = $pdo->prepare("INSERT INTO package_images (package_id, image_data, image_type) VALUES (?, ?, ?)");
                    $stmt_image->execute([$package_id, $image_data, $image_type]);
                }
            }
        }

        // Handle image deletions
        if (isset($_POST['delete_images'])) {
            $delete_image_ids = $_POST['delete_images'];
            foreach ($delete_image_ids as $image_id) {
                // Delete selected images from the package_images table
                $stmt_delete_image = $pdo->prepare("DELETE FROM package_images WHERE image_id = ?");
                $stmt_delete_image->execute([$image_id]);
            }
        }

        if ($success) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=$package_id");
            exit();
        } else {
            echo "<div class='error'>Update failed. Please try again.</div>";
        }
    }

    // Handle Delete Package action
    if (isset($_POST['delete']) && !empty($package_id)) {
        // First, remove the references to this package in the bookings table
        $stmt_remove_bookings = $pdo->prepare("DELETE FROM bookings WHERE package_id = ?");
        $stmt_remove_bookings->execute([$package_id]);

        // Delete images related to the package
        $stmt_delete_images = $pdo->prepare("DELETE FROM package_images WHERE package_id = ?");
        $stmt_delete_images->execute([$package_id]);

        // Then, delete the package itself
        $stmt_delete_package = $pdo->prepare("DELETE FROM packages WHERE package_id = ?");
        $stmt_delete_package->execute([$package_id]);

        // Redirect to index.php after deleting the package
        header("Location: detail.php");
        exit();
    }
}
?>

<?php include('../../Components/header.php'); ?>

<div class="content">
<!-- Back Button -->
<div class="back-button-container">
    <button type="button" class="btn btn-info w-20" onclick="window.location.href='travel_packages.php';">
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 5l-7 7 7 7"></path>
        </svg>
    </button>
</div>

<!-- Form for uploading/updating/deleting package -->
<form class="form-container" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="package_name">Package Name:</label>
        <input type="text" name="package_name" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_name'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_category">Package Category:</label>
        <input type="text" name="package_category" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_category'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_pax">Package Pax:</label>
        <input type="text" name="package_pax" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_pax'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_location">Package Location:</label>
        <input type="text" name="package_location" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_location'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_price">Package Price:</label>
        <input type="text" name="package_price" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_price'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_description">Package Description:</label>
        <textarea name="package_description" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required><?php echo htmlspecialchars($package_data['package_description'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="package_inclusion">Package Inclusion:</label>
        <textarea name="package_inclusion" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required><?php echo htmlspecialchars($package_data['package_inclusion'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="package_requirements">Package Requirements:</label>
        <textarea name="package_requirements" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required><?php echo htmlspecialchars($package_data['package_requirements'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="package_duration">Package Duration:</label>
        <input type="text" name="package_duration" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_duration'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_cancellation_policy">Cancellation Policy:</label>
        <textarea name="package_cancellation_policy" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required><?php echo htmlspecialchars($package_data['package_cancellation_policy'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="package_itinerary">Package Itinerary:</label>
        <textarea name="package_itinerary" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required><?php echo htmlspecialchars($package_data['package_itinerary'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <div class="form-group">
        <label for="package_minimum">Minimum Passengers:</label>
        <input type="number" name="package_minimum" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_minimum'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <!-- Start and End Date Picker Section -->
    <div class="form-group">
        <label for="package_start">Package Start Date:</label>
        <input type="date" name="package_start" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_start'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="package_ends">Package End Date:</label>
        <input type="date" name="package_ends" class="input input-bordered w-full bg-[#CBDCEB] text-black" value="<?php echo htmlspecialchars($package_data['package_ends'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <!-- Images Section -->
    <div class="form-group">
        <label for="package_images">Upload Package Images (Optional):</label>
        <input type="file" name="package_images[]" class="file-input file-input-bordered w-full file-input-info bg-[#CBDCEB] text-black" required>
    </div>

    <!-- <div class="form-group">
        <label for="delete_images">Delete Images (Select to delete):</label>
        <?php if ($images): ?>
            <?php foreach ($images as $image): ?>
                <div class="image-checkbox">
                    <input type="checkbox" name="delete_images[]" value="<?php echo $image['image_id']; ?>">
                    <img src="data:<?php echo $image['image_type']; ?>;base64,<?php echo base64_encode($image['image_data']); ?>" width="100" alt="Package Image">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div> -->

    <div class="form-group">
        <button type="submit" name="update" class="btn btn-info">Update</button>
        <button type="submit" name="delete" class="btn btn-error" onclick="return confirm('Are you sure you want to delete this package?');">Delete</button>
    </div>
</form>
</div>

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
    height: 800px;
    overflow: scroll;
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



.back-button{
    margin-left: 10px;
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
