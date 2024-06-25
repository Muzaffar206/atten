<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember_me']); // Check if "Remember Me" is selected

    $conn = new mysqli('localhost', 'root', '', 'attendance_system');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            if ($rememberMe) {
                // Set a cookie with a hashed value of username and password
                $cookie_value = base64_encode(json_encode([
                    'username' => $username,
                    'token' => bin2hex(random_bytes(16)), // Token for additional security
                ]));
                setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30 days
            }

            header("Location: home.php");
            exit();
        } else {
            echo 'Wrong email id or Password';
        }
    } else {
        echo "No user found.";
    }

    $conn->close();
} elseif (isset($_COOKIE['remember_me'])) {
    $cookie_value = json_decode(base64_decode($_COOKIE['remember_me']), true);

    if ($cookie_value) {
        $username = $cookie_value['username'];

        $conn = new mysqli('localhost', 'root', '', 'attendance_system');

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM users WHERE username=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            header("Location: home.php");
            exit();
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Login</title>
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
	<div class="preloader">
<div class="lava-lamp">
  <div class="bubble"></div>
  <div class="bubble1"></div>
  <div class="bubble2"></div>
  <div class="bubble3"></div>
</div>
</div>

<!-- <form method="post" action="">
    Username: <input type="text" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Login"> -->

	<div class="limiter">
		<div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
			<div class="wrap-login100">
				<form method="post" action="" class="login100-form validate-form">
					<span class="login100-form-logo">
						<img src="assest/css/MESCO.png" alt="MESCO LOGO" width="100px">
					</span>

					<span class="login100-form-title p-b-34 p-t-27">
						Log in
					</span>

					<div class="wrap-input100 validate-input" data-validate = "Enter username">
						<input class="input100" type="text" name="username" placeholder="Username"required>
						<span class="focus-input100" data-placeholder="&#xf207;"></span>
					</div>

					<div class="wrap-input100 validate-input" data-validate="Enter password">
						<input class="input100" type="password" name="password" placeholder="Password" required>
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

					<div class="contact100-form-checkbox">
						<input class="input-checkbox100" type="checkbox" id="remember_me" name="remember_me">
						<label class="label-checkbox100" for="remember_me">
							Remember me
						</label>
					</div>

					<div class="container-login100-form-btn">
						<button type="submit" value="Login" class="login100-form-btn">
							Login
						</button>
					</div>

					<div class="text-center p-t-90">
						<a class="txt1" href="#">
							Forgot Password?
						</a>
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

<script>

window.onload = function(){
        //hide the preloader
        document.querySelector(".preloader").style.display = "none";
    }
</script>
<!-- </form> -->
</body>
</html>