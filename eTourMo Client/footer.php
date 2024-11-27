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
    // Create a PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the latest policy from the database
    $stmt = $pdo->query("SELECT * FROM policies ORDER BY created_at DESC LIMIT 1");
    $policyData = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no data is found, set default values
    if (!$policyData) {
        $privacyPolicy = "This is a sample Privacy Policy. You can replace this text with your real privacy policy content.";
        $termsCondition = "This is a sample Terms and Conditions. You can replace this text with your real terms and conditions content.";
    } else {
        $privacyPolicy = $policyData['privacy_policy'];
        $termsCondition = $policyData['terms_condition'];
    }

} catch (PDOException $e) {
    // Handle database connection error
    die(json_encode(["error" => "Could not connect to the database: " . $e->getMessage()]));
}
?>

<style>
   /* Footer styling to make it stick to the bottom */
.footer-container {
    background-color: #333;
    color: #fff;
    text-align: center;
    padding: 0rem;
    width: 100%;
    box-sizing: border-box;
    height: 60px; /* Default height */
    position: fixed; /* Fix the footer at the bottom */
    bottom: 0; /* Align it to the bottom */
    left: 0;
    z-index: 100; /* Ensure it's on top of other content */
    display: flex;
    justify-content: center;
    align-items: center;
}

.rights-reserved-wrapper {
    margin: 0px; /* Add space below "All rights reserved" */
}

.footer-links {
    display: flex;
    justify-content: center; /* Horizontally center the links */
    gap: 15px; /* Space between the two links */
    margin-top: 0px; /* Ensures no additional space above the links */
    margin-bottom: 0px; /* Ensures no bottom margin is added */
    padding-bottom: 0px;
}

.privacy-policy-link,
.terms-and-conditions-link {
    text-decoration: underline;
    cursor: pointer;
}

.privacy-policy-link:hover,
.terms-and-conditions-link:hover {
    color: #007BFF; /* Optional: Change color when hovered */
}

/* Modal styling */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1; /* Sit on top of the page */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black with opacity */
    padding-top: 60px;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    box-sizing: border-box;
    max-width: 800px;
    border-radius: 10px;
}

/* Modal close button */
.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 25px;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.privacy-policy-container,
.terms-and-conditions-container {
    background-color: white;
    border-radius: 5px;
    color: #333;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden;
}

.privacy-policy-container h3,
.terms-and-conditions-container h3 {
    text-align: center;
    margin-top: 10px;
}

.privacy-policy-container p,
.terms-and-conditions-container p {
    text-align: justify;
    margin: 15px;
    word-wrap: break-word;
}

</style>
    <!-- Footer Section -->
    <footer class="footer-container">
        <div class="rights-reserved-wrapper">
            <p>© 2023 RightPath. All rights reserved.</p>
        </div>

        <div class="footer-links">
            <span id="privacy-policy-link" class="privacy-policy-link">Privacy Policy</span>
            | 
            <span id="terms-and-conditions-link" class="terms-and-conditions-link">Terms and Conditions</span>
        </div>
    </footer>

    <!-- Privacy Policy Modal -->
    <div id="privacy-policy-modal" class="modal">
        <div class="modal-content">
            <span class="close" id="close-privacy-policy-modal">&times;</span>
            <div class="privacy-policy-container">
                <h3>Privacy Policy</h3>
                <div class="privacy-policy-detail">
                    <p>
                        <?php echo nl2br(htmlspecialchars($privacyPolicy)); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div id="terms-and-conditions-modal" class="modal">
        <div class="modal-content">
            <span class="close" id="close-terms-and-conditions-modal">&times;</span>
            <div class="terms-and-conditions-container">
                <h3>Terms and Conditions</h3>
                <p>
                    <?php echo nl2br(htmlspecialchars($termsCondition)); ?>
                </p>
            </div>
        </div>
    </div>
</div>

    <script>
        // Modal functionality
        const privacyPolicyLink = document.getElementById("privacy-policy-link");
        const termsConditionsLink = document.getElementById("terms-and-conditions-link");

        const privacyPolicyModal = document.getElementById("privacy-policy-modal");
        const termsConditionsModal = document.getElementById("terms-and-conditions-modal");

        const closePrivacyPolicyModal = document.getElementById("close-privacy-policy-modal");
        const closeTermsConditionsModal = document.getElementById("close-terms-and-conditions-modal");

        // Open Privacy Policy modal
        privacyPolicyLink.onclick = function() {
            privacyPolicyModal.style.display = "block";
        };

        // Open Terms and Conditions modal
        termsConditionsLink.onclick = function() {
            termsConditionsModal.style.display = "block";
        };

        // Close Privacy Policy modal
        closePrivacyPolicyModal.onclick = function() {
            privacyPolicyModal.style.display = "none";
        };

        // Close Terms and Conditions modal
        closeTermsConditionsModal.onclick = function() {
            termsConditionsModal.style.display = "none";
        };

        // Close modals if clicked outside of the modal content
        window.onclick = function(event) {
            if (event.target === privacyPolicyModal) {
                privacyPolicyModal.style.display = "none";
            } else if (event.target === termsConditionsModal) {
                termsConditionsModal.style.display = "none";
            }
        };
    </script>

</body>
</html>
