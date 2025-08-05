<?php
session_start();
require_once 'db_connection.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

// Get POST data
$subject_id = $_POST['subject_id'];
$exam_date = $_POST['exam_date'];
$question_ids = $_POST['questions'];
$template_type = isset($_POST['template_type']) ? $_POST['template_type'] : 'default';

if (!$subject_id || !$exam_date || empty($question_ids)) {
    die("Missing required parameters.");
}

// Fetch subject details
$stmt = $conn->prepare("
    SELECT s.subject_name, s.duration, u.university_name_en, s.university_ID 
    FROM subject s
    JOIN university u ON s.university_ID = u.university_ID
    WHERE s.subject_ID = ?
");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch selected questions with answers
$placeholders = implode(',', array_fill(0, count($question_ids), '?'));
$types = str_repeat('i', count($question_ids));
$stmt = $conn->prepare("
    SELECT question_ID, question_text, mark,
           ans_A, ans_B, ans_C, ans_D, ans_E,
           is_correct_A, is_correct_B, is_correct_C, is_correct_D, is_correct_E
    FROM question 
    WHERE question_ID IN ($placeholders)
");
$stmt->bind_param($types, ...$question_ids);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Create a zip archive for all versions
$zip = new ZipArchive();
$zip_filename = 'exams/exam_versions_' . time() . '.zip';
if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
    die("Cannot create zip file");
}

// Generate versions A through D, both graded and ungraded
$versions = [];
$shuffled_questions = [];

// First, prepare shuffled questions for each version
for ($version = 'A'; $version <= 'D'; $version++) {
    // For version A, keep original order
    if ($version == 'A') {
        $shuffled_questions[$version] = $questions;
    } else {
        // For other versions, shuffle once and store
        $shuffled_questions[$version] = $questions;
        shuffle($shuffled_questions[$version]);
    }
    
    // Add both graded and ungraded versions to the list
    $versions[] = ['version' => $version, 'graded' => true];
    $versions[] = ['version' => $version, 'graded' => false];
}

// Process each version
foreach ($versions as $version_info) {
    $version = $version_info['version'];
    $is_graded = $version_info['graded'];
    
    // Use the pre-shuffled questions for this version
    $questions_for_version = $shuffled_questions[$version];

    // Build questions content
    $questions_tex = '';
    foreach ($questions_for_version as $index => $q) {
        // Escape special characters
        $text = htmlspecialchars_decode($q['question_text'], ENT_QUOTES);
        $text = str_replace(['%', '&', '_', '#', '{', '}'], ['\\%','\\&','\\_','\\#','\\{','\\}'], $text);

        // Replace math delimiters
        $text = preg_replace('/\$(.*?)\$/', '\\(\1\\)', $text);
        $text = preg_replace('/\$\$(.*?)\$\$/', '\\[\1\\]', $text);

        // Start question
        $questions_tex .= "\\noindent \\textbf{" . ($index + 1) . ".} $text\n\n";

        // Prepare choices
        $choices = [];
        $options = ['A', 'B', 'C', 'D', 'E'];
        foreach ($options as $opt) {
            $answer = '';
            if (!empty($q["ans_$opt"])) {
                $answer = str_replace(['%','&','_','#','{','}'], 
                                    ['\\%','\\&','\\_','\\#','\\{','\\}'], 
                                    $q["ans_$opt"]);
                // Only underline correct answers in graded versions
                if ($is_graded && $q["is_correct_$opt"]) {
                    $answer = "\\underline{" . $answer . "}"; // Underline correct answer
                }
            }
            $choices[] = $answer;
        }

        // Generate the choices using the choice command defined in template.tex
        foreach ($options as $index => $opt) {
            if (!empty($choices[$index])) {
                $questions_tex .= "\\noindent\\choice{" . $opt . "}{" . $choices[$index] . "}{}"
                    . "\n";
            }
        }
        
        // Add points information
        $questions_tex .= "\\hfill \\textbf{[".$q['mark']." points]}\n\n\\vspace{0.5em}\n";
    }

    // Load template based on selected type
    $template_path = __DIR__ . '/Latex/';
    
    // Select the appropriate template file based on user's choice
    // - default: Standard layout with questions in sequence
    // - split: Questions are arranged in two columns
    switch ($template_type) {
        case 'split':
            $template_path .= 'template_split.tex';
            break;
        case 'split_line':
            $template_path .= 'template_split_line.tex';
            break;
        default:
            $template_path .= 'template.tex';
            break;
    }
    $latex_content = file_get_contents($template_path);

    // Replace placeholders
    // Just show the version letter without indicating if it's graded or ungraded
    $replacements = [
        '<<SUBJECT>>' => $subject['subject_name'],
        '<<UNIVERSITY>>' => $subject['university_name_en'],
        '<<DURATION>>' => $subject['duration'],
        '<<MODEL>>' => $version, // Only show the version letter (A, B, C, or D)
        '<<DATE>>' => date('F j, Y', strtotime($exam_date)),
        '<<QUESTIONS>>' => $questions_tex
    ];
    
    
    
    // Apply all replacements
    $latex_content = str_replace(array_keys($replacements), array_values($replacements), $latex_content);

    // Save .tex file
    $exam_dir = __DIR__ . '/exams/';
    
    // Ensure the exams directory exists
    if (!is_dir($exam_dir)) {
        mkdir($exam_dir, 0755, true);
    }
    
    $grade_suffix = $is_graded ? '_graded' : '_ungraded';
    $filename_base = 'exam_v' . $version . $grade_suffix . '_' . time();
    $latex_file = $exam_dir . $filename_base . '.tex';
    $pdf_file = $exam_dir . $filename_base . '.pdf';

    file_put_contents($latex_file, $latex_content);

    // Run pdflatex
    $output = [];
    $return_var = null;
    // Use pdflatex from PATH if available, otherwise use the full path
    if (trim(shell_exec('where pdflatex')) !== '') {
        $command = 'pdflatex --interaction=nonstopmode --output-directory="' . $exam_dir . '" "' . $latex_file . '"';
    } else {
        $command = '"C:\\Program Files\\MiKTeX\\miktex\\bin\\x64\\pdflatex.exe" --interaction=nonstopmode --output-directory="' . $exam_dir . '" "' . $latex_file . '"';
    }
    exec($command, $output, $return_var);

    if ($return_var !== 0 || !file_exists($pdf_file)) {
        // Log error but continue with other versions
        $error_message = "Error generating PDF for version $version: " . implode("\n", $output);
        error_log($error_message);
        
        // Create a debug file with more information
        $debug_file = $exam_dir . 'debug_' . $version . '_' . time() . '.txt';
        $debug_content = "Command: $command\n\nReturn code: $return_var\n\nOutput:\n" . implode("\n", $output);
        file_put_contents($debug_file, $debug_content);
        
        // For bordered template, check if mdframed package is installed
        if ($template_type == 'bordered' && strpos(implode("\n", $output), 'mdframed.sty') !== false) {
            $debug_content .= "\n\nIMPORTANT: The LaTeX mdframed package appears to be missing.\n"
                           . "Please install it using: tlmgr install mdframed\n"
                           . "or through your LaTeX distribution's package manager.";
            file_put_contents($debug_file, $debug_content);
        }
        
        continue;
    }

    // Add PDF to zip
    $zip->addFile($pdf_file, $filename_base . '.pdf');

    // Save to database (only once, for graded version A)
    if ($version == 'A' && $is_graded) {
        $professor_id = $_SESSION['user_id'];
        $university_id = $subject['university_ID'];
        $pdf_path = 'exams/' . $filename_base . '.pdf';
        $creation_date = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO exam (professor_ID, university_ID, subject_ID, creation_date, exam_date, pdf_file_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $professor_id, $university_id, $subject_id, $creation_date, $exam_date, $pdf_path);
        $stmt->execute();
        $exam_id = $conn->insert_id;
        $stmt->close();
    }
}

$zip->close();

// Verify zip file exists and is readable before serving
if (!file_exists($zip_filename) || !is_readable($zip_filename)) {
    header('HTTP/1.1 500 Internal Server Error');
    die('Error: Could not create exam package. Please try again.');
}

// Serve the zip file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="exam_versions.zip"');
header('Content-Length: ' . filesize($zip_filename));
readfile($zip_filename);

// Clean up
unlink($zip_filename);
exit;