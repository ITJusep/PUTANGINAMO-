<?php
// Database connection parameters
$host = 'localhost';  // Change this to your database host (e.g., localhost)
$dbname = 'etourmodb'; // Change this to your database name
$username = 'root';  // Your MySQL username
$password = '';  // Your MySQL password

// Create a connection to the MySQL database using mysqli
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get testimonials and their corresponding booking information
$sql = "SELECT t.id, t.booking_id, t.testimonial, b.booking_id, b.booking_date
        FROM testimonial t
        LEFT JOIN bookings b ON t.booking_id = b.booking_id";

$result = $conn->query($sql);

?>
    <style>
        .content{
            font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 270px;
        }
        /* General table styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border: 1px solid #ddd;
        }

        /* Table header styles */
        .user-table thead {
            background-color: #f7fafc;
            color: #4a5568;
            text-align: left;
        }

        .user-table th {
            padding: 12px 15px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.875rem;
        }

        /* Table body styles */
        .user-table tbody {
            font-size: 0.875rem;
            color: #4a5568;
        }

        .user-table tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .user-table tr:hover {
            background-color: #f1f5f9;
        }

        .user-table td {
            padding: 12px 15px;
        }

        /* Link styles inside table */
        .user-table a {
            color: #3182ce;
            text-decoration: none;
        }

        .user-table a:hover {
            text-decoration: underline;
        }

        /* In case of no accounts */
        .no-accounts {
            font-size: 1.25rem;
            color: #e53e3e;
            text-align: center;
        }
    </style>
<?php include('../../Components/header.php'); ?>
<div class="content">
    <!-- Table to display testimonials -->
    <table class="user-table">
        <thead>
            <tr>
                <th>Testimonial ID</th>
                <th>Booking ID</th>
                <th>Booking Date</th>
                <th>Testimonial</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Check if there are any testimonials
            if ($result->num_rows > 0) {
                // Loop through each row and display the testimonial details
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['testimonial']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' class='no-accounts'>No testimonials found.</td></tr>";
            }

            // Close the database connection
            $conn->close();
            ?>
        </tbody>
    </table>
</div>
<?php include('../../Components/footer.php'); ?>