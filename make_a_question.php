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
    SELECT s.subject_name, u.university_name_en 
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

$error = '';
$success = '';

// Initialize test paper questions array in session if not exists
if (!isset($_SESSION['test_paper_questions'])) {
    $_SESSION['test_paper_questions'] = [];
}

// Handle adding question to test paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_test'])) {
    $question_text = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
    $mark = isset($_POST['mark']) ? (int)$_POST['mark'] : 1;
    $difficulty = isset($_POST['difficulty']) ? (int)$_POST['difficulty'] : 1;
    $is_sub = isset($_POST['is_sub']) ? 1 : 0;
    
    // Get choices from form
    $choices = [];
    $correct_answer = isset($_POST['correct_answer']) ? $_POST['correct_answer'] : '';
    
    // Debug line to check what's being received
    // echo "Debug - Correct answer: " . $correct_answer;
    
    foreach ($_POST['choices'] as $key => $choice_text) {
        if (!empty(trim($choice_text))) {
            $is_correct = ($correct_answer == $key);
            $choices[$key] = [
                'text' => $choice_text,
                'is_correct' => $is_correct
            ];
            
            if ($is_correct) {
                $correct_answer = $key;
            }
        }
    }
    
    // Validate inputs
    if (empty($question_text)) {
        $error = 'Question text is required';
    } elseif (empty($choices)) {
        $error = 'At least one answer choice is required';
    } elseif ($correct_answer === '') { // <-- Fix: check for empty string, not empty()
        $error = 'Please select the correct answer';
    } else {
        // Add question to test paper in session
        $_SESSION['test_paper_questions'][] = [
            'subject_id' => $subject_id,
            'question_text' => $question_text,
            'mark' => $mark,
            'difficulty' => $difficulty,
            'is_sub' => $is_sub,
            'choices' => $choices,
            'correct_answer' => $correct_answer
        ];
        
        $success = 'Question added to test paper!';
        $_POST = []; // Clear form
    }
}

// Handle saving all questions to database
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_test_paper'])) {
    if (empty($_SESSION['test_paper_questions'])) {
        $error = 'No questions in test paper to save';
    } else {
        // Get next group number
        $stmt = $conn->prepare("SELECT COALESCE(MAX(group_num), 0) + 1 AS next_group FROM question");
        $stmt->execute();
        $result = $stmt->get_result();
        $group_num = $result->fetch_assoc()['next_group'];
        $stmt->close();
        
        // Insert all questions with the same group_num
        $success_count = 0;
        foreach ($_SESSION['test_paper_questions'] as $question) {
            $stmt = $conn->prepare("
                INSERT INTO question (
                    professor_ID, subject_ID, question_text, difficulty, mark,
                    ans_A, is_correct_A, ans_B, is_correct_B, 
                    ans_C, is_correct_C, ans_D, is_correct_D, ans_E, is_correct_E,
                    group_num, date, is_sub
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)
            ");
            
            // Prepare answer data (up to 5 answers)
            $answers = array_slice($question['choices'], 0, 5);
            while (count($answers) < 5) {
                $answers[] = ['text' => '', 'is_correct' => 0];
            }
            
            // Convert boolean values to integers for database
            $ans_A = $answers[0]['text'];
            $is_correct_A = $answers[0]['is_correct'] ? 1 : 0;
            $ans_B = $answers[1]['text'];
            $is_correct_B = $answers[1]['is_correct'] ? 1 : 0;
            $ans_C = $answers[2]['text'];
            $is_correct_C = $answers[2]['is_correct'] ? 1 : 0;
            $ans_D = $answers[3]['text'];
            $is_correct_D = $answers[3]['is_correct'] ? 1 : 0;
            $ans_E = $answers[4]['text'];
            $is_correct_E = $answers[4]['is_correct'] ? 1 : 0;
            
            $stmt->bind_param(
                "iisiisisiisiiiiii", 
                $professor_id, $question['subject_id'], $question['question_text'], 
                $question['difficulty'], $question['mark'],
                $ans_A, $is_correct_A,
                $ans_B, $is_correct_B,
                $ans_C, $is_correct_C,
                $ans_D, $is_correct_D,
                $ans_E, $is_correct_E,
                $group_num, $question['is_sub']
            );
            
            if ($stmt->execute()) {
                $success_count++;
            }
            $stmt->close();
        }
        
        if ($success_count > 0) {
            $success = "Successfully saved $success_count questions to database with group number $group_num";
            $_SESSION['test_paper_questions'] = []; // Clear test paper
        } else {
            $error = 'Failed to save questions to database';
        }
    }
}

// Handle clearing test paper
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_test_paper'])) {
    $_SESSION['test_paper_questions'] = [];
    $success = 'Test paper cleared';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - <?php echo htmlspecialchars($subject['subject_name']); ?></title>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script src="make_a_question_js.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="make_a_question_style.css">
    <link rel = "icon" class="fas fa-square-root-alt"> </icon>
</head>
<body>
<div class="sidebar">
        <div class="sidebar-header">
            <span style="margin-left:8px;">Math Symbols</span>
            <button class="toggle-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <div class="sidebar-content">
            <div class="category" onclick="toggleCategory(this)">
                <div class="category-header">
                    <i class="fas fa-calculator"></i>
                    <span class="category-name">Basic Operations</span>
                </div>
                <div class="symbol-panel">
                    <button class="symbol-button" onclick="insertSymbol('+')">+</button>
                    <button class="symbol-button" onclick="insertSymbol('-')">-</button>
                    <button class="symbol-button" onclick="insertSymbol('\\times')">×</button>
                    <button class="symbol-button" onclick="insertSymbol('\\div')">÷</button>
                    <button class="symbol-button" onclick="insertSymbol('=')">=</button>
                    <button class="symbol-button" onclick="insertSymbol('\\neq')">≠</button>
                    <button class="symbol-button" onclick="insertSymbol('\\pm')">±</button>
                    <button class="symbol-button" onclick="insertSymbol('\\cdot')">·</button>
                </div>
            </div>
            
            <div class="category" onclick="toggleCategory(this)">
                <div class="category-header">
                    <i class="fas fa-infinity"></i>
                    <span class="category-name">Calculus</span>
                </div>
                <div class="symbol-panel">
                    <button class="symbol-button" onclick="insertSymbol('\\int_{a}^{b}')">∫</button>
                    <button class="symbol-button" onclick="insertSymbol('\\iint')">∬</button>
                    <button class="symbol-button" onclick="insertSymbol('\\iiint')">∭</button>
                    <button class="symbol-button" onclick="insertSymbol('\\oint')">∮</button>
                    <button class="symbol-button" onclick="insertSymbol('\\frac{d}{dx}')">d/dx</button>
                    <button class="symbol-button" onclick="insertSymbol('\\frac{\\partial}{\\partial x}')">∂/∂x</button>
                    <button class="symbol-button" onclick="insertSymbol('\\nabla')">∇</button>
                    <button class="symbol-button" onclick="insertSymbol('\\Delta')">Δ</button>
                    <button class="symbol-button" onclick="insertSymbol('\\lim_{x \\to a}')">lim</button>
                    <button class="symbol-button" onclick="insertSymbol('\\sum_{i=1}^{n}')">Σ</button>
                    <button class="symbol-button" onclick="insertSymbol('\\prod_{i=1}^{n}')">Π</button>
                    <button class="symbol-button" onclick="insertSymbol('\\infty')">∞</button>
                </div>
            </div>
            
            <div class="category" onclick="toggleCategory(this)">
                <div class="category-header">
                    <i class="fas fa-greater-than-equal"></i>
                    <span class="category-name">Algebra</span>
                </div>
                <div class="symbol-panel">
                    <button class="symbol-button" onclick="insertSymbol('\\frac{a}{b}')">a/b</button>
                    <button class="symbol-button" onclick="insertSymbol('\\sqrt{x}')">√x</button>
                    <button class="symbol-button" onclick="insertSymbol('\\sqrt[n]{x}')">ⁿ√x</button>
                    <button class="symbol-button" onclick="insertSymbol('x^{n}')">xⁿ</button>
                    <button class="symbol-button" onclick="insertSymbol('x_{n}')">xₙ</button>
                    <button class="symbol-button" onclick="insertSymbol('\\leq')">≤</button>
                    <button class="symbol-button" onclick="insertSymbol('\\geq')">≥</button>
                    <button class="symbol-button" onclick="insertSymbol('\\approx')">≈</button>
                    <button class="symbol-button" onclick="insertSymbol('\\equiv')">≡</button>
                    <button class="symbol-button" onclick="insertSymbol('\\propto')">∝</button>
                </div>
            </div>
            
            <div class="category" onclick="toggleCategory(this)">
                <div class="category-header">
                    <i class="fas fa-shapes"></i>
                    <span class="category-name">Geometry</span>
                </div>
                <div class="symbol-panel">
                    <button class="symbol-button" onclick="insertSymbol('\\pi')">π</button>
                    <button class="symbol-button" onclick="insertSymbol('\\theta')">θ</button>
                    <button class="symbol-button" onclick="insertSymbol('\\alpha')">α</button>
                    <button class="symbol-button" onclick="insertSymbol('\\beta')">β</button>
                    <button class="symbol-button" onclick="insertSymbol('\\gamma')">γ</button>
                    <button class="symbol-button" onclick="insertSymbol('\\sin')">sin</button>
                    <button class="symbol-button" onclick="insertSymbol('\\cos')">cos</button>
                    <button class="symbol-button" onclick="insertSymbol('\\tan')">tan</button>
                    <button class="symbol-button" onclick="insertSymbol('\\angle')">∠</button>
                    <button class="symbol-button" onclick="insertSymbol('\\perp')">⊥</button>
                    <button class="symbol-button" onclick="insertSymbol('\\parallel')">∥</button>
                </div>
            </div>
            
            <div class="category" onclick="toggleCategory(this)">
                <div class="category-header">
                    <i class="fas fa-superscript"></i>
                    <span class="category-name">Advanced</span>
                </div>
                <div class="symbol-panel">
                    <button class="symbol-button" onclick="insertSymbol('\\forall')">∀</button>
                    <button class="symbol-button" onclick="insertSymbol('\\exists')">∃</button>
                    <button class="symbol-button" onclick="insertSymbol('\\emptyset')">∅</button>
                    <button class="symbol-button" onclick="insertSymbol('\\in')">∈</button>
                    <button class="symbol-button" onclick="insertSymbol('\\subset')">⊂</button>
                    <button class="symbol-button" onclick="insertSymbol('\\subseteq')">⊆</button>
                    <button class="symbol-button" onclick="insertSymbol('\\cup')">∪</button>
                    <button class="symbol-button" onclick="insertSymbol('\\cap')">∩</button>
                    <button class="symbol-button" onclick="insertSymbol('\\mathbb{R}')">ℝ</button>
                    <button class="symbol-button" onclick="insertSymbol('\\mathbb{Z}')">ℤ</button>
                </div>
            </div>
            
            <div class="category" onclick="toggleCategory(this)">
                <div class="category-header">
                    <i class="fas fa-grip-lines"></i>
                    <span class="category-name">Matrices</span>
                </div>
                <div class="symbol-panel">
                    <button class="symbol-button" onclick="insertSymbol('\\begin{pmatrix} a & b \\\\ c & d \\end{pmatrix}')">2x2</button>
                    <button class="symbol-button" onclick="insertSymbol('\\begin{bmatrix} a & b \\\\ c & d \\end{bmatrix}')">[2x2]</button>
                    <button class="symbol-button" onclick="insertSymbol('\\begin{pmatrix} a & b & c \\\\ d & e & f \\\\ g & h & i  \\end{pmatrix}')">3x3</button>
                    <button class="symbol-button" onclick="insertSymbol('\\begin{bmatrix} a & b & c \\\\ d & e & f \\\\ g & h & i \\end{bmatrix}')">[3x3]</button>

                    <button class="symbol-button" onclick="insertSymbol('\\vec{v}')">v⃗</button>
                    <button class="symbol-button" onclick="insertSymbol('\\hat{i}')">î</button>
                    <button class="symbol-button" onclick="insertSymbol('\\times')">×</button>
                    <button class="symbol-button" onclick="insertSymbol('\\cdot')">·</button>
                    <button class="symbol-button" onclick="insertSymbol('\\|v\\|')">‖v‖</button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <h1>
                <i class="fas fa-square-root-alt"></i> 
                Add Questions for <?php echo htmlspecialchars($subject['subject_name']); ?>
                <small><?php echo htmlspecialchars($subject['university_name_en']); ?></small>
            </h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="make_a_question.php?subject_id=<?php echo $subject_id; ?>">
                <input type="hidden" name="add_to_test" value="1">
                
                <div class="question-creator">
                    <h2>Create New Question</h2>
                    
                    <div class="form-group">
                        <label for="questionText">Question Text:</label>
                        <textarea id="questionText" name="question_text" placeholder="Enter your question here..." oninput="updatePreview()" required><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
                        <div>
                            <button type="button" class="symbol-button" onclick="insertSymbolIntoField('questionText', '\\[ \\]')">
                                <i class="fas fa-plus"></i> Add Equation
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Multiple Choice Answers:</label>
                        <div id="choicesContainer">
                            <?php
                            if (isset($_POST['choices'])) {
                                foreach ($_POST['choices'] as $key => $choice) {
                                    if (!empty(trim($choice))) {
                                        $fieldId = 'choices['.$key.']';
                                        echo '
                                        <div class="choice-item">
                                            <input type="radio" id="correct_'.$key.'" name="correct_answer" value="'.$key.'" '.(isset($_POST['correct_answer']) && $_POST['correct_answer'] == $key ? 'checked' : '').'>
                                            <input type="text" name="choices['.$key.']" placeholder="Answer choice '.($key+1).'" value="'.htmlspecialchars($choice).'">
                                            <div style="margin-left: 10px;">
                                                <button type="button" class="symbol-button" onclick="insertSymbolIntoField(\''.$fieldId.'\', \'\\\\[ \\\\]\')">
                                                    <i class="fas fa-plus"></i> Eq
                                                </button>
                                                <button type="button" class="symbol-button clear-btn" onclick="this.parentElement.parentElement.remove(); updatePreview()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                        <button type="button" class="add-choice" onclick="addChoice()">
                            <i class="fas fa-plus"></i> Add Choice
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="mark">Mark:</label>
                        <input type="number" id="mark" name="mark" min="1" value="<?php echo isset($_POST['mark']) ? $_POST['mark'] : 1; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty">Difficulty:</label>
                        <select id="difficulty" name="difficulty" required>
                            <option value="1" <?php if (isset($_POST['difficulty']) && $_POST['difficulty'] == 1) echo 'selected'; ?>>Easy</option>
                            <option value="2" <?php if (isset($_POST['difficulty']) && $_POST['difficulty'] == 2) echo 'selected'; ?>>Medium</option>
                            <option value="3" <?php if (isset($_POST['difficulty']) && $_POST['difficulty'] == 3) echo 'selected'; ?>>Hard</option>
                        </select>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_sub" name="is_sub" value="1" <?php if (isset($_POST['is_sub'])) echo 'checked'; ?>>
                        <label for="is_sub">Is Sub Question</label>
                    </div>
                    
                    <button type="submit" class="add-question">
                        <i class="fas fa-plus-circle"></i> Add Question to Test Paper
                    </button>
                </div>
            </form>
            
            <div class="question-preview">
                <h2>Question Preview</h2>
                <div id="livePreview">
                    <p class="empty-message">Your question will appear here as you type...</p>
                </div>
            </div>
            
            <div class="test-paper">
                <h2>Add Questions to the Same Group (<?php echo count($_SESSION['test_paper_questions']); ?> questions)</h2>
                
                <?php if (empty($_SESSION['test_paper_questions'])): ?>
                    <p class="empty-message">No questions added to test paper yet.</p>
                <?php else: ?>
                    <div id="testPaper">
                        <?php foreach ($_SESSION['test_paper_questions'] as $index => $question): ?>
                            <div class="question-item">
                                <div class="question-text">
                                    <strong>Question <?php echo $index + 1; ?>:</strong> 
                                    <?php echo htmlspecialchars($question['question_text']); ?>
                                </div>
                                <div class="choices-list">
                                    <?php foreach ($question['choices'] as $key => $choice): ?>
                                        <span class="<?php echo $key == $question['correct_answer'] ? 'choice-correct' : ''; ?>">
                                            <?php echo chr(65 + $key) . '. ' . htmlspecialchars($choice['text']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div>
                                    <small>Mark: <?php echo $question['mark']; ?> | Difficulty: 
                                    <?php 
                                        switch($question['difficulty']) {
                                            case 1: echo 'Easy'; break;
                                            case 2: echo 'Medium'; break;
                                            case 3: echo 'Hard'; break;
                                        }
                                    ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" action="make_a_question.php?subject_id=<?php echo $subject_id; ?>" class="controls">
                        <button type="submit" name="save_test_paper" class="btn-primary">
                            <i class="fas fa-save"></i> Save All Questions to Database
                        </button>
                        <button type="submit" name="clear_test_paper" class="clear-btn">
                            <i class="fas fa-trash"></i> Clear Test Paper
                        </button>
                        <a href="my_subjects.php" class="btn" style="background-color:#3498db;color:white;padding:10px 15px;border-radius:4px;text-decoration:none;">
                            <i class="fas fa-arrow-left"></i> Back to Subjects
                        </a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>