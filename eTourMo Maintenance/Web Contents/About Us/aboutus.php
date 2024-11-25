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

//organization Information Start

// Function to handle the company image upload and update
if (isset($_POST['upload_company_images'])) {
    if (!empty($_FILES['company_images']['name'][0])) {
        foreach ($_FILES['company_images']['tmp_name'] as $key => $tmp_name) {
            $imageData = file_get_contents($tmp_name);
            $imageType = $_FILES['company_images']['type'][$key];

            // Ensure the file is an image
            if (strpos($imageType, 'image') === 0) {
                $stmt = $pdo->prepare("INSERT INTO company_images (image_data, image_type) VALUES (:image_data, :image_type)");
                $stmt->bindParam(':image_data', $imageData, PDO::PARAM_LOB);
                $stmt->bindParam(':image_type', $imageType);
                $stmt->execute();
            }
        }
    }
}

// Handle image deletion
if (isset($_POST['delete_company_image'])) {
    $selectedImageId = $_POST['selected_company_image'];

    if (!empty($selectedImageId)) {
        $stmt = $pdo->prepare("DELETE FROM company_images WHERE image_id = :image_id");
        $stmt->bindParam(':image_id', $selectedImageId, PDO::PARAM_INT);
        $stmt->execute();
    }
}

// Fetch images for dropdown
$imageOptions = "";
$stmt = $pdo->query("SELECT image_id, image_type FROM company_images");
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as $image) {
    $imageOptions .= "<option value='" . htmlspecialchars($image['image_id']) . "'>Image ID: " . htmlspecialchars($image['image_id']) . " (" . htmlspecialchars($image['image_type']) . ")</option>";
}

// Function to handle the organization info upload and update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_organization'])) {
    // Prepare the data for insertion/updating
    $whoWeAre = $_POST['who_we_are'];
    $ourVision = $_POST['our_vision'];
    $ourMission = $_POST['our_mission'];
    $ceoMessage = $_POST['ceo_message'];
    $address = $_POST['address'];
    $contactNumber = $_POST['contact_number'];
    $facebookLink = $_POST['facebook_link'];

    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM organization_info WHERE organization_info_id = 1");
    $exists = $stmt->fetchColumn() > 0;

    if ($exists) {
        // Update existing record
        $updateStmt = $pdo->prepare("UPDATE organization_info SET who_we_are = ?, our_vision = ?, our_mission = ?, ceo_message = ?, address = ?, contact_number = ?, facebook_link = ? WHERE organization_info_id = 1");
        $updateStmt->execute([$whoWeAre, $ourVision, $ourMission, $ceoMessage, $address, $contactNumber, $facebookLink]);
    } else {
        // Insert new record
        $insertStmt = $pdo->prepare("INSERT INTO organization_info (organization_info_id, who_we_are, our_vision, our_mission, ceo_message, address, contact_number, facebook_link) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$whoWeAre, $ourVision, $ourMission, $ceoMessage, $address, $contactNumber, $facebookLink]);
    }
}

// Fetch existing organization info
$orgStmt = $pdo->query("SELECT * FROM organization_info WHERE organization_info_id = 1");
$orgInfo = $orgStmt->fetch(PDO::FETCH_ASSOC);

// If there's no existing data, initialize $orgInfo as an empty array
if (!$orgInfo) {
    $orgInfo = [
        'who_we_are' => '',
        'our_vision' => '',
        'our_mission' => '',
        'ceo_message' => '',
        'address' => '',
        'contact_number' => '',
        'facebook_link' => ''
    ];
}

//organization Information End

// Certificate Function Start

// Handling file upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_certificates'])) {
    if (isset($_FILES['certificate_images'])) {
        $images = $_FILES['certificate_images'];

        foreach ($images['tmp_name'] as $index => $tmpName) {
            if ($images['error'][$index] === 0) {
                // Get image type (e.g., image/jpeg, image/png)
                $imageType = $images['type'][$index];
                $imageData = file_get_contents($tmpName); // Get binary data

                // Prepare the query to insert image data into the database
                $stmt = $pdo->prepare("INSERT INTO certificates (image_data, image_type) VALUES (?, ?)");
                $stmt->execute([$imageData, $imageType]);
            } else {
                echo "Error uploading file: " . $images['name'][$index];
            }
        }
    }
}

// Handling certificate deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_certificate'])) {
    $selectedCertificateId = $_POST['selected_certificate'];

    if (!empty($selectedCertificateId)) {
        // Prepare the query to delete the selected certificate
        $stmt = $pdo->prepare("DELETE FROM certificates WHERE certificate_id = ?");
        $stmt->execute([$selectedCertificateId]);
    } else {
        echo "Please select a certificate to delete.";
    }
}
// Certificate Function End

// Initialize variables
$services = [];
$selectedService = null;

// Fetch existing services from the database
$certStmt = $pdo->query("SELECT * FROM services");
$services = $certStmt->fetchAll(PDO::FETCH_ASSOC);

// Handling form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // If a service is selected for update or delete
    if (isset($_POST['selected_service']) && !empty($_POST['selected_service'])) {
        $selectedServiceId = $_POST['selected_service'];

        // Fetch the selected service details from the database
        $stmt = $pdo->prepare('SELECT * FROM services WHERE services_id = :id');
        $stmt->execute(['id' => $selectedServiceId]);
        $selectedService = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle uploading a new service
    if (isset($_POST['upload_service'])) {
        $serviceTitle = htmlspecialchars($_POST['service_title']);
        $serviceDescription = htmlspecialchars($_POST['service_description']);
        $serviceImage = $_FILES['service_image'];

        // Insert new service into the database
        $insertParams = [];
        $insertFields = [];

        if (!empty($serviceTitle)) {
            $insertFields[] = "services_title";
            $insertParams[] = $serviceTitle;
        }

        if (!empty($serviceDescription)) {
            $insertFields[] = "services_description";
            $insertParams[] = $serviceDescription;
        }

        if (isset($serviceImage) && $serviceImage['error'] === 0) {
            // Validate file type and size
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($serviceImage['type'], $allowedTypes)) {
                die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }

            if ($serviceImage['size'] > 2 * 1024 * 1024) {
                die("File size exceeds the 2MB limit.");
            }

            // Read image content
            $imageData = file_get_contents($serviceImage['tmp_name']);
            $insertFields[] = "services_image";
            $insertParams[] = $imageData;
        }

        if (!empty($insertFields)) {
            // Prepare the SQL query to insert the new service
            $sql = "INSERT INTO services (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", array_fill(0, count($insertParams), '?')) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insertParams);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Handle updating the selected service
    if (isset($_POST['update_service']) && isset($selectedService)) {
        $serviceTitle = htmlspecialchars($_POST['service_title']);
        $serviceDescription = htmlspecialchars($_POST['service_description']);
        $serviceImage = $_FILES['service_image'];
    
        // Prepare the query to update the selected service
        $updateFields = [];
        $updateParams = [];
    
        if (!empty($serviceTitle)) {
            $updateFields[] = "services_title = ?";
            $updateParams[] = $serviceTitle;
        }
    
        if (!empty($serviceDescription)) {
            $updateFields[] = "services_description = ?";
            $updateParams[] = $serviceDescription;
        }
    
        // Handle image update
        if (isset($serviceImage) && $serviceImage['error'] === 0) {
            // Validate file type and size
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($serviceImage['type'], $allowedTypes)) {
                die("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }
    
            if ($serviceImage['size'] > 2 * 1024 * 1024) {
                die("File size exceeds the 2MB limit.");
            }
    
            // Delete the existing image if there is one
            if (!empty($selectedService['services_image'])) {
                // You can add logic here to delete the image from your server if you're storing it as a file
                // For example:
                // $filePath = 'path/to/images/' . $selectedService['services_image'];
                // if (file_exists($filePath)) {
                //     unlink($filePath); // Delete the old image file
                // }
            }
    
            // Read the new image content
            $imageData = file_get_contents($serviceImage['tmp_name']);
            $updateFields[] = "services_image = ?";
            $updateParams[] = $imageData;
        }
    
        // If there are fields to update, update the service
        if (!empty($updateFields)) {
            $updateParams[] = $selectedService['services_id']; // Add service ID to parameters
            $sql = "UPDATE services SET " . implode(", ", $updateFields) . " WHERE services_id = ?";
            
            // Debugging output (to check the query)
            // echo "SQL Query: $sql\n";
            // echo "Update Params: ";
            // print_r($updateParams); // Print the parameters to check for mismatch
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateParams);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Handle deleting the selected service
    if (isset($_POST['delete_service'])) {
        if (isset($selectedService['services_id'])) {
            $stmt = $pdo->prepare("DELETE FROM services WHERE services_id = ?");
            $stmt->execute([$selectedService['services_id']]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// FAQs Function Start
$faqs = [];

// Fetch all FAQs for dropdown
$stmt = $pdo->query("SELECT faq_id, question, answer FROM faqs");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize $faqs_data to handle form persistence
$faqs_data = null;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Fetch selected FAQ for update or delete if faq_id is provided
    if (isset($_POST['faq_id']) && !empty($_POST['faq_id'])) {
        $selectedFaqId = $_POST['faq_id'];

        // Fetch the selected FAQ details from the database
        $stmt = $pdo->prepare('SELECT * FROM faqs WHERE faq_id = :id');
        $stmt->execute(['id' => $selectedFaqId]);
        $faqs_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle Upload (Add FAQ)
    if (isset($_POST['upload'])) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);

        if (!empty($question) && !empty($answer)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO faqs (question, answer) VALUES (?, ?)");
                $stmt->execute([$question, $answer]);
                header("Location: " . $_SERVER['PHP_SELF']); // Redirect to refresh
                exit();
            } catch (Exception $e) {
                echo "Error adding FAQ: " . $e->getMessage();
            }
        } else {
            echo "Question and Answer cannot be empty!";
        }
    }

    // Handle Update FAQ
    if (isset($_POST['update']) && $faqs_data) {
        $question = trim($_POST['question']);
        $answer = trim($_POST['answer']);

        // Update only if question and answer are provided
        if (!empty($question) && !empty($answer)) {
            try {
                $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ? WHERE faq_id = ?");
                $stmt->execute([$question, $answer, $faqs_data['faq_id']]);
                header("Location: " . $_SERVER['PHP_SELF']); // Refresh page after update
                exit();
            } catch (Exception $e) {
                echo "Error updating FAQ: " . $e->getMessage();
            }
        } else {
            echo "Question and Answer cannot be empty!";
        }
    }

    // Handle Delete FAQ
    if (isset($_POST['delete']) && $faqs_data) {
        try {
            $stmt = $pdo->prepare("DELETE FROM faqs WHERE faq_id = ?");
            $stmt->execute([$faqs_data['faq_id']]);
            header("Location: " . $_SERVER['PHP_SELF']); // Refresh page after deletion
            exit();
        } catch (Exception $e) {
            echo "Error deleting FAQ: " . $e->getMessage();
        }
    }
}
// FAQs Function End
?>


<?php include('../../Components/header.php'); ?>
<div class="content">
<!-- Upload Form Section -->
<div id="uploadForm" class="upload-form">
    <!-- Company Images Section -->
    <div id="companyImagesForm" class="form-section">
        <!-- Upload and Delete Company Image Form -->
        <form action="" method="POST" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <input type="file" name="company_images[]" multiple class="input-file" accept="image/jpeg, image/png, image/gif">
            </div>
            <div class="form-group">
                <label for="select_company_image" class="label">Select Company Image to Delete:</label>
                <select id="select_company_image" name="selected_company_image" class="input-select">
                    <option value="">Select a company image...</option>
                    <?php
                    // Fetch company images from the database
                    $imgStmt = $pdo->query("SELECT image_id, image_type FROM company_images");
                    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($images as $image) {
                        echo "<option value='" . htmlspecialchars($image['image_id']) . "'>Image ID: " . htmlspecialchars($image['image_id']) . " (" . htmlspecialchars($image['image_type']) . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Upload and Delete Buttons -->
            <div class="button-group">
                <input type="submit" name="upload_company_images" value="Upload" class="button">
                <input type="submit" name="delete_company_image" value="Delete" class="button">
            </div>
        </form>
    </div>

    <!-- Introduction Section -->
    <div id="introductionForm" class="form-section">
        <form action="" method="POST" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <label for="who_we_are" class="label">Who We Are:</label>
                <textarea name="who_we_are" class="input-textarea" required><?php echo htmlspecialchars($orgInfo['who_we_are']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="our_vision" class="label">Our Vision:</label>
                <textarea name="our_vision" class="input-textarea" required><?php echo htmlspecialchars($orgInfo['our_vision']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="our_mission" class="label">Our Mission:</label>
                <textarea name="our_mission" class="input-textarea" required><?php echo htmlspecialchars($orgInfo['our_mission']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="ceo_message" class="label">CEO Message:</label>
                <textarea name="ceo_message" class="input-textarea" required><?php echo htmlspecialchars($orgInfo['ceo_message']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="address" class="label">Address:</label>
                <input type="text" name="address" class="input-text" required value="<?php echo htmlspecialchars($orgInfo['address']); ?>">
            </div>
            <div class="form-group">
                <label for="contact_number" class="label">Contact Number:</label>
                <input type="text" name="contact_number" class="input-text" required value="<?php echo htmlspecialchars($orgInfo['contact_number']); ?>">
            </div>
            <div class="form-group">
                <label for="facebook_link" class="label">Facebook Link:</label>
                <input type="text" name="facebook_link" class="input-text" required value="<?php echo htmlspecialchars($orgInfo['facebook_link']); ?>">
            </div>
            <div class="button-group">
                <input type="submit" name="update_organization" value="Update" class="button">
            </div>
        </form>
    </div>

    <!-- Certificates Section -->
    <div id="certificatesForm" class="form-section">
        <!-- Upload and Delete Certificate Form -->
        <form action="" method="POST" enctype="multipart/form-data" class="form">
            <div class="form-group">
                <label for="certificate_images" class="label">Upload Certificate Images:</label>
                <input type="file" name="certificate_images[]" multiple class="input-file" accept="image/jpeg, image/png, image/gif">
            </div>
            <div class="form-group">
                <label for="select_certificate" class="label">Select Certificate to Delete:</label>
                <select id="select_certificate" name="selected_certificate" class="input-select">
                    <option value="">Select a certificate...</option>
                    <?php
                    // Fetch certificates from the database
                    $certStmt = $pdo->query("SELECT certificate_id, image_type FROM certificates");
                    $certificates = $certStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($certificates as $certificate) {
                        echo "<option value='" . htmlspecialchars($certificate['certificate_id']) . "'>Certificate ID: " . htmlspecialchars($certificate['certificate_id']) . " (" . htmlspecialchars($certificate['image_type']) . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Upload and Delete Buttons -->
            <div class="button-group">
                <input type="submit" name="upload_certificates" value="Upload" class="button">
                <input type="submit" name="delete_certificate" value="Delete" class="button">
            </div>
        </form>
    </div>

    <!-- Services Section -->
    <div id="servicesForm" class="form-section">
        <form action="" method="POST" enctype="multipart/form-data" class="form">
            <!-- Select Service Dropdown -->
            <div class="form-group">
                <label for="select_service" class="label">Select Service:</label>
                <select id="select_service" name="selected_service" class="input-select" onchange="this.form.submit()">
                    <option value="">Select a service...</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo htmlspecialchars($service['services_id']); ?>"
                            <?php echo (isset($selectedService) && $selectedService['services_id'] == $service['services_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service['services_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Service Title -->
            <div class="form-group">
                <label for="service_title" class="label">Service Title:</label>
                <input type="text" name="service_title" class="input-text" required value="<?php echo isset($selectedService['services_title']) ? htmlspecialchars($selectedService['services_title']) : ''; ?>">
            </div>

            <!-- Service Description -->
            <div class="form-group">
                <label for="service_description" class="label">Service Description:</label>
                <textarea name="service_description" class="input-textarea" required><?php echo isset($selectedService['services_description']) ? htmlspecialchars($selectedService['services_description']) : ''; ?></textarea>
            </div>

            <!-- Service Image (Display existing image if selected) -->
            <div class="form-group">
                <label for="service_image" class="label">Upload Service Image:</label>
                <?php if (isset($selectedService['services_image']) && $selectedService['services_image']): ?>
                    <div>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($selectedService['services_image']); ?>" alt="Service Image" style="max-width: 200px; max-height: 200px;">
                        <div>
                            <label for="delete_image">Delete Image:</label>
                            <input type="checkbox" name="delete_image" id="delete_image" value="1">
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="service_image" class="input-file" accept="image/jpeg, image/png, image/gif">
            </div>

            <!-- Submit Buttons -->
            <div class="button-group">
                <input type="submit" name="upload_service" value="Upload" class="button">
                <input type="submit" name="update_service" value="Update" class="button" <?php echo !isset($selectedService) ? 'disabled' : ''; ?>>
                <input type="submit" name="delete_service" value="Delete" class="button" <?php echo !isset($selectedService) ? 'disabled' : ''; ?>>
            </div>
        </form>
    </div>

    <div id="faqForm" class="form-section">
    <form action="" method="POST" enctype="multipart/form-data" class="form">
        <!-- Select FAQ Dropdown -->
        <div class="form-group">
            <label for="faq_select" class="label">Select FAQ:</label>
            <select id="faq_select" name="faq_id" class="input-select" onchange="this.form.submit()">
                <option value="">Select an existing question...</option>
                <?php foreach ($faqs as $faq): ?>
                    <option value="<?php echo htmlspecialchars($faq['faq_id']); ?>"
                        <?php echo (isset($faqs_data['faq_id']) && $faqs_data['faq_id'] == $faq['faq_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($faq['question']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- FAQ Question -->
        <div class="form-group">
            <label for="faq_question" class="label">FAQ Question:</label>
            <textarea id="faq_question" name="question" class="input-textarea" required><?php echo isset($faqs_data['question']) ? htmlspecialchars($faqs_data['question']) : ''; ?></textarea>
        </div>
        
        <!-- FAQ Answer -->
        <div class="form-group">
            <label for="faq_answer" class="label">FAQ Answer:</label>
            <textarea id="faq_answer" name="answer" class="input-textarea" required><?php echo isset($faqs_data['answer']) ? htmlspecialchars($faqs_data['answer']) : ''; ?></textarea>
        </div>

        <!-- Action Buttons (Upload, Update, Delete) -->
        <div class="button-group">
            <input type="submit" id="add_faq_btn" name="upload" value="Upload" class="button">
            <input type="submit" id="update_faq_btn" name="update" value="Update" class="button">
            <input type="submit" id="delete_faq_btn" name="delete" value="Delete" class="button">
        </div>
    </form>
</div>
</div>
</div>

<?php include('../../Components/footer.php'); ?>

<!-- Add the CSS styles below -->
<style>
    .content{
        margin-left: 250px;
    }
/* Center the entire form container */

.upload-form {
    width: 1000px;
    max-width: 2000px; /* Increased max-width to 2000px for larger screens */
    padding: 30px; /* Added more padding for a spacious layout */
    background-color: white;
    border-radius: 12px; /* Slightly more rounded corners */
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); /* Softer, larger box shadow */
    margin: 20px; /* Added margin to ensure it doesn't touch the sides on smaller screens */
}

.section-title {
    text-align: center;
    margin-bottom: 30px;
    font-size: 28px; /* Increased font size for the section title */
    color: #333;
}

.form-section {
    margin-bottom: 30px;
}

.form {
    display: flex;
    flex-direction: column;
    gap: 20px; /* Increased gap between form fields */
}

.form-group {
    display: flex;
    flex-direction: column;
}

.label {
    font-weight: bold;
    margin-bottom: 10px; /* Increased margin for better spacing */
    color: black;
}

.input-file,
.input-text,
.input-textarea,
.input-select {
    padding: 12px; /* Increased padding for input fields */
    border: 1px solid #ccc;
    border-radius: 6px; /* Slightly rounder borders */
    font-size: 16px; /* Increased font size */
    color: #333;
}

.input-textarea {
    resize: vertical;
    height: 140px; /* Increased height for text areas */
}

.button-group {
    display: flex;
    gap: 20px;
    justify-content: center;
}

.button {
    padding: 15px 25px; /* Larger button size */
    border: none;
    background-color: #007bff;
    color: white;
    font-size: 18px; /* Slightly larger text on buttons */
    cursor: pointer;
    border-radius: 6px; /* Slightly more rounded buttons */
    transition: background-color 0.3s, transform 0.3s ease; /* Added transform for button hover effect */
}

.button:hover {
    background-color: #0056b3;
    transform: translateY(-2px); /* Slight lift effect on hover */
}

.button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 1024px) {
    .upload-form {
        width: 90%; /* Make the form 90% of the width on smaller screens */
        padding: 25px; /* Reduce padding */
    }

    .section-title {
        font-size: 24px; /* Reduce title size on medium screens */
    }

    .form {
        gap: 16px; /* Adjust gap between form fields */
    }

    .input-file,
    .input-text,
    .input-textarea,
    .input-select {
        font-size: 15px; /* Slightly smaller font size for inputs */
    }

    .button {
        font-size: 17px; /* Adjust button font size */
        padding: 14px 22px; /* Adjust padding for medium screens */
    }
}

/* Adjust for even smaller screens */
@media (max-width: 768px) {
    .upload-form {
        width: 90%; /* Make the form 90% of the width on smaller screens */
        padding: 20px; /* Reduce padding */
    }

    .section-title {
        font-size: 22px; /* Reduce title size on smaller screens */
    }

    .form {
        gap: 12px; /* Adjust gap between form fields */
    }

    .input-file,
    .input-text,
    .input-textarea,
    .input-select {
        font-size: 14px; /* Smaller font size for inputs */
    }

    .button {
        font-size: 16px; /* Smaller buttons on mobile */
        padding: 12px 20px; /* Adjust padding for mobile */
    }
}

</style>

<script>
    // Save the scroll position before form submission
    document.addEventListener('submit', function (event) {
        localStorage.setItem('scrollPosition', window.scrollY);
    });
    
    // Restore the scroll position after the page reloads
    window.onload = function () {
        const scrollPosition = localStorage.getItem('scrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition));
            localStorage.removeItem('scrollPosition'); // Clear the value after use
        }
    };
    // Function to save scroll position
    function saveScrollPosition() {
        localStorage.setItem('scrollPosition', window.scrollY);
    }

    // Save scroll position on form submission
    document.addEventListener('submit', saveScrollPosition);

    // Save scroll position on dropdown change
    const dropdowns = document.querySelectorAll('select');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', saveScrollPosition);
    });

    // Restore scroll position after page reload
    window.onload = function () {
        const scrollPosition = localStorage.getItem('scrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition));
            localStorage.removeItem('scrollPosition'); // Clear the value after use
        }
    };
</script>
