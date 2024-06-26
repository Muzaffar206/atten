<?php
session_start();
include("assest/connection/config.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
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

    if (!empty($_FILES["passport_size_photo"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . time() . '_' . basename($_FILES["passport_size_photo"]["name"]);
        move_uploaded_file($_FILES["passport_size_photo"]["tmp_name"], $target_file);
        $passport_size_photo = $target_file;

        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, passport_size_photo=?, address=?, department=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssis", $username, $employer_id, $full_name, $email, $phone_number, $passport_size_photo, $address,$department, $id);
    } else {
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, address=?, department=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssis", $username, $employer_id, $full_name, $email, $phone_number, $address, $department, $id);
    }

    if ($stmt->execute()) {
        header("Location: users.php");
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
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
        Passport Size Photo: <input type="file" name="passport_size_photo" accept="image/*"><br>
        Address: <textarea name="address"><?php echo $user['address']; ?></textarea><br>
        
                            Select the Department 
                        <select name="department" id="department" required>
                              <option value="<?php echo $user['department']; ?>" >Select department</option>
                              <option value="Education">Education</option>
                              <option value="Medical">Medical</option>
                              <option value="ROP">ROP</option>
                              <option value="Admin">Admin</option>
                              <option value="Clinics">Clinics</option>
                        </select>
                        
        <input type="submit" value="Update">
    </form>
</body>
</html>
