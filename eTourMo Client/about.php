<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$host = 'localhost'; 
$db = 'etourmodb'; 
$user = 'root'; 
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Could not connect to the database: " . $e->getMessage()]));  // Return an error message if connection fails
}

// Fetching organization information
$organization_stmt = $pdo->query("SELECT * FROM organization_info LIMIT 1");
$organization_info = $organization_stmt->fetch(PDO::FETCH_ASSOC);

// Fetching company image
$company_image_stmt = $pdo->query("SELECT image_data FROM company_images LIMIT 1");
$company_images = $company_image_stmt->fetch(PDO::FETCH_ASSOC);

// Fetching certificates and convert to base64
$certificates_stmt = $pdo->query("SELECT certificate_id, image_data, image_type FROM certificates");
$certificates = $certificates_stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($certificates as &$certificate) {
    if ($certificate['image_data']) {
        $certificate['image_data'] = base64_encode($certificate['image_data']);  // Encode certificates to base64
    } else {
        $certificate['image_data'] = null;
    }
}

// Fetching services and convert to base64
$services_stmt = $pdo->query("SELECT services_id, services_image, services_title, services_description FROM services");
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($services as &$service) {
    if ($service['services_image']) {
        $service['services_image'] = base64_encode($service['services_image']);  // Encode services images to base64
    } else {
        $service['services_image'] = null;
    }
}

// Fetching FAQs
$faqs_stmt = $pdo->query("SELECT * FROM faqs");
$faqs = $faqs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare the response data array
$aboutData = [
    'organization_info' => $organization_info,
    'company_image' => isset($company_images['image_data']) ? base64_encode($company_images['image_data']) : null,
    'certificates' => $certificates,
    'services' => $services,
    'faqs' => $faqs
];

// Handle errors
$error = null;
if (empty($aboutData['organization_info'])) {
    $error = "Error fetching data from the database.";
}
?>

<?php include('header.php'); ?>
    <div class="about-us-container">
        <?php if (!$aboutData): ?>
            <div class="loading-message"><?php echo $error ? $error : 'Loading...'; ?></div>
        <?php else: ?>
        
        <div class="companyimage-whoweare-container">
            <div class="company-image-wrapper">
                <?php if (!empty($aboutData['company_image'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo $aboutData['company_image']; ?>" alt="About Us" class="about-image">
                <?php else: ?>
                    <p>No company image available</p>
                <?php endif; ?>
            </div>

            <div class="who-we-are-wrapper">
                <h3>Who We Are</h3>
                <p><?php echo $aboutData['organization_info']['who_we_are'] ?? "Default description about who we are."; ?></p>
            </div>
        </div>

        <div class="certification-image-container">
            <?php foreach ($aboutData['certificates'] as $certificate): ?>
                <div class="certification-image">
                    <div class="certificate-img">
                        <img src="data:image/jpeg;base64,<?php echo $certificate['image_data']; ?>" alt="<?php echo $certificate['certificate_title']; ?>" />
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="vision-mission-container">
            <div class="vision-container">
                <h3>Our Vision</h3>
                <p><?php echo $aboutData['organization_info']['our_vision'] ?? "Default vision statement."; ?></p>
            </div>
            <div class="mission-container">
                <h3>Our Mission</h3>
                <p><?php echo $aboutData['organization_info']['our_mission'] ?? "Default mission statement."; ?></p>
            </div>
        </div>

        <div class="ceo-message-container">
            <h3>A Message from the CEO</h3>
            <p><?php echo $aboutData['organization_info']['ceo_message'] ?? "Default CEO message."; ?></p>
        </div>

        <div class="location-container" style="display: flex; justify-content: center; align-items: center;">
            <div>
                <h3 style="text-align: center;">Location</h3>
                <iframe 
                    width="450" 
                    height="250" 
                    frameborder="0" 
                    style="border:0" 
                    src="https://www.google.com/maps?q=214+Abacan+Brgy+Calvario,+Meycauayan+City,+Bulacan,+Philippines&output=embed" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>

        <div class="service-container">
            <h3>Our Services</h3>
            <?php foreach ($aboutData['services'] as $service): ?>
                <div class="service">
                    <div class="service-image-container">
                        <?php if (!empty($service['services_image'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo $service['services_image']; ?>" alt="<?php echo $service['services_title']; ?>" class="service-image" style="width: 100%">
                        <?php else: ?>
                            <img src="path/to/your/default-service-image.jpg" alt="Default Service" class="service-image" />
                        <?php endif; ?>
                    </div>
                    <h3><?php echo $service['services_title'] ?? "Default Service Title"; ?></h3>
                    <p><?php echo $service['services_description'] ?? "Default service description."; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
    <?php include('footer.php'); ?>