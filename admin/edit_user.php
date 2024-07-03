<?php
session_start();
include("../assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
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
include("include/header.php");
include("include/topbar.php");
include("include/sidebar.php");
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Edit user</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">edit</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

<section class="content">
      <div class="container-fluid">
        <div class="row">
          <!-- left column -->
          <div class="col-md-6">
            <!-- general form elements -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Username : <?php echo $user['username']; ?></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
    <form method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
    <div class="card-body">
        <div class="form-group">
        <label>Username</label>
        <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>"  required>
    </div>
    <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control"  name="password"> <!-- Admin to set new password -->
                  </div>

                  <div class="form-group">
                    <label>Employee id</label>
                    <input type="text" class="form-control" value="<?php echo $user['employer_id']; ?>" name="employer_id">
                  </div>
                  <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control"  value="<?php echo $user['full_name']; ?>" name="full_name">
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input type="Email" class="form-control" value="<?php echo $user['email']; ?>"  name="email" >
                  </div>
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" class="form-control" value="<?php echo $user['phone_number']; ?>" name="phone_number">
                  </div>
                  <div class="form-group">
                  <label >Address</label>
                  <textarea class="form-control" name="address"><?php echo $user['address']; ?></textarea>
                  </div>
                  <div class="form-group">
                  <label>Select department</label>
                  <select name="department" id="department" class="form-control select2" style="width: 100%;">
            <option value="">Select department</option>
            <option value="Education" <?php if ($user['department'] === 'Education') echo 'selected'; ?>>Education</option>
            <option value="Medical" <?php if ($user['department'] === 'Medical') echo 'selected'; ?>>Medical</option>
            <option value="ROP" <?php if ($user['department'] === 'ROP') echo 'selected'; ?>>ROP</option>
            <option value="Admin" <?php if ($user['department'] === 'Admin') echo 'selected'; ?>>Admin</option>
            <option value="Clinics" <?php if ($user['department'] === 'Clinics') echo 'selected'; ?>>Clinics</option>
        </select>
        </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" value="Update" class="btn btn-primary">Submit</button>
                </div>
              </form>
            </div>







       
    <?php    include("include/footer.php"); ?>