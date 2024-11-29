<?php
// Static privacy policy content
$privacyPolicy = "At EtourMo Travel and Tours, we are committed to protecting your privacy. We collect personal information such as your name, email, phone number, and payment details to process your bookings and provide customer support. We may also collect non-personal data like IP addresses and usage patterns to improve our services. We use industry-standard security measures to safeguard your information and may share it with third-party service providers to assist with our operations. We do not sell your personal data. We may update this policy occasionally, and any changes will be posted on our site. For questions, contact us at etourmo@gmail.com.";
?>
<?php include('header.php'); ?>
<?php include('./carousel/carousel.php'); ?>
<style>
.content{
    width:900px;
    margin-left:190px;
    margin-top:40px;
}

.privacy-policy-container{
    overflow
}
.privacy-policy-container h3 {
    font-size: 16px;
    color: #333;
    margin-bottom: 15px;
}

.privacy-policy-container p {
    font-size: 12px;
    color: #555;
    line-height: 1.6;
    margin-bottom: 20px;
}

/* Back to Home Link Styling */
.back-link-container {
    text-align: center;
}

.back-link-container a {
    font-size: 16px;
    color: #007BFF;
    text-decoration: none;
    padding: 10px;
    border: 1px solid #007BFF;
    border-radius: 5px;
    background-color: #ffffff;
    transition: background-color 0.3s, color 0.3s;
}

.back-link-container a:hover {
    background-color: #007BFF;
    color: #ffffff;
}
</style>
<div class="content">
    <div class="privacy-policy-container">
        <h3>Privacy Policy</h3>
        <p><?php echo nl2br(htmlspecialchars($privacyPolicy)); ?></p>
            <!-- Optional: Add a back link to the main page -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="../eTourMo Client/loginsignup.php">Back to Home</a>
    </div>
    </div>
</div>    
<?php include('footer.php'); ?>