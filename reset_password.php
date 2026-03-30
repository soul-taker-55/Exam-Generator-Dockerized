<?php
session_start();
require_once 'db_connection.php';

$message = '';
$message_type = ''; // 'error' or 'success'
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['token'] ?? '';
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($pass === '' || $confirm === '') {
        $message = "Please enter and confirm your new password.";
        $message_type = 'error';
    } elseif ($pass !== $confirm) {
        $message = "Passwords do not match.";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("SELECT prof_ID, reset_expires_at FROM professor WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if (strtotime($row['reset_expires_at']) > time()) {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);

                $up = $conn->prepare("UPDATE professor 
                                      SET password = ?, reset_token = NULL, reset_expires_at = NULL 
                                      WHERE prof_ID = ?");
                $up->bind_param("si", $hashed, $row['prof_ID']);
                $up->execute();
                $up->close();

                $message = "Your password has been successfully reset. <a href='index.php'>Login here</a>";
                $message_type = 'success';
            } else {
                $message = "This reset link has expired.";
                $message_type = 'error';
            }
        } else {
            $message = "Invalid reset link.";
            $message_type = 'error';
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
    <title>Reset Password</title>

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
        input[type="password"] {
            width: 250px;
            padding: 10px;
            margin: 10px 10px;
            border: none;
            border-radius: 5px;
            display: block;
            justify-self: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Reset Password</h2>

        <?php if ($message): ?>
            <div class="<?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($token && !$message_type): ?>
        <form method="POST" action="reset_password.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="password" name="password" placeholder="New Password" required>
            <input type="password" name="password_confirm" placeholder="Confirm Password" required>
            <button type="submit">Save New Password</button>
            <div class="footer">
                <p><a href="index.php">Back to Login</a></p>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
