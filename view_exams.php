<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$professor_id = $_SESSION['user_id'];

// Helper to bind dynamic params to mysqli stmt
function bind_all_params(mysqli_stmt $stmt, string $types, array $params): void {
    if (count($params) === 0) return;
    $refs = [];
    foreach ($params as $i => $v) { $refs[$i] = &$params[$i]; }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Fetch subjects for filters
$subjects = [];
$stmt = $conn->prepare('SELECT subject_ID, subject_name FROM subject WHERE professor_ID = ? ORDER BY subject_name ASC');
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $subjects[] = $row; }
$stmt->close();

// Filters & options
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$subject_id   = isset($_GET['subject']) ? (int)$_GET['subject'] : 0; // 0 = all
$status       = isset($_GET['status']) ? $_GET['status'] : '';       // completed|scheduled|nodate|""
$date_on = isset($_GET['date']) ? trim($_GET['date']) : '';
$sort_by      = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'exam_date';
$sort_dir     = isset($_GET['sort_dir']) ? strtolower($_GET['sort_dir']) : 'desc';
$per_page     = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page         = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Sanitize inputs
$allowed_sort = [
    'exam_date' => 'e.exam_date',
    'creation_date' => 'e.creation_date',
    'subject_name' => 's.subject_name',
    'q_count' => 'qagg.q_count',
    'total_marks' => 'qagg.total_marks'
];
$order_by = $allowed_sort[$sort_by] ?? 'e.exam_date';
$order_dir = ($sort_dir === 'asc') ? 'ASC' : 'DESC';

if ($per_page < 5) $per_page = 5;
if ($per_page > 50) $per_page = 50;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = 'WHERE e.professor_ID = ?';
$params = [$professor_id];
$types = 'i';

if ($search !== '') {
    $where .= ' AND (s.subject_name LIKE ? OR e.pdf_file_path LIKE ?)';
    $like = "%" . $search . "%";
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($subject_id > 0) {
    $where .= ' AND e.subject_ID = ?';
    $params[] = $subject_id; $types .= 'i';
}
if ($status === 'completed') {
    $where .= ' AND e.exam_date IS NOT NULL AND e.exam_date < CURDATE()';
} elseif ($status === 'scheduled') {
    $where .= ' AND e.exam_date IS NOT NULL AND e.exam_date >= CURDATE()';
} elseif ($status === 'nodate') {
    $where .= ' AND e.exam_date IS NULL';
}
// Apply single exact exam date filter if provided
if ($date_on !== '') {
    $where .= ' AND e.exam_date = ?';
    $params[] = $date_on; $types .= 's';
}

$base_from = 'FROM exam e
JOIN subject s ON e.subject_ID = s.subject_ID
LEFT JOIN (
    SELECT exam_id, COUNT(*) AS q_count, COALESCE(SUM(marks),0) AS total_marks
    FROM exam_question
    GROUP BY exam_id
) qagg ON qagg.exam_id = e.exam_ID';

// Count total rows for pagination
$count_sql = "SELECT COUNT(*) $base_from $where";
$stmt = $conn->prepare($count_sql);
bind_all_params($stmt, $types, $params);
$stmt->execute();
$stmt->bind_result($total_rows);
$stmt->fetch();
$stmt->close();
$total_pages = (int)ceil($total_rows / $per_page);

// Fetch current page
$data_sql = "SELECT 
    e.exam_ID, e.creation_date, e.exam_date, e.pdf_file_path,
    s.subject_name, s.subject_ID,
    COALESCE(qagg.q_count, 0) AS q_count,
    COALESCE(qagg.total_marks, 0) AS total_marks
    $base_from
    $where
    ORDER BY $order_by $order_dir
    LIMIT ? OFFSET ?";

$params_with_limit = $params;
$types_with_limit = $types . 'ii';
$params_with_limit[] = $per_page;
$params_with_limit[] = $offset;

$stmt = $conn->prepare($data_sql);
bind_all_params($stmt, $types_with_limit, $params_with_limit);
$stmt->execute();
$result = $stmt->get_result();
$exams = [];
while ($row = $result->fetch_assoc()) {
    // Determine status & file presence
    $pdf_file = basename($row['pdf_file_path']);
    $full_path = __DIR__ . DIRECTORY_SEPARATOR . 'exams' . DIRECTORY_SEPARATOR . $pdf_file;
    $has_pdf = is_file($full_path);

    $status_label = 'No date';
    $status_class = 'badge-secondary';
    if (!empty($row['exam_date'])) {
        $d = strtotime($row['exam_date']);
        if ($d !== false) {
            if ($d < strtotime('today')) { $status_label = 'Completed'; $status_class = 'badge-success'; }
            else { $status_label = 'Scheduled'; $status_class = 'badge-info'; }
        }
    }
    if (!$has_pdf) { $status_label = 'Missing PDF'; $status_class = 'badge-danger'; }

    $row['status_label'] = $status_label;
    $row['status_class'] = $status_class;
    $row['has_pdf'] = $has_pdf;
    $row['pdf_basename'] = $pdf_file;
    $exams[] = $row;
}
$stmt->close();

// Universities for sidebar
$universities = [];
$stmt = $conn->prepare('SELECT u.university_name_en FROM university u INNER JOIN professor_university pu ON u.university_ID = pu.university_ID WHERE pu.professor_ID = ?');
$stmt->bind_param('i', $professor_id);
$stmt->execute();
$r2 = $stmt->get_result();
while ($row = $r2->fetch_assoc()) { $universities[] = $row; }
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Exams</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="view_exams.css">
    <link rel="stylesheet" href="music_player.css">
    <style>
        .filters { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .filters .wide { grid-column: span 2; }
        .table-card { background: var(--white); border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #fafafa; font-weight: 600; }
        .actions a, .actions button { margin-right: 8px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; color: #fff; }
        .badge-success { background: #2ecc71; }
        .badge-info { background: #3498db; }
        .badge-secondary { background: #7f8c8d; }
        .badge-danger { background: #e74c3c; }
        .pagination { display: flex; gap: 6px; justify-content: flex-end; margin-top: 12px; }
        .pagination a { padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: inherit; }
        .pagination a.active { background: var(--secondary-color); color: #fff; border-color: var(--secondary-color); }
        .table-responsive { overflow-x: auto; }
        .controls { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .download-busy { opacity: 0.7; pointer-events: none; }
        @media (max-width: 900px) {
            .filters { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <span>Exam Generator</span>
        <button class="toggle-sidebar"><i class="fas fa-chevron-left"></i></button>
    </div>
    <div class="sidebar-content">
        <div class="user-profile">
            <div class="avatar"><i class="fas fa-user-circle"></i></div>
            <div class="user-info">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Professor in 
                    <?php 
                        for ($i = 0; $i < count($universities); $i++) {
                            echo htmlspecialchars($universities[$i]['university_name_en']);
                            if (count($universities) > 1) {
                                if ($i+2 == count($universities)) echo htmlspecialchars(' and ');
                                else if ($i+1 != count($universities)) echo htmlspecialchars(', ');
                            }
                        }
                    ?>
                </p>
            </div>
        </div>
        <nav class="dashboard-nav">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            <a href="my_subjects.php"><i class="fas fa-book"></i><span>My Subjects</span></a>
            <a href="create_exam.php"><i class="fas fa-file-alt"></i><span>Create Exam</span></a>
            <a href="view_exams.php" class="active"><i class="fas fa-list"></i><span>View Exams</span></a>
            <a href="profile.php"><i class="fas fa-user-cog"></i><span>Profile</span></a>
            <a href="#" id="music-btn"><i class="fas fa-music"></i><span>Music</span></a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </nav>
    </div>
</div>

<div class="main-content">
    <div class="dashboard-header">
        <h1><i class="fas fa-list"></i> Your Exams</h1>
    </div>

    <div class="table-card">
        <form class="filters" method="get" action="view_exams.php">
            <input class="wide" type="text" name="search" placeholder="Search by subject or file name" value="<?php echo htmlspecialchars($search); ?>" />
            <select name="subject">
                <option value="0">All subjects</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?php echo (int)$sub['subject_ID']; ?>" <?php echo ($subject_id === (int)$sub['subject_ID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sub['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All statuses</option>
                <option value="completed" <?php echo $status==='completed'?'selected':''; ?>>Completed</option>
                <option value="scheduled" <?php echo $status==='scheduled'?'selected':''; ?>>Scheduled</option>
                <option value="nodate" <?php echo $status==='nodate'?'selected':''; ?>>No date</option>
            </select>
            <input type="date" name="date" value="<?php echo htmlspecialchars($date_on); ?>" />

            <div class="controls">
                <div class="controls-left">
                    <label>
                        Sort by
                        <select name="sort_by">
                            <option value="exam_date" <?php echo $sort_by==='exam_date'?'selected':''; ?>>Exam date</option>
                            <option value="creation_date" <?php echo $sort_by==='creation_date'?'selected':''; ?>>Creation date</option>
                            <option value="subject_name" <?php echo $sort_by==='subject_name'?'selected':''; ?>>Subject</option>
                            <option value="q_count" <?php echo $sort_by==='q_count'?'selected':''; ?>>Questions</option>
                            <option value="total_marks" <?php echo $sort_by==='total_marks'?'selected':''; ?>>Total marks</option>
                        </select>
                    </label>
                    <label>
                        Direction
                        <select name="sort_dir">
                            <option value="asc" <?php echo $order_dir==='ASC'?'selected':''; ?>>Ascending</option>
                            <option value="desc" <?php echo $order_dir==='DESC'?'selected':''; ?>>Descending</option>
                        </select>
                    </label>
                    <label>
                        Per page
                        <select name="per_page">
                            <?php foreach ([5,10,20,30,50] as $pp): ?>
                                <option value="<?php echo $pp; ?>" <?php echo ($per_page===$pp)?'selected':''; ?>><?php echo $pp; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="controls-right">
                    <button type="submit" class="back-btn apply-btn"><i class="fas fa-filter"></i> Apply</button>
                    <button type="button" class="back-btn reset-btn" id="filtersReset"><i class="fas fa-undo"></i> Reset</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Exam title</th>
                    <th>Exam date</th>
                    <th>Created</th>
                    <th>Questions</th>
                    <th>Total marks</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Summary</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($exams)): ?>
                    <tr><td colspan="9">No exams found.</td></tr>
                <?php else: ?>
                    <?php foreach ($exams as $ex): ?>
                        <?php 
                        $title = $ex['subject_name'] . ' Exam';
                        $exam_date_str = $ex['exam_date'] ? date('F j, Y', strtotime($ex['exam_date'])) : '—';
                        $created_str = $ex['creation_date'] ? date('F j, Y', strtotime($ex['creation_date'])) : '—';
                        $summary = $ex['q_count'] . ' questions • ' . $ex['total_marks'] . ' marks';
                        $download_href = $ex['has_pdf'] ? ('download_exam.php?id=' . (int)$ex['exam_ID']) : '#';
                        $view_href = 'view_exam.php?id=' . (int)$ex['exam_ID'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($title); ?></td>
                            <td><?php echo htmlspecialchars($exam_date_str); ?></td>
                            <td><?php echo htmlspecialchars($created_str); ?></td>
                            <td><?php echo (int)$ex['q_count']; ?></td>
                            <td><?php echo (int)$ex['total_marks']; ?></td>
                            <td><span class="badge <?php echo $ex['status_class']; ?>"><?php echo htmlspecialchars($ex['status_label']); ?></span></td>
                            <td title="Student score/grade not stored in DB yet">N/A</td>
                            <td><?php echo htmlspecialchars($summary); ?></td>
                            <td class="actions">
                                <a class="back-btn" style="padding:6px 10px" href="<?php echo $view_href; ?>"><i class="fas fa-eye"></i> View</a>
                                <?php if ($ex['has_pdf']): ?>
                                <a class="back-btn download-link" style="padding:6px 10px" data-examid="<?php echo (int)$ex['exam_ID']; ?>" href="<?php echo $download_href; ?>"><i class="fas fa-download"></i> Download</a>
                                <?php else: ?>
                                <button class="back-btn" style="padding:6px 10px; background:#bdc3c7; cursor:not-allowed" disabled><i class="fas fa-ban"></i> No PDF</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php 
            $qs = $_GET; unset($qs['page']);
            $base = 'view_exams.php?' . http_build_query($qs);
            ?>
            <a href="<?php echo $base . '&page=' . max(1, $page-1); ?>" class="<?php echo $page<=1?'disabled':''; ?>">« Prev</a>
            <?php for ($p=1; $p<=$total_pages; $p++): ?>
                <a href="<?php echo $base . '&page=' . $p; ?>" class="<?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
            <a href="<?php echo $base . '&page=' . min($total_pages, $page+1); ?>" class="<?php echo $page>=$total_pages?'disabled':''; ?>">Next »</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelector('.toggle-sidebar').addEventListener('click', function(){
        document.querySelector('.sidebar').classList.toggle('collapsed');
    });
    document.querySelectorAll('.download-link').forEach(function(a){
        a.addEventListener('click', function(){
            a.classList.add('download-busy');
            a.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
        });
    });
    // Enhanced reset: navigate to base view to clear all filters neatly
    var resetBtn = document.getElementById('filtersReset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            window.location.href = 'view_exams.php';
        });
    }
</script>
<script defer src="music_player.js"></script>

</body>
</html>