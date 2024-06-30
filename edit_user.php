<?php
session_start();
include("assest/connection/config.php");

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect to login page if user is not an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $employer_id = $_POST['employer_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $department = $_POST['department'];

    // Check if password field is set and not empty
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Update user with password
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, password=?, address=?, department=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssi", $username, $employer_id, $full_name, $email, $phone_number, $password, $address, $department, $id);
    } else {
        // Update user without password change
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, address=?, department=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssi", $username, $employer_id, $full_name, $email, $phone_number, $address, $department, $id);
    }

    // Execute SQL statement
    if ($stmt->execute()) {
        header("Location: users.php");
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    // Fetch user data for editing
    $id = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
</head>
<body>
    <h1>Edit User</h1>
    <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        Username: <input type="text" name="username" value="<?php echo $user['username']; ?>" required><br>
        Employer ID: <input type="text" name="employer_id" value="<?php echo $user['employer_id']; ?>"><br>
        Full Name: <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>"><br>
        Email: <input type="email" name="email" value="<?php echo $user['email']; ?>"><br>
        Phone Number: <input type="text" name="phone_number" value="<?php echo $user['phone_number']; ?>"><br>
        Password: <input type="password" name="password"><br> <!-- Allow admin to set new password -->
        Passport Size Photo: <input type="file" name="passport_size_photo" accept="image/*"><br>
        Address: <textarea name="address"><?php echo $user['address']; ?></textarea><br>
        
        <!-- Select Department with default value from database -->
        Select the Department 
        <select name="department" id="department" required>
            <option value="">Select department</option>
            <option value="Education" <?php if ($user['department'] === 'Education') echo 'selected'; ?>>Education</option>
            <option value="Medical" <?php if ($user['department'] === 'Medical') echo 'selected'; ?>>Medical</option>
            <option value="ROP" <?php if ($user['department'] === 'ROP') echo 'selected'; ?>>ROP</option>
            <option value="Admin" <?php if ($user['department'] === 'Admin') echo 'selected'; ?>>Admin</option>
            <option value="Clinics" <?php if ($user['department'] === 'Clinics') echo 'selected'; ?>>Clinics</option>
        </select><br>
        
        <input type="submit" value="Update">
    </form>
</body>
</html>
