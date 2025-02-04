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
    $department = htmlspecialchars($_POST['department']);
    $role = htmlspecialchars($_POST['role']); // Handle role as a string

    // Check if password is provided
    $password = !empty($_POST['password']) ? password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT) : null;

    // Prepare SQL statement based on whether password is provided
    if ($password) {
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, password=?, department=?, role=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssssi", $username, $employer_id, $full_name, $email, $phone_number, $password, $department, $role, $id);
    } else {
        $sql = "UPDATE users SET username=?, employer_id=?, full_name=?, email=?, phone_number=?, department=?, role=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssi", $username, $employer_id, $full_name, $email, $phone_number, $department, $role, $id);
    }

    // Debugging to check the prepared statement and parameters
    echo "SQL: $sql<br>";
    echo "Bound Params: $username, $employer_id, $full_name, $email, $phone_number, $department, $role, $id<br>";
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User updated successfully!";
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
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Edit User: <?php echo htmlspecialchars($user['username']); ?></h1>
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

  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Edit User Details</h3>
            </div>
            <form method="post" action="">
              <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
              <div class="card-body">
                <div class="form-group">
                  <label>Username</label>
                  <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" class="form-control" name="password">
                  <small class="text-muted">Leave blank if not changing.</small>
                </div>
                <div class="form-group">
                  <label>Employee ID</label>
                  <input type="text" class="form-control" name="employer_id" value="<?php echo htmlspecialchars($user['employer_id']); ?>">
                </div>
                <div class="form-group">
                  <label>Full Name</label>
                  <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                <div class="form-group">
                  <label>Phone Number</label>
                  <input type="text" class="form-control" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                </div>
                <div class="form-group">
                  <label>Select Department</label>
                  <select name="department" class="form-control">
                    <option value="">Select department</option>
                    <!-- List of departments -->
                    <option value="Education" <?php echo ($user['department'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                    <option value="Medical" <?php echo ($user['department'] == 'Medical') ? 'selected' : ''; ?>>Medical</option>
                    <option value="ROP" <?php echo ($user['department'] == 'ROP') ? 'selected' : ''; ?>>ROP</option>
                    <option value="Admin" <?php echo ($user['department'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="Accounts" <?php echo ($user['department'] == 'Accounts') ? 'selected' : ''; ?>>Accounts</option>
                    <option value="FRD" <?php echo ($user['department'] == 'FRD') ? 'selected' : ''; ?>>FRD</option>
                    <option value="Newspaper" <?php echo ($user['department'] == 'Newspaper') ? 'selected' : ''; ?>>Newspaper</option>
                    <option value="RC Mahim" <?php echo ($user['department'] == 'RC Mahim') ? 'selected' : ''; ?>>RC Mahim</option>
                    <option value="Study centre" <?php echo ($user['department'] == 'Study centre') ? 'selected' : ''; ?>>Study centre</option>
                    <option value="Clinics" <?php echo ($user['department'] == 'Clinics') ? 'selected' : ''; ?>>Clinics</option>
                    <option value="EO" <?php echo ($user['department'] == 'EO') ? 'selected' : ''; ?>>EO</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Role</label>
                  <select name="role" class="form-control">
                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                  </select>
                </div>
              </div>
              <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update User</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>
</div>
<?php include("include/footer.php"); ?>
