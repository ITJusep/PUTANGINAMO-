<?php
session_start(); // Start the session

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
}

// Fetch messages from the database
$stmt = $pdo->prepare("SELECT * FROM messages ORDER BY created_at DESC");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission to mark messages as viewed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['viewed_messages'])) {
    // Update the status of the message to 'viewed' (assuming you know the message ID)
    $messageId = $_POST['message_id'];
    $updateStmt = $pdo->prepare("UPDATE messages SET status = 'viewed' WHERE id = :id");
    $updateStmt->execute(['id' => $messageId]);
    $_SESSION['viewed_messages'] = true;
}
?>
<?php include('../../Components/header.php'); ?>
<!-- Notification for new messages -->
<?php if (isset($_SESSION['new_messages_notification'])): ?>
    <div id="notification" class="notification">
        <strong>You have <?= $newMessagesCount ?> new message(s)!</strong>
    </div>
    <?php unset($_SESSION['new_messages_notification']); ?>
<?php endif; ?>

<form method="post" class="form-container">
    <div class="table-container">
        <table class="message-table">
            <thead>
                <tr class="table-header">
                    <th class="table-cell">Name</th>
                    <th class="table-cell">Email</th>
                    <th class="table-cell">Subject</th>
                    <th class="table-cell">Date</th>
                    <th class="table-cell">Status</th>
                    <th class="table-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr class="table-row">
                        <td class="table-cell"><?= htmlspecialchars($message['name']) ?></td>
                        <td class="table-cell"><?= htmlspecialchars($message['email']) ?></td>
                        <td class="table-cell"><?= htmlspecialchars($message['subject']) ?></td>
                        <td class="table-cell"><?= htmlspecialchars($message['created_at']) ?></td>
                        <td class="table-cell"><?= htmlspecialchars($message['status']) ?></td>
                        <td class="table-cell">
                            <a href="#" class="view-message-link" 
                               data-message='<?= json_encode($message) ?>'>View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<!-- Modal Structure -->
<div id="messageModal" class="modal hidden">
    <div class="modal-content">
        <h2 class="modal-title" id="modalSubject"></h2>
        <p><strong>Name:</strong> <span id="modalName"></span></p>
        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
        <p><strong>Message:</strong> <span id="modalMessage"></span></p>
        <p><strong>Date:</strong> <span id="modalDate"></span></p>
        <button class="close-modal">Close</button>
    </div>
</div>

<script>
document.querySelectorAll('.view-message-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Retrieve the message data from the clicked link's data-message attribute
        const message = JSON.parse(this.getAttribute('data-message'));
        
        // Fill modal with message data
        document.getElementById('modalSubject').innerText = message.subject;
        document.getElementById('modalName').innerText = message.name;
        document.getElementById('modalEmail').innerText = message.email;
        document.getElementById('modalMessage').innerText = message.message;
        document.getElementById('modalDate').innerText = message.created_at;

        // Show the modal by removing the 'hidden' class
        document.getElementById('messageModal').classList.remove('hidden');
        
        // Mark message as viewed (via AJAX POST request)
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'viewed_messages=true&message_id=' + message.id
        });
    });
});

// Close the modal when the 'close-modal' button is clicked
document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('messageModal').classList.add('hidden');  // Hide the modal
    location.reload();  // Optionally reload the page to reflect changes (e.g., mark as viewed)
});
</script>

<?php include('../../Components/footer.php'); ?>

<!-- Additional CSS -->
<style>
.form-container{
    margin-top: 50px;
    margin-left: 200px;
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

    .message-table {
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

    .view-message-link {
        color: #3182ce;
        text-decoration: none;
    }

    .view-message-link:hover {
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
