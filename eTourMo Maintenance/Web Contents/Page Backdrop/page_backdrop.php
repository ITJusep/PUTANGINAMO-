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

// Initialize upload success variable
$uploadSuccess = false;

// Define the upload directory
$uploadDirectory = 'uploads/';

// Ensure the upload directory exists
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

// Supported image types
$supportedTypes = ['jpg', 'jpeg', 'png', 'gif'];

// Handle image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload'])) {
    // Check if the 'fileToUpload' is set and not empty
    if (isset($_FILES["fileToUpload"]) && is_array($_FILES["fileToUpload"]["tmp_name"])) {
        foreach ($_FILES["fileToUpload"]["tmp_name"] as $key => $tmp_name) {
            $imageFileName = basename($_FILES["fileToUpload"]["name"][$key]);
            $imageFileType = strtolower(pathinfo($imageFileName, PATHINFO_EXTENSION));

            // Validate file type
            if (!in_array($imageFileType, $supportedTypes)) {
                echo "File " . htmlspecialchars($imageFileName) . " is not a supported image type.<br>";
                continue; // Skip to the next file
            }

            // Check file size (5MB limit as an example)
            if ($_FILES["fileToUpload"]["size"][$key] > 5000000) {
                echo "File " . htmlspecialchars($imageFileName) . " exceeds the maximum size limit.<br>";
                continue; // Skip to the next file
            }

            // Ensure unique file names
            $uniqueFileName = uniqid('', true) . '.' . $imageFileType;
            $imageFilePath = $uploadDirectory . $uniqueFileName;

            // Check if the file is an actual image
            $check = getimagesize($tmp_name);
            if ($check === false) {
                echo "File " . htmlspecialchars($imageFileName) . " is not a valid image.<br>";
                continue; // Skip to the next file
            }

            // Move the uploaded file to the target directory
            if (move_uploaded_file($tmp_name, $imageFilePath)) {
                // Save the file path in the database
                $stmt = $pdo->prepare("INSERT INTO carousel_images (image_path, image_type) VALUES (?, ?)");
                $stmt->bindParam(1, $imageFilePath);
                $stmt->bindParam(2, $imageFileType);
                if ($stmt->execute()) {
                    $uploadSuccess = true; // Set upload success to true
                } else {
                    echo "Error uploading image " . htmlspecialchars($imageFileName) . ": " . $stmt->errorInfo()[2] . "<br>";
                }
            } else {
                echo "Sorry, there was an error uploading your file " . htmlspecialchars($imageFileName) . ".<br>";
            }
        }
        
        // Redirect to the same page to prevent the form from resubmitting on refresh
        if ($uploadSuccess) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        // echo "No files uploaded or an error occurred.<br>";
    }
}

// Handle image deletion
if (isset($_GET['delete_id'])) {
    $imageId = $_GET['delete_id'];

    // Fetch the image path to delete the file
    $stmt = $pdo->prepare("SELECT image_path FROM carousel_images WHERE carousel_images_id = :id");
    $stmt->bindParam(':id', $imageId, PDO::PARAM_INT);
    $stmt->execute();
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        $imagePath = $image['image_path'];

        // Delete the image from the file system (if necessary)
        if (file_exists($imagePath)) {
            unlink($imagePath); // This deletes the image file
        }

        // Delete the image from the database
        $deleteStmt = $pdo->prepare("DELETE FROM carousel_images WHERE carousel_images_id = :id");
        $deleteStmt->bindParam(':id', $imageId, PDO::PARAM_INT);
        $deleteStmt->execute();
        
        // Redirect back after deletion
        header("Location: page_backdrop.php"); // Assuming this page is named manage.php
        exit();
    }
}

// Fetch all images from the database
$stmt = $pdo->prepare("SELECT image_path, carousel_images_id, image_type FROM carousel_images");
$stmt->execute();
$carousel_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if a backdrop image already exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM backdrops");
$stmt->execute();
$backdropExists = $stmt->fetchColumn() > 0;

// Handle backdrop image upload or replacement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload-backdrop'])) {
    // Check if a backdrop image is uploaded and no image exists yet
    if ($backdropExists && isset($_POST['replace'])) {
        // Replace the existing backdrop image
        if (isset($_FILES['backdrop_image']) && $_FILES['backdrop_image']['error'] == 0) {
            // Get the uploaded image data
            $imageData = file_get_contents($_FILES['backdrop_image']['tmp_name']);
            
            // Prepare SQL to update the existing backdrop image
            $sql = "UPDATE backdrops SET backdrop_image = :backdrop_image WHERE backdrop_id = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':backdrop_image', $imageData, PDO::PARAM_LOB);
            
            if ($stmt->execute()) {
                // echo "Backdrop image replaced successfully!";
            } else {
                echo "Error replacing backdrop image!";
            }
        } else {
            echo "No backdrop image uploaded!";
        }
    } elseif (!$backdropExists && isset($_FILES['backdrop_image']) && $_FILES['backdrop_image']['error'] == 0) {
        // No image exists, so insert a new one
        $imageData = file_get_contents($_FILES['backdrop_image']['tmp_name']);
        
        // Prepare SQL to insert the new backdrop image into the database
        $sql = "INSERT INTO backdrops (backdrop_image) VALUES (:backdrop_image)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':backdrop_image', $imageData, PDO::PARAM_LOB);
        
        if ($stmt->execute()) {
            // echo "Backdrop image uploaded successfully!";
            $backdropExists = true; // Set flag to indicate image is uploaded
        } else {
            echo "Error uploading backdrop image!";
        }
    } else {
        // echo "You can only upload one backdrop image.";
    }
}

// Handle deleting the backdrop image
if (isset($_GET['delete_backdrop_id']) && $backdropExists) {
    $deleteBackdropId = $_GET['delete_backdrop_id'];

    // Delete the backdrop image from the database
    $stmt = $pdo->prepare("DELETE FROM backdrops WHERE backdrop_id = :id");
    $stmt->bindParam(':id', $deleteBackdropId, PDO::PARAM_INT);
    $stmt->execute();

    // Set $backdropExists to false since the image was deleted
    $backdropExists = false;

    echo "Backdrop image deleted successfully!";
    header("Location: page_backdrop.php");
    exit();
}

// Fetch all backdrops from the database to display them
$sql = "SELECT backdrop_id, backdrop_image FROM backdrops";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$backdrops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include('../../Components/header.php'); ?>
<div class="content">
    <div class="upload-container">
        <!-- Open Modal Button -->
        <button id="openModalButton" class="upload-button">+</button>
        <h1>Carousel</h1>
    </div>

    <!-- Modal for Image Upload -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>

            <!-- Image Upload Form for Carousel -->
            <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
                <label for="fileToUpload" class="form-label">Select images to upload:</label>
                <input type="file" name="fileToUpload[]" id="fileToUpload" required multiple class="file-input">
                <input type="submit" value="Upload Images" name="upload" class="upload-button">
            </form>

            <!-- Success Message for Upload -->
            <?php if ($uploadSuccess): ?>
                <p class="success-message">The images have been successfully uploaded!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Check if there are any images -->
    <?php if ($carousel_images): ?>
        <table class="carousel-table">
            <thead>
                <tr class="table-header">
                    <th scope="col" class="table-header-cell">Images</th>
                    <th scope="col" class="table-header-cell">Actions</th>
                </tr>
            </thead>
            <tbody class="table-body">
                <?php foreach ($carousel_images as $image): ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <?php
                            $imagePath = htmlspecialchars($image['image_path']);
                            echo "<img src=\"$imagePath\" alt=\"Carousel Image\" class=\"carousel-image\">";
                            ?>
                        </td>
                        <td class="table-cell">
                            <a class="delete-button" href="?delete_id=<?php echo htmlspecialchars($image['carousel_images_id']); ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No images available.</p>
    <?php endif; ?>

    <div class="upload-container">
        <button id="openBackdropModalButton" class="upload-backdrop-button" <?php echo $backdropExists ? 'disabled' : ''; ?>>+</button>
        <h1>Backdrop</h1>
    </div>

    <!-- Modal for Backdrop Image Upload -->
    <div id="backdropUploadModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeBackdropModal">&times;</span>

            <!-- Backdrop Image Upload Form -->
            <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
                <?php if ($backdropExists): ?>
                    <p>You already have a backdrop image. If you want to change it, delete the existing one first.</p>
                    <a href="?delete_backdrop_id=1" class="delete-button">Delete Existing Backdrop</a><br><br>
                <?php endif; ?>

                <label for="backdrop_image" class="form-label">Select backdrop image to upload:</label>
                <input type="file" name="backdrop_image" id="backdrop_image" required class="file-input" <?php echo $backdropExists ? 'disabled' : ''; ?>>
                <input type="submit" value="Upload Image" name="upload-backdrop" class="upload-button" <?php echo $backdropExists ? 'disabled' : ''; ?>>
            </form>

            <?php if (isset($_POST['upload-backdrop']) && !$backdropExists): ?>
                <p class="success-message">The backdrop image has been successfully uploaded!</p>
            <?php elseif (isset($_POST['upload-backdrop']) && $backdropExists): ?>
                <p class="success-message">The backdrop image has been successfully replaced!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Display the existing backdrop image -->
    <?php if ($backdrops): ?>
        <table class="carousel-table">
            <thead>
                <tr class="table-header">
                    <th scope="col" class="table-header-cell">Image</th>
                    <th scope="col" class="table-header-cell">Actions</th>
                </tr>
            </thead>
            <tbody class="table-body">
                <?php foreach ($backdrops as $backdrop): ?>
                    <tr class="table-row">
                        <td class="table-cell">
                            <?php
                            echo "<img src='data:image/jpeg;base64," . base64_encode($backdrop['backdrop_image']) . "' alt='Backdrop Image' class='carousel-image'>";
                            ?>
                        </td>
                        <td class="table-cell">
                            <a class="delete-button" href="?delete_backdrop_id=<?php echo htmlspecialchars($backdrop['backdrop_id']); ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No backdrop images available.</p>
    <?php endif; ?>
</div>
<?php include('../../Components/footer.php'); ?>


<style>
/* General Layout */
.content {
    display: flex; /* Use Flexbox */
    flex-direction: column; /* Stack elements vertically */
    justify-content: center; /* Center elements vertically */
    align-items: flex-start; /* Align elements to the left */
    height: 100vh; /* Full height of the viewport */
    text-align: left; /* Align text to the left */
    margin-left: 260px;
    margin-top: 190px;
}

/* Upload Container - Align buttons to the left */
.upload-container {
    text-align: left; /* Align the button to the left */
    margin-bottom: 16px;
    width: 100%; /* Ensure it takes up the full width of the container */
}

.upload-container h1{
    text-align: center;
    color: #2563EB;
}

/* Upload Backdrop Button (Disabled) */
.upload-backdrop-button:disabled {
    background-color: #ddd;
    cursor: not-allowed;
    background-color: #3B82F6;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    width: auto;
    height: 50px;
}

.upload-backdrop-button:disabled:hover {
    background-color: #2563EB;
}

/* Modal Layout */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto; /* Center modal content vertically */
    padding: 20px;
    border-radius: 8px;
    width: 50%;
    max-width: 600px;
}

/* Close Button */
.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    cursor: pointer;
}

/* Form Styling */
.upload-form {
    margin-top: 20px;
    max-width: 500px;
    margin-left: auto; /* Center form horizontally */
    margin-right: auto;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.form-label {
    display: block;
    font-size: 1rem;
    margin-bottom: 8px;
    font-weight: bold;
    width: 100px;
}

.file-input {
    width: 200px;
    padding: 8px;
    margin-bottom: 16px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* Button Styling */

/* Open Modal Button */
#openModalButton {
    width: 50px;
    height: 50px;
    background-color: #3B82F6;
    color: white;
    font-size: 1.5rem;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

#openModalButton:hover {
    background-color: #2563EB;
}

/* Backdrop Modal Button */
#openBackdropModalButton {
    width: 50px;
    height: 50px;
    background-color: #3B82F6;
    color: white;
    font-size: 1.5rem;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

#openBackdropModalButton:hover {
    background-color: #2563EB;
}

/* Upload Submit Button */
.upload-form .upload-button {
    background-color: #3B82F6;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    width: auto;
    height: 50px;
}

.upload-form .upload-button:hover {
    background-color: #2563EB;
}

/* Success Message */
.success-message {
    color: blue;
    text-align: center;
    font-size: 1rem;
    margin-top: 20px;
}

/* Table Layout */
.carousel-table {
    margin-bottom: 50px;
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border: 1px solid #D1D5DB;
}

.table-header {
    background-color: #F3F4F6;
    text-transform: uppercase;
    font-size: 0.875rem;
    font-weight: 600;
}

.table-header-cell {
    padding: 12px;
    border: 1px solid #D1D5DB;
}

.table-row:hover {
    background-color: #F9FAFB;
}

.table-cell {
    padding: 12px;
    border: 1px solid #D1D5DB;
}

/* Image Styling */
.carousel-image {
    max-width: 200px;
    max-height: 150px;
}

/* Delete Button Styling */
.delete-button {
    color: #DC2626;
    font-weight: 500;
    cursor: pointer;
}

.delete-button:hover {
    text-decoration: underline;
}
</style>

<script>
// Get modal element
var modal = document.getElementById('uploadModal');

// Get open modal button
var openModalButton = document.getElementById('openModalButton');

// Get close button
var closeModalButton = document.getElementById('closeModal');

// When the user clicks on the button, open the modal
openModalButton.onclick = function() {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
closeModalButton.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Modal handling for backdrop
// Get modal elements
var modal = document.getElementById('uploadModal');
var backdropModal = document.getElementById('backdropUploadModal');

// Get open modal buttons
var openModalButton = document.getElementById('openModalButton');
var openBackdropModalButton = document.getElementById('openBackdropModalButton');

// Get close buttons
var closeModalButton = document.getElementById('closeModal');
var closeBackdropModalButton = document.getElementById('closeBackdropModal');

// When the user clicks on the button, open the modal
openModalButton.onclick = function() {
    modal.style.display = "block";
}

openBackdropModalButton.onclick = function() {
    backdropModal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
closeModalButton.onclick = function() {
    modal.style.display = "none";
}

closeBackdropModalButton.onclick = function() {
    backdropModal.style.display = "none";
}

// When the user clicks anywhere outside the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
    if (event.target == backdropModal) {
        backdropModal.style.display = "none";
    }
}
</script>
