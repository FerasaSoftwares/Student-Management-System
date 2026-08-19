<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['isFacultyLogin'])) {
    header("Location: faculty_login.php");
    exit();
}

include "Faculty_init.php";
$email = $_SESSION['email'];

// Fetch faculty name
$fac_query = mysqli_query($con, "SELECT * FROM `$FACULTY_ADD` WHERE `faculty_email` = '$email'");
$faculty_basic = mysqli_fetch_assoc($fac_query);

$msg = "";
$msg_type = "";

// ----------------------------------------
// Handle POST Actions
// ----------------------------------------

// 1. Add Internship Posting
if (isset($_POST['add_internship'])) {
    $company = mysqli_real_escape_string($con, $_POST['company']);
    $position = mysqli_real_escape_string($con, $_POST['position']);
    $desc = mysqli_real_escape_string($con, $_POST['description']);
    $skills = mysqli_real_escape_string($con, $_POST['required_skills']);
    $loc = mysqli_real_escape_string($con, $_POST['location']);
    $deadline = mysqli_real_escape_string($con, $_POST['application_deadline']);
    
    $ins = "INSERT INTO `internship_opportunities` (`company`, `position`, `description`, `required_skills`, `location`, `application_deadline`) 
            VALUES ('$company', '$position', '$desc', '$skills', '$loc', '$deadline')";
    if (mysqli_query($con, $ins)) {
        $msg = "Internship posted successfully!";
        $msg_type = "success";
    } else {
        $msg = "Failed to post internship: " . mysqli_error($con);
        $msg_type = "danger";
    }
}

// 2. Delete Internship Posting
if (isset($_GET['delete_internship'])) {
    $id = (int)$_GET['delete_internship'];
    if (mysqli_query($con, "DELETE FROM `internship_opportunities` WHERE `id` = $id")) {
        $msg = "Internship posting deleted.";
        $msg_type = "info";
    }
}

// 3. Update Student Application Status
if (isset($_POST['update_application'])) {
    $app_id = (int)$_POST['application_id'];
    $status = mysqli_real_escape_string($con, $_POST['application_status']);
    $selection = mysqli_real_escape_string($con, $_POST['selection_status']);
    
    $interview_clause = "";
    if ($status == 'Interview Scheduled' && !empty($_POST['interview_date'])) {
        $date_formatted = date('Y-m-d H:i:s', strtotime($_POST['interview_date']));
        $interview_clause = ", `interview_date` = '$date_formatted'";
    } else if ($status != 'Interview Scheduled') {
        $interview_clause = ", `interview_date` = NULL";
    }
    
    // If application_status is Selected, automatically set selection_status to Selected. If Rejected, set selection_status to Rejected.
    if ($status == 'Selected') {
        $selection = 'Selected';
    } else if ($status == 'Rejected') {
        $selection = 'Rejected';
    }
    
    $up = "UPDATE `internship_applications` SET 
           `application_status` = '$status', 
           `selection_status` = '$selection' 
           $interview_clause 
           WHERE `id` = $app_id";
           
    if (mysqli_query($con, $up)) {
        $msg = "Application status updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Failed to update application: " . mysqli_error($con);
        $msg_type = "danger";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Portal | SkillTrack</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .student-profile-card {
            border: 1px solid var(--bg-accent);
            border-radius: var(--border-radius);
            padding: 20px;
            background-color: var(--bg-secondary);
            margin-bottom: 20px;
        }
        .student-profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        .student-profile-img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--color-primary);
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Navbar -->
    <header class="navbar">
        <div class="navbar-brand">
            <i class="fa-solid fa-graduation-cap text-indigo-500"></i>
            <span>SkillTrack Faculty</span>
        </div>
        <nav class="navbar-nav">
            <a href="faculty_panel.php" class="active"><i class="fa-solid fa-house"></i> Home Dashboard</a>
            <a href="student_update.php"><i class="fa-solid fa-user-pen"></i> Update Student</a>
            <a href="faculty_assignments.php"><i class="fa-solid fa-book"></i> Assignments</a>
            <a href="faculty_notice.php"><i class="fa-solid fa-bell"></i> Notices</a>
            <a href="faculty_logout.php" class="btn btn-danger" style="color: white; padding: 6px 12px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </header>

    <?php if ($msg != ""): ?>
        <div class="card badge-<?php echo $msg_type; ?>" style="padding: 12px 20px; font-weight: 500; font-size: 0.95rem; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-info" style="margin-right: 8px;"></i>
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Faculty Intro Banner -->
    <div class="card">
        <h2>Welcome back, <?php echo htmlspecialchars($faculty_basic['faculty_name']); ?></h2>
        <p style="margin-bottom: 0;">Qualification: <strong><?php echo htmlspecialchars($faculty_basic['faculty_qualification']); ?></strong> | Email: <strong><?php echo htmlspecialchars($email); ?></strong></p>
    </div>

    <!-- Tab navigation -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="openTab(event, 'tab-students')"><i class="fa-solid fa-users"></i> Student Directory</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-internships')"><i class="fa-solid fa-briefcase"></i> Post Internships</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-applications')"><i class="fa-solid fa-paper-plane"></i> Review Applications</button>
    </div>

    <!-- TAB 1: STUDENT DIRECTORY (Advanced Skill-based Filtering) -->
    <div id="tab-students" class="tab-content active">
        <div class="card">
            <h3>Filter Student Talents</h3>
            <p>Search by student name or use comma/plus separation to filter by skills. Example: <code>PHP + MySQL + JavaScript</code></p>
            <form action="faculty_panel.php" method="POST" style="display: flex; gap: 12px; margin-bottom: 20px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
                    <label class="form-label">Search Criteria</label>
                    <input type="text" class="form-control" name="search_query" placeholder="e.g. John Doe, or PHP + MySQL + Cloud" value="<?php echo isset($_POST['search_query']) ? htmlspecialchars($_POST['search_query']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary" name="btnSearch"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <a href="faculty_panel.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            </form>

            <div>
                <?php
                // Initialize variables
                $search_query = "";
                $sql = "";
                
                if (isset($_POST['btnSearch']) && !empty($_POST['search_query'])) {
                    $search_query = $_POST['search_query'];
                    
                    // Parse for "+" or "," to detect multiple skill search
                    $skills_input = preg_split('/[\+,]/', $search_query);
                    $skills = [];
                    foreach ($skills_input as $s) {
                        $s_trimmed = trim($s);
                        if ($s_trimmed != "") {
                            $skills[] = mysqli_real_escape_string($con, $s_trimmed);
                        }
                    }
                    
                    if (count($skills) > 0 && strpos($search_query, '+') !== false || count($skills) > 1) {
                        // Advanced Skill Filter
                        $skill_list = "'" . implode("','", $skills) . "'";
                        $num_skills = count($skills);
                        $sql = "SELECT s.*, sp.student_id, sp.department, sp.year, sp.phone, sp.profile_photo 
                                FROM `$STUDENT_ADD` s
                                LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                                JOIN `student_skills` sk ON s.student_email = sk.student_email
                                WHERE LOWER(sk.skill_name) IN (LOWER($skill_list))
                                GROUP BY s.student_email
                                HAVING COUNT(DISTINCT LOWER(sk.skill_name)) = $num_skills";
                    } else {
                        // Normal search (Name, Course, Email)
                        $term = mysqli_real_escape_string($con, $search_query);
                        $sql = "SELECT s.*, sp.student_id, sp.department, sp.year, sp.phone, sp.profile_photo 
                                FROM `$STUDENT_ADD` s
                                LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                                WHERE s.student_name LIKE '%$term%' 
                                   OR s.student_email LIKE '%$term%' 
                                   OR sp.department LIKE '%$term%'
                                   OR sp.student_id LIKE '%$term%'";
                    }
                } else {
                    // Default query
                    $sql = "SELECT s.*, sp.student_id, sp.department, sp.year, sp.phone, sp.profile_photo 
                            FROM `$STUDENT_ADD` s
                            LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                            ORDER BY s.student_name ASC";
                }
                
                $result = mysqli_query($con, $sql);
                if (mysqli_num_rows($result) == 0) {
                    echo "<div class='card' style='text-align:center;'>No matching students found.</div>";
                } else {
                    while ($st = mysqli_fetch_assoc($result)) {
                        $st_email = $st['student_email'];
                        
                        // Fetch skills
                        $skills_q = mysqli_query($con, "SELECT * FROM `student_skills` WHERE `student_email` = '$st_email'");
                        $st_skills = [];
                        while ($sk = mysqli_fetch_assoc($skills_q)) {
                            $st_skills[] = $sk;
                        }
                        
                        // Fetch projects
                        $proj_q = mysqli_query($con, "SELECT * FROM `student_projects` WHERE `student_email` = '$st_email'");
                        $st_projects = [];
                        while ($pr = mysqli_fetch_assoc($proj_q)) {
                            $st_projects[] = $pr;
                        }

                        // Fetch certificates
                        $cert_q = mysqli_query($con, "SELECT * FROM `student_certificates` WHERE `student_email` = '$st_email'");
                        $st_certs = [];
                        while ($cr = mysqli_fetch_assoc($cert_q)) {
                            $st_certs[] = $cr;
                        }
                        
                        echo "<div class='student-profile-card'>";
                        echo "<div class='student-profile-header'>";
                        echo "<img class='student-profile-img' src='" . ($st['profile_photo'] ? '../'.$st['profile_photo'] : '../Student/images_student/dp_student.png') . "' alt='DP'>";
                        echo "<div>";
                        echo "<h4>" . htmlspecialchars($st['student_name']) . "</h4>";
                        echo "<p style='margin-bottom:0; font-size:0.9rem;'>Course: <strong>" . htmlspecialchars($st['department'] ?: 'N/A') . "</strong> | Year: <strong>" . htmlspecialchars($st['year'] ?: 'N/A') . "</strong> | CGPA/Result: <strong>" . htmlspecialchars($st['student_result']) . "</strong></p>";
                        echo "<p style='margin-bottom:0; font-size:0.85rem;'>Email: " . htmlspecialchars($st['student_email']) . " | Phone: " . htmlspecialchars($st['phone'] ?: $st['student_mno']) . "</p>";
                        echo "</div>";
                        echo "</div>";
                        
                        // Skills Tags
                        echo "<div style='margin-top:15px; margin-bottom:10px;'>";
                        echo "<strong>Skills: </strong>";
                        if (empty($st_skills)) {
                            echo "<span style='color: var(--text-secondary); font-size: 0.9rem;'>No skills added yet.</span>";
                        } else {
                            foreach ($st_skills as $sk) {
                                echo "<span class='badge badge-info' style='margin-right: 6px; margin-bottom: 4px;'>" . htmlspecialchars($sk['skill_name']) . " (" . htmlspecialchars($sk['skill_level']) . ")</span>";
                            }
                        }
                        echo "</div>";
                        
                        // Details dropdown button
                        echo "<details style='margin-top: 10px; cursor: pointer; color: var(--color-primary);'>";
                        echo "<summary style='font-weight: 500; font-size:0.9rem;'>Show Project Portfolio & Certificates</summary>";
                        echo "<div style='margin-top:15px; padding-top: 15px; border-top: 1px dashed var(--bg-accent); color: var(--text-primary); cursor: default;'>";
                        
                        // Portfolio projects
                        echo "<h5>Projects Portfolio</h5>";
                        if (empty($st_projects)) {
                            echo "<p style='font-size:0.9rem; color: var(--text-secondary);'>No projects added.</p>";
                        } else {
                            echo "<ul>";
                            foreach ($st_projects as $pr) {
                                echo "<li style='margin-left: 20px; margin-bottom:8px; font-size:0.9rem;'>";
                                echo "<strong>" . htmlspecialchars($pr['project_title']) . "</strong> - " . htmlspecialchars($pr['technologies']);
                                echo "<br><span style='color: var(--text-secondary); font-size:0.85rem;'>" . htmlspecialchars($pr['description']) . "</span>";
                                if ($pr['github_url']) {
                                    echo " | <a href='" . htmlspecialchars($pr['github_url']) . "' target='_blank' style='font-size:0.8rem;'>GitHub</a>";
                                }
                                if ($pr['demo_url']) {
                                    echo " | <a href='" . htmlspecialchars($pr['demo_url']) . "' target='_blank' style='font-size:0.8rem;'>Demo</a>";
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                        }
                        
                        // Certifications
                        echo "<h5 style='margin-top: 15px;'>Certifications</h5>";
                        if (empty($st_certs)) {
                            echo "<p style='font-size:0.9rem; color: var(--text-secondary);'>No certificates uploaded.</p>";
                        } else {
                            echo "<ul>";
                            foreach ($st_certs as $cr) {
                                echo "<li style='margin-left: 20px; margin-bottom:8px; font-size:0.9rem;'>";
                                echo "<strong>" . htmlspecialchars($cr['certificate_name']) . "</strong> (" . htmlspecialchars($cr['issuing_organization']) . ") - Issued: " . $cr['issue_date'];
                                if ($cr['certificate_file']) {
                                    echo " | <a href='../" . $cr['certificate_file'] . "' target='_blank' style='font-size:0.8rem;'>View Certificate</a>";
                                }
                                echo "</li>";
                            }
                            echo "</ul>";
                        }
                        
                        echo "</div>";
                        echo "</details>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- TAB 2: POST & MANAGE INTERNSHIPS -->
    <div id="tab-internships" class="tab-content">
        <div class="grid-sidebar">
            <!-- Add Internship Form -->
            <div class="card">
                <h3>Post Internship Job</h3>
                <form action="faculty_panel.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company" placeholder="e.g. Stripe, Amazon" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Job Position</label>
                        <input type="text" class="form-control" name="position" placeholder="e.g. Backend Developer Intern" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" placeholder="e.g. Bangalore, India (or Remote)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Required Skills (Comma-separated)</label>
                        <input type="text" class="form-control" name="required_skills" placeholder="e.g. PHP, MySQL, JavaScript" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Application Deadline</label>
                        <input type="date" class="form-control" name="application_deadline" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description & Requirements</label>
                        <textarea class="form-control" name="description" placeholder="Specify internship duties and basic criteria..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" name="add_internship"><i class="fa-solid fa-plus"></i> Post Internship</button>
                </form>
            </div>

            <!-- List Active Postings -->
            <div class="card">
                <h3>Active Internship Postings</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Position</th>
                                <th>Company</th>
                                <th>Skills</th>
                                <th>Location</th>
                                <th>Deadline</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $jobs_query = mysqli_query($con, "SELECT * FROM `internship_opportunities` ORDER BY `id` DESC");
                            if (mysqli_num_rows($jobs_query) == 0) {
                                echo "<tr><td colspan='6' style='text-align:center;'>No internships posted yet.</td></tr>";
                            } else {
                                while ($job = mysqli_fetch_assoc($jobs_query)) {
                                    echo "<tr>";
                                    echo "<td><strong>" . htmlspecialchars($job['position']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($job['company']) . "</td>";
                                    echo "<td>" . htmlspecialchars($job['required_skills']) . "</td>";
                                    echo "<td>" . htmlspecialchars($job['location']) . "</td>";
                                    echo "<td>" . $job['application_deadline'] . "</td>";
                                    echo "<td><a href='faculty_panel.php?delete_internship=" . $job['id'] . "' onclick='return confirm(\"Delete posting? This will remove all student applications too.\");' class='btn btn-danger' style='padding: 4px 8px; font-size: 0.8rem;'><i class='fa-solid fa-trash'></i></a></td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: REVIEW APPLICATIONS -->
    <div id="tab-applications" class="tab-content">
        <div class="card">
            <h3>Student Internship Applications</h3>
            <p>Review submitted profiles, schedule interviews, and update selection statuses.</p>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Applied For</th>
                            <th>Date Applied</th>
                            <th>Interview Date</th>
                            <th>Status Details</th>
                            <th>Update Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $apps_query = mysqli_query($con, "SELECT ia.*, io.company, io.position, s.student_name, sp.department, sp.year
                                                         FROM `internship_applications` ia
                                                         JOIN `internship_opportunities` io ON ia.internship_id = io.id
                                                         JOIN `$STUDENT_ADD` s ON ia.student_email = s.student_email
                                                         LEFT JOIN `student_profiles` sp ON s.student_email = sp.student_email
                                                         ORDER BY ia.id DESC");
                                                         
                        if (mysqli_num_rows($apps_query) == 0) {
                            echo "<tr><td colspan='6' style='text-align:center;'>No student applications received yet.</td></tr>";
                        } else {
                            while ($app = mysqli_fetch_assoc($apps_query)) {
                                $status_class = "badge-warning";
                                if ($app['application_status'] == 'Selected') $status_class = "badge-success";
                                if ($app['application_status'] == 'Rejected') $status_class = "badge-danger";
                                if ($app['application_status'] == 'Interview Scheduled') $status_class = "badge-info";
                                
                                echo "<tr>";
                                // Student basic
                                echo "<td>";
                                echo "<strong>" . htmlspecialchars($app['student_name']) . "</strong><br>";
                                echo "<span style='font-size:0.8rem; color:var(--text-secondary);'>" . htmlspecialchars($app['department']) . " (" . htmlspecialchars($app['year']) . ")</span>";
                                echo "</td>";
                                
                                // Position
                                echo "<td>";
                                echo "<strong>" . htmlspecialchars($app['position']) . "</strong><br>";
                                echo "<span style='font-size:0.8rem; color:var(--text-secondary);'>" . htmlspecialchars($app['company']) . "</span>";
                                echo "</td>";
                                
                                // Applied Date
                                echo "<td>" . $app['applied_date'] . "</td>";
                                
                                // Interview Date
                                echo "<td>" . ($app['interview_date'] ? date('d M Y, h:i A', strtotime($app['interview_date'])) : '<span style="color:var(--text-secondary);">Not Scheduled</span>') . "</td>";
                                
                                // Status
                                echo "<td>";
                                echo "Status: <span class='badge " . $status_class . "'>" . htmlspecialchars($app['application_status']) . "</span><br>";
                                echo "Selection: <strong>" . htmlspecialchars($app['selection_status']) . "</strong>";
                                echo "</td>";
                                
                                // Form to Update
                                echo "<td>";
                                echo "<form action='faculty_panel.php' method='POST' style='display:flex; flex-direction:column; gap:6px;'>";
                                echo "<input type='hidden' name='application_id' value='{$app['id']}'>";
                                
                                echo "<select class='form-control' name='application_status' style='padding: 4px 8px; font-size: 0.85rem;' required onchange='toggleInterviewField(this, {$app['id']})'>";
                                echo "<option value='Pending' " . ($app['application_status'] == 'Pending' ? 'selected' : '') . ">Pending</option>";
                                echo "<option value='Under Review' " . ($app['application_status'] == 'Under Review' ? 'selected' : '') . ">Under Review</option>";
                                echo "<option value='Interview Scheduled' " . ($app['application_status'] == 'Interview Scheduled' ? 'selected' : '') . ">Interview Scheduled</option>";
                                echo "<option value='Selected' " . ($app['application_status'] == 'Selected' ? 'selected' : '') . ">Selected</option>";
                                echo "<option value='Rejected' " . ($app['application_status'] == 'Rejected' ? 'selected' : '') . ">Rejected</option>";
                                echo "</select>";
                                
                                $int_display = ($app['application_status'] == 'Interview Scheduled') ? 'block' : 'none';
                                $val_datetime = $app['interview_date'] ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : '';
                                echo "<div id='int-field-{$app['id']}' style='display: $int_display;'>";
                                echo "<label class='form-label' style='font-size:0.75rem; margin-top:4px;'>Interview Date</label>";
                                echo "<input type='datetime-local' class='form-control' name='interview_date' style='padding: 4px 8px; font-size: 0.85rem;' value='$val_datetime'>";
                                echo "</div>";
                                
                                echo "<select class='form-control' name='selection_status' style='padding: 4px 8px; font-size: 0.85rem; display:none;'>";
                                echo "<option value='Pending' " . ($app['selection_status'] == 'Pending' ? 'selected' : '') . ">Pending</option>";
                                echo "<option value='Selected' " . ($app['selection_status'] == 'Selected' ? 'selected' : '') . ">Selected</option>";
                                echo "<option value='Rejected' " . ($app['selection_status'] == 'Rejected' ? 'selected' : '') . ">Rejected</option>";
                                echo "</select>";
                                
                                echo "<button type='submit' class='btn btn-primary' name='update_application' style='padding: 4px 8px; font-size: 0.85rem;'><i class='fa-solid fa-save'></i> Update</button>";
                                echo "</form>";
                                echo "</td>";
                                
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function openTab(evt, tabId) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function toggleInterviewField(selectObj, appId) {
    var field = document.getElementById("int-field-" + appId);
    if (selectObj.value === "Interview Scheduled") {
        field.style.display = "block";
    } else {
        field.style.display = "none";
    }
}
</script>
</body>
</html>