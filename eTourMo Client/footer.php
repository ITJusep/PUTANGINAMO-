<style>
    /* Footer styling to make it stick to the bottom */
    .footer-container {
        background-color: #333;
        color: #fff;
        text-align: center;
        padding: 0rem;
        width: 100%;
        box-sizing: border-box;
        height: 60px;
        /* Default height */
        position: fixed;
        /* Fix the footer at the bottom */
        bottom: 0;
        /* Align it to the bottom */
        left: 0;
        z-index: 100;
        /* Ensure it's on top of other content */
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }

    .rights-reserved-wrapper {
        margin: 0px;
        /* Add space below "All rights reserved" */
    }

    .footer-links {
        display: flex;
        justify-content: center;
        /* Horizontally center the links */
        gap: 15px;
        /* Space between the two links */
        margin-top: 0px;
        /* Ensures no additional space above the links */
        margin-bottom: 0px;
        /* Ensures no bottom margin is added */
        padding-bottom: 0px;
    }

    .privacy-policy-link,
    .terms-and-conditions-link {
        text-decoration: underline;
        cursor: pointer;
    }

    .privacy-policy-link:hover,
    .terms-and-conditions-link:hover {
        color: #007BFF;
        /* Optional: Change color when hovered */
    }

    /* Modal styling */
    .modal {
        display: none;
        /* Hidden by default */
        position: fixed;
        z-index: 1;
        /* Sit on top of the page */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0, 0, 0);
        /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4);
        /* Black with opacity */
        padding-top: 60px;
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
        min-height: 40vh;
        max-height: 40vh;
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
        <p>© 2024 eTourMo Travel and Tours. All rights reserved.</p>
    </div>

    <div class="footer-links">
        <a href="privacy_policy.php" class="privacy-policy-link">Privacy Policy</a>
        |
        <a href="terms_conditions.php" class="terms-and-conditions-link">Terms and Conditions</a>
    </div>
</footer>

