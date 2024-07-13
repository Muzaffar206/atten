<?php
session_start();
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
include("include/sidebar.php");




if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $employer_id = htmlspecialchars($_POST['employer_id']);
    $full_name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $address = htmlspecialchars($_POST['address']);
    $department = $_POST['department'];

 // Check if the username, employer_id, or email already exists
 $sql_check = $conn->prepare("SELECT * FROM users WHERE username = ? OR employer_id = ? OR email = ?");
 $sql_check->bind_param("sss", $username, $employer_id, $email);
 $sql_check->execute();
 $result = $sql_check->get_result();

 $alert = '';

 if ($result->num_rows > 0) {
     while ($row = $result->fetch_assoc()) {
         if ($row['username'] == $username) {
             $alert .= '<div class="alert alert-danger" role="alert">Username already exists. Please choose a different username.</div>';
         }
         if ($row['employer_id'] == $employer_id) {
             $alert .= '<div class="alert alert-danger" role="alert">Employer ID already exists. Please use a different employer ID.</div>';
         }
         if ($row['email'] == $email) {
             $alert .= '<div class="alert alert-danger" role="alert">Email already exists. Please use a different email address.</div>';
         }
     }
 } else {
    // File upload
    $target_dir = "../uploads/";
    $target_file = $target_dir . time() . '_' . basename($_FILES["passport_size_photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES["passport_size_photo"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $alert .= '<div class="alert alert-danger" role="alert">File is not an image.</div> ';
        $uploadOk = 0;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        $alert .= '<div class="alert alert-danger" role="alert">Sorry, file already exists.</div>';
        $uploadOk = 0;
    }

    // Check file size (limit to 5MB)
    if ($_FILES["passport_size_photo"]["size"] > 5000000) {
        $alert .= '<div class="alert alert-danger" role="alert">Sorry, your file is too large.</div>';
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $alert .= '<div class="alert alert-danger" role="alert">Sorry, only JPG, JPEG, PNG & GIF files are allowed.</div>';
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $alert .= '<div class="alert alert-danger" role="alert">Sorry, your file was not uploaded.</div>';
    } else {
        if (move_uploaded_file($_FILES["passport_size_photo"]["tmp_name"], $target_file)) {
            $passport_size_photo = $target_file; // Store the file path in the database

            // Insert user data into the database
            $sql = $conn->prepare("INSERT INTO users (username, password, employer_id, full_name, email, phone_number, passport_size_photo, address, department) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sql->bind_param("ssissssss", $username, $password, $employer_id, $full_name, $email, $phone_number, $passport_size_photo, $address, $department);

            if ($sql->execute() === TRUE) {
                $alert .= '<div class="alert alert-success" role="alert">Registration successful!</div>';
            } else {
                $alert .= '<div class="alert alert-danger" role="alert">Error: ' . $sql->error . '</div>';
            }

            $sql->close();
        } else {
            $alert .= '<div class="alert alert-danger" role="alert">Sorry, there was an error uploading your file.</div>';
        }
    }
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
        <?php if(!empty($alert)) echo $alert; ?>
          <div class="col-sm-6">
            <h1>Create New User</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">new user</li>
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
                <h3 class="card-title">New user</h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form method="post" action="" enctype="multipart/form-data">
                <div class="card-body">
                  <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" placeholder="Enter username" name="username"  required>
                  </div>
                  <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" placeholder="Password"  name="password" required>
                  </div>
                  <div class="form-group">
                    <label>Employee id</label>
                    <input type="text" class="form-control" placeholder="Enter Employee id" name="employer_id"  required>
                  </div>
                  <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" placeholder="Enter Full name" name="full_name" required>
                  </div>
                  <div class="form-group">
                    <label>Email</label>
                    <input type="Email" class="form-control" placeholder="Enter Email"  name="email"  required>
                  </div>
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" class="form-control" placeholder="Enter Phone Number" name="phone_number" required>
                  </div>

                  <div class="form-group">
                    <label for="exampleInputFile">File input</label>
                    <div class="input-group">
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" id="exampleInputFile" name="passport_size_photo" accept="image/*">
                        <label class="custom-file-label" for="exampleInputFile">Choose file</label>
                      </div>
                      <div class="input-group-append">
                        <span class="input-group-text">Upload</span>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                  <label >Address</label>
                  <textarea class="form-control" name="address" placeholder="Address" ></textarea>
                  </div>
                  <div class="form-group">
                  <label>Select department</label>
                  <select name="department" id="department" class="form-control select2" style="width: 100%;" required>
                              <option  value="">Select department</option>
                              <option value="Education">Education</option>
                              <option value="Medical">Medical</option>
                              <option value="ROP">ROP</option>
                              <option value="Admin">Admin</option>
                              <option value="Clinics">Clinics</option>
                        </select>
                  </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </form>
            </div>
            </section>
    <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="https://outerinfo.online">Outerinfo</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0
    </div>
  </footer>




<?php    include("include/footer.php"); ?>
