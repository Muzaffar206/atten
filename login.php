<?php
session_start();
session_regenerate_id(true);
date_default_timezone_set('Asia/Kolkata');

include("assest/connection/config.php");

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim(mysqli_real_escape_string($conn, $_POST['password']));

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if account is locked
        if ($row['lockout_time'] && strtotime($row['lockout_time']) > time()) {
            $alert = '<div class="alert alert-danger">Account locked due to too many failed attempts. Please try again later.</div>';
        } else {
            // Verify the password
            if (password_verify($password, $row['password'])) {
                // Reset failed attempts and lockout time
                $sql_reset = "UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE username = ?";
                $stmt_reset = $conn->prepare($sql_reset);
                $stmt_reset->bind_param("s", $username);
                $stmt_reset->execute();

                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                session_regenerate_id(true);

                // Redirect based on role
                if ($row['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                // Increment failed attempts
                $failed_attempts = $row['failed_attempts'] + 1;
                $lockout_time = $failed_attempts >= 5 ? date("Y-m-d H:i:s", strtotime("+15 minutes")) : NULL;

                $sql_update = "UPDATE users SET failed_attempts = ?, lockout_time = ? WHERE username = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("iss", $failed_attempts, $lockout_time, $username);
                $stmt_update->execute();

                $alert = '<div class="alert alert-danger">Wrong username or password</div>';
            }
        }
    } else {
        $alert = '<div class="alert alert-danger">No users found</div>';
    }

    $conn->close();
} elseif (isset($_COOKIE['remember_me'])) {
    $cookie_value = json_decode(base64_decode($_COOKIE['remember_me']), true);

    if ($cookie_value) {
        $username = $cookie_value['username'];

        $sql = "SELECT * FROM users WHERE username = ?";
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
</head>

<body>
    <div class="limiter">
        <div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
            <div class="wrap-login100">
                <?php if (!empty($alert)) echo $alert; ?>
                <div class="date" id="date"></div>
                <div class="clock" id="clock"></div>
                <form method="post" action="" class="login100-form validate-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <span class="login100-form-logo">
                        <img src="assest/images/MESCO.png" alt="MESCO LOGO" width="100px">
                    </span>
                    <span class="login100-form-title p-b-34 p-t-27">Log in</span>

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
                        <label class="label-checkbox100" for="remember_me">Remember me</label>
                    </div>

                    <div class="container-login100-form-btn">
                        <button type="submit" value="Login" class="login100-form-btn">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include("include/footer.php"); ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        window.onload = function() {
            document.querySelector(".preloader").style.display = "none";
        }
    </script>
</body>

</html>