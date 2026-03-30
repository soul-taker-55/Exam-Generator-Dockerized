<?php
session_start();
require_once 'db_connection.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = ''; // 'error' or 'success'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $message = "Please enter your email address.";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT prof_ID, first_name FROM professor WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            $message = "This email is not registered.";
            $message_type = 'error';
        } else {
            $user = $res->fetch_assoc();

            // Generate token and expiry (1 hour)
            $token   = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $up = $conn->prepare("UPDATE professor SET reset_token = ?, reset_expires_at = ? WHERE prof_ID = ?");
            $up->bind_param("ssi", $token, $expires, $user['prof_ID']);
            $up->execute();
            $up->close();

            // Build reset link
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $reset_link = "{$scheme}://{$host}{$base}/reset_password.php?token={$token}";

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
                $mail->addAddress($email, $user['first_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset';
                $mail->Body    = "Hello {$user['first_name']},<br><br>"
                               . "Click the link below to reset your password:<br>"
                               . "<a href=\"{$reset_link}\">{$reset_link}</a><br><br>"
                               . "This link expires in 1 hour.<br><br>Exam Generator Team";
                $mail->AltBody = "Open this link to reset your password:\n{$reset_link}\nThis link expires in 1 hour.";

                $mail->send();

                $message = "A reset link has been sent to your email.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Failed to send email. Please try again.";
                $message_type = 'error';
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Forgot Password</title>

    <link rel="stylesheet" href="index.css">

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
        .login-container { height: auto; min-height: 350px; }
        input[type="email"] {
            width: 250px;
            padding: 10px;
            margin: 15px 10px;
            border: none;
            border-radius: 5px;
            display: block;
            justify-self: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Forgot Password</h2>

        <?php if ($message): ?>
            <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
            <div class="footer">
                <p><a href="index.php">Back to Login</a></p>
            </div>
        </form>
    </div>
</body>
</html>
