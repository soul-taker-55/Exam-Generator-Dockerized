<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$professor_id = $_SESSION['user_id'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name  = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $email      = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone      = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $two_factor_enabled = isset($_POST['two_factor_enabled']) ? 1 : 0;

    // NEW: password fields (optional)
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate profile inputs (same as before)
    if (empty($first_name) || empty($last_name)) {
        $error = 'First name and Last name are required';
    } 
    elseif (!preg_match("/^[A-Z][a-z]+([-][A-Z][a-z]+)*$/", $first_name)) {
        $error = 'The first name must contain only letters and hyphens(-). The first letter and any letter following a hyphen must be capitalized.';
    }
    elseif (!preg_match("/^[A-Z][a-z]+([-][A-Z][a-z]+)*$/", $last_name)) {
        $error = 'The first name must contain only letters and hyphens(-). The first letter and any letter following a hyphen must be capitalized.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    }
    elseif (!preg_match("/^09\d{8}$/", $phone)) {
        $error = 'Phone number must be 10 digits and start with 09xx xxx xxx';
    }
    else {
        // 1) Update profile info first
        $stmt = $conn->prepare("UPDATE professor SET first_name = ?, last_name = ?, email = ?, phone_number = ?, two_factor_enabled = ? WHERE prof_ID = ?");
        $stmt->bind_param("ssssii", $first_name, $last_name, $email, $phone, $two_factor_enabled, $professor_id);

        $profile_ok = $stmt->execute();
        $stmt->close();

        if (!$profile_ok) {
            $error = 'Error updating profile: ' . $conn->error;
        } else {
            $_SESSION['name'] = $first_name . ' ' . $last_name;
            $success = 'Profile updated successfully!';
        }

        // 2) If user provided any password field, process password change
        $wants_password_change = ($current_password !== '' || $new_password !== '' || $confirm_password !== '');
        if ($profile_ok && $wants_password_change) {
            // Must provide all three fields
            if ($current_password === '' || $new_password === '' || $confirm_password === '') {
                $error = 'To change your password, please fill all password fields.';
                $success = ''; // override success if error happens
            } elseif (strlen($new_password) < 8) {
                $error = 'New password must be at least 8 characters.';
                $success = '';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and its confirmation do not match.';
                $success = '';
            } else {
                // Fetch current password hash
                $pwdStmt = $conn->prepare("SELECT password FROM professor WHERE prof_ID = ?");
                $pwdStmt->bind_param("i", $professor_id);
                $pwdStmt->execute();
                $pwdRes = $pwdStmt->get_result();
                $pwdRow = $pwdRes->fetch_assoc();
                $pwdStmt->close();

                if (!$pwdRow || !password_verify($current_password, $pwdRow['password'])) {
                    $error = 'Current password is incorrect.';
                    $success = '';
                } elseif (password_verify($new_password, $pwdRow['password'])) {
                    $error = 'New password must be different from the current password.';
                    $success = '';
                } else {
                    // Update to new password
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $upPwd = $conn->prepare("UPDATE professor SET password = ? WHERE prof_ID = ?");
                    $upPwd->bind_param("si", $new_hash, $professor_id);
                    if ($upPwd->execute()) {
                        // Optional: clear remember_me cookie since hash changed
                        if (isset($_COOKIE['remember_me'])) {
                            setcookie('remember_me', '', time() - 3600, "/");
                        }
                        $success = ($success ? $success . ' ' : '') . 'Password changed successfully!';
                    } else {
                        $error = 'Error changing password. Please try again.';
                        $success = '';
                    }
                    $upPwd->close();
                }
            }
        }
    }
}


// Handle token purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_tokens'])) {
    $token_package = isset($_POST['token_package']) ? $_POST['token_package'] : '';
    
    // Define token packages
    $packages = array(
        'small' => array('tokens' => 10, 'price' => 4.99),
        'medium' => array('tokens' => 25, 'price' => 9.99),
        'large' => array('tokens' => 50, 'price' => 14.99)
    );
    
    if (!array_key_exists($token_package, $packages)) {
        $error = 'Invalid token package selected';
    } else {
        $tokens = $packages[$token_package]['tokens'];
        $price = $packages[$token_package]['price'];
        
        // Record transaction
        $stmt = $conn->prepare("INSERT INTO token_transactions (professor_id, tokens_purchased, amount_paid, status) VALUES (?, ?, ?, 'completed')");
        $stmt->bind_param("iid", $professor_id, $tokens, $price);
        
        if ($stmt->execute()) {
            // Update token balance
            $update_stmt = $conn->prepare("UPDATE professor SET tokens = tokens + ? WHERE prof_ID = ?");
            $update_stmt->bind_param("ii", $tokens, $professor_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $success = "Successfully purchased $tokens tokens!";
        } else {
            $error = 'Error processing token purchase';
        }
        $stmt->close();
    }
}

// Handle university addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_university'])) {
    $university_id = isset($_POST['university_id']) ? (int)$_POST['university_id'] : 0;
    
    if ($university_id > 0) {
        // Check if already associated
        $stmt = $conn->prepare("SELECT * FROM professor_university WHERE professor_ID = ? AND university_ID = ?");
        $stmt->bind_param("ii", $professor_id, $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Add new association
            $stmt = $conn->prepare("INSERT INTO professor_university (professor_ID, university_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $professor_id, $university_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "University added successfully!";
            } else {
                $_SESSION['error'] = "Error adding university";
            }
        } else {
            $_SESSION['error'] = "You're already associated with this university";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Please select a university";
    }
    
    header("Location: profile.php");
    exit();
}

// Get current profile data
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone_number, tokens, two_factor_enabled FROM professor WHERE prof_ID = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$professor = $result->fetch_assoc();
$stmt->close();

// Get token purchase history
$transactions = array();
$stmt = $conn->prepare("SELECT tokens_purchased, amount_paid, transaction_date FROM token_transactions WHERE professor_id = ? ORDER BY transaction_date DESC LIMIT 5");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();

// Get all universities for dropdown
$all_universities = array();
$stmt = $conn->prepare("SELECT university_ID, university_name_en FROM university ORDER BY university_name_en");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $all_universities[] = $row;
}
$stmt->close();

// Get professor's current universities
$current_universities = array();
$stmt = $conn->prepare("SELECT u.university_ID, u.university_name_en 
                       FROM university u
                       JOIN professor_university pu ON u.university_ID = pu.university_ID
                       WHERE pu.professor_ID = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_universities[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="profile_style.css">
        <link rel="stylesheet" href="music_player.css">
</head>
<body>
<div class="sidebar">
        <div class="sidebar-header">
            <span>Exam Generator</span>
            <button class="toggle-sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <div class="sidebar-content">
            <div class="user-profile">
                <div class="avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-info">
                    <h3 style="color:white;"><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                    <p>Professor in 
                        <?php 
                            for ($i = 0; $i < count($current_universities); $i++) {
                                echo htmlspecialchars($current_universities[$i]['university_name_en']) ;
                                if ( count($current_universities) > 1 ) {
                                    if ( $i+2 == count($current_universities) ) {
                                        echo htmlspecialchars(" and ") ;
                                    }
                                    else if ( $i+1 != count($current_universities) ) {
                                        echo htmlspecialchars(', ') ;
                                    }
                                }
                            }
                        ?> 
                    </p>
                </div>
            </div>
            
            <nav class="dashboard-nav">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="my_subjects.php">
                    <i class="fas fa-book"></i>
                    <span>My Subjects</span>
                </a>
                <a href="create_exam.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Create Exam</span>
                </a>
                <a href="view_exams.php">
                    <i class="fas fa-list"></i>
                    <span>View Exams</span>
                </a>
                <a href="profile.php" class="active">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>
                 <a href="#" id="music-btn"><i class="fas fa-music"></i><span>Music</span></a>

                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>
    
    <div class="main-content">
        <div class="profile-container">
            <h1><i class="fas fa-user-cog"></i> My Profile</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="profile-section">
                <h2><i class="fas fa-user-edit"></i> Profile Information</h2>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo isset($professor['first_name']) ? htmlspecialchars($professor['first_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo isset($professor['last_name']) ? htmlspecialchars($professor['last_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo isset($professor['email']) ? htmlspecialchars($professor['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($professor['phone_number']) ? htmlspecialchars($professor['phone_number']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="two_factor_enabled">Two-Step Verification (email PIN)</label>
                        <div>
                            <input type="checkbox" id="two_factor_enabled" name="two_factor_enabled" <?php echo (!empty($professor['two_factor_enabled'])) ? 'checked' : ''; ?>>
                            <label for="two_factor_enabled">Require email PIN after password on login</label>
                        </div>
                        <small>This will send a 6-digit PIN to your email after you enter the correct password. The PIN expires in 5 minutes.</small>
                    </div>
                    
                    <hr style="margin: 25px 0;">
                    <h2><i class="fas fa-key"></i> Change Password (optional)</h2>
                    <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="At least 8 characters">
                    </div>

                    <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-type new password">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
            
            <div class="tokens-section">
                <h2><i class="fas fa-coins"></i> Exam Tokens</h2>
                
                <div class="token-balance">
                    <h3>Current Balance</h3>
                    <div class="balance-amount">
                        <span><?php echo isset($professor['tokens']) ? htmlspecialchars($professor['tokens']) : 0; ?></span>
                        <i class="fas fa-coins"></i>
                    </div>
                    <p>Tokens are used to generate exam papers (1 token per exam)</p>
                </div>
                
                <div class="purchase-section">
                    <h3>Purchase Additional Tokens</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="purchase_tokens" value="1">
                        
                        <div class="token-packages">
                            <div class="package">
                                <input type="radio" id="package-small" name="token_package" value="small" checked>
                                <label for="package-small">
                                    <span class="amount">10 Tokens</span>
                                    <span class="price">$4.99</span>
                                </label>
                            </div>
                            
                            <div class="package">
                                <input type="radio" id="package-medium" name="token_package" value="medium">
                                <label for="package-medium">
                                    <span class="amount">25 Tokens</span>
                                    <span class="price">$9.99</span>
                                </label>
                            </div>
                            
                            <div class="package">
                                <input type="radio" id="package-large" name="token_package" value="large">
                                <label for="package-large">
                                    <span class="amount">50 Tokens</span>
                                    <span class="price">$14.99</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-purchase">
                            <i class="fas fa-credit-card"></i> Purchase Tokens
                        </button>
                    </form>
                </div>
                
                <?php if (!empty($transactions)): ?>
                <div class="transaction-history">
                    <h3>Recent Transactions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Tokens</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($tx['transaction_date'])); ?></td>
                                <td>+<?php echo htmlspecialchars($tx['tokens_purchased']); ?></td>
                                <td>$<?php echo number_format($tx['amount_paid'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="university-section">
                <h2><i class="fas fa-university"></i> University Affiliation</h2>
                
                <div class="current-university-box">
                    <h3>Your Current University</h3>
                    <?php if (!empty($current_universities)): ?>
                        <div class="university-list">
                            <?php foreach ($current_universities as $univ): ?>
                            <div class="university-item">
                                <span><?php echo htmlspecialchars($univ['university_name_en']); ?></span>
                                <a href="remove_university.php?id=<?php echo $univ['university_ID']; ?>" class="remove-university">
                                    <i class="fas fa-times"></i> Remove
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-university">No university selected</p>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="add-university-form">
                    <input type="hidden" name="add_university" value="1">
                    
                    <div class="form-group">
                        <label for="university_id">Add New University</label>
                        <div class="university-select-wrapper">
                            <select id="university_id" name="university_id" class="university-select">
                                <option value="">-- Select University --</option>
                                <?php foreach ($all_universities as $univ): ?>
                                    <?php if (!in_array($univ['university_ID'], array_column($current_universities, 'university_ID'))): ?>
                                        <option value="<?php echo $univ['university_ID']; ?>">
                                            <?php echo htmlspecialchars($univ['university_name_en']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-add">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    // Toggle sidebar
    document.querySelector('.toggle-sidebar').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        
        const icon = this.querySelector('i');
        if (document.querySelector('.sidebar').classList.contains('collapsed')) {
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        } else {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-left');
        }
    });
</script>

<script defer src="music_player.js"></script>


</body>
</html>
<?php
$conn->close();
?>