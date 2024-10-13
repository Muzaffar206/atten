<?php
session_start();
session_regenerate_id(true);
date_default_timezone_set('Asia/Kolkata');

include("assest/connection/config.php");

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$last_username = ''; // Initialize the variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF token validation
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $alert = '<div class="alert alert-danger">Security token validation failed. Please try again.</div>';
    } else {
        $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
        $last_username = htmlspecialchars($username); // Store the last entered username
        $password = trim($_POST['password']); // Don't escape the password before verification

        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Check if account is locked
            if ($row['lockout_time'] && strtotime($row['lockout_time']) > time()) {
                $remaining_time = ceil((strtotime($row['lockout_time']) - time()) / 60);
                $alert = "<div class='alert alert-danger'>Account is temporarily locked. Please try again in {$remaining_time} minute(s).</div>";
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

                    // Always create a remember token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $sql_remember = "UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?";
                    $stmt_remember = $conn->prepare($sql_remember);
                    $stmt_remember->bind_param("ssi", $token, $expiry, $row['id']);
                    $stmt_remember->execute();

                    // Set the remember token cookie
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);

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

                    if ($failed_attempts >= 5) {
                        $alert = '<div class="alert alert-danger">Too many failed attempts. Your account has been locked for 15 minutes.</div>';
                    } else {
                        $remaining_attempts = 5 - $failed_attempts;
                        $alert = "<div class='alert alert-danger'>Incorrect password. You have {$remaining_attempts} attempt(s) remaining.</div>";
                    }
                }
            }
        } else {
            $alert = '<div class="alert alert-danger">No account found with this username.</div>';
        }

        $conn->close();
    }
} elseif (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $sql = "SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Refresh the token
        $new_token = bin2hex(random_bytes(32));
        $new_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $sql_refresh = "UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?";
        $stmt_refresh = $conn->prepare($sql_refresh);
        $stmt_refresh->bind_param("ssi", $new_token, $new_expiry, $row['id']);
        $stmt_refresh->execute();

        setcookie('remember_token', $new_token, time() + (30 * 24 * 60 * 60), '/', '', true, true);

        header("Location: home.php");
        exit();
    }

    $conn->close();
}
?>
<?php
$pageTitle = 'Login';
$pageDescription = 'Secure login page for MESCO Attendance System. Access your account to manage attendance and view reports.';
include("include/header.php");
?>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
    </style>

    <div class="login-container">
        <div class="login-header">
            <img src="assest/images/MESCO.png" alt="MESCO LOGO">
            <h2>Login</h2>
        </div>
        <div class="login-form">
            <?php if (!empty($alert)) echo $alert; ?>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo $last_username; ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <span toggle="#password" class="fa fa-fw fa-eye toggle-password"></span>
                    </div>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>

    <?php include("include/footer.php"); ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-group input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'black';
                    this.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.style.borderColor = '#4caf50';
                        this.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>

</html>