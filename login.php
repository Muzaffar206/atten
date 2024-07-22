<?php
session_start();
session_regenerate_id(true);
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST
include("assest/connection/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $rememberMe = isset($_POST['remember_me']); // if "Remember Me" is selected



    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $alert = '';

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

            if ($row['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            $alert .= '<div class="alert alert-danger" role="alert">Wrong username or password</div>';
        }
    } else {
        $alert .= '<div class="alert alert-danger" role="alert">No users found</div>';
    }

    $conn->close();
} elseif (isset($_COOKIE['remember_me'])) {
    $cookie_value = json_decode(base64_decode($_COOKIE['remember_me']), true);

    if ($cookie_value) {
        $username = $cookie_value['username'];

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
    <?php include("include/header.php"); ?>

    <div class="limiter">
        <div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
            <div class="wrap-login100">
                <?php if (!empty($alert)) echo $alert; ?>
                <div class="date" id="date"></div>
                <div class="clock" id="clock"></div>
                <form method="post" action="" class="login100-form validate-form">
                    <span class="login100-form-logo">
                        <img src="assest/images/MESCO.png" alt="MESCO LOGO" width="100px">
                    </span>

                    <span class="login100-form-title p-b-34 p-t-27">
                        Log in
                    </span>

                    <div class="wrap-input100 validate-input" data-validate="Enter username">
                        <input class="input100" type="text" name="username" placeholder="Username" required>
                        <span class="focus-input100" data-placeholder="&#xf207;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Enter password">
                        <input class="input100" type="password" name="password" id="password" placeholder="Password" required>
                        <span class="focus-input100" data-placeholder="&#xf191;"></span>
                        <span toggle="#password" class="eye-toggle fa fa-fw fa-eye field-icon toggle-password"></span>
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
                </form>
            </div>
        </div>
    </div>



    <?php include("include/footer.php"); ?>


    </body>

</html>