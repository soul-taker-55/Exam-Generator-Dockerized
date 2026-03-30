<?php
session_start();
require_once 'db_connection.php';
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$username = '';
$password = '';
$remember = false;
$error = '';
$success = '';
$twofa_message = '';
$show_2fa_form = false;

// Pull flash messages from session (from verify.php/register.php)
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Check if user is already logged in (redirect to dashboard)
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Abort 2FA on refresh/revisit (GET): force user back to login form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['pending_2fa_user_id'])) {
    $user_id = (int)$_SESSION['pending_2fa_user_id'];

    // (اختياري) إبطال الكود القديم
    if (isset($conn)) {
        $stmt = $conn->prepare("UPDATE professor 
                                SET two_factor_code_hash = NULL, two_factor_expires_at = NULL 
                                WHERE prof_ID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // تنظيف حالة 2FA من الجلسة
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_remember']);

    // رسالة خطأ ستظهر للمستخدم
    $error = "The two-step verification has been canceled due to a page update. Please log in and submit a new code.";
}


// Handle 2FA verification submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_2fa'])) {
    $code = isset($_POST['two_factor_code']) ? trim($_POST['two_factor_code']) : '';
    if (empty($_SESSION['pending_2fa_user_id'])) {
        $error = "Session expired. Please login again.";
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = "Invalid code format.";
        $show_2fa_form = true;
    } else {
        $user_id = (int)$_SESSION['pending_2fa_user_id'];
        $stmt = $conn->prepare("SELECT prof_ID, username, password, first_name, last_name, two_factor_code_hash, two_factor_expires_at FROM professor WHERE prof_ID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $expires_at = isset($user['two_factor_expires_at']) ? strtotime($user['two_factor_expires_at']) : 0;
            if ($expires_at < time()) {
                $error = "Verification code expired. Please resend or login again.";
                $show_2fa_form = true;
            } elseif (!empty($user['two_factor_code_hash']) && password_verify($code, $user['two_factor_code_hash'])) {
                // Clear 2FA code
                $clear = $conn->prepare("UPDATE professor SET two_factor_code_hash = NULL, two_factor_expires_at = NULL WHERE prof_ID = ?");
                $clear->bind_param("i", $user_id);
                $clear->execute();
                $clear->close();

                // Set full session
                $_SESSION['user_id'] = $user['prof_ID'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];

                // Remember me cookie after successful 2FA
                if (!empty($_SESSION['pending_2fa_remember'])) {
                    $cookie_value = base64_encode($user['prof_ID'] . ':' . hash('sha256', $user['password']));
                    setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/");
                }

                // Clear pending session flags
                unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_remember']);

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid verification code.";
                $show_2fa_form = true;
            }
        } else {
            $error = "Session invalid. Please login again.";
        }
        $stmt->close();
    }
}

// Resend code action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend_2fa']) && isset($_SESSION['pending_2fa_user_id'])) {
    $user_id = (int)$_SESSION['pending_2fa_user_id'];
    // Load user
    $stmt = $conn->prepare("SELECT prof_ID, email, first_name, last_name FROM professor WHERE prof_ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $u = $res->fetch_assoc();
        // Generate code
        $code = (string)random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        // Save
        $up = $conn->prepare("UPDATE professor SET two_factor_code_hash = ?, two_factor_expires_at = ? WHERE prof_ID = ?");
        $up->bind_param("ssi", $hash, $expires_at, $user_id);
        $up->execute();
        $up->close();
        // Send email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mauriciosayegh7@gmail.com';
            $mail->Password   = 'fdwv cybm mgwe bafg';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('no-reply@examgenerator.com', 'Exam Generator System');
            $mail->addAddress($u['email'], $u['first_name'] . ' ' . $u['last_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Your login verification code';
            $mail->Body    = "Dear {$u['first_name']},<br><br>Your verification code is: <b>{$code}</b><br>This code expires in 5 minutes.<br><br>Best regards,<br>Exam Generator Team";
            $mail->AltBody = "Your verification code is: {$code}\nThis code expires in 5 minutes.";

            $mail->send();
            $twofa_message = "A new verification code has been sent to your email.";
            $show_2fa_form = true;
        } catch (Exception $e) {
            $error = "Failed to send verification code. Please try again.";
            $show_2fa_form = true;
        }
    } else {
        $error = "Session invalid. Please login again.";
    }
    $stmt->close();
}

// Process login form if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Prepare SQL to prevent SQL injection

        $stmt = $conn->prepare("SELECT prof_ID, username, password, first_name, last_name, email, is_verified, two_factor_enabled, two_factor_code_hash FROM professor WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if ((int)$user['is_verified'] === 0) {
                $error = "Please verify your email first!";
            } elseif (password_verify($password, $user['password'])) {
                // If 2FA enabled, send code and show 2FA form
                if (!empty($user['two_factor_enabled'])) {
                    $code = (string)random_int(100000, 999999);
                    $hash = password_hash($code, PASSWORD_DEFAULT);
                    $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes

                    // Save hashed code and expiry
                    $up = $conn->prepare("UPDATE professor SET two_factor_code_hash = ?, two_factor_expires_at = ? WHERE prof_ID = ?");
                    $up->bind_param("ssi", $hash, $expires_at, $user['prof_ID']);
                    $up->execute();
                    $up->close();

                    // Send email with PHPMailer
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'mauriciosayegh7@gmail.com';
                        $mail->Password   = 'fdwv cybm mgwe bafg';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('no-reply@examgenerator.com', 'Exam Generator System');
                        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

                        $mail->isHTML(true);
                        $mail->Subject = 'Your login verification code';
                        $mail->Body    = "Dear {$user['first_name']},<br><br>Your verification code is: <b>{$code}</b><br>This code expires in 5 minutes.<br><br>Best regards,<br>Exam Generator Team";
                        $mail->AltBody = "Your verification code is: {$code}\nThis code expires in 5 minutes.";

                        $mail->send();

                        // Mark session as pending 2FA
                        $_SESSION['pending_2fa_user_id'] = $user['prof_ID'];
                        $_SESSION['pending_2fa_remember'] = $remember ? 1 : 0;
                        $twofa_message = "A verification code has been sent to your email. Enter it below.";
                        $show_2fa_form = true;
                    } catch (Exception $e) {
                        $error = "Failed to send verification code. Please try again.";
                    }
                } else {
                    // 2FA not enabled: log in directly
                    $_SESSION['user_id'] = $user['prof_ID'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    // Remember me functionality (set cookie)
                    if ($remember) {
                        $cookie_value = base64_encode($user['prof_ID'] . ':' . hash('sha256', $user['password']));
                        setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30 days
                    }
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        
        $stmt->close();
    }
}

// Check for "remember me" cookie
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_me']) && !isset($_SESSION['pending_2fa_user_id'])) {
    $cookie_data = base64_decode($_COOKIE['remember_me']);
    list($user_id, $token) = explode(':', $cookie_data);
    
    $stmt = $conn->prepare("SELECT prof_ID, username, password, first_name, last_name FROM professor WHERE prof_ID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $expected_token = hash('sha256', $user['password']);
        
        if (hash_equals($expected_token, $token)) {
            $_SESSION['user_id'] = $user['prof_ID'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
            header("Location: dashboard.php");
            exit();
        }
    }
    
    // Invalid cookie, clear it
    setcookie('remember_me', '', time() - 3600, "/");
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="index.css">
    <link rel="manifest" href="manifest.json"/> 
    <style>
        .error-message {
            color: #ff6b6b;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;

        }
        .success-message {
            color: #2ecc71;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
    </style>
<script>navigator.serviceWorker.register("service-worker.js")</script>  
       
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($twofa_message)): ?>
            <div class="error-message" style="color:#2ecc71;"><?php echo htmlspecialchars($twofa_message); ?></div>
        <?php endif; ?>

        <?php if ($show_2fa_form || isset($_SESSION['pending_2fa_user_id'])): ?>
            <form method="POST" action="index.php">
                <input type="text" name="two_factor_code" placeholder="Enter 6-digit code" required pattern="\d{6}" maxlength="6">
                <button type="submit" name="verify_2fa">Verify Code</button>
            </form>
            <form method="POST" action="index.php" style="margin-top:10px;">
                <button type="submit" name="resend_2fa">Resend Code</button>
            </form>
        <?php else: ?>
            <form method="POST" action="index.php">
                <input type="text" name="username" placeholder="Username" required 
                       value="<?php echo htmlspecialchars($username); ?>">
                
                <input type="password" name="password" placeholder="Password" required>
                
                <div class="Remember">
                    <input type="checkbox" name="remember" id="remember" <?php echo $remember ? 'checked' : ''; ?>>
                    <label for="remember">Remember me</label>
                </div>
                
                <button type="submit" name="login">Login</button>
                
                <div class="footer">
                    <p><a href="forgot_password.php">Forgot password?</a></p>
                    <p>Don't have an account? <a href="register.php">Register</a></p>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>