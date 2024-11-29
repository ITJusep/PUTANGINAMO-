<?php
// Static terms and conditions content
$termsCondition = "By booking travel services through this platform, you agree to abide by the following terms and conditions. All bookings are subject to availability and confirmation by the respective service provider. Prices quoted are subject to change without notice and are not guaranteed until the booking is confirmed. Payment is required in full at the time of booking, and any additional charges incurred during the trip are your responsibility. Cancellations and changes to bookings may be subject to fees and penalties imposed by the service provider. We act only as an intermediary between you and the service providers and do not assume any liability for the actions, errors, or omissions of these providers. Travel insurance is highly recommended to protect against unforeseen circumstances such as trip cancellations, delays, or medical emergencies. By booking through this platform, you acknowledge that you have read, understood, and agree to these terms and conditions, as well as any specific terms imposed by the service providers.";
?>
<?php include('header.php'); ?>
<?php include('./carousel/carousel.php'); ?>
<style>
/* General body styling */

.content{
    width:900px;
    margin-left:190px;
    margin-top:40px;
}

/* Terms and Conditions Container Styling */


.terms-and-conditions-container h3 {
    font-size: 16px;
    color: #333;
    margin-bottom: 15px;
}

.terms-and-conditions-container p {
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
    <div class="terms-and-conditions-container">
        <h3>Terms and Conditions</h3>
        <p><?php echo nl2br(htmlspecialchars($termsCondition)); ?></p>
            <!-- Optional: Add a back link to the main page -->
    <div style="text-align: center; margin-top: 20px;">
        <a href="../eTourMo Client/loginsignup.php">Back to Home</a>
    </div>
    </div>
</div>
<?php include('footer.php'); ?>
