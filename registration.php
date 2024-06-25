<?php
// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     $username = $_POST['username'];
//     $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

//     $conn = new mysqli('localhost', 'root', '', 'attendance_system');

//     if ($conn->connect_error) {
//         die("Connection failed: " . $conn->connect_error);
//     }

//     $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
//     if ($conn->query($sql) === TRUE) {
//         echo "Registration successful! "; header("location:login.php");
//     } else {
//         echo "Error: " . $sql . "<br>" . $conn->error;
//     }

//     $conn->close();
// }
?>
<!-- <form method="post" action="">
    Username: <input type="text" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Register">
</form> -->

<?php
session_start();



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $employer_id = $_POST['employer_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    // File upload
    $target_dir = "uploads/";
    $target_file = $target_dir . time() . '_' . basename($_FILES["passport_size_photo"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES["passport_size_photo"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size (limit to 5MB)
    if ($_FILES["passport_size_photo"]["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        if (move_uploaded_file($_FILES["passport_size_photo"]["tmp_name"], $target_file)) {
            echo "The file " . basename($_FILES["passport_size_photo"]["name"]) . " has been uploaded.";
            $passport_size_photo = $target_file; // Store the file path in the database
            $conn = new mysqli('localhost', 'root', '', 'attendance_system');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            

            $sql = $conn->prepare("INSERT INTO users (username, password, employer_id, full_name, email, phone_number, passport_size_photo, address, device_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sql->bind_param("ssissssss", $username, $password, $employer_id, $full_name, $email, $phone_number, $passport_size_photo, $address, $deviceID);

            if ($sql->execute() === TRUE) {
                echo "New user created successfully";
                header("Location: login.php");
            } else {
                echo "Error: " . $sql->error;
            }

            $sql->close();
            $conn->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Registration</title>
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="assest/images/icons/favicon.ico"/>
<!--===============================================================================================-->	
    <link rel="stylesheet" href="assest/css/bootstrap.min.css">
<!--===============================================================================================-->	
    <link rel="stylesheet" type="text/css" href="assest/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/fonts/iconic/css/material-design-iconic-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="assest/vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="assest/vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/css/util.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/css/main.css">
<!--===============================================================================================-->
</head>
<body>


<form method="post" action="" enctype="multipart/form-data">
    <p id=reg >Registration Form</p>
    Username: <br> <input ID=US type="text" name="username" required><br><br>
    Password: <br><input type="password" name="password" required><br><br>
    Employer ID: <br><input type="text" name="employer_id"><br><br>
    Full Name:<br> <input type="text" name="full_name"><br><br>
    Email: <br><input type="email" name="email"><br><br>
    Phone Number: <br><input type="text" name="phone_number"><br><br>
    Passport Size Photo: <input type="file" name="passport_size_photo" accept="image/*" required><br><br>
    Address : <br><textarea name="address"> Add Your Address Here </textarea><br><br>
    <button id=but type = submit >Register</button>
</form>
<div id="dropDownSelect1"></div>
	



<div class="limiter">
		<div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
			<div class="wrap-login100">
				<form method="post" action="" enctype="multipart/form-data" class="login100-form validate-form">
					<span class="login100-form-logo">
						<i class="zmdi zmdi-landscape"></i>
					</span>

					<span class="login100-form-title p-b-34 p-t-27">
						Register
					</span>

					<div class="wrap-input100 validate-input" data-validate = "Enter username">
						<input class="input100"  ID=US type="text" name="username" placeholder="Username"required>
						<span class="focus-input100" data-placeholder="&#xf207;"></span>
					</div>

					<div class="wrap-input100 validate-input" data-validate="Enter password">
						<input class="input100" type="password" name="password" placeholder="Password" required>
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

					<div class="wrap-input100 validate-input" data-validate="Employer ID">
						<input class="input100" type="text" name="employer_id" placeholder="Employer ID" required>
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

                    <div class="wrap-input100 validate-input" data-validate="Full Name">
						<input class="input100" type="text" name="full_name" placeholder="Full Name" required>
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

                    <div class="wrap-input100 validate-input" data-validate="Email">
						<input class="input100" type="email" name="email" placeholder="Email" required>
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

                    <div class="wrap-input100 validate-input" data-validate="Phone Number">
						<input class="input100" type="text" name="phone_number" placeholder="Phone Number" required>
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

					<div class="container-login100-form-btn">
						<button type="submit" value="Login" class="login100-form-btn">
							Login
						</button>
					</div>

				</form>
			</div>
		</div>
	</div>
	





<!--===============================================================================================-->
	<script src="assest/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/bootstrap/js/popper.js"></script>
	<script src="assest/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/daterangepicker/moment.min.js"></script>
	<script src="assest/vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="assest/js/main.js"></script>
</body>
</html>