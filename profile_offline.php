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
    <script>navigator.serviceWorker.register("service-worker.js")</script>
    <style>
        .container {
            font-family: sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f5f5f5;
            color: #333;
        }
        h1 {
            font-size: 2em;
            color: #007acc;
        }
        p {
            font-size: 1.2em;
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
                    <h3>Sorry</h3>
                    <p>There is no internet connection
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
                <a href="view_exams.php">
                    <i class="fas fa-list"></i>
                    <span>View Exams</span>
                </a>
                <a href="profile.php" class="active">
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
            <h1>📡 You are offline</h1>
    <p>It seems that you are currently offline.</p>
    <p>Please check your connection and try again later.</p>
        </div>
    </div>
</body>
</html>