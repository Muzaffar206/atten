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
include("include/header.php");
include("include/topbar.php");
$activePage = 'registration';
include("include/sidebar.php");

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $confirm_password = password_hash($_POST['confirm_password'], PASSWORD_DEFAULT);
    $employer_id = htmlspecialchars($_POST['employer_id']);
    $full_name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $department = $_POST['department'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'user'; // Default role to 'user'

    // Check if the username, employer_id, or email already exists and is not deleted
    $sql_check = $conn->prepare("SELECT * FROM users WHERE username = ? OR employer_id = ? OR email = ?");
    $sql_check->bind_param("sss", $username, $employer_id, $email);
    $sql_check->execute();
    $result = $sql_check->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['deleted_at'] !== null) {
                $errors[] = 'This account has been deleted and cannot be reused. Please use a different username, employer ID, or email.';
            } else {
                if ($row['username'] == $username) {
                    $errors[] = 'Username already exists. Please choose a different username.';
                }
                if ($row['employer_id'] == $employer_id) {
                    $errors[] = 'Employer ID already exists. Please use a different employer ID.';
                }
                if ($row['email'] == $email) {
                    $errors[] = 'Email already exists. Please use a different email address.';
                }
            }
        }
    }

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = 'Passwords do not match. Please re-enter passwords.';
    }

    // If there are errors, display them in a single alert message
    if (!empty($errors)) {
        $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        foreach ($errors as $error) {
            $alert .= $error . '<br>';
        }
        $alert .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
          </div>';
    } else {
        // No errors, proceed with user registration
        // Insert user data into the database
        $sql = $conn->prepare("INSERT INTO users (username, password, employer_id, full_name, email, phone_number, department, role) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $sql->bind_param("ssisssss", $username, $password, $employer_id, $full_name, $email, $phone_number, $department, $role);

        if ($sql->execute() === TRUE) {
            $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Registration successful!
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                  </div>';
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    Error: ' . $sql->error . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                  </div>';
        }

        $sql->close();
    }

    $sql_check->close();
    $conn->close();
}
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Create New User</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">New User</li>
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
                            <h3 class="card-title">New User</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form method="post" action="" enctype="multipart/form-data">
                            <?php if (!empty($alert)) echo $alert; ?>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" placeholder="Enter username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" placeholder="Password" name="password" id="password" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-eye" id="togglePassword"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <input type="password" class="form-control" placeholder="Confirm Password" name="confirm_password" id="confirm_password" required>
                                </div>
                                <div class="form-group">
                                    <label>Employee ID</label>
                                    <input type="text" class="form-control" placeholder="Enter Employee ID" name="employer_id" required>
                                </div>
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Full name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" placeholder="Enter Email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" class="form-control" placeholder="Enter Phone Number" name="phone_number" required>
                                </div>

                                <div class="form-group">
                                    <label>Select Department</label>
                                    <select name="department" id="department" class="form-control select2" style="width: 100%;" required>
                                        <option value="">Select department</option>
                                        <option value="Education">Education</option>
                                        <option value="Medical">Medical</option>
                                        <option value="ROP">ROP</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Accounts">Accounts</option>
                                        <option value="FRD">FRD</option>
                                        <option value="Newspaper">Newspaper</option>
                                        <option value="RC Mahim">RC Mahim</option>
                                        <option value="Study centre">Study centre</option>
                                        <option value="Clinics">Clinics</option>
                                        <option value="EO">EO</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Select Role</label>
                                    <select name="role" id="role" class="form-control" required>
                                        <option value="user" selected>User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include("include/footer.php"); ?>

<script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    togglePassword.addEventListener('click', function (e) {
        // Toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        // Toggle the eye icon
        this.classList.toggle('fa-eye-slash');
    });

    const confirmTogglePassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');

    confirmTogglePassword.addEventListener('click', function (e) {
        const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPassword.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
</script>
