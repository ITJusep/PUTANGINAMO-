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

// Handle form submission for uploading rental details
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload'])) {
    // Collect form data
    $rental_type = $_POST['rental_type']; // Car, Bus, Van
    $rental_pax = $_POST['rental_pax'];  // Number of passengers
    $rental_price = $_POST['rental_price'];
    $rental_description = $_POST['rental_description'];
    $rental_not_included = $_POST['rental_not_included'];
    $rental_duration = $_POST['rental_duration'];
    $rental_cancellation_policy = $_POST['rental_cancellation_policy'];

    // Handle Upload Rental action
    try {
        // Insert rental data into 'rentals' table
        $stmt = $pdo->prepare("INSERT INTO rentals (rental_type, rental_pax, rental_price, 
            rental_description, rental_not_included, rental_duration, rental_cancellation_policy) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([$rental_type, $rental_pax, $rental_price, $rental_description, 
            $rental_not_included, $rental_duration, $rental_cancellation_policy]);

        $rental_id = $pdo->lastInsertId(); // Get the last inserted rental ID

        // Handle image uploads (if any)
        if (isset($_FILES['rental_images']) && count($_FILES['rental_images']['name']) > 0) {
            $images = $_FILES['rental_images'];
            for ($i = 0; $i < count($images['name']); $i++) {
                if ($images['error'][$i] === UPLOAD_ERR_OK) {
                    $image_tmp = $images['tmp_name'][$i];
                    $image_data = file_get_contents($image_tmp);
                    $image_type = $images['type'][$i];

                    // Insert image data into the 'rental_images' table
                    $stmt = $pdo->prepare("INSERT INTO rental_images (rental_id, image_data, image_type) VALUES (?, ?, ?)");
                    $stmt->execute([$rental_id, $image_data, $image_type]);
                }
            }
        }

        echo "Rental and images uploaded successfully!";
        // Redirect to the same page to refresh the form and avoid resubmission on reload
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        echo "Error uploading rental: " . $e->getMessage();
    }
}

// Fetch all rentals from the 'rentals' table
$stmt = $pdo->prepare("SELECT * FROM rentals");
$stmt->execute();
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include('../../Components/header.php'); ?>
<div class="content">
<h2 class="text-6xl font-bold mb-4 text-black">Manage Rentals</h2>

<!-- Upload Button (on the right side of the screen) -->
<form class="search-form" method="GET" action="">
    <div class="join">
        <div>
            <div>
            <input type="search" id="default-search" name="search" class="input input-bordered join-item bg-[#CBDCEB] placeholder-black text-black" placeholder="Search Package Name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
            </div>
        </div>
        <button type="submit" class="btn btn-info join-item">Search</button>
        <a href="#" id="uploadButton" onclick="openModal()" class="btn btn-info btn-md join-item bg-[#133E87]">+</a>
    </div>
</form>

<?php if ($rentals): ?>
    <!-- Loop through all rentals -->
    <table class="table table-lg text-black mt-12">  
        <thead>
            <tr class="bg-[#608BC1] text-black">
                <th>Rental Type</th>
                <th>Capacity</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rentals as $rental): ?>
                <tr>
                    <td><?php echo $rental['rental_type']; ?></td>
                    <td><?php echo $rental['rental_pax']; ?></td>
                    <td><?php echo number_format($rental['rental_price'], 0, '.', ','); ?></td>
                    <td><a href="/eTourMo Maintenance/Postings/Rental Packages/details.php?id=<?php echo $rental['rental_id']; ?>">Edit</a></td>
                </tr> 
            <?php endforeach; ?> 
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align: center;">No Rentals found matching your search.</p>
<?php endif; ?>

<!-- Modal for Upload Rental Form -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Upload Rental</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="rental_type">Rental Type:</label>
                <select name="rental_type" required>
                    <option value="car">Car</option>
                    <option value="bus">Bus</option>
                    <option value="van">Van</option>
                </select>
            </div>

            <div class="form-group">
                <label for="rental_pax">Capacity:</label>
                <input type="number" name="rental_pax" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="rental_price">Rental Price:</label>
                <input type="number" name="rental_price" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="rental_description">Rental Description:</label>
                <textarea name="rental_description" class="input input-bordered w-full bg-[#CBDCEB] text-black" required></textarea>
            </div>

            <div class="form-group">
                <label for="rental_not_included">Rental Not Included:</label>
                <textarea name="rental_not_included" class="input input-bordered w-full bg-[#CBDCEB] text-black" required></textarea>
            </div>

            <div class="form-group">
                <label for="rental_duration">Rental Duration:</label>
                <input type="text" name="rental_duration" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="rental_cancellation_policy">Rental Cancellation Policy:</label>
                <textarea name="rental_cancellation_policy" class="input input-bordered w-full bg-[#CBDCEB] text-black" required></textarea>
            </div>

            <!-- File upload for Rental Images -->
            <div class="form-group">
                <label for="rental_images">Rental Images:</label>
                <input type="file" name="rental_images[]" multiple accept="image/*"class="file-input file-input-bordered w-full file-input-info bg-[#CBDCEB] text-black" />
            </div>

            <div class="button-group">
                <input type="submit" name="upload" value="Upload">
            </div>
        </form>
    </div>
</div>
</div>
<?php include('../../Components/footer.php'); ?>

<!-- CSS for Modal -->
<style>
/* General page styles */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 150px;
    background-color: #F3F3E0;
    height: 1040px;
    padding: 100px;
}
/* Form container */
.search-form {
max-width: 28rem; /* max-w-md */
margin: 0 auto;   /* mx-auto */
}

/* Header */
h1 {
    text-align: center;
    margin-top: 20px;
    font-size: 2em;
}

/* Upload button */
.upload-button {
    text-align: left;
    margin: 20px;
    margin-left: 110px;
}

.upload-button a {
    display: inline-block;
    padding: 5px;  /* Adjust padding for smaller button */
    background-color: #3498db;
    color: white;
    font-weight: bold;
    text-decoration: none;
    border-radius: 50%;  /* Make the button circular */
    font-size: 16px;  /* Smaller font size to fit inside the 20px button */
    text-align: center; /* Ensure the "+" is centered in the circle */
    width: 40px;  /* Set fixed width for the circle */
    height: 40px;  /* Set fixed height for the circle */
    line-height: 40px; /* Vertically center the "+" symbol */
}

.upload-button a:hover {
    background-color: #2980b9;
}

/* Table styles */
.rental-table {
    width: 80%;
    margin: 20px auto;
    border-collapse: collapse;
    background-color: white;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.rental-table th, .rental-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.rental-table th {
    background-color: #f1f1f1;
}

.rental-table td a {
    color: #3498db;
    text-decoration: none;
}

.rental-table td a:hover {
    text-decoration: underline;
}

/* Modal styles */
.modal {
    display: none;  /* Initially hidden */
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    display: flex;  /* Flex to center modal */
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
/* Form styles */
form {
    display: flex;
    flex-direction: column;
}

.form-group {
    margin-bottom: 15px;
}

label {
    font-size: 14px;
    margin-bottom: 5px;
}

input, textarea, select {
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

input[type="file"] {
    padding: 5px;
}

.button-group {
    text-align: center;
    margin-top: 20px;
}

/* Button styles */
input[type="submit"] {
    padding: 12px 20px;
    background-color: #3498db;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
}

input[type="submit"]:hover {
    background-color: #2980b9;
}
</style>

<!-- JavaScript for Modal -->
<script>
// Ensure the modal is hidden when the page loads (default behavior)
window.onload = function() {
    document.getElementById("uploadModal").style.display = "none";  // Hide the modal initially
};
// Open the modal when the button is clicked
function openModal() {
    document.getElementById("uploadModal").style.display = "flex";  // Show the modal
}

// Close the modal when the close button is clicked
function closeModal() {
    document.getElementById("uploadModal").style.display = "none";  // Hide the modal
}

// Close the modal if the user clicks outside of the modal content
window.onclick = function(event) {
    if (event.target == document.getElementById("uploadModal")) {
        closeModal();  // Close modal if clicked outside the modal
    }
}
</script>
