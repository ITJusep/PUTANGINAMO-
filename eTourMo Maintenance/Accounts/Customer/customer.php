<?php
// Database connection
$host = 'localhost';
$dbname = 'etourmodb';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error connecting to the database: " . $e->getMessage());
}

// Handle search query (if any)
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// SQL query to fetch user profiles
$sql = "SELECT user_id, firstname, lastname, email FROM user_profiles";

// If there is a search term, modify the query to filter users
if ($searchTerm) {
    $sql .= " WHERE firstname LIKE :searchTerm OR lastname LIKE :searchTerm";
}

$stmt = $pdo->prepare($sql);

// Bind the search term to the query (if provided)
if ($searchTerm) {
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include('../../Components/header.php'); ?>
<style>
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 150px;
    background-color: #F3F3E0;
    height: 1000px;
    padding: 100px;
}

    .content{
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    margin-left: 100px;
    margin-top: 50px;
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

    /*New*/
    
        /* Form container */
.search-form {
    max-width: 28rem; /* max-w-md */
    margin: 0 auto;   /* mx-auto */
}

/* Label styling */
.search-label {
    margin-bottom: 0.5rem; /* mb-2 */
    font-size: 0.875rem;   /* text-sm */
    font-weight: 500;      /* font-medium */
    color: #1f2937;        /* text-gray-900 */
    display: none;         /* sr-only, hides label visually but keeps it accessible */
}

/* Search container */
.search-container {
    position: relative;
}

/* Search icon styling */
.search-icon {
    position: absolute;
    top: 0;
    left: 0;
    display: flex;
    align-items: center;
    padding-left: 0.75rem;  /* ps-3 */
    pointer-events: none;
}

.search-svg {
    width: 1rem;  /* w-4 */
    height: 1rem; /* h-4 */
    color: #6b7280;  /* text-gray-500 */
}

/* Input field */
.input {
    display: block;
    width: 400px;
    padding: 1rem 1rem 1rem 2.5rem;  /* p-4 ps-10 */
    font-size: 0.875rem;  /* text-sm */
    color: #111827;       /* text-gray-900 */
    border: 1px solid #d1d5db;  /* border-gray-300 */
    border-radius: 0.5rem; /* rounded-lg */
    transition: border-color 0.2s, box-shadow 0.2s;
    margin-bottom: 30px;
}

.search-input:focus {
    border-color: #3b82f6;  /* focus:border-blue-500 */
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);  /* focus:ring-blue-500 */
}

/* Dark mode styles */
@media (prefers-color-scheme: dark) {
    .search-label {
        color: #ffffff;  /* dark:text-white */
    }
    .search-icon svg {
        color: #9ca3af;  /* dark:text-gray-400 */
    }
    .search-input {
        background-color: #374151;  /* dark:bg-gray-700 */
        border-color: #4b5563;      /* dark:border-gray-600 */
        color: #ffffff;             /* dark:text-white */
    }
    .search-input:focus {
        border-color: #3b82f6;      /* dark:focus:border-blue-500 */
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);  /* dark:focus:ring-blue-500 */
    }
}

/* Button styling */
.search-button {
    position: absolute;
    bottom: 0.625rem; /* bottom-2.5 */
    right: 0.625rem;  /* end-2.5 */
    background-color: #2563eb; /* bg-blue-700 */
    color: white;
    padding: 0.5rem 1rem; /* px-4 py-2 */
    font-size: 0.875rem;  /* text-sm */
    font-weight: 500;     /* font-medium */
    border-radius: 0.375rem;  /* rounded-lg */
    transition: background-color 0.2s, box-shadow 0.2s;
    border: none;
    cursor: pointer;
    margin-bottom: -4px;
    margin-right: 50px;
}

.search-button:hover {
    background-color: #1d4ed8;  /* hover:bg-blue-800 */
}

.search-button:focus {
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);  /* focus:ring-blue-300 */
    outline: none;
}

/* Dark mode button styles */
@media (prefers-color-scheme: dark) {
    .search-button {
        background-color: #4b7cf3;  /* dark:bg-blue-600 */
    }
    .search-button:hover {
        background-color: #3b5ebd;  /* dark:hover:bg-blue-700 */
    }
    .search-button:focus {
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5);  /* dark:focus:ring-blue-800 */
    }
}
</style>

<div class="content">
<form class="search-form" method="GET" action="">
    <div class="search-container">
        <input type="search" id="default-search" name="search" class="input input-bordered join-item bg-[#CBDCEB] placeholder-black text-black" placeholder="Search Customer's Name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" required />
        <button type="submit" class="search-button">Search</button>
    </div>
</form>

<!-- Check if there are any user accounts -->
<!-- Check if there are any user accounts -->
<?php if ($users): ?>
    <!-- Loop through all users and display their information -->
    <table class="table table-lg text-black mt-12">
        <thead>
            <tr class="bg-[#608BC1] text-black">
                <th>Full Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <!-- Concatenate firstname and lastname to display full name -->
                    <td><?php echo htmlspecialchars($user['firstname']) . ' ' . htmlspecialchars($user['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td> 
                        <a href="/eTourMo Maintenance/Accounts/Customer/detail.php?id=<?php echo $user['user_id']; ?>">View</a>
                    </td>
                </tr> 
            <?php endforeach; ?> 
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align: center; margin-right:45px">No customers found matching your search.</p>
<?php endif; ?>
</div>
<?php include('../../Components/footer.php'); ?>
