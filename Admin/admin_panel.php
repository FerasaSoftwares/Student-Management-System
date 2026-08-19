<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['isAdminLogin'])) {
    header("Location: admin_login.php");
    exit();
}

include "Admin_init.php";
$email = $_SESSION['email'];

// Handle CSV Export
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=placement_report_' . date('Ymd') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Output column headers
    fputcsv($output, ['Name', 'Email', 'Phone', 'Department', 'Year', 'Result/CGPA', 'Placement Status', 'Placed Company', 'Role']);
    
    $query = "SELECT s.student_name, s.student_email, s.student_result, sp.department, sp.year, sp.phone,
                       ia.application_status, io.company, io.position
                FROM `$STUDENT_ADD` s
                LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                LEFT JOIN `internship_applications` ia ON s.student_email = ia.student_email AND ia.application_status = 'Selected'
                LEFT JOIN `internship_opportunities` io ON ia.internship_id = io.id
                GROUP BY s.student_email
                ORDER BY s.student_name ASC";
                
    $res = mysqli_query($con, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $status = $row['company'] ? 'Placed' : 'Not Placed';
        fputcsv($output, [
            $row['student_name'],
            $row['student_email'],
            $row['phone'] ?: '',
            $row['department'] ?: '',
            $row['year'] ?: '',
            $row['student_result'],
            $status,
            $row['company'] ?: '',
            $row['position'] ?: ''
        ]);
    }
    fclose($output);
    exit();
}

// 1. Fetch Stats
// Total Students
$q_students = mysqli_query($con, "SELECT COUNT(*) as count FROM `$STUDENT_ADD`");
$r_students = mysqli_fetch_assoc($q_students);
$total_students = $r_students['count'];

// Total Internships
$q_internships = mysqli_query($con, "SELECT COUNT(*) as count FROM `internship_opportunities`");
$r_internships = mysqli_fetch_assoc($q_internships);
$total_internships = $r_internships['count'];

// Total Applications
$q_apps = mysqli_query($con, "SELECT COUNT(*) as count FROM `internship_applications`");
$r_apps = mysqli_fetch_assoc($q_apps);
$total_applications = $r_apps['count'];

// Placed/Selected Students
$q_placed = mysqli_query($con, "SELECT COUNT(DISTINCT student_email) as count FROM `internship_applications` WHERE `application_status` = 'Selected'");
$r_placed = mysqli_fetch_assoc($q_placed);
$placed_students = $r_placed['count'];

// Students without internships
$unplaced_students = max(0, $total_students - $placed_students);

// 2. Fetch Chart Data
// App status distribution
$status_data = ['Pending' => 0, 'Under Review' => 0, 'Interview Scheduled' => 0, 'Selected' => 0, 'Rejected' => 0];
$q_status_dist = mysqli_query($con, "SELECT `application_status`, COUNT(*) as count FROM `internship_applications` GROUP BY `application_status`");
while ($row = mysqli_fetch_assoc($q_status_dist)) {
    if (isset($status_data[$row['application_status']])) {
        $status_data[$row['application_status']] = (int)$row['count'];
    }
}

// Top skill categories in system
$category_data = [];
$q_cat_dist = mysqli_query($con, "SELECT `category`, COUNT(*) as count FROM `student_skills` GROUP BY `category` ORDER BY count DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($q_cat_dist)) {
    $category_data[$row['category']] = (int)$row['count'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | SkillTrack</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 30px;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }
        .chart-container {
            background-color: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            min-height: 320px;
        }
        @media screen and (max-width: 1000px) {
            .dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media screen and (max-width: 700px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Navbar -->
    <header class="navbar">
        <div class="navbar-brand">
            <i class="fa-solid fa-graduation-cap text-indigo-500"></i>
            <span>SkillTrack Admin</span>
        </div>
        <nav class="navbar-nav">
            <a href="admin_panel.php" class="active"><i class="fa-solid fa-chart-pie"></i> Placement Dashboard</a>
            <a href="student_panel.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
            <a href="faculty_panel.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty</a>
            <a href="notice_panel.php"><i class="fa-solid fa-envelope-open-text"></i> Notices</a>
            <a href="admin_logout.php" class="btn btn-danger" style="color: white; padding: 6px 12px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </header>

    <!-- Placement Statistics Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
            <div>
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(99,102,241,0.15); color: var(--color-primary);"><i class="fa-solid fa-briefcase"></i></div>
            <div>
                <div class="stat-value"><?php echo $total_internships; ?></div>
                <div class="stat-label">Active Internships</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(245,158,11,0.15); color: var(--color-warning);"><i class="fa-solid fa-paper-plane"></i></div>
            <div>
                <div class="stat-value"><?php echo $total_applications; ?></div>
                <div class="stat-label">Applications</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(16,185,129,0.15); color: var(--color-success);"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="stat-value"><?php echo $placed_students; ?></div>
                <div class="stat-label">Placed Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(239,68,68,0.15); color: var(--color-danger);"><i class="fa-solid fa-user-xmark"></i></div>
            <div>
                <div class="stat-value"><?php echo $unplaced_students; ?></div>
                <div class="stat-label">Unplaced Students</div>
            </div>
        </div>
    </div>

    <!-- Quick Export Card -->
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <h3>Placement & Skill Analytics</h3>
            <p style="margin-bottom:0;">Generate and download real-time CSV reports of placements, skills, and student lists.</p>
        </div>
        <a href="admin_panel.php?export_csv=1" class="btn btn-success"><i class="fa-solid fa-file-csv"></i> Export Placement Report (CSV)</a>
    </div>

    <!-- Chart Analytics Grid -->
    <div class="chart-grid">
        <div class="chart-container">
            <h4 style="text-align: center; margin-bottom: 15px;">Application Status Distribution</h4>
            <canvas id="statusChart" style="max-height: 250px;"></canvas>
        </div>
        <div class="chart-container">
            <h4 style="text-align: center; margin-bottom: 15px;">Top Skill Categories</h4>
            <canvas id="skillsChart" style="max-height: 250px;"></canvas>
        </div>
    </div>

    <!-- Skill Filter Directory -->
    <div class="card">
        <h3>Placement Skill Filter Directory</h3>
        <p>Directly search students by their skillsets. Use format: <code>PHP + MySQL + JavaScript</code> to query students possessing all specified skills.</p>
        <form action="admin_panel.php" method="POST" style="display: flex; gap: 12px; margin-bottom: 20px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                <input type="text" class="form-control" name="search_query" placeholder="e.g. PHP + MySQL" value="<?php echo isset($_POST['search_query']) ? htmlspecialchars($_POST['search_query']) : ''; ?>">
            </div>
            <button type="submit" class="btn btn-primary" name="btnSearch"><i class="fa-solid fa-magnifying-glass"></i> Filter Talent</button>
            <a href="admin_panel.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-left"></i> Clear</a>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email ID</th>
                        <th>Department</th>
                        <th>CGPA</th>
                        <th>Skills</th>
                        <th>Placement Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $search_query = "";
                    $sql = "";
                    
                    if (isset($_POST['btnSearch']) && !empty($_POST['search_query'])) {
                        $search_query = $_POST['search_query'];
                        $skills_input = preg_split('/[\+,]/', $search_query);
                        $skills = [];
                        foreach ($skills_input as $s) {
                            $s_trimmed = trim($s);
                            if ($s_trimmed != "") {
                                $skills[] = mysqli_real_escape_string($con, $s_trimmed);
                            }
                        }
                        
                        if (count($skills) > 0) {
                            $skill_list = "'" . implode("','", $skills) . "'";
                            $num_skills = count($skills);
                            $sql = "SELECT s.*, sp.department, sp.year 
                                    FROM `$STUDENT_ADD` s
                                    LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                                    JOIN `student_skills` sk ON s.student_email = sk.student_email
                                    WHERE LOWER(sk.skill_name) IN (LOWER($skill_list))
                                    GROUP BY s.student_email
                                    HAVING COUNT(DISTINCT LOWER(sk.skill_name)) = $num_skills";
                        }
                    }
                    
                    if ($sql == "") {
                        $sql = "SELECT s.*, sp.department, sp.year 
                                FROM `$STUDENT_ADD` s
                                LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                                ORDER BY s.student_name ASC";
                    }
                    
                    $res = mysqli_query($con, $sql);
                    if (mysqli_num_rows($res) == 0) {
                        echo "<tr><td colspan='6' style='text-align:center;'>No matching students found.</td></tr>";
                    } else {
                        while ($st = mysqli_fetch_assoc($res)) {
                            $st_email = $st['student_email'];
                            
                            // Fetch placement
                            $place_chk = mysqli_query($con, "SELECT io.company, io.position 
                                                             FROM `internship_applications` ia
                                                             JOIN `internship_opportunities` io ON ia.internship_id = io.id
                                                             WHERE ia.student_email = '$st_email' AND ia.application_status = 'Selected' LIMIT 1");
                            $placed = mysqli_fetch_assoc($place_chk);
                            $place_status = $placed ? "<span class='badge badge-success'>Placed @ " . htmlspecialchars($placed['company']) . "</span>" : "<span class='badge badge-danger'>Not Placed</span>";
                            
                            // Fetch all skills
                            $skills_q = mysqli_query($con, "SELECT `skill_name` FROM `student_skills` WHERE `student_email` = '$st_email'");
                            $st_skills = [];
                            while ($sk = mysqli_fetch_assoc($skills_q)) {
                                $st_skills[] = htmlspecialchars($sk['skill_name']);
                            }
                            
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($st['student_name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($st['student_email']) . "</td>";
                            echo "<td>" . htmlspecialchars($st['department'] ?: 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($st['student_result']) . "</td>";
                            echo "<td>" . (empty($st_skills) ? '<span style="color:var(--text-secondary);">None</span>' : implode(', ', $st_skills)) . "</td>";
                            echo "<td>" . $place_status . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart Script Rendering -->
<script>
    // Application Status Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Under Review', 'Interview Scheduled', 'Selected', 'Rejected'],
            datasets: [{
                data: [
                    <?php echo $status_data['Pending']; ?>,
                    <?php echo $status_data['Under Review']; ?>,
                    <?php echo $status_data['Interview Scheduled']; ?>,
                    <?php echo $status_data['Selected']; ?>,
                    <?php echo $status_data['Rejected']; ?>
                ],
                backgroundColor: [
                    '#f59e0b', // Amber
                    '#3b82f6', // Blue
                    '#6366f1', // Indigo
                    '#10b981', // Emerald
                    '#ef4444'  // Rose
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#cbd5e1' }
                }
            }
        }
    });

    // Top Skill Categories Chart
    const ctxSkills = document.getElementById('skillsChart').getContext('2d');
    new Chart(ctxSkills, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($category_data)); ?>,
            datasets: [{
                label: 'Added Skills Count',
                data: <?php echo json_encode(array_values($category_data)); ?>,
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#cbd5e1', stepSize: 1 },
                    grid: { color: '#334155' }
                },
                x: {
                    ticks: { color: '#cbd5e1' },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
</body>
</html>