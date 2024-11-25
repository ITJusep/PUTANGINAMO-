<?php
session_start();

$host = 'localhost'; 
$db = 'etourmodb'; 
$user = 'root'; 
$pass = ''; 

// Set the timezone
date_default_timezone_set('America/New_York');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Fetch trip planner data from the database
$stmt = $pdo->prepare("SELECT * FROM trip_planner ORDER BY id DESC");
$stmt->execute();
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission to mark trips as viewed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['viewed_trips'])) {
    $tripId = $_POST['trip_id'];
    $updateStmt = $pdo->prepare("UPDATE trip_planner SET status = 'viewed' WHERE id = :id");
    $updateStmt->execute(['id' => $tripId]);
    $_SESSION['viewed_trips'] = true;
}
?>
<?php include('../../Components/header.php'); ?>
<div class="content">
    <!-- Notification for new trip planners -->
    <?php if (isset($_SESSION['new_trips_notification'])): ?>
        <div id="notification" class="notification">
            <strong>You have <?= htmlspecialchars($newTripsCount ?? 0) ?> new trip planner(s)!</strong>
        </div>
        <?php unset($_SESSION['new_trips_notification']); ?>
    <?php endif; ?>

    <form method="post" class="form-container">
        <div class="table-container">
            <table class="trip-table">
                <thead>
                    <tr class="table-header">
                        <th class="table-cell">Name</th>
                        <th class="table-cell">Email</th>
                        <th class="table-cell">Destination</th>
                        <th class="table-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr class="table-row">
                            <td class="table-cell"><?= htmlspecialchars($trip['name'] ?? 'N/A') ?></td>
                            <td class="table-cell"><?= htmlspecialchars($trip['email'] ?? 'N/A') ?></td>
                            <td class="table-cell"><?= htmlspecialchars($trip['destination'] ?? 'N/A') ?></td>
                            <td class="table-cell">
                                <a href="#" class="view-trip-link" 
                                   data-trip='<?= json_encode($trip) ?>'>View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Modal Structure -->
    <div id="tripModal" class="modal hidden">
        <div class="modal-content">
            <h2 class="modal-title">Trip Details</h2>
            <p><strong>Destination:</strong> <span id="modalDestination"></span></p>
            <p><strong>Name:</strong> <span id="modalName"></span></p>
            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
            <p><strong>Contact:</strong> <span id="modalContact"></span></p>
            <p><strong>Pax:</strong> <span id="modalPax"></span></p>
            <p><strong>Duration:</strong> <span id="modalDuration"></span></p>
            <p><strong>Itinerary:</strong> <span id="modalItinerary"></span></p>
            <button class="close-modal">Close</button>
        </div>
    </div>

    <script>
    document.querySelectorAll('.view-trip-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Retrieve the trip data from the clicked link's data-trip attribute
            const trip = JSON.parse(this.getAttribute('data-trip'));

            // Fill modal with trip data (fallback for undefined fields)
            document.getElementById('modalDestination').innerText = trip.destination || 'N/A';
            document.getElementById('modalName').innerText = trip.name || 'N/A';
            document.getElementById('modalEmail').innerText = trip.email || 'N/A';
            document.getElementById('modalContact').innerText = trip.contact || 'N/A';
            document.getElementById('modalPax').innerText = trip.pax || 'N/A';
            document.getElementById('modalDuration').innerText = trip.duration || 'N/A';
            document.getElementById('modalItinerary').innerText = trip.itinerary || 'N/A';

            // Show the modal
            document.getElementById('tripModal').classList.remove('hidden');

            // Mark trip as viewed (via AJAX POST request)
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `viewed_trips=true&trip_id=${trip.id}`
            });
        });
    });

    // Close the modal when the 'close-modal' button is clicked
    document.querySelector('.close-modal').addEventListener('click', function() {
        document.getElementById('tripModal').classList.add('hidden'); // Hide the modal
    });
    </script>
</div>
<?php include('../../Components/footer.php'); ?>

<!-- Additional CSS -->
<style>
    .content{
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 270px;
    margin-top: 50px;
    }
    .page-title {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .notification {
        background-color: #3182ce;
        color: white;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .form-container {
        display: flex;
        justify-content: center;
    }

    .table-container {
        overflow-x: auto;
    }

    .trip-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        border: 1px solid #e2e8f0;
    }

    .table-header {
        background-color: #edf2f7;
        color: #4a5568;
        font-size: 0.875rem;
        text-transform: uppercase;
    }

    .table-cell {
        padding: 12px;
        text-align: left;
    }

    .table-row:hover {
        background-color: #f7fafc;
    }

    .view-trip-link {
        color: #3182ce;
        text-decoration: none;
    }

    .view-trip-link:hover {
        text-decoration: underline;
    }

    /* Make the modal initially hidden */
.modal.hidden {
    display: none;
}

/* Modal container styles (unchanged) */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
}

/* Modal content styles */
.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-width: 600px;
    width: 100%;
}

    .modal-title {
        font-size: 1.25rem;
        font-weight: bold;
        margin-bottom: 12px;
    }

    .close-modal {
        background-color: #3182ce;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
    }

    .close-modal:hover {
        background-color: #2b6cb0;
    }
</style>
