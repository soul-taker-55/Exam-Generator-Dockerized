<?php
session_start();
require_once 'db_connection.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get exam ID from URL parameter
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$exam_id) {
    die("Invalid exam ID");
}

// Fetch exam details
$stmt = $conn->prepare("
    SELECT e.*, s.subject_name, u.university_name_en 
    FROM exam e
    JOIN subject s ON e.subject_ID = s.subject_ID
    JOIN university u ON e.university_ID = u.university_ID
    WHERE e.exam_ID = ? AND e.professor_ID = ?
");
$stmt->bind_param("ii", $exam_id, $_SESSION['user_id']);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    die("Exam not found or you don't have permission to view it");
}

// Get the PDF file path
$pdf_path = $exam['pdf_file_path'];

// Check if file exists
$full_path = __DIR__ . '/exams/' . basename($pdf_path);
if (!file_exists($full_path)) {
    die("PDF file not found");
}

$universities = [] ;
$stmt = $conn->prepare("
    SELECT u.university_name_en 
    FROM university as u INNER JOIN professor_university as pu on u.university_ID = pu.university_ID
    where pu.professor_ID = ?
");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $universities[] = $row;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Exam - <?php echo htmlspecialchars($exam['subject_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="view_exam.css">
    <link rel="stylesheet" href="view_exams.css">
    <link rel="stylesheet" href="music_player.css">

    <style>
        .exam-container {
            background-color: var(--white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .exam-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }
        .exam-details {
            margin-bottom: 20px;
        }
        .exam-details p {
            margin: 5px 0;
        }
        .pdf-container {
            width: 100%;
            height: 800px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .back-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #2980b9;
        }
    </style>
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
                    <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                    <p>Professor in 
                        <?php 
                            for ($i = 0; $i < count($universities); $i++) {
                                echo htmlspecialchars($universities[$i]['university_name_en']) ;
                                if ( count($universities) > 1 ) {
                                    if ( $i+2 == count($universities) ) {
                                        echo htmlspecialchars(" and ") ;
                                    }
                                    else if ( $i+1 != count($universities) ) {
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
                <a href="view_exams.php" class="active">
                    <i class="fas fa-list"></i>
                    <span>View Exams</span>
                </a>
                <a href="profile.php">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>

                <a href="#" id="music-btn">
                    <i class="fas fa-music"></i>
                    <span>Music</span>
                </a>

                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>
    
    <div class="main-content">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        
        <div class="exam-container">
            <div class="exam-header">
                <h1><?php echo htmlspecialchars($exam['subject_name']); ?> Exam</h1>
                <p>Exam Date: <?php echo date('F j, Y', strtotime($exam['exam_date'])); ?></p>
            </div>
            
            <div class="exam-details">
                <p><strong>University:</strong> <?php echo htmlspecialchars($exam['university_name_en']); ?></p>
                <p><strong>Created:</strong> <?php echo date('F j, Y', strtotime($exam['creation_date'])); ?></p>
            </div>
            
            <h2>Exam Preview</h2>
            <iframe src="exams/<?php echo basename($pdf_path); ?>" class="pdf-container"></iframe>
            
            <div style="margin-top: 20px;">
                <a href="exams/<?php echo basename($pdf_path); ?>" download class="back-btn">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle sidebar
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
    </script>

    <script defer src="music_player.js"></script>
</body>
</html>