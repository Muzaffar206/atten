<?php
session_start();
session_regenerate_id(true);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}
include("../assest/connection/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $username = htmlspecialchars($_POST['username']);
    $employer_id = htmlspecialchars($_POST['employer_id']);
    $full_name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $department = $_POST['department'];
    $role = $_POST['role'];
    
    // Initialize variables for new photo handling
    $new_photo = null;
    $old_photo = null;

    // Fetch existing photo for deletion if a new photo is uploaded
    $sql = "SELECT passport_size_photo FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $old_photo = $row['passport_size_photo'];
    }
    $stmt->close();
    // Track which fields were updated
    $updated_fields = [];
    $updates = [];

    // Check if a new photo is uploaded
    if (isset($_FILES['passport_size_photo']) && $_FILES['passport_size_photo']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['passport_size_photo']['tmp_name'];
        $fileName = $_FILES['passport_size_photo']['name'];
        $fileSize = $_FILES['passport_size_photo']['size'];
        $fileType = $_FILES['passport_size_photo']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Define allowed file extensions and upload directory
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $uploadDir = '../uploads/';
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadFileDir = $uploadDir . $newFileName;

        if (in_array($fileExtension, $allowedExtensions)) {
            if (move_uploaded_file($fileTmpPath, $uploadFileDir)) {
                $new_photo = $uploadFileDir;

                // Delete the old photo if it exists
                if ($old_photo && file_exists($old_photo)) {
                    unlink($old_photo);
                }
                 // Mark photo field as updated
                 $updates[] = 'Passport Size Photo';
            } else {
                echo "There was an error uploading the file.";
                exit();
            }
        } else {
            echo "Upload failed. Allowed file types: jpg, jpeg, png, gif.";
            exit();
        }
    }

    // Prepare SQL query
    if (!empty($_POST['password'])) {
        $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, password=?, department=?, role=?, passport_size_photo=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssi", $username, $employer_id, $full_name, $email, $phone_number, $password, $department, $role, $new_photo, $id);
        // Mark password field as updated
        $updates[] = 'Password';
    } else {
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, department=?, role=?, passport_size_photo=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssi", $username, $employer_id, $full_name, $email, $phone_number, $department, $role, $new_photo, $id);
    }

    // Execute SQL statement
    if ($stmt->execute()) {
      // Add updated fields to message
      $updated_fields = implode(', ', $updates);
      $_SESSION['message'] = "User updated successfully! Updated fields: $updated_fields";
      header("Location: users.php");
      exit();
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

<?php include("include/header.php"); ?>
<?php include("include/topbar.php"); ?>
<?php include("include/sidebar.php"); ?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Edit User: <?php echo $user['username']; ?></h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Edit User</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Edit User Details</h3>
            </div>
            <!-- /.card-header -->
            <!-- form start -->
            <form method="post" action="" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
              <div class="card-body">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                </div>
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" class="form-control" name="password">
                  <small class="text-muted">Leave blank if not changing.</small>
                </div>
                <div class="form-group">
                  <label>Employee ID</label>
                  <input type="text" class="form-control" name="employer_id" value="<?php echo $user['employer_id']; ?>">
                </div>
                <div class="form-group">
                  <label>Full Name</label>
                  <input type="text" class="form-control" name="full_name" value="<?php echo $user['full_name']; ?>">
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>">
                </div>
                <div class="form-group">
                  <label>Phone Number</label>
                  <input type="text" class="form-control" name="phone_number" value="<?php echo $user['phone_number']; ?>">
                </div>
                <div class="form-group">
                  <label>Select Department</label>
                  <select name="department" class="form-control">
                    <option value="">Select department</option>
                    <option value="Education" <?php echo ($user['department'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                    <option value="Medical" <?php echo ($user['department'] == 'Medical') ? 'selected' : ''; ?>>Medical</option>
                    <option value="ROP" <?php echo ($user['department'] == 'ROP') ? 'selected' : ''; ?>>ROP</option>
                    <option value="Admin" <?php echo ($user['department'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="Newspaper" <?php echo ($user['department'] == 'Newspaper') ? 'selected' : ''; ?>>Newspaper</option>
                    <option value="Others" <?php echo ($user['department'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Role</label>
                  <select name="role" class="form-control">
                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Passport Size Photo</label>
                  <input type="file" class="form-control" name="passport_size_photo">
                  <?php if (!empty($user['passport_size_photo'])): ?>
                    <br>
                    <img src="<?php echo $user['passport_size_photo']; ?>" alt="User Photo" style="width: 150px;">
                  <?php endif; ?>
                </div>
              </div>
              <!-- /.card-body -->

              <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update User</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
<?php include("include/footer.php"); ?>
