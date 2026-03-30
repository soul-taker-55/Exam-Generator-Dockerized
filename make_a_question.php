<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// ===== Inputs / Subject guard =====
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

// ===== Ensure session buffer for test paper =====
if (!isset($_SESSION['test_paper_questions'])) {
    $_SESSION['test_paper_questions'] = [];
}

// ===== Helper: normalize & cap choices =====
function buildNormalizedChoicesAndCorrectIndex(array $rawChoices, $correct_answer_raw, &$err, &$note)
{
    $rawChoices = array_map('trim', $rawChoices);
    $rawChoices = array_filter($rawChoices, fn($c) => $c !== '');
    $choicesList = array_values($rawChoices); // reindex 0..n-1

    if (count($choicesList) < 2) {
        $err = 'Please provide at least two answer choices';
        return [[], null];
    }

    if ($correct_answer_raw === '' || !is_numeric($correct_answer_raw)) {
        $err = 'Please select the correct answer';
        return [[], null];
    }

    $correctIdx = (int)$correct_answer_raw;
    if ($correctIdx < 0 || $correctIdx >= count($choicesList)) {
        $err = 'Selected correct answer is out of range';
        return [[], null];
    }

    if (count($choicesList) > 5) {
        if ($correctIdx >= 5) {
            $err = 'You added more than 5 choices and the selected correct answer would be dropped. Please re-select a correct answer within the first 5.';
            return [[], null];
        }
        $choicesList = array_slice($choicesList, 0, 5);
        $note = 'Note: Only the first 5 choices are kept (A–E). Extra choices were dropped.';
    }

    $choices = [];
    foreach ($choicesList as $i => $text) {
        $choices[] = [
            'text' => $text,
            'is_correct' => ($i === $correctIdx),
        ];
    }
    return [$choices, $correctIdx];
}

// ===== POST: add to test paper =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_test'])) {
    $question_text = trim($_POST['question_text'] ?? '');
    $mark = (int)($_POST['mark'] ?? 1);
    $difficulty = (int)($_POST['difficulty'] ?? 1);
    $is_sub = isset($_POST['is_sub']) ? 1 : 0;
    $sub_label = isset($_POST['sub_label']) ? trim($_POST['sub_label']) : '';

    if ($question_text === '') {
        $error = 'Question text is required';
    } else {
        if ($is_sub) {
            if ($sub_label === '') {
                $error = 'Please provide a sub-question label (e.g., A) when marking as Sub Question';
            } elseif (mb_strlen($sub_label) > 64) {
                $error = 'Sub-question label is too long (max 64 chars).';
            }
        }

        if (!$error) {
            $note = '';
            [$choices, $correctIdx] = buildNormalizedChoicesAndCorrectIndex(
                $_POST['choices'] ?? [],
                $_POST['correct_answer'] ?? '',
                $error,
                $note
            );

            if (!$error) {
                $_SESSION['test_paper_questions'][] = [
                    'subject_id'     => $subject_id,
                    'question_text'  => $question_text,
                    'mark'           => $mark,
                    'difficulty'     => $difficulty,
                    'is_sub'         => $is_sub,
                    'sub_label'      => $is_sub ? $sub_label : '',
                    'choices'        => $choices,     // 0..n
                    'correct_answer' => $correctIdx,  // points into choices
                ];

                $success = 'Question added to test paper!' . ($note ? ' ' . $note : '');
                $_POST = []; // clear form
            }
        }
    }
}

// ===== POST: remove one question =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_test'])) {
    $idx = (int)($_POST['remove_index'] ?? -1);
    if ($idx >= 0 && $idx < count($_SESSION['test_paper_questions'])) {
        array_splice($_SESSION['test_paper_questions'], $idx, 1);
        $success = 'Removed question from test paper';
    } else {
        $error = 'Invalid question index';
    }
}

// ===== POST: clear all =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_test_paper'])) {
    $_SESSION['test_paper_questions'] = [];
    $success = 'Test paper cleared';
}

// ===== POST: save all to DB =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_test_paper'])) {
    if (empty($_SESSION['test_paper_questions'])) {
        $error = 'No questions in test paper to save';
    } else {
        $stmt = $conn->prepare("SELECT COALESCE(MAX(group_num), 0) + 1 AS next_group FROM question");
        $stmt->execute();
        $result = $stmt->get_result();
        $group_num = (int)($result->fetch_assoc()['next_group'] ?? 1);
        $stmt->close();

        $success_count = 0;
        foreach ($_SESSION['test_paper_questions'] as $q) {
            $stmt = $conn->prepare("
                INSERT INTO question (
                    professor_ID, subject_ID, question_text, difficulty, mark,
                    ans_A, is_correct_A, ans_B, is_correct_B, 
                    ans_C, is_correct_C, ans_D, is_correct_D, ans_E, is_correct_E,
                    group_num, date, is_sub, sub_label
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)
            ");

            $answers = array_values($q['choices']);
            $answers = array_slice($answers, 0, 5);
            while (count($answers) < 5) {
                $answers[] = ['text' => '', 'is_correct' => 0];
            }

            $ans_A = $answers[0]['text']; $is_correct_A = $answers[0]['is_correct'] ? 1 : 0;
            $ans_B = $answers[1]['text']; $is_correct_B = $answers[1]['is_correct'] ? 1 : 0;
            $ans_C = $answers[2]['text']; $is_correct_C = $answers[2]['is_correct'] ? 1 : 0;
            $ans_D = $answers[3]['text']; $is_correct_D = $answers[3]['is_correct'] ? 1 : 0;
            $ans_E = $answers[4]['text']; $is_correct_E = $answers[4]['is_correct'] ? 1 : 0;

            $types = "ii";    // professor_id, subject_id
            $types .= "s";    // question_text
            $types .= "ii";   // difficulty, mark
            $types .= "si";   // A
            $types .= "si";   // B
            $types .= "si";   // C
            $types .= "si";   // D
            $types .= "si";   // E
            $types .= "ii";   // group_num, is_sub
            $types .= "s";    // sub_label

            $stmt->bind_param(
                $types,
                $professor_id, $q['subject_id'], $q['question_text'],
                $q['difficulty'], $q['mark'],
                $ans_A, $is_correct_A,
                $ans_B, $is_correct_B,
                $ans_C, $is_correct_C,
                $ans_D, $is_correct_D,
                $ans_E, $is_correct_E,
                $group_num,
                $q['is_sub'],
                $q['sub_label']
            );

            if ($stmt->execute()) {
                $success_count++;
            }
            $stmt->close();
        }

        if ($success_count > 0) {
            $success = "Successfully saved $success_count question(s) to database with group number $group_num";
            $_SESSION['test_paper_questions'] = []; // clear after commit
        } else {
            $error = 'Failed to save questions to database';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Add Questions - <?php echo htmlspecialchars($subject['subject_name']); ?></title>

<!-- MathJax for LaTeX -->
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="make_a_question_style.css">
<link rel="stylesheet" href="music_player.css">


<!-- Small CSS additions/overrides to ensure robust sidebar behavior -->
<style>
  /* Sidebar panels hidden by default; shown when the category is open */
  .symbol-panel { display: none; }
  .category.open > .symbol-panel { display: grid; }

  /* If your existing CSS uses '.active', keep this too for safety */
  .category.active > .symbol-panel { display: grid; }

  /* Click feedback and text selection */
  .category-header { cursor: pointer; user-select: none; }

  /* Preview styles (lightweight) */
  .badge { display:inline-block; padding:2px 6px; border-radius:6px; background:#eef; color:#334; font-size:12px; margin-left:6px; }
  .muted { color:#777; font-size:12px; }
  .choice-row { margin:4px 0; }
  .choice-correct { font-weight:bold; text-decoration:underline; }
  .inline-form { display:inline-block; margin-left:8px; }

  /* Quick actions bar */
  .top-actions { display:flex; gap:10px; align-items:center; margin:10px 0 20px; }
  .action-btn { display:flex; align-items:center; justify-content:center; background-color: var(--secondary-color, #3498db); color:#fff; text-decoration:none; padding:10px 14px; border-radius:8px; transition: background-color .3s; }
  .action-btn:hover { background-color:#2980b9; }
  .action-btn i { margin-right:8px; }
</style>
</head>
<body>

<!-- ======= FIXED, SELF-CONTAINED SIDEBAR ======= -->
<div class="sidebar">
  <div class="sidebar-header">
      <span style="margin-left:8px;">Math Symbols</span>
      <button class="toggle-sidebar" onclick="toggleSidebar()">
          <i class="fas fa-chevron-left"></i>
      </button>
  </div>
  <div class="sidebar-content">

      <div class="category">
          <div class="category-header" onclick="toggleCategory(this.parentElement)">
              <i class="fas fa-calculator"></i>
              <span class="category-name">Basic Operations</span>
          </div>
          <div class="symbol-panel" onclick="event.stopPropagation()">
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

      <div class="category">
          <div class="category-header" onclick="toggleCategory(this.parentElement)">
              <i class="fas fa-infinity"></i>
              <span class="category-name">Calculus</span>
          </div>
          <div class="symbol-panel" onclick="event.stopPropagation()">
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

      <div class="category">
          <div class="category-header" onclick="toggleCategory(this.parentElement)">
              <i class="fas fa-greater-than-equal"></i>
              <span class="category-name">Algebra</span>
          </div>
          <div class="symbol-panel" onclick="event.stopPropagation()">
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

      <div class="category">
          <div class="category-header" onclick="toggleCategory(this.parentElement)">
              <i class="fas fa-shapes"></i>
              <span class="category-name">Geometry</span>
          </div>
          <div class="symbol-panel" onclick="event.stopPropagation()">
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

      <div class="category">
          <div class="category-header" onclick="toggleCategory(this.parentElement)">
              <i class="fas fa-superscript"></i>
              <span class="category-name">Advanced</span>
          </div>
          <div class="symbol-panel" onclick="event.stopPropagation()">
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

      <div class="category">
          <div class="category-header" onclick="toggleCategory(this.parentElement)">
              <i class="fas fa-grip-lines"></i>
              <span class="category-name">Matrices</span>
          </div>
          <div class="symbol-panel" onclick="event.stopPropagation()">
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
<!-- ======= /SIDEBAR ======= -->

<div class="main-content">
  <div class="container">
    <h1>
      <i class="fas fa-square-root-alt"></i>
      Add Questions for <?php echo htmlspecialchars($subject['subject_name']); ?>
      <small><?php echo htmlspecialchars($subject['university_name_en']); ?></small>
    </h1>

    <div class="top-actions">
      <a href="dashboard.php" class="action-btn"><i class="fas fa-th-large"></i> Go To Dashboard</a>
      <a href="my_subjects.php" class="action-btn"><i class="fas fa-list"></i> Back to Subjects</a>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- ========== Create Question Form ========== -->
    <form method="POST" action="make_a_question.php?subject_id=<?php echo $subject_id; ?>">
      <input type="hidden" name="add_to_test" value="1">

      <div class="question-creator">
        <h2>Create New Question</h2>

        <div class="form-group">
          <label for="questionText">Question Text:</label>
          <textarea id="questionText" name="question_text" placeholder="Enter your question here..." oninput="updatePreview()" required><?php echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; ?></textarea>
          <div>
            <button type="button" class="symbol-button" onclick="insertSymbolIntoField('questionText', '\\\\[ \\\\]')">
              <i class="fas fa-plus"></i> Add Equation
            </button>
          </div>
        </div>

        <div class="form-group">
          <label>Multiple Choice Answers:</label>
          <div id="choicesContainer">
            <?php
            if (isset($_POST['choices']) && is_array($_POST['choices'])) {
                foreach ($_POST['choices'] as $key => $choice) {
                    if (trim($choice) !== '') {
                        $fieldName = 'choices['.$key.']';
                        echo '
                        <div class="choice-item">
                          <input type="radio" id="correct_'.$key.'" name="correct_answer" value="'.$key.'" '.(isset($_POST['correct_answer']) && $_POST['correct_answer'] == $key ? 'checked' : '').'>
                          <input type="text" id="'.$fieldName.'" name="'.$fieldName.'" placeholder="Answer choice '.($key+1).'" value="'.htmlspecialchars($choice).'" oninput="updatePreview()">
                          <div style="margin-left:10px;">
                            <button type="button" class="symbol-button" onclick="insertSymbolIntoField(\''.$fieldName.'\', \'\\\\[ \\\\]\')"><i class="fas fa-plus"></i> Eq</button>
                            <button type="button" class="symbol-button clear-btn" onclick="this.parentElement.parentElement.remove(); renumberChoices(); updatePreview()"><i class="fas fa-times"></i></button>
                          </div>
                        </div>';
                    }
                }
            }
            ?>
          </div>
          <button type="button" class="add-choice" onclick="addChoice()"><i class="fas fa-plus"></i> Add Choice</button>
          <div class="hint muted">Maximum 5 choices (A–E).</div>
        </div>

        <div class="form-group">
          <label for="mark">Mark:</label>
          <input type="number" id="mark" name="mark" min="1" value="<?php echo isset($_POST['mark']) ? (int)$_POST['mark'] : 1; ?>" required>
        </div>

        <div class="form-group">
          <label for="difficulty">Difficulty:</label>
          <select id="difficulty" name="difficulty" required>
            <option value="1" <?php if (isset($_POST['difficulty']) && (int)$_POST['difficulty'] === 1) echo 'selected'; ?>>Easy</option>
            <option value="2" <?php if (isset($_POST['difficulty']) && (int)$_POST['difficulty'] === 2) echo 'selected'; ?>>Medium</option>
            <option value="3" <?php if (isset($_POST['difficulty']) && (int)$_POST['difficulty'] === 3) echo 'selected'; ?>>Hard</option>
          </select>
        </div>

        <div class="form-group checkbox-group">
          <input type="checkbox" id="is_sub" name="is_sub" value="1" <?php if (isset($_POST['is_sub'])) echo 'checked'; ?> onchange="toggleSubLabel()">
          <label for="is_sub">Is Sub Question (block)</label>
        </div>

        <div class="form-group" id="subLabelWrap" style="display: <?php echo isset($_POST['is_sub']) ? 'block' : 'none'; ?>">
          <label for="sub_label">Sub-Question Label (e.g., A, A1):</label>
          <input type="text" id="sub_label" name="sub_label" maxlength="64" value="<?php echo isset($_POST['sub_label']) ? htmlspecialchars($_POST['sub_label']) : ''; ?>" oninput="updatePreview()">
          <small class="muted">Questions with the same label in the same group will stick together on shuffle.</small>
        </div>

        <button type="submit" class="add-question">
          <i class="fas fa-plus-circle"></i> Add Question to Test Paper
        </button>
      </div>
    </form>

    <!-- ========== Always-on Preview (MathJax-enabled) ========== -->
    <div class="question-preview">
      <h2>Question Preview</h2>
      <div id="livePreview"><p class="empty-message">Your question will appear here as you type...</p></div>
      <div id="liveChoicesPreview" class="choices-preview"></div>
    </div>

    <!-- ========== Test Paper Buffer ========== -->
    <div class="test-paper">
      <h2>Test Paper (<?php echo count($_SESSION['test_paper_questions']); ?> questions)</h2>

      <?php if (empty($_SESSION['test_paper_questions'])): ?>
        <p class="empty-message">No questions added to test paper yet.</p>
      <?php else: ?>
        <div id="testPaper">
          <?php foreach ($_SESSION['test_paper_questions'] as $index => $question): ?>
            <div class="question-item">
              <div class="question-text">
                <strong>
                  Question <?php echo $index + 1; ?>
                  <?php if (!empty($question['is_sub'])): ?>
                    <span class="badge">Sub: <?php echo htmlspecialchars($question['sub_label'] ?? ''); ?></span>
                  <?php endif; ?>
                  :
                </strong>
                <?php echo htmlspecialchars($question['question_text']); ?>
              </div>
              <div class="choices-list">
                <?php foreach ($question['choices'] as $key => $choice): ?>
                  <span class="<?php echo $choice['is_correct'] ? 'choice-correct' : ''; ?>">
                    <?php echo chr(65 + $key) . '. ' . htmlspecialchars($choice['text']); ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <div>
                <small>Mark: <?php echo (int)$question['mark']; ?> | Difficulty:
                <?php
                  switch ((int)$question['difficulty']) {
                    case 1: echo 'Easy'; break;
                    case 2: echo 'Medium'; break;
                    case 3: echo 'Hard'; break;
                    default: echo 'Unknown';
                  }
                ?>
                <?php if (!empty($question['is_sub'])) echo ' | <em>Sub-question</em>'; ?>
                </small>
              </div>
              <form method="POST" action="make_a_question.php?subject_id=<?php echo $subject_id; ?>" class="inline-form">
                <input type="hidden" name="remove_from_test" value="1">
                <input type="hidden" name="remove_index" value="<?php echo $index; ?>">
                <button type="submit" class="clear-btn"><i class="fas fa-trash"></i> Delete</button>
              </form>
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

<!-- ===== Inlined, conflict-proof JS ===== -->
<script>
  // Track the last focused input/textarea
  let lastFocusedElement = null;

  document.addEventListener('DOMContentLoaded', function () {
    // Focus tracking for insertion helpers
    document.querySelectorAll('textarea, input[type="text"]').forEach(input => {
      input.addEventListener('focus', function () { lastFocusedElement = this; });
    });

    // Initialize a choice only if none exist (avoids duplicates after postback)
    const choicesContainer = document.getElementById('choicesContainer');
    if (choicesContainer && choicesContainer.querySelectorAll('.choice-item').length === 0) {
      addChoice();
    }

    // Prevent clicks inside symbol panels from bubbling up
    document.querySelectorAll('.symbol-panel').forEach(panel => {
      panel.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    // Render preview as user types/selects
    document.querySelectorAll('textarea, input, select').forEach(el=>{
      el.addEventListener('input', updatePreview);
      el.addEventListener('change', updatePreview);
    });

    toggleSubLabel();
    updatePreview();
  });

  // Sidebar collapse/expand
  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed');

    const icon = document.querySelector('.toggle-sidebar i');
    if (icon) {
      const collapsed = sidebar.classList.contains('collapsed');
      icon.classList.toggle('fa-chevron-left', !collapsed);
      icon.classList.toggle('fa-chevron-right', collapsed);
    }
  }

  // Toggle a category (header-only, safe)
  function toggleCategory(categoryElement) {
    if (!categoryElement) return;

    // Optional accordion: close others
    document.querySelectorAll('.category').forEach(cat => {
      if (cat !== categoryElement) cat.classList.remove('open','active');
    });

    // Use both classes for compatibility with older CSS
    categoryElement.classList.toggle('open');
    categoryElement.classList.toggle('active');
  }

  // Insertion helpers — supports ID or name
  function insertAtCursor(el, symbol) {
    const start = el.selectionStart || 0;
    const end = el.selectionEnd || 0;
    const val = el.value || '';
    el.value = val.slice(0,start) + symbol + val.slice(end);
    el.focus();
    const pos = start + symbol.length;
    el.selectionStart = el.selectionEnd = pos;
    updatePreview();
  }
  function insertSymbol(symbol) {
    const el = lastFocusedElement;
    if (!el || (el.tagName!=='TEXTAREA' && el.tagName!=='INPUT')) return;

    // Special case: add \[ \] and place caret inside
    if (symbol === '\\[ \\]') {
      const start = el.selectionStart || 0;
      const val = el.value || '';
      el.value = val.slice(0,start) + '\\[ \\]' + val.slice(start);
      el.selectionStart = el.selectionEnd = start + 2;
      el.focus();
      updatePreview();
      return;
    }

    insertAtCursor(el, symbol);
  }
  function insertSymbolIntoField(fieldIdOrName, symbol) {
    let el = document.getElementById(fieldIdOrName);
    if (!el) {
      el = document.querySelector('textarea[name="'+fieldIdOrName+'"], input[name="'+fieldIdOrName+'"]');
    }
    if (!el) return;

    if (symbol === '\\[ \\]') {
      const start = el.selectionStart || 0;
      const val = el.value || '';
      el.value = val.slice(0,start) + '\\[ \\]' + val.slice(start);
      el.selectionStart = el.selectionEnd = start + 2;
      el.focus();
      updatePreview();
      return;
    }

    insertAtCursor(el, symbol);
  }

  // Choices helpers
  function addChoice() {
    const choicesContainer = document.getElementById('choicesContainer');
    if (!choicesContainer) return;

    const count = choicesContainer.querySelectorAll('.choice-item').length;
    if (count >= 5) { alert('Maximum 5 choices (A–E).'); return; }

    const name = `choices[${count}]`;

    const choiceDiv = document.createElement('div');
    choiceDiv.className = 'choice-item';
    choiceDiv.innerHTML = `
      <input type="radio" id="correct_${count}" name="correct_answer" value="${count}">
      <input type="text" id="${name}" name="${name}" placeholder="Answer choice ${count + 1}" oninput="updatePreview()">
      <div style="margin-left:10px;">
        <button type="button" class="symbol-button" onclick="insertSymbolIntoField('${name}', '\\\\[ \\\\]')">
          <i class="fas fa-plus"></i> Eq
        </button>
        <button type="button" class="symbol-button clear-btn" onclick="this.parentElement.parentElement.remove(); renumberChoices(); updatePreview()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;

    choicesContainer.appendChild(choiceDiv);

    // Track focus on the new input
    const newInput = document.getElementById(name);
    if (newInput) {
      lastFocusedElement = newInput;
      newInput.addEventListener('focus', function () { lastFocusedElement = this; });
    }

    // Update preview when picking correct answer
    const radio = choiceDiv.querySelector('input[type="radio"]');
    if (radio) radio.addEventListener('change', updatePreview);
  }

  function renumberChoices() {
    const items = document.querySelectorAll('#choicesContainer .choice-item');
    items.forEach((el, idx) => {
      const r = el.querySelector('input[type="radio"]');
      const t = el.querySelector('input[type="text"]');
      if (r) { r.id = 'correct_' + idx; r.value = idx; }
      if (t) { t.name = `choices[${idx}]`; t.id = `choices[${idx}]`; t.placeholder = `Answer choice ${idx + 1}`; }
    });
  }

  // Live preview (MathJax)
  function q(id){return document.getElementById(id)}
  function sanitizeForMath(str){
    return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
  function updatePreview(){
    const qText = q('questionText')?.value || '';
    const container = q('livePreview');
    const choicesDiv = q('liveChoicesPreview');
    if(!container) return;

    container.innerHTML = qText.trim()
      ? sanitizeForMath(qText)
      : '<p class="empty-message">Your question will appear here as you type...</p>';

    if(choicesDiv){
      const nodes = document.querySelectorAll('.choice-item input[type="text"]');
      let html = '';
      nodes.forEach((inp, i)=>{
        const radio = document.getElementById('correct_'+i);
        const isCorrect = radio && radio.checked;
        const label = String.fromCharCode(65 + i);
        const text = (inp.value||'').trim();
        if(text){
          html += '<div class="choice-row '+(isCorrect?'choice-correct':'')+'"><strong>'+label+'.</strong> '+sanitizeForMath(text)+'</div>';
        }
      });
      choicesDiv.innerHTML = html || '<div class="muted">Add choices to preview them here…</div>';
    }

    if (window.MathJax && MathJax.typesetPromise) {
      MathJax.typesetPromise([container, choicesDiv].filter(Boolean)).catch(console.error);
    }
  }

  // Sub label show/hide
  function toggleSubLabel(){
    const wrap = document.getElementById('subLabelWrap');
    const cb = document.getElementById('is_sub');
    if (wrap && cb) wrap.style.display = cb.checked ? 'block' : 'none';
  }
</script>

</body>
<script defer src="music_player.js"></script
</html>
<?php $conn->close(); ?>
