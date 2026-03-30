<?php
session_start();
require_once 'db_connection.php';

/* ========= Auth ========= */
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

/* ========= Inputs ========= */
$subject_id    = $_POST['subject_id']    ?? null;
$exam_date     = $_POST['exam_date']     ?? null;
$question_ids  = $_POST['questions']     ?? [];
$template_type = $_POST['template_type'] ?? 'default';

if (!$subject_id || !$exam_date || empty($question_ids)) {
    die("Missing required parameters.");
}

/* ========= Sanity checks ========= */
if (!class_exists('ZipArchive')) {
    die("PHP ZipArchive extension is not enabled.");
}

/* ========= LaTeX helpers (mixed Arabic/English, math-safe) ========= */

/** Escape LaTeX special chars INSIDE macro arguments (do NOT touch { } ) */
function latex_escape_arg($s) {
    if ($s === null) return '';
    $map = [
        '\\' => '\\textbackslash{}',
        '$'  => '\\$',
        '&'  => '\\&',
        '#'  => '\\#',
        '_'  => '\\_',
        '%'  => '\\%',
        '^'  => '\\^{}',
        '~'  => '\\~{}',
    ];
    return strtr((string)$s, $map);
}

/** Detect Arabic codepoints */
function has_arabic($s) {
    return preg_match('/\p{Arabic}/u', (string)$s) === 1;
}

/** Wrap plain (non-math) text with language macro (direction handled elsewhere) */
function lang_wrap_basic($s) {
    $inner = latex_escape_arg($s);
    return has_arabic($s)
        ? '{\\textarabic{' . $inner . '}}'
        : '{\\textenglish{' . $inner . '}}';
}

/** Keep \(...\) / \[...\] as-is; convert $..$ / $$..$$ into those forms */
function normalize_math_segment($seg) {
    $seg = (string)$seg;

    if ((strpos($seg, '\\(') === 0 && substr($seg, -2) === '\\)') ||
        (strpos($seg, '\\[') === 0 && substr($seg, -2) === '\\]')) {
        return $seg;
    }
    if (substr($seg, 0, 2) === '$$' && substr($seg, -2) === '$$') {
        $inner = substr($seg, 2, -2);
        return '\\[' . $inner . '\\]';
    }
    if (substr($seg, 0, 1) === '$' && substr($seg, -1) === '$') {
        $inner = substr($seg, 1, -1);
        return '\\(' . $inner . '\\)';
    }
    return $seg;
}

/** Wrap text while PRESERVING math */
function wrap_mixed_text_math_safe($s) {
    if ($s === null || $s === '') return '';

    $s = htmlspecialchars_decode((string)$s, ENT_QUOTES);

    $parts = preg_split(
        '/(\\\\\\[.*?\\\\\\]|\\\\\\(.*?\\\\\\)|\\$\\$.*?\\$\\$|\\$.*?\\$)/s',
        $s, -1, PREG_SPLIT_DELIM_CAPTURE
    );

    $out = '';
    foreach ($parts as $part) {
        if ($part === '') continue;
        if ($part[0] === '\\' || $part[0] === '$') {
            $out .= normalize_math_segment($part);
        } else {
            $out .= lang_wrap_basic($part);
        }
    }
    return $out;
}

/** Force display math \[...\] to inline \( ... \) (answers only) */
function force_inline_math($s) {
    return preg_replace('/\\\\\[(.*?)\\\\\]/s', '\\\\(\\1\\\\)', (string)$s);
}

/** Safe path join */
function join_paths(...$parts) {
    $out = [];
    foreach ($parts as $p) {
        if ($p === '' || $p === null) continue;
        $out[] = rtrim($p, "\\/");
    }
    return join(DIRECTORY_SEPARATOR, $out);
}

/** Run external command with fallback to exec if proc_open is disabled */
function run_cmd($cmd, $cwd = null) {
    if (function_exists('proc_open') && stripos(ini_get('disable_functions'), 'proc_open') === false) {
        $desc = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $desc, $pipes, $cwd);
        $stdout = $stderr = '';
        $code = -1;
        if (is_resource($proc)) {
            if (isset($pipes[1])) { $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]); }
            if (isset($pipes[2])) { $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]); }
            $code = proc_close($proc);
        }
        return [$code, $stdout, $stderr];
    }

    $output = [];
    $ret = 0;
    $redir = ' 2>&1';
    $prev = null;
    if ($cwd) { $prev = getcwd(); chdir($cwd); }
    exec($cmd . $redir, $output, $ret);
    if ($prev !== null) { chdir($prev); }
    return [$ret, implode("\n", $output), ""];
}

/* ========= Fetch subject ========= */
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

if (!$subject) {
    die("Subject not found.");
}

/* ========= Fetch selected questions ========= */
$placeholders = implode(',', array_fill(0, count($question_ids), '?'));
$types = str_repeat('i', count($question_ids));
$sql = "
    SELECT question_ID, question_text, mark,
           ans_A, ans_B, ans_C, ans_D, ans_E,
           is_correct_A, is_correct_B, is_correct_C, is_correct_D, is_correct_E
    FROM question 
    WHERE question_ID IN ($placeholders)
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$question_ids);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$questions) {
    die("No questions found.");
}

/* ========= Absolute output folder for exams & ZIP ========= */
$exam_dir = join_paths(__DIR__, 'exams');
if (!is_dir($exam_dir)) {
    mkdir($exam_dir, 0755, true);
}

/* ========= Prepare ZIP on an absolute path ========= */
$zip = new ZipArchive();
$zip_filename = join_paths($exam_dir, 'exam_versions_' . time() . '.zip');
$zip_status = $zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($zip_status !== TRUE) {
    die("Cannot create zip file at: " . $zip_filename . " (status: $zip_status)");
}
$__added_pdfs = 0;

/* ========= Prepare versions A..D (graded & ungraded) ========= */
$versions = [];
$shuffled_questions = [];

for ($version = 'A'; $version <= 'D'; $version++) {
    if ($version == 'A') {
        $shuffled_questions[$version] = $questions;
    } else {
        $shuffled_questions[$version] = $questions;
        shuffle($shuffled_questions[$version]);
    }
    $versions[] = ['version' => $version, 'graded' => true];
    $versions[] = ['version' => $version, 'graded' => false];
}

/* ========= Templates & engine ========= */
$template_dir = __DIR__ . '/latex/';
if (!is_dir($template_dir)) {
    die("Templates directory not found at: " . $template_dir);
}

/*
 * Modes & filenames EXACTLY as on disk:
 * - default     -> template.tex
 * - split       -> template_split.tex
 * - split_line  -> template_split_line.tex
 */
switch ($template_type) {
    case 'split':
        $template_file = 'template_split.tex';
        break;
    case 'split_line':
        $template_file = 'template_split_line.tex';
        break;
    case 'default':
    default:
        $template_file = 'template.tex';
        break;
}
$template_path = $template_dir . $template_file;
$template_content = file_get_contents($template_path);
if ($template_content === false) {
    die("Cannot read template file: {$template_path}");
}

// XeLaTeX path (MiKTeX on Windows)
$xelatex = 'C:\\Users\\medoo\\AppData\\Local\\Programs\\MiKTeX\\miktex\\bin\\x64\\xelatex.exe';
if (!file_exists($xelatex)) {
    die("XeLaTeX not found at: {$xelatex}");
}

/* ========= Generate each version ========= */
foreach ($versions as $version_info) {
    $version   = $version_info['version']; // A..D
    $is_graded = $version_info['graded'];
    $questions_for_version = $shuffled_questions[$version];

    $questions_tex = '';
    $options = ['A','B','C','D','E'];

    foreach ($questions_for_version as $index => $q) {

        $qraw  = $q['question_text'] ?? '';
        $qtext = wrap_mixed_text_math_safe($qraw);

        /* إن كان السطر كله إنكليزي (لا أحرف عربية) لفّه كلّه ببيئة LTR لمنع القلب */
        if (!has_arabic($qraw)) {
            $qtext = '\\begin{LTR}' . $qtext . '\\end{LTR}';
        }

        $qno = (string)($index + 1);
        $questions_tex .= "\\noindent \\qheader{{$qno}} {$qtext}\n\n";

        // Build choices (force display math to inline for answers)
        $A = $B = $C = $D = $E = '';
        foreach ($options as $opt) {
            $ans_raw = $q["ans_{$opt}"] ?? '';
            if ($ans_raw === '' || $ans_raw === null) { ${$opt} = ''; continue; }
            $val = wrap_mixed_text_math_safe(force_inline_math($ans_raw));

            // الخيار إنكليزي بالكامل؟ لُفّه ببيئة LTR
            if (!has_arabic($ans_raw)) {
                $val = '\\begin{LTR}' . $val . '\\end{LTR}';
            }

            if ($is_graded && !empty($q["is_correct_{$opt}"])) {
                $val = "\\underline{{$val}}";
            }
            ${$opt} = $val; // set $A/$B/... dynamically
        }

        // Render choices in a responsive 2x2 + last-row table
        $questions_tex .= "\\renderchoices{{$A}}{{$B}}{{$C}}{{$D}}{{$E}}\n";

        // Points (singular/plural) — لُفّها LTR
        $mark = isset($q['mark']) ? latex_escape_arg((string)$q['mark']) : '0';
        $points_label = ($mark === '1') ? 'Point' : 'Points';
        $questions_tex .= "{\\begin{LTR}\\hfill\\textenglish{[{$mark} {$points_label}]}\\end{LTR}}\\par\\vspace{0.5em}\n\n";
    }


    // --- Force "University Of Aleppo" capitalization if this is the target uni ---
    $university_raw = $subject['university_name_en'] ?? '';
    if (preg_match('/^\s*university\s+of\s+aleppo\s*$/i', $university_raw)) {
        $university_fixed = 'University Of Aleppo';
    } else {
        // Keep original, but fix common "of" case only for this uni name (no effect otherwise)
        $university_fixed = preg_replace('/\bUniversity of Aleppo\b/i', 'University Of Aleppo', $university_raw);
    }


    // Fill template placeholders — الأجزاء الإنجليزية تُلفّ ببيئة LTR
    $replacements = [
        '<<SUBJECT>>'    => wrap_mixed_text_math_safe($subject['subject_name'] ?? ''),
        '<<UNIVERSITY>>' => '{\\begin{LTR}' . wrap_mixed_text_math_safe($university_fixed) . '\\end{LTR}}',
        '<<DURATION>>'   => '{\\begin{LTR}' . wrap_mixed_text_math_safe($subject['duration'] ?? '') . '\\end{LTR}}',
        '<<MODEL>>'      => '{\\begin{LTR}' . wrap_mixed_text_math_safe($version) . '\\end{LTR}}',
        '<<DATE>>'       => '{\\begin{LTR}' . wrap_mixed_text_math_safe(date('F j, Y', strtotime($exam_date))) . '\\end{LTR}}',
        '<<QUESTIONS>>'  => $questions_tex
    ];
    $latex_content = str_replace(array_keys($replacements), array_values($replacements), $template_content);

    // Write .tex & compile (twice)
    $filename_base = 'exam_v' . $version . ($is_graded ? '_graded' : '_ungraded') . '_' . time();
    $latex_file    = join_paths($exam_dir, $filename_base . '.tex');
    $pdf_file      = join_paths($exam_dir, $filename_base . '.pdf');

    file_put_contents($latex_file, $latex_content);

    $cmd = '"' . $xelatex . '"' .
           ' -interaction=nonstopmode -halt-on-error -no-shell-escape' .
           ' -output-directory="' . $exam_dir . '" "' . $latex_file . '"';

    list($code1, $out1, $err1) = run_cmd($cmd, $exam_dir);
    list($code2, $out2, $err2) = run_cmd($cmd, $exam_dir);

    if ($code2 !== 0 || !file_exists($pdf_file)) {
        $jobname   = pathinfo($latex_file, PATHINFO_FILENAME);
        $log_file  = join_paths($exam_dir, $jobname . '.log');

        $log_tail = '';
        if (is_file($log_file)) {
            $log = file_get_contents($log_file);
            if ($log !== false) {
                $log_utf8 = @iconv('UTF-8', 'UTF-8//IGNORE', $log);
                if ($log_utf8 === false) {
                    $log_utf8 = @iconv('Windows-1252', 'UTF-8//IGNORE', $log);
                }
                $log_tail_full = $log_utf8 ?? $log;
                $lines = preg_split("/\R/", $log_tail_full);
                $n = count($lines);
                $start = max(0, $n - 300);
                $log_tail = implode("\n", array_slice($lines, $start));
            }
        }

        $tex_src = '';
        if (is_file($latex_file)) {
            $src = file_get_contents($latex_file);
            $src_utf8 = @iconv('UTF-8', 'UTF-8//IGNORE', $src);
            if ($src_utf8 === false) {
                $src_utf8 = @iconv('Windows-1252', 'UTF-8//IGNORE', $src);
            }
            $tex_src = $src_utf8 ?? $src;
        }

        header('HTTP/1.1 500 Internal Server Error');
        echo "<pre>";
        echo "==== XeLaTeX Error while generating version {$version} (" . ($is_graded ? "graded" : "ungraded") . ") ====\n\n";
        echo "Command:\n$cmd\n\n";
        echo "Return1: $code1\nSTDERR1:\n$err1\n\n";
        echo "Return2: $code2\nSTDERR2:\n$err2\n\n";
        echo "---- exam log (tail) ----\n";
        echo ($log_tail !== '' ? $log_tail : "[log file not found: $log_file]\n");
        echo "\n---- generated .tex ----\n";
        echo ($tex_src !== '' ? $tex_src : "[tex file not found: $latex_file]\n");
        echo "\n==============================================\n";
        echo "</pre>";
        exit;
    }

    if ($zip->addFile($pdf_file, $filename_base . '.pdf')) {
        $__added_pdfs++;
    }

    if ($version == 'A' && $is_graded) {
        $professor_id  = $_SESSION['user_id'];
        $university_id = $subject['university_ID'];
        $pdf_path      = 'exams/' . $filename_base . '.pdf';
        $creation_date = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO exam (professor_ID, university_ID, subject_ID, creation_date, exam_date, pdf_file_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $professor_id, $university_id, $subject_id, $creation_date, $exam_date, $pdf_path);
        $stmt->execute();
        $exam_id = $conn->insert_id;
        $stmt->close();
    }
}

/* ========= Close & serve ZIP ========= */
if ($__added_pdfs === 0) {
    $zip->addFromString('README.txt', "No PDFs were generated. Check the XeLaTeX diagnostics printed earlier.");
}

$close_ok = $zip->close();
clearstatcache(true, $zip_filename);

if ($close_ok !== TRUE || !file_exists($zip_filename)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo "<pre>";
    echo "==== ZIP Creation Error ====\n\n";
    echo "zip_close: " . var_export($close_ok, true) . "\n";
    echo "zip_path: $zip_filename\n";
    echo "exists: " . (file_exists($zip_filename) ? 'yes' : 'no') . "\n";
    echo "============================\n";
    echo "</pre>";
    exit;
}

if (!is_readable($zip_filename)) {
    header('HTTP/1.1 500 Internal Server Error');
    die('Error: ZIP created but not readable: ' . $zip_filename);
}

/* ========= Serve ZIP ========= */
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="exam_versions.zip"'); // <-- تم الإصلاح هنا
header('Content-Length: ' . filesize($zip_filename));
readfile($zip_filename);

@unlink($zip_filename);

/* ========= Deduct 1 token after successful generation ========= */
$professor_id = $_SESSION['user_id'];
$dec = $conn->prepare("UPDATE professor SET tokens = tokens - 1 WHERE prof_ID = ? AND tokens > 0");
$dec->bind_param("i", $professor_id);
$dec->execute();
$dec->close();

exit;