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

// Handle form submission for uploading packages
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload'])) {
    // Collect form data
    $package_name = $_POST['package_name'];
    $package_pax = $_POST['package_pax'];
    $package_location = $_POST['package_location'];
    $package_starts = $_POST['package_start'];
    $package_ends = $_POST['package_ends'];
    $package_price = $_POST['package_price'];
    $package_description = $_POST['package_description'];
    $package_inclusion = $_POST['package_inclusion'];
    $package_requirements = $_POST['package_requirements'];
    $package_duration = $_POST['package_duration'];
    $package_cancellation_policy = $_POST['package_cancellation_policy'];
    $package_itinerary = $_POST['package_itinerary'];
    $package_category = $_POST['package_category']; 
    $package_minimum = $_POST['package_minimum'];
    // New category field

    // Handle Upload Package action
    try {
        // Insert package data into 'packages' table, including the category
        $stmt = $pdo->prepare("INSERT INTO packages (package_name, package_pax, package_location, package_price, 
            package_description, package_inclusion, package_requirements, package_duration, 
            package_cancellation_policy, package_itinerary, package_category, package_start, package_ends, package_minimum) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?, ?)");

        $stmt->execute([$package_name, $package_pax, $package_location, $package_price,
            $package_description, $package_inclusion,
            $package_requirements,  $package_duration, $package_cancellation_policy, $package_itinerary, $package_category, $package_starts, $package_ends, $package_minimum]);

        $package_id = $pdo->lastInsertId(); // Get the last inserted package ID

        // Handle image uploads (if any)
        if (isset($_FILES['package_images']) && count($_FILES['package_images']['name']) > 0) {
            $images = $_FILES['package_images'];
            for ($i = 0; $i < count($images['name']); $i++) {
                if ($images['error'][$i] === UPLOAD_ERR_OK) {
                    $image_tmp = $images['tmp_name'][$i];
                    $image_data = file_get_contents($image_tmp);
                    $image_type = $images['type'][$i];

                    // Insert image data into the 'package_images' table
                    $stmt = $pdo->prepare("INSERT INTO package_images (package_id, image_data, image_type) VALUES (?, ?, ?)");
                    $stmt->execute([$package_id, $image_data, $image_type]);
                }
            }
        }

        echo "Package and images uploaded successfully!";
        // Redirect to the same page to refresh the form and avoid resubmission on reload
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        echo "Error uploading package: " . $e->getMessage();
    }
}

// Handle package search functionality
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    // Prepare query with search functionality
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE package_name LIKE :searchTerm");
    $stmt->execute(['searchTerm' => '%' . $searchTerm . '%']);
} else {
    // Fetch all packages if no search term is entered
    $stmt = $pdo->prepare("SELECT * FROM packages");
    $stmt->execute();
}

$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<?php include('../../Components/header.php'); ?>
<!-- Upload Button (on the right side of the screen) -->
 <div class="content">

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

<?php if ($packages): ?>
    <!-- Loop through all packages -->
    <table class="table table-lg text-black mt-12">  
        <thead>
            <tr class="bg-[#608BC1] text-black">
                <th>Package Name</th>
                <th>Package Location</th>
                <th>Package Price</th>
                <th>Category</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($packages as $package): ?>
                <tr>
                    <td><?php echo $package['package_name']; ?></td>
                    <td><?php echo $package['package_location']; ?></td>
                    <td><?php echo number_format($package['package_price'], 0, '.', ','); ?></td>
                    <td><?php echo $package['package_category']; ?></td>
                    <td class="underline"><a href="/eTourMo Maintenance/Postings/Travel Packages/detail.php?id=<?php echo $package['package_id']; ?>">View</a></td>
                </tr> 
            <?php endforeach; ?> 
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align: center;">No packages found matching your search.</p>
<?php endif; ?>

<!-- Modal for Upload Package Form -->
<div id="uploadModal" class="custom-modal">
    <div class="modal-content text-black">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Upload Package</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="package_name">Package Name:</label>
                <input type="text" name="package_name" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_pax">Package Pax:</label>
                <input type="number" name="package_pax" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_location">Package Location:</label>
                <input type="text" name="package_location" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_price">Package Price:</label>
                <input type="number" name="package_price" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_starts">Package Starts:</label>
                <input type="date" name="package_start" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_ends">Package Ends:</label>
                <input type="date" name="package_ends" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_description">Package Description:</label>
                <textarea name="package_description" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required></textarea>
            </div>

            <div class="form-group">
                <label for="package_inclusion">Package Inclusion:</label>
                <textarea name="package_inclusion" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required></textarea>
            </div>

            <div class="form-group">
                <label for="package_requirements">Package Requirements:</label>
                <textarea name="package_requirements" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md" required></textarea>
            </div>

            <div class="form-group">
                <label for="package_duration">Package Duration:</label>
                <input type="text" name="package_duration" class="input input-bordered w-full bg-[#CBDCEB] text-black" required>
            </div>

            <div class="form-group">
                <label for="package_cancellation_policy">Package Cancellation Policy:</label>
                <textarea name="package_cancellation_policy" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md"></textarea>
            </div>

            <div class="form-group">
                <label for="package_itinerary">Package Itinerary:</label>
                <textarea name="package_itinerary" class="textarea textarea-bordered bg-[#CBDCEB] text-black textarea-md"></textarea>
            </div>
a
            <div class="form-group">
                <label for="package_minimum">Minimum Passengers:</label>
                <input type="number" name="package_minimum" class="input input-bordered w-full bg-[#CBDCEB] text-black" max="100" min="1" required>
            </div>

            <!-- Dropdown for Package Category -->
            <div class="form-group">
                <label for="package_category">Package Category:</label>
                <select name="package_category" class="select select-bordered w-full max-full bg-[#CBDCEB] text-black" required>
                    <option value="Local Package">Local Package</option>
                    <option value="International Package">International Package</option>
                    <option value="Educational Package">Educational Package</option>
                </select>
            </div>

            <div class="form-group">
                <label for="package_images">Package Images:</label>
                <input type="file" name="package_images[]" multiple accept="image/*" class="file-input file-input-bordered w-full file-input-info bg-[#CBDCEB] text-black" />
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
    height: 1000px;
    padding: 100px;
}

/* Header */
h1 {
    text-align: center;
    margin-top: 20px;
    font-size: 2em;
}



.upload-button a:hover {
    background-color: #2980b9;
}

/* Table styles */
.package-table {
    width: 80%;
    margin: 20px auto;
    border-collapse: collapse;
    background-color: white;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.package-table th, .package-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.package-table th {
    background-color: #f1f1f1;
}

.package-table td a {
    color: #3498db;
    text-decoration: none;
}

.package-table td a:hover {
    text-decoration: underline;
}

/* Modal styles */
.custom-modal {
    display: none;  /* Ensure modal is hidden by default */
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    display: flex;  /* Flexbox for centering */
    justify-content: center;
    align-items: center;
    margin-left: 100px;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto; /* Make the modal content scrollable if necessary */
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
    /* Form container */
    .search-form {
    max-width: 28rem; /* max-w-md */
    margin: 0 auto;   /* mx-auto */
}


/* Label styling */
.search-label {
    margin-bottom: 0.5rem; /* mb-2 */
    font-size: 0.875rem;   /* text-sm */
    font-weight: 500;      /* font-medium */
    color: #1f2937;        /* text-gray-900 */
    display: none;         /* sr-only, hides label visually but keeps it accessible */
}

/* Search container */
.search-container {
    position: relative;
}

/* Search icon styling */
.search-icon {
    position: absolute;
    top: 0;
    left: 0;
    display: flex;
    align-items: center;
    padding-left: 0.75rem;  /* ps-3 */
    pointer-events: none;
}

.search-svg {
    width: 1rem;  /* w-4 */
    height: 1rem; /* h-4 */
    color: #6b7280;  /* text-gray-500 */
}

/* Input field */
.search-input {
    display: block;
    width: 100%;
    padding: 1rem 1rem 1rem 2.5rem;  /* p-4 ps-10 */
    font-size: 0.875rem;  /* text-sm */
    color: #111827;       /* text-gray-900 */
    border: 1px solid #d1d5db;  /* border-gray-300 */
    border-radius: 0.5rem; /* rounded-lg */
    background-color: #f9fafb;  /* bg-gray-50 */
    transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
    border-color: #3b82f6;  /* focus:border-blue-500 */
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);  /* focus:ring-blue-500 */
}

/* Dark mode styles */
@media (prefers-color-scheme: dark) {
    .search-label {
        color: #ffffff;  /* dark:text-white */
    }
    .search-icon svg {
        color: #9ca3af;  /* dark:text-gray-400 */
    }
    .search-input {
        background-color: #374151;  /* dark:bg-gray-700 */
        border-color: #4b5563;      /* dark:border-gray-600 */
        color: #ffffff;             /* dark:text-white */
    }
    .search-input:focus {
        border-color: #3b82f6;      /* dark:focus:border-blue-500 */
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);  /* dark:focus:ring-blue-500 */
    }
}

/* Button styling */
.search-button {
    position: absolute;
    bottom: 0.625rem; /* bottom-2.5 */
    right: 0.625rem;  /* end-2.5 */
    background-color: #2563eb; /* bg-blue-700 */
    color: white;
    padding: 0.5rem 1rem; /* px-4 py-2 */
    font-size: 0.875rem;  /* text-sm */
    font-weight: 500;     /* font-medium */
    border-radius: 0.375rem;  /* rounded-lg */
    transition: background-color 0.2s, box-shadow 0.2s;
    border: none;
    cursor: pointer;
}

.search-button:hover {
    background-color: #1d4ed8;  /* hover:bg-blue-800 */
}

.search-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);  /* focus:ring-blue-300 */
    outline: none;
}

/* Dark mode button styles */
@media (prefers-color-scheme: dark) {
    .search-button {
        background-color: #4b7cf3;  /* dark:bg-blue-600 */
    }
    .search-button:hover {
        background-color: #3b5ebd;  /* dark:hover:bg-blue-700 */
    }
    .search-button:focus {
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);  /* dark:focus:ring-blue-800 */
    }
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

