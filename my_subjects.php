<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

// Get professor's subjects
$professor_id = $_SESSION['user_id'];
$subjects = [];
$universities = [];

// Get subjects
$stmt = $conn->prepare("
    SELECT s.subject_ID, s.subject_name, s.total_mark, s.duration, u.university_name_en 
    FROM subject s
    JOIN university u ON s.university_ID = u.university_ID
    WHERE s.professor_ID = ?
    ORDER BY s.subject_name ASC
");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Get universities for dropdown
$stmt = $conn->prepare("
    SELECT u.university_ID, u.university_name_en 
    FROM university u
    JOIN professor_university pu ON u.university_ID = pu.university_ID
    WHERE pu.professor_ID = ?
    ORDER BY u.university_name_en ASC
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
    <title>My Subjects</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="my_subject.css">
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
            <h1><i class="fas fa-book"></i> My Subjects</h1>
            
            <button class="add-subject-btn" onclick="openAddSubjectModal()">
                <i class="fas fa-plus"></i> Add New Subject
            </button>
            
            <div class="subjects-container">
                <?php if (empty($subjects)): ?>
                    <div class="empty-message">
                        <p>You haven't created any subjects yet. Click the button above to add your first subject.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($subjects as $subject): ?>
                        <div class="subject-card" data-id="<?php echo $subject['subject_ID']; ?>">
                            <div class="subject-info">
                                <h3>
                                    <a href="subject_questions.php?subject_id=<?php echo $subject['subject_ID']; ?>" 
                                    style="text-decoration:none;color:inherit;">
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </a>
                                </h3>
                                <p><?php echo htmlspecialchars($subject['university_name_en']); ?></p>
                                <p>Total Mark: <?php echo htmlspecialchars($subject['total_mark']); ?> | Duration: <?php echo htmlspecialchars($subject['duration']); ?></p>
                            </div>
                            <div class="subject-actions">
                                <button class="action-btn edit-btn" onclick="openEditSubjectModal(<?php echo $subject['subject_ID']; ?>,
                                '<?php echo htmlspecialchars($subject['subject_name'], ENT_QUOTES); ?>',
                                 <?php echo $subject['total_mark']; ?>, '<?php echo htmlspecialchars($subject['duration'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDeleteSubject(<?php echo $subject['subject_ID']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeAddSubjectModal()">&times;</span>
            <h2><i class="fas fa-plus"></i> Add New Subject</h2>
            <form id="addSubjectForm">
                <div class="form-group">
                    <label for="subjectName">Subject Name:</label>
                    <input type="text" id="subjectName" name="subjectName" required>
                </div>
                
                <div class="form-group">
                    <label for="university">University:</label>
                    <select id="university" name="university" required>
                        <option value="">Select University</option>
                        <?php foreach ($universities as $university): ?>
                            <option value="<?php echo $university['university_ID']; ?>">
                                <?php echo htmlspecialchars($university['university_name_en']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="totalMark">Total Mark:</label>
                    <input type="number" id="totalMark" name="totalMark" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="duration">Duration:</label>
                    <input type="text" id="duration" name="duration" placeholder="e.g. 2 hours" required>
                </div>
                
                <button type="cancel" class="cancel-btn">
                    <i class="fas fa-cancel"></i> Cancel
                </button>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Save Subject
                </button>

            </form>
        </div>
    </div>
    
    <!-- Edit Subject Modal -->
    <div id="editSubjectModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditSubjectModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Subject</h2>
            <form id="editSubjectForm">
                <input type="hidden" id="editSubjectId" name="subjectId">
                
                <div class="form-group">
                    <label for="editSubjectName">Subject Name:</label>
                    <input type="text" id="editSubjectName" name="subjectName" required>
                </div>
                
                <div class="form-group">
                    <label for="editTotalMark">Total Mark:</label>
                    <input type="number" id="editTotalMark" name="totalMark" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="editDuration">Duration:</label>
                    <input type="text" id="editDuration" name="duration" required>
                </div>
                
                <button type="cancel" class="cancel-btn">
                    <i class="fas fa-cancel"></i> Cancel
                </button>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Save Subject
                </button>
            </form>
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
        
        // Modal functions
        function openAddSubjectModal() {
            document.getElementById('addSubjectModal').style.display = 'block';
        }
        
        function closeAddSubjectModal() {
            document.getElementById('addSubjectModal').style.display = 'none';
            document.getElementById('addSubjectForm').reset();
        }
        
        function openEditSubjectModal(id, name, totalMark, duration) {
            document.getElementById('editSubjectId').value = id;
            document.getElementById('editSubjectName').value = name;
            document.getElementById('editTotalMark').value = totalMark;
            document.getElementById('editDuration').value = duration;
            document.getElementById('editSubjectModal').style.display = 'block';
        }
        
        function closeEditSubjectModal() {
            document.getElementById('editSubjectModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                document.getElementById('addSubjectForm').reset();
            }
        }
        
        // Form submissions
        document.getElementById('addSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            addSubject();
        });
        
        document.getElementById('editSubjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateSubject();
        });
        
        // AJAX functions
        function addSubject() {
            const formData = new FormData(document.getElementById('addSubjectForm'));
            
            fetch('add_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Subject added successfully!');
                    closeAddSubjectModal();
                    location.reload(); // Refresh to show new subject
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the subject.');
            });
        }
        
        function updateSubject() {
            const formData = new FormData(document.getElementById('editSubjectForm'));
            
            fetch('update_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Subject updated successfully!');
                    closeEditSubjectModal();
                    location.reload(); // Refresh to show updated data
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the subject.');
            });
        }
        
        function confirmDeleteSubject(subjectId) {
            if (confirm('Are you sure you want to delete this subject? All related questions and exams will also be deleted.')) {
                deleteSubject(subjectId);
            }
        }
        
        function deleteSubject(subjectId) {
            const formData = new FormData();
            formData.append('subjectId', subjectId);
            
            fetch('delete_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Subject deleted successfully!');
                    location.reload(); // Refresh to show updated list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the subject.');
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>