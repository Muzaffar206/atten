<?php
session_start();
session_regenerate_id(true);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../assest/connection/config.php");

$errors = [];
$successes = [];

// Check if a file has been uploaded
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    $fileName = $_FILES['csv_file']['name'];
    $fileSize = $_FILES['csv_file']['size'];
    $fileType = $_FILES['csv_file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // Check if the uploaded file is a CSV
    if ($fileExtension === 'csv') {
        if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
            // Skip the header row
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Extract data from CSV
                list($username, $password, $confirm_password, $employer_id, $full_name, $email, $phone_number, $department, $role) = $data;

                // Validate and sanitize input
                $username = htmlspecialchars($username);
                $password = htmlspecialchars($password);
                $confirm_password = htmlspecialchars($confirm_password);
                $employer_id = htmlspecialchars($employer_id);
                $full_name = htmlspecialchars($full_name);
                $email = htmlspecialchars($email);
                $phone_number = htmlspecialchars($phone_number);
                $department = htmlspecialchars($department);
                $role = htmlspecialchars($role);

                // Check if the username, employer_id, or email already exists and is not deleted
                $sql_check = $conn->prepare("SELECT * FROM users WHERE username = ? OR employer_id = ? OR email = ?");
                $sql_check->bind_param("sss", $username, $employer_id, $email);
                $sql_check->execute();
                $result = $sql_check->get_result();

                if ($result->num_rows > 0) {
                    $errors[] = "Data for username '$username' already exists or has been deleted.";
                } else {
                    // Check if passwords match
                    if ($password !== $confirm_password) {
                        $errors[] = "Passwords for username '$username' do not match.";
                    } else {
                        // Hash password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                        // Insert user data into the database
                        $sql = $conn->prepare("INSERT INTO users (username, password, employer_id, full_name, email, phone_number, department, role) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $sql->bind_param("ssisssss", $username, $hashedPassword, $employer_id, $full_name, $email, $phone_number, $department, $role);

                        if ($sql->execute() === TRUE) {
                            $successes[] = "User '$username' registered successfully.";
                        } else {
                            $errors[] = "Error registering user '$username': " . $sql->error;
                        }
                        $sql->close();
                    }
                }
                $sql_check->close();
            }
            fclose($handle);
        }
    } else {
        $errors[] = "Uploaded file is not a CSV.";
    }
} else {
    $errors[] = "No file uploaded or there was an upload error.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Registration</title>
    <link rel="stylesheet" href="../path/to/bootstrap.css">
</head>
<body>
    <div class="container">
        <h1>Bulk User Registration</h1>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($successes)): ?>
            <div class="alert alert-success">
                <?php foreach ($successes as $success): ?>
                    <p><?php echo $success; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">Upload CSV File</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload and Register</button>
        </form>
    </div>
</body>
</html>
