<?php
session_start();
require_once 'db_connection.php';
require 'vendor/autoload.php'; // Require PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$form_data = [
    'full_name' => '',
    'username' => '',
    'university_id' => '',
    'faculty' => '',
    'email' => '',
    'phone' => ''
];
$errors = [];

// Get all universities
$universities = [];
$result = $conn->query("SELECT university_ID, university_name_en FROM university ORDER BY university_name_en");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $universities[$row['university_ID']] = $row['university_name_en'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $form_data['full_name'] = trim($_POST['full_name']);
    $form_data['username'] = trim($_POST['username']);
    $form_data['university_id'] = trim($_POST['university_id']);
    $form_data['faculty'] = trim($_POST['faculty']);
    $form_data['email'] = trim($_POST['email']);
    $form_data['phone'] = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if (empty($form_data['full_name'])) {
        $errors[] = "Full name is required";
    }
    
    if (empty($form_data['username'])) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = "Username can only contain letters, numbers and underscores";
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT prof_ID FROM professor WHERE username = ?");
        $stmt->bind_param("s", $form_data['username']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Username already exists";
        }
        $stmt->close();
    }

    if (empty($form_data['university_id'])) {
        $errors[] = "University is required";
    } elseif (!array_key_exists($form_data['university_id'], $universities)) {
        $errors[] = "Invalid university selected";
    }

    if (empty($form_data['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT prof_ID FROM professor WHERE email = ?");
        $stmt->bind_param("s", $form_data['email']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Split full name into first and last name
    $name_parts = explode(' ', $form_data['full_name'], 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $registration_date = date('Y-m-d');
        if (!function_exists('random_bytes')) {
            function random_bytes($length) {
                $bytes = '';
                for ($i = 0; $i < $length; $i++) {
                    $bytes .= chr(mt_rand(0, 255));
                }
                return $bytes;
            }
        }
        $verification_token = bin2hex(random_bytes(32));
        $is_verified = 0;

        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Insert professor
            $stmt = $conn->prepare("INSERT INTO professor (username, password, first_name, last_name, email, registration_date, phone_number, verification_token, is_verified) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssi", 
                $form_data['username'], 
                $hashed_password, 
                $first_name, 
                $last_name, 
                $form_data['email'], 
                $registration_date, 
                $form_data['phone'],
                $verification_token,
                $is_verified);
            
            if (!$stmt->execute()) {
                throw new Exception("Professor insertion failed: " . $stmt->error);
            }
            
            $professor_id = $conn->insert_id;
            $stmt->close();

            // 2. Link professor to university
            $stmt = $conn->prepare("INSERT INTO professor_university (professor_ID, university_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $professor_id, $form_data['university_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Professor-University link failed: " . $stmt->error);
            }
            $stmt->close();

            // Send verification email
            $mail = new PHPMailer(true);
            
            try {
                // Server settings (configure these with your SMTP provider)
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'mauriciosayegh7@gmail.com'; // SMTP username
                $mail->Password   = 'fdwv cybm mgwe bafg'; // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('no-reply@examgenerator.com', 'Exam Generator System');
                $mail->addAddress($form_data['email'], $first_name . ' ' . $last_name);

                // Content
                // Build a correct absolute URL based on current host and directory
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $verification_url = $scheme . '://' . $host . $basePath . '/verify.php?token=' . urlencode($verification_token);
                
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Email Address';
                $mail->Body    = "Dear Professor $first_name,<br><br>
                                 Thank you for registering with Exam Generator System.<br>
                                 Please click the button below to verify your email address:<br><br>
                                 <a href=\"$verification_url\" style=\"display:inline-block;padding:10px 16px;background:#4CAF50;color:#ffffff;text-decoration:none;border-radius:4px;font-weight:bold;\" target=\"_blank\">Verify My Email</a><br><br>
                                 If the button doesn't work, copy and paste this link into your browser:<br>
                                 <a href=\"$verification_url\">$verification_url</a><br><br>
                                 If you didn't request this, please ignore this email.<br><br>
                                 Best regards,<br>
                                 Exam Generator Team";
                $mail->AltBody = "Dear Professor $first_name,\n\n"
                               . "Thank you for registering with Exam Generator System.\n"
                               . "Please visit the following link to verify your email address:\n"
                               . "$verification_url\n\n"
                               . "If you didn't request this, please ignore this email.\n\n"
                               . "Best regards,\n"
                               . "Exam Generator Team";

                $mail->send();
                
                // Commit transaction if all succeeds
                $conn->commit();
                
                $_SESSION['success'] = "Registration successful! Please check your email to verify your account.";
                header("Location: index.php");
                exit();
                
            } catch (Exception $emailException) {
                $conn->rollback();
                //error_log("Email sending failed: " . $emailException->getMessage());
                //$errors[] = "Email Error: " . $emailException->getMessage(); // Show error during testing
                $errors[] = "Registration completed but we couldn't send verification email. Please contact support.";
            }

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again.";
            error_log("Registration Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Page</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <input type="text" name="full_name" placeholder="Full Name" required 
                   value="<?php echo htmlspecialchars($form_data['full_name']); ?>">
            
            <input type="text" name="username" placeholder="Username" required 
                   value="<?php echo htmlspecialchars($form_data['username']); ?>">
            
            <select name="university_id" required>
                <option value="">Select University</option>
                <?php foreach ($universities as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo ($form_data['university_id'] == $id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="faculty" placeholder="Faculty" required 
                   value="<?php echo htmlspecialchars($form_data['faculty']); ?>">
            
            <input type="email" name="email" placeholder="Email" required 
                   value="<?php echo htmlspecialchars($form_data['email']); ?>">
            
            <input type="text" name="phone" placeholder="Phone Number" 
                   value="<?php echo htmlspecialchars($form_data['phone']); ?>">
            
            <input type="password" name="password" placeholder="Password" required>
            
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="footer">
            <p>Already have an account? <a href="index.php">Login</a></p>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>