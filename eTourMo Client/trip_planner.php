<?php
// db_config.php
$servername = "localhost";
$username = "root";   // your database username
$password = "";       // your database password
$dbname = "etourmodb"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = '';  // Variable to hold success message

if (isset($_POST['submit'])) {

    // Get data from form
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $pax = mysqli_real_escape_string($conn, $_POST['pax']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $itinerary = mysqli_real_escape_string($conn, $_POST['itinerary']);

    // SQL query to insert data into the database
    $sql = "INSERT INTO trip_planner (name, email, contact, destination, pax, duration, itinerary)
            VALUES ('$name', '$email', '$contact', '$destination', '$pax', '$duration', '$itinerary')";

    // Execute the query and check if the insertion was successful
    if ($conn->query($sql) === TRUE) {
        $successMessage = 'New record created successfully';
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close the database connection
    $conn->close();
}
?>  

<style>
    /* style.css */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.trip-planner-container {
    width: 50%;
    margin: 50px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

h2 {
    text-align: center;
}

label {
    display: block;
    margin: 10px 0 5px;
}

input, textarea {
    width: 100%;
    padding: 10px;
    margin: 5px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    display: block;
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #45a049;
}

/* Style for the success message */
.success-message {
    margin-top: 20px;
    padding: 10px;
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
}
</style>

<?php include('header.php'); ?>

<div class="trip-planner-container">
    <h2>Trip Planner Form</h2>
    <form action="trip_planner.php" method="POST">
        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="contact">Contact Number:</label>
        <input type="text" id="contact" name="contact" required>

        <label for="destination">Destination:</label>
        <input type="text" id="destination" name="destination" required>

        <label for="pax">Number of Pax:</label>
        <input type="text" id="pax" name="pax" required>

        <label for="duration">Duration:</label>
        <input type="text" id="duration" name="duration" required>

        <label for="itinerary">Itinerary:</label>
        <textarea id="itinerary" name="itinerary" rows="4" required></textarea>

        <button type="submit" name="submit">Submit</button>
    </form>

    <?php if ($successMessage): ?>
        <div class="success-message">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
