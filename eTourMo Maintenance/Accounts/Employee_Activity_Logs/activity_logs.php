<?php
// Database connection
$servername = "localhost"; // Change if necessary
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "etourmodb"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch activity logs along with admin information from admin_accounts table
$sql = "SELECT al.log_id, a.admin_id, a.first_name, a.last_name, a.user_type, al.login_time, al.logout_time 
        FROM activity_logs al
        JOIN admin_accounts a ON al.admin_id = a.admin_id
        ORDER BY al.login_time DESC";
$result = $conn->query($sql);
?>

<?php include('../../Components/header.php'); ?>
<h2 class="text-6xl font-bold mb-4 text-black">Activity Logs</h2>
<div class="content">
<!-- Table with custom CSS classes -->
<table class="table table-lg text-black mt-12"  >
    <thead>
        <tr class="bg-[#608BC1] text-black">
            <th>Admin ID</th>
            <th>Full Name</th>
            <th>User Type</th>
            <th>Login Time</th>
            <th>Logout Time</th>
            <th>Duration</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Check if there are results
        if ($result->num_rows > 0) {
            // Output data of each row
            while($row = $result->fetch_assoc()) {
                $admin_id = htmlspecialchars($row["admin_id"]);
                $first_name = htmlspecialchars($row["first_name"]);
                $last_name = htmlspecialchars($row["last_name"]);
                $user_type = htmlspecialchars($row["user_type"]);
                $login_time = $row["login_time"];
                $logout_time = $row["logout_time"];

                // Check if both login_time and logout_time are present
                if ($logout_time) {
                    try {
                        // Create DateTime objects for login and logout times
                        $login_date = new DateTime($login_time);
                        $logout_date = new DateTime($logout_time);
                        
                        // Calculate the interval between login and logout
                        $interval = $login_date->diff($logout_date);
                        
                        // Format the interval into hours, minutes, seconds
                        $duration = $interval->format('%H:%I:%S');
                    } catch (Exception $e) {
                        // In case of an invalid DateTime format
                        $duration = "Invalid date format";
                    }
                } else {
                    // If no logout_time, show 'Not Logged Out' or a placeholder message
                    $duration = "Active Session"; // or leave this empty, depending on how you want to present it
                }

                // Display the row in the table
                echo "<tr>
                        <td>" . $admin_id . "</td>
                        <td>" . $first_name . " " . $last_name . "</td>
                        <td>" . $user_type . "</td>
                        <td>" . htmlspecialchars($login_time) . "</td>
                        <td>" . htmlspecialchars($logout_time ? $logout_time : "N/A") . "</td>
                        <td>" . $duration . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No activity logs found.</td></tr>";
        }
        ?>
    </tbody>
</table>
</div>
<?php
// Close the database connection
$conn->close();
?>
<?php include('../../Components/footer.php'); ?>

<style>
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 150px;
    background-color: #F3F3E0;
    height: 1040px;
    padding: 100px;
    overflow: hidden;
    }
    .content{
        margin-left: 10px;
        overflow: scroll;
        max-height: 800px;
    }
  /* Custom styles for the activity log table */
.activity-log-table {
    width: 1000px;
    border-collapse: collapse;
    background-color: #fff;
    border: 30px solid #ddd;
    margin: 20px auto; /* Centers the table horizontally */
}

.activity-log-table th, .activity-log-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
    font-size: 14px;
}

.activity-log-table th {
    background-color: #f4f4f4;
    color: #333;
    font-weight: bold;
}

.activity-log-table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.activity-log-table tbody tr:hover {
    background-color: #f1f1f1;
}

.activity-log-table td {
    color: #555;
}

.activity-log-table td[colspan="7"] { /* Adjusted colspan to 7 */
    text-align: center;
    color: #888;
}
</style>
