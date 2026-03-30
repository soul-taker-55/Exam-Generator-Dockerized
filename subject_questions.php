<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Get subject ID from URL
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
if ($subject_id <= 0) {
    header("Location: my_subjects.php");
    exit();
}

// Verify the subject belongs to the professor
$professor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT s.*, u.university_name_en 
    FROM subject s
    JOIN university u ON s.university_ID = u.university_ID
    WHERE s.subject_ID = ? AND s.professor_ID = ?
");
$stmt->bind_param("ii", $subject_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$subject = $result->fetch_assoc();
$stmt->close();

if (!$subject) {
    header("Location: my_subjects.php");
    exit();
}

// Pagination setup
$questions_per_page = 9;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $questions_per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$difficulty = isset($_GET['difficulty']) ? (int)$_GET['difficulty'] : 0;
$group_num = isset($_GET['group']) ? (int)$_GET['group'] : 0;

// Build query conditions
$where = "q.subject_ID = ?";
$params = [$subject_id];
$types = "i";

if (!empty($search)) {
    $where .= " AND q.question_text LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($difficulty > 0) {
    $where .= " AND q.difficulty = ?";
    $params[] = $difficulty;
    $types .= "i";
}

if ($group_num > 0) {
    $where .= " AND q.group_num = ?";
    $params[] = $group_num;
    $types .= "i";
}

// Get total count of questions
$count_query = "SELECT COUNT(*) FROM question q WHERE $where";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_questions = $stmt->get_result()->fetch_row()[0];
$stmt->close();

// Get paginated questions
$query = "
    SELECT q.question_ID, q.question_text, q.difficulty, q.mark, q.date, q.group_num,
           COUNT(qg.group_num) as group_size
    FROM question q
    LEFT JOIN question qg ON qg.group_num = q.group_num AND qg.subject_ID = q.subject_ID
    WHERE $where
    GROUP BY q.question_ID
    ORDER BY q.group_num DESC, q.date DESC
    LIMIT ? OFFSET ?
";

$params[] = $questions_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get distinct groups for filter
$group_stmt = $conn->prepare("
    SELECT DISTINCT group_num 
    FROM question 
    WHERE subject_ID = ? AND group_num > 0
    ORDER BY group_num DESC
");
$group_stmt->bind_param("i", $subject_id);
$group_stmt->execute();
$groups = $group_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$group_stmt->close();

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($subject['subject_name']); ?> Questions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="subject_questions.css">
    <link rel="stylesheet" href="music_player.css">
    <script src="subject_questions.js"></script>
    

    <script>
        MathJax = {
            tex: {
            inlineMath: [['\\(', '\\)']],
            displayMath: [['\\[', '\\]']],
            processEscapes: true,
            packages: {'[+]': ['ams', 'boldsymbol']}
            },
            options: {
            ignoreHtmlClass: 'tex2jax_ignore',
            processHtmlClass: 'tex2jax_process'
            },
            loader: {load: ['[tex]/ams', '[tex]/boldsymbol']}
        };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
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
                <a href="my_subjects.php" class="active">
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
        <div class="container">
            <div class="page-header">
                <h1>
                    <i class="fas fa-question-circle"></i>
                    <?php echo htmlspecialchars($subject['subject_name']); ?> Questions
                    <small><?php echo htmlspecialchars($subject['university_name_en']); ?></small>
                </h1>
                <a href="make_a_question.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Question
                </a>
            </div>

            <!-- Question Controls -->
            <div class="question-controls">
                <form method="get" action="subject_questions.php" class="filter-form">
                    <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                    
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search questions..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="difficulty">
                            <option value="">All Difficulties</option>
                            <option value="1" <?php echo $difficulty == 1 ? 'selected' : ''; ?>>Easy</option>
                            <option value="2" <?php echo $difficulty == 2 ? 'selected' : ''; ?>>Medium</option>
                            <option value="3" <?php echo $difficulty == 3 ? 'selected' : ''; ?>>Hard</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="group">
                            <option value="">All Groups</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['group_num']; ?>" 
                                    <?php echo $group_num == $group['group_num'] ? 'selected' : ''; ?>>
                                    Group <?php echo $group['group_num']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-filter">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    
                    <?php if (!empty($search) || $difficulty > 0 || $group_num > 0): ?>
                        <a href="subject_questions.php?subject_id=<?php echo $subject_id; ?>" class="btn btn-clear">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select id="bulk-action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    <option value="change-difficulty">Change Difficulty</option>
                    <option value="export">Export Selected</option>
                    <option value="group">Add to Group</option>
                </select>
                <button id="apply-bulk-action" class="btn btn-bulk">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>

            <!-- Question Tabs -->
            <div class="question-tabs">
                <button class="tab-btn active" data-tab="all">All Questions</button>
                <button class="tab-btn" data-tab="groups">By Groups</button>
                <button class="tab-btn" data-tab="difficulty">By Difficulty</button>
            </div>

            <!-- All Questions Tab -->
            <div class="tab-content active" id="all-tab">
                <?php if (empty($questions)): ?>
                    <div class="no-questions">
                        <i class="fas fa-question-circle"></i>
                        <p>No questions found</p>
                    </div>
                <?php else: ?>
                    <div class="questions-container">
                        <?php $current_question_number = ($page - 1) * $questions_per_page + 1; ?>
                        <?php foreach ($questions as $question): ?>
                            <div class="question-card" data-id="<?php echo $question['question_ID']; ?>">
                                <div class="card-header">
                                    <input type="checkbox" class="question-checkbox" 
                                           value="<?php echo $question['question_ID']; ?>">
                                    <span class="question-id">#<?php echo $current_question_number; ?></span>
                                    <span class="difficulty-badge difficulty-<?php echo $question['difficulty']; ?>">
                                        <?php echo ['Easy', 'Medium', 'Hard'][$question['difficulty'] - 1]; ?>
                                    </span>
                                    <?php if ($question['group_num'] > 0): ?>
                                        <span class="group-badge">
                                            Group <?php echo $question['group_num']; ?>
                                            (<?php echo $question['group_size']; ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-preview">
                                    <?php echo substr(strip_tags($question['question_text']), 0, 150); ?>...
                                </div>
                                <div class="card-footer">
                                    <span class="mark"><?php echo $question['mark']; ?> pts</span>
                                    <span class="date"><?php echo date('M d, Y', strtotime($question['date'])); ?></span>
                                    <div class="card-actions">
                                        <a href="edit_question.php?id=<?php echo $question['question_ID']; ?>" 
                                           class="btn-action quick-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_question.php?id=<?php echo $question['question_ID']; ?>" 
                                           class="btn-action quick-delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php $current_question_number++; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

             <!-- By Groups Tab -->
             <div class="tab-content" id="groups-tab">
                <div class="loading">Loading group questions...</div>
            </div>

            <!-- By Difficulty Tab -->
            <div class="tab-content" id="difficulty-tab">
                <div class="loading">Loading questions by difficulty...</div>
            </div>

            <!-- Pagination -->
            <?php if ($total_questions > $questions_per_page): ?>
                <div class="pagination" data-page="<?php echo $page; ?>">
                    <?php if ($page > 1): ?>
                        <a href="?subject_id=<?php echo $subject_id; ?>&page=<?php echo $page-1; ?><?php 
                            echo !empty($search) ? "&search=".urlencode($search) : '';
                            echo $difficulty > 0 ? "&difficulty=$difficulty" : '';
                            echo $group_num > 0 ? "&group=$group_num" : '';
                        ?>">
                            &laquo; Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $total_pages = ceil($total_questions / $questions_per_page);
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<a href="?subject_id='.$subject_id.'&page=1'.(!empty($search) ? "&search=".urlencode($search) : '').'">1</a>';
                        if ($start_page > 2) echo '<span class="dots">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?subject_id=<?php echo $subject_id; ?>&page=<?php echo $i; ?><?php 
                            echo !empty($search) ? "&search=".urlencode($search) : '';
                            echo $difficulty > 0 ? "&difficulty=$difficulty" : '';
                            echo $group_num > 0 ? "&group=$group_num" : '';
                        ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php 
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) echo '<span class="dots">...</span>';
                        echo '<a href="?subject_id='.$subject_id.'&page='.$total_pages.(!empty($search) ? "&search=".urlencode($search) : '').'">'.$total_pages.'</a>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?subject_id=<?php echo $subject_id; ?>&page=<?php echo $page+1; ?><?php 
                            echo !empty($search) ? "&search=".urlencode($search) : '';
                            echo $difficulty > 0 ? "&difficulty=$difficulty" : '';
                            echo $group_num > 0 ? "&group=$group_num" : '';
                        ?>">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for bulk actions -->
    <div id="bulk-modal" class="modal">
        <div class="modal-content">
            <h3 id="modal-title">Bulk Action</h3>
            <div id="modal-body">
                <!-- Content will be loaded here based on action -->
            </div>
            <div class="modal-footer">
                <button id="confirm-bulk-action" class="btn btn-primary">Confirm</button>
                <button class="btn btn-cancel close-modal">Cancel</button>
            </div>
        </div>
    </div>

    <script defer src="music_player.js"></script>
</body>
</html>
