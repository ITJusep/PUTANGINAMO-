<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "etourmodb"; // Adjust this to your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle image upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $imageData = file_get_contents($_FILES["fileToUpload"]["tmp_name"]); // Get the image data as binary
    $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Check if the file is an actual image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (optional limit: 5MB)
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if everything is OK
    if ($uploadOk == 1) {
        // Save the image data (binary) in the database
        $stmt = $conn->prepare("INSERT INTO logo_images (image_data, image_type) VALUES (?, ?)");
        $stmt->bind_param("bs", $imageData, $imageFileType); // Bind binary and type
        $stmt->send_long_data(0, $imageData); // Send the BLOB data

        if ($stmt->execute()) {
            echo "The image has been uploaded and saved in the database.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Sorry, your file was not uploaded.";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
</head>
<body>

<h2>Upload Logo Image</h2>

<!-- Image Upload Form -->
<form action="" method="POST" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload" required>
    <input type="submit" value="Upload Image" name="submit">
</form>

</body>
</html>
