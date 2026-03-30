<?php
session_start();

$welcomePhrases = [
    // Noraml Greeting
    "Welcome back, ",
    "Great to see you again, ",
    "Hello there, ",
    "Hi, ",
    "Welcome aboard, ",
    "Good to have you here, ",
    "Nice to see you, ",
    "How's it going, ",
    "Ready to work, ",
    "Let's get started, ",

    // Professional/exam-related
    "Time to craft some challenging questions, ",
    "Ready to create your next masterpiece exam, ",
    "Let's make some exams that students will remember, ",
    "Another day, another opportunity to assess knowledge, ",
    "The exam generator is at your service, ",
    "Preparing the next generation of test-takers, ",

    // Funny
    "Brace yourself - exam creation in progress, ",
    "Warning: Genius at work (that's you), ",
    "The students don't know what's coming, ",
    "Creating exams since " . date('Y') . ", ",
    "Professional question-writer extraordinaire, ",
    "The architect of student anxiety, ",
    "Making students cry since... just kidding, ",
    "This exam will write itself... said no professor ever, ",
    "Coffee in one hand, exam questions in the other, ",
    "Plotting... I mean, planning the next exam, ",
    "The only thing we have to fear is... this exam, ",
    "Keep calm and make exams, ",
    "Not all heroes wear capes - some write exams, ",
    "With great exam power comes great responsibility, ",
    "Examining the examiners since... today, "
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Get statistics for the dashboard
$professor_id = $_SESSION['user_id'];

// Count questions
$question_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM question WHERE professor_ID = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$stmt->bind_result($question_count);
$stmt->fetch();
$stmt->close();

// Count exams
$exam_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM exam WHERE professor_ID = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$stmt->bind_result($exam_count);
$stmt->fetch();
$stmt->close();

// Count subjects
$subject_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM subject WHERE professor_ID = ?");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$stmt->bind_result($subject_count);
$stmt->fetch();
$stmt->close();

// Get recent activity
$recent_activity = [];
$stmt = $conn->prepare("
    (SELECT 'exam' AS type, exam_ID AS id, creation_date AS date, 'Created exam' AS action 
     FROM exam WHERE professor_ID = ? ORDER BY creation_date DESC LIMIT 2)
    UNION
    (SELECT 'question' AS type, question_ID AS id, date AS date, 'Added question' AS action 
     FROM question WHERE professor_ID = ? ORDER BY date DESC LIMIT 2)
    ORDER BY date DESC LIMIT 3
");
$stmt->bind_param("ii", $professor_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
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
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="music_player.css">

    <script>
    MathJax = {
        tex: {
            inlineMath: [['\\(', '\\)']],
            displayMath: [['\\[', '\\]']],
            processEscapes: true,
            packages: {'[+]': ['ams']}
        },
        startup: {
            pageReady: () => {
                return MathJax.startup.defaultPageReady().catch(function (err) {
                    console.log('MathJax startup error:', err);
                });
            }
        },
        options: {
            enableMenu: false
        }
    };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>
    <script>navigator.serviceWorker.register("service-worker.js")</script>  
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
                <a href="dashboard.php" class="active">
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
        <div class="dashboard-header">
            <h1> <?php echo $welcomePhrases[array_rand($welcomePhrases)]?> Professor <?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?>!</h1>
            <div class="quick-stats">
                <div class="stat-card">
                    <i class="fas fa-question"></i>
                    <div>
                        <h3>Questions</h3>
                        <p><?php echo $question_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-alt"></i>
                    <div>
                        <h3>Exams</h3>
                        <p><?php echo $exam_count; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-book"></i>
                    <div>
                        <h3>Subjects</h3>
                        <p><?php echo $subject_count; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="recent-activity">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <div class="activity-list">
                    <?php if (empty($recent_activity)): ?>
                        <div class="activity-item">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <p>No recent activity found</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <i class="fas fa-<?php echo $activity['type'] === 'exam' ? 'file-alt' : 'question-circle'; ?>"></i>
                                <div>
                                    <p><?php echo htmlspecialchars($activity['action']); ?></p>
                                    <small><?php echo date('F j, Y', strtotime($activity['date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="upcoming-exams">
                <h2><i class="fas fa-calendar-alt"></i> Upcoming Exams</h2>
                <div class="exams-list">
                    <?php
                    // Get upcoming exams (next 30 days)
                    $upcoming_exams = [];
                    $stmt = $conn->prepare("
                        SELECT e.exam_ID, e.exam_date, s.subject_name 
                        FROM exam e
                        JOIN subject s ON e.subject_ID = s.subject_ID
                        WHERE e.professor_ID = ? 
                        AND e.exam_date >= CURDATE()
                        AND e.exam_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        ORDER BY e.exam_date ASC
                        LIMIT 3
                    ");
                    $stmt->bind_param("i", $professor_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $upcoming_exams[] = $row;
                    }
                    $stmt->close();
                    
                    if (empty($upcoming_exams)): ?>
                        <div class="exam-item">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <p>No upcoming exams in the next 30 days</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_exams as $exam): ?>
                            <div class="exam-item">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <p><?php echo ($exam['subject_name']); ?></p>
                                    <small>
                                        <?php 
                                        $days_remaining = floor((strtotime($exam['exam_date']) - time()) / (60 * 60 * 24));
                                        echo date('F j, Y', strtotime($exam['exam_date'])) . " (" . $days_remaining . " days remaining)";
                                        ?>
                                    </small>
                                </div>
                                <a href="view_exam.php?id=<?php echo $exam['exam_ID']; ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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