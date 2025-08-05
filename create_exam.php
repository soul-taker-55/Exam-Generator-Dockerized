<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$professor_id = $_SESSION['user_id'];
$error = '';
$success = '';
$questions = [];
$selected_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : (isset($_POST['subject_id']) ? $_POST['subject_id'] : null);
$exam_date = isset($_POST['exam_date']) ? $_POST['exam_date'] : '';

// Get professor's subjects
$subjects = [];
$stmt = $conn->prepare("SELECT subject_ID, subject_name FROM subject WHERE professor_ID = ?");
if ($stmt === false) {
    $error = "Database error: " . $conn->error;
} else {
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
}

// Get universities
$universities = [];
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

// Load questions when subject is selected
if ($selected_subject) {
    $stmt = $conn->prepare("
        SELECT q.question_ID, q.question_text, q.difficulty, q.mark, q.date, q.group_num, COUNT(q_group.question_ID) as group_size
        FROM question q
        LEFT JOIN question q_group ON q.group_num = q_group.group_num AND q.subject_ID = q_group.subject_ID AND q.group_num > 0
        WHERE q.professor_ID = ? AND q.subject_ID = ?
        GROUP BY q.question_ID, q.question_text, q.difficulty, q.mark, q.date, q.group_num
        ORDER BY q.difficulty, q.date DESC
    ");
    if ($stmt === false) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $professor_id, $selected_subject);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
        $stmt->close();
    }
}

// Handle form submission for generating exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_exam']) && !empty($_POST['questions'])) {
    if (empty($_POST['exam_date'])) {
        $error = "Exam date is required";
    } else {
        $success = "Exam generated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="create_exam_style.css">
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" id="MathJax-script" async></script>
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
                                echo htmlspecialchars($universities[$i]['university_name_en']);
                                if (count($universities) > 1) {
                                    if ($i+2 == count($universities)) {
                                        echo htmlspecialchars(" and ");
                                    }
                                    else if ($i+1 != count($universities)) {
                                        echo htmlspecialchars(', ');
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
                <a href="create_exam.php" class="active">
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
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>
    
    <div class="main-content">
        <h1><i class="fas fa-file-alt"></i> Create New Exam</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="examForm" action="generate_pdf.php">
            <div class="exam-creation-container">
                <div class="subject-selection">
                    <h2>Select Subject and Date</h2>
                    
                    <div class="form-group">
                        <label for="subject_id">Subject:</label>
                        <select id="subject_id" name="subject_id" onchange="loadQuestions(this.value)" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_ID']; ?>"
                                    <?php if ($selected_subject == $subject['subject_ID']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_date">Exam Date:</label>
                        <input style="width:280px;" type="date" id="exam_date" name="exam_date" 
                               value="<?php echo htmlspecialchars($exam_date); ?>" required>
                    </div>
                    
                    <h3>Exam Layout Options</h3>
                    <div class="form-group">
                        <label for="template_type">Template Style:</label>
                        <select id="template_type" name="template_type" onchange="showTemplatePreview(this.value)">
                            <option value="default">Default Template</option>
                            <option value="split">Split Page (Two Columns)</option>
                            <option value="split_line">Split Page with line (Two Columns)</option>
                        </select>
                    </div>
                    
                    <div class="template-preview">
                        <h3>Template Preview</h3>
                        <div id="preview-container">
                            <img id="template-preview-img" src="template_default_preview.svg" alt="Template Preview" width="100%">
                        </div>
                        <div id="template-description" class="template-description">
                            <p id="default-description">Standard layout with questions and answers in sequence.</p>
                            <p id="split-description" style="display: none;">Questions are arranged in two columns to minimaize wasted space.</p>
                            <p id="split-line-description" style="display: none;">Questions are arranged in two columns with a line between them to minimaize wasted space.</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($questions)): ?>
                    <div class="exam-summary">
                        <h3>Exam Summary</h3>
                        <div class="summary-item">
                            <span>Total Questions:</span>
                            <span id="totalQuestions"><?php echo count($questions); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Selected Questions:</span>
                            <span id="selectedCount">0</span>
                        </div>
                        <div class="summary-item">
                            <span>Total Points:</span>
                            <span id="totalPoints">0</span>
                        </div>
                        
                        <button type="submit" name="generate_exam" class="btn btn-generate">
                            <i class="fas fa-file-pdf"></i> Generate Exam PDF
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="question-selection">
                    <?php if (empty($questions) && $selected_subject): ?>
                        <div class="no-questions-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>No questions found for this subject.</p>
                            <a href="add_question.php?subject_id=<?php echo $selected_subject; ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Questions
                            </a>
                        </div>
                    <?php elseif (!empty($questions)): ?>
                        <h2>Select Questions</h2>
                        
                        <div class="filter-controls">
                            <div class="search-box">
                                <input type="text" id="questionSearch" placeholder="Search questions..." onkeyup="filterQuestions()">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="difficulty-filter">
                                <label>Filter by difficulty:</label>
                                <select id="difficultyFilter" onchange="filterQuestions()">
                                    <option value="0">All Difficulties</option>
                                    <option value="1">Easy</option>
                                    <option value="2">Medium</option>
                                    <option value="3">Hard</option>
                                </select>
                            </div>
                            <div class="selection-actions">
                                <button type="button" class="btn btn-secondary" onclick="selectAllQuestions()">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="deselectAllQuestions()">
                                    <i class="fas fa-times"></i> Deselect All
                                </button>
                            </div>
                        </div>
                        
                        <div class="question-list">
                            <?php $current_question_serial_number = 1; ?>
                            <?php foreach ($questions as $question): ?>
                            <div class="question-card" data-difficulty="<?php echo $question['difficulty']; ?>" data-points="<?php echo $question['mark']; ?>">
                                <div class="question-header">
                                    <div class="question-actions">
                                        <input type="checkbox" id="question_<?php echo $question['question_ID']; ?>" 
                                               name="questions[]" value="<?php echo $question['question_ID']; ?>"
                                               onchange="updateSelectedCount()">
                                        <label for="question_<?php echo $question['question_ID']; ?>" class="checkbox-label"></label>
                                    </div>
                                    <span class="question-serial-number">#<?php echo $current_question_serial_number; ?></span>
                                    <div class="difficulty-badge difficulty-<?php echo $question['difficulty']; ?>">
                                        <?php echo $question['difficulty'] == 1 ? 'Easy' : ($question['difficulty'] == 2 ? 'Medium' : 'Hard'); ?>
                                    </div>
                                    <?php if ($question['group_num'] > 0 && isset($question['group_size']) && $question['group_size'] > 0): ?>
                                        <span class="group-badge">
                                            Group <?php echo htmlspecialchars($question['group_num']); ?>
                                            (<?php echo htmlspecialchars($question['group_size']); ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="question-content">
                                    <?php echo htmlspecialchars($question['question_text']); ?>
                                </div>
                                <div class="question-footer">
                                    <span class="date" style="margin-right: 10px;"><?php echo date('M d, Y', strtotime($question['date'])); ?></span>
                                    <span class="mark"><?php echo $question['mark']; ?> pts</span>
                                </div>
                            </div>
                            <?php $current_question_serial_number++; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="no-results" style="display: none;">
                            <i class="fas fa-search"></i>
                            <p>No questions match your search criteria</p>
                        </div>
                    <?php elseif (empty($selected_subject)): ?>
                        <div class="select-subject-message">
                            <i class="fas fa-arrow-left"></i>
                            <p>Please select a subject to view available questions</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
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
        
        // Load questions when subject changes
        function loadQuestions(subjectId) {
            if (subjectId) {
                window.location.href = 'create_exam.php?subject_id=' + subjectId;
            }
        }
        
        // Filter questions based on search and difficulty
        function filterQuestions() {
            const searchText = document.getElementById('questionSearch').value.toLowerCase();
            const difficultyFilter = parseInt(document.getElementById('difficultyFilter').value);
            const questionCards = document.querySelectorAll('.question-card');
            let visibleCount = 0;
            
            questionCards.forEach(card => {
                const questionText = card.querySelector('.question-content').textContent.toLowerCase();
                const difficulty = parseInt(card.dataset.difficulty);
                
                const matchesSearch = questionText.includes(searchText);
                const matchesDifficulty = difficultyFilter === 0 || difficulty === difficultyFilter;
                
                if (matchesSearch && matchesDifficulty) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResultsElement = document.querySelector('.no-results');
            if (noResultsElement) {
                noResultsElement.style.display = visibleCount === 0 ? 'flex' : 'none';
            }
        }
        
        // Update selected count and total points
        function updateSelectedCount() {
            const selectedCheckboxes = document.querySelectorAll('input[name="questions[]"]:checked');
            const selectedCountElement = document.getElementById('selectedCount');
            const totalPointsElement = document.getElementById('totalPoints');
            
            if (selectedCountElement) {
                selectedCountElement.textContent = selectedCheckboxes.length;
            }
            
            if (totalPointsElement) {
                let totalPoints = 0;
                selectedCheckboxes.forEach(checkbox => {
                    const card = checkbox.closest('.question-card');
                    totalPoints += parseInt(card.dataset.points || 0);
                });
                totalPointsElement.textContent = totalPoints;
            }
        }
        
        // Select all visible questions
        function selectAllQuestions() {
            const visibleQuestions = document.querySelectorAll('.question-card[style="display: block"] input[type="checkbox"], .question-card:not([style]) input[type="checkbox"]');
            visibleQuestions.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
        }
        
        // Deselect all questions
        function deselectAllQuestions() {
            const checkboxes = document.querySelectorAll('input[name="questions[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }
        
        // Show template preview based on selection
        function showTemplatePreview(templateType) {
            const previewImg = document.getElementById('template-preview-img');
            
            // Hide all descriptions first
            document.getElementById('default-description').style.display = 'none';
            document.getElementById('split-description').style.display = 'none';
            document.getElementById('split-line-description').style.display = 'none';
            
            // Show the appropriate image and description
            switch(templateType) {
                case 'split':
                    previewImg.src = 'template_split_preview.png';
                    document.getElementById('split-description').style.display = 'block';
                    break;
                case 'split_line':
                    previewImg.src = 'template_split_line_preview.png';
                    document.getElementById('split-line-description').style.display = 'block';
                    break;
                default:
                    previewImg.src = 'template_default_preview.png';
                    document.getElementById('default-description').style.display = 'block';
                    break;
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
            // Initialize template preview
            showTemplatePreview(document.getElementById('template_type').value);
        });
    </script>
</body>
</html>