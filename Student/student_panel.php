<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['isStudentLogin'])) {
    header("Location: student_login.php");
    exit();
}

include "Student_init.php";
$email = $_SESSION['email'];

// Create upload folders dynamically if they don't exist
if (!file_exists('../uploads/profiles')) {
    mkdir('../uploads/profiles', 0777, true);
}
if (!file_exists('../uploads/certificates')) {
    mkdir('../uploads/certificates', 0777, true);
}

// Fetch basic student info
$student_query = mysqli_query($con, "SELECT * FROM `$STUDENT_ADD` WHERE `student_email` = '$email'");
$student_basic = mysqli_fetch_assoc($student_query);

// Fetch profile info or initialize empty
$profile_query = mysqli_query($con, "SELECT * FROM `student_profiles` WHERE `student_email` = '$email'");
$student_profile = mysqli_fetch_assoc($profile_query);
if (!$student_profile) {
    // Insert blank profile if not exists
    mysqli_query($con, "INSERT INTO `student_profiles` (`student_email`, `student_id`, `department`, `year`, `phone`, `profile_photo`) VALUES ('$email', '', '', '', '', NULL)");
    $profile_query = mysqli_query($con, "SELECT * FROM `student_profiles` WHERE `student_email` = '$email'");
    $student_profile = mysqli_fetch_assoc($profile_query);
}

// ----------------------------------------
// Handle POST Actions
// ----------------------------------------
$msg = "";
$msg_type = "";

// 1. Update Profile Action
if (isset($_POST['update_profile'])) {
    $student_id = mysqli_real_escape_string($con, $_POST['student_id']);
    $department = mysqli_real_escape_string($con, $_POST['department']);
    $year = mysqli_real_escape_string($con, $_POST['year']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $name = mysqli_real_escape_string($con, $_POST['student_name']);
    
    // Update name in student_add table
    mysqli_query($con, "UPDATE `$STUDENT_ADD` SET `student_name` = '$name' WHERE `student_email` = '$email'");
    
    // Profile photo upload
    $photo_path = $student_profile['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "profile_" . time() . "." . $ext;
            $target = "../uploads/profiles/" . $new_name;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
                $photo_path = "uploads/profiles/" . $new_name;
            }
        }
    }
    
    $up_prof = "UPDATE `student_profiles` SET 
                `student_id` = '$student_id', 
                `department` = '$department', 
                `year` = '$year', 
                `phone` = '$phone', 
                `profile_photo` = " . ($photo_path ? "'$photo_path'" : "NULL") . "
                WHERE `student_email` = '$email'";
                
    if (mysqli_query($con, $up_prof)) {
        $msg = "Profile updated successfully!";
        $msg_type = "success";
        // Refresh data
        $profile_query = mysqli_query($con, "SELECT * FROM `student_profiles` WHERE `student_email` = '$email'");
        $student_profile = mysqli_fetch_assoc($profile_query);
        $student_query = mysqli_query($con, "SELECT * FROM `$STUDENT_ADD` WHERE `student_email` = '$email'");
        $student_basic = mysqli_fetch_assoc($student_query);
    } else {
        $msg = "Failed to update profile: " . mysqli_error($con);
        $msg_type = "danger";
    }
}

// 2. Add Skill Action
if (isset($_POST['add_skill'])) {
    $skill_name = mysqli_real_escape_string($con, $_POST['skill_name']);
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $skill_level = mysqli_real_escape_string($con, $_POST['skill_level']);
    $exp_years = (int)$_POST['exp_years'];
    $exp_months = (int)$_POST['exp_months'];
    $total_months = ($exp_years * 12) + $exp_months;
    
    // Check if skill already exists for student
    $check = mysqli_query($con, "SELECT * FROM `student_skills` WHERE `student_email` = '$email' AND LOWER(`skill_name`) = LOWER('$skill_name')");
    if (mysqli_num_rows($check) > 0) {
        $msg = "Skill '$skill_name' is already added!";
        $msg_type = "warning";
    } else {
        $ins = "INSERT INTO `student_skills` (`student_email`, `skill_name`, `category`, `skill_level`, `experience_months`) 
                VALUES ('$email', '$skill_name', '$category', '$skill_level', $total_months)";
        if (mysqli_query($con, $ins)) {
            $msg = "Skill added successfully!";
            $msg_type = "success";
        } else {
            $msg = "Failed to add skill: " . mysqli_error($con);
            $msg_type = "danger";
        }
    }
}

// 3. Delete Skill Action
if (isset($_GET['delete_skill'])) {
    $skill_id = (int)$_GET['delete_skill'];
    mysqli_query($con, "DELETE FROM `student_skills` WHERE `id` = $skill_id AND `student_email` = '$email'");
    $msg = "Skill removed.";
    $msg_type = "info";
}

// 4. Add Certificate Action
if (isset($_POST['add_certificate'])) {
    $cert_name = mysqli_real_escape_string($con, $_POST['certificate_name']);
    $issuer = mysqli_real_escape_string($con, $_POST['issuing_organization']);
    $issue_date = mysqli_real_escape_string($con, $_POST['issue_date']);
    $cert_number = mysqli_real_escape_string($con, $_POST['certificate_number']);
    
    $file_path = "";
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
        $allowed = ['pdf', 'png', 'jpg', 'jpeg'];
        $filename = $_FILES['certificate_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_name = "cert_" . time() . "." . $ext;
            $target = "../uploads/certificates/" . $new_name;
            if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $target)) {
                $file_path = "uploads/certificates/" . $new_name;
            }
        }
    }
    
    $ins = "INSERT INTO `student_certificates` (`student_email`, `certificate_name`, `issuing_organization`, `issue_date`, `certificate_number`, `certificate_file`) 
            VALUES ('$email', '$cert_name', '$issuer', '$issue_date', '$cert_number', '$file_path')";
    if (mysqli_query($con, $ins)) {
        $msg = "Certificate uploaded successfully!";
        $msg_type = "success";
    } else {
        $msg = "Failed to add certificate: " . mysqli_error($con);
        $msg_type = "danger";
    }
}

// 5. Delete Certificate Action
if (isset($_GET['delete_cert'])) {
    $cert_id = (int)$_GET['delete_cert'];
    mysqli_query($con, "DELETE FROM `student_certificates` WHERE `id` = $cert_id AND `student_email` = '$email'");
    $msg = "Certificate deleted.";
    $msg_type = "info";
}

// 6. Add Project Action
if (isset($_POST['add_project'])) {
    $title = mysqli_real_escape_string($con, $_POST['project_title']);
    $desc = mysqli_real_escape_string($con, $_POST['description']);
    $tech = mysqli_real_escape_string($con, $_POST['technologies']);
    $github = mysqli_real_escape_string($con, $_POST['github_url']);
    $demo = mysqli_real_escape_string($con, $_POST['demo_url']);
    
    $ins = "INSERT INTO `student_projects` (`student_email`, `project_title`, `description`, `technologies`, `github_url`, `demo_url`) 
            VALUES ('$email', '$title', '$desc', '$tech', '$github', '$demo')";
    if (mysqli_query($con, $ins)) {
        $msg = "Project added to portfolio!";
        $msg_type = "success";
    } else {
        $msg = "Failed to add project: " . mysqli_error($con);
        $msg_type = "danger";
    }
}

// 7. Delete Project Action
if (isset($_GET['delete_project'])) {
    $project_id = (int)$_GET['delete_project'];
    mysqli_query($con, "DELETE FROM `student_projects` WHERE `id` = $project_id AND `student_email` = '$email'");
    $msg = "Project removed from portfolio.";
    $msg_type = "info";
}

// 8. Apply Internship Action
if (isset($_GET['apply_internship'])) {
    $intern_id = (int)$_GET['apply_internship'];
    
    // Check if already applied
    $check = mysqli_query($con, "SELECT * FROM `internship_applications` WHERE `internship_id` = $intern_id AND `student_email` = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "You have already applied for this internship!";
        $msg_type = "warning";
    } else {
        $today = date('Y-m-d');
        $ins = "INSERT INTO `internship_applications` (`internship_id`, `student_email`, `applied_date`, `application_status`, `selection_status`) 
                VALUES ($intern_id, '$email', '$today', 'Pending', 'Pending')";
        if (mysqli_query($con, $ins)) {
            $msg = "Application submitted successfully!";
            $msg_type = "success";
        } else {
            $msg = "Failed to apply: " . mysqli_error($con);
            $msg_type = "danger";
        }
    }
}

// 9. Withdraw Application Action
if (isset($_GET['withdraw_internship'])) {
    $app_id = (int)$_GET['withdraw_internship'];
    mysqli_query($con, "DELETE FROM `internship_applications` WHERE `id` = $app_id AND `student_email` = '$email'");
    $msg = "Application withdrawn.";
    $msg_type = "info";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | SkillTrack</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-img-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-primary);
            margin-bottom: 15px;
        }
        .rec-card {
            border-left: 5px solid var(--color-primary);
            background-color: var(--bg-accent);
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Navbar -->
    <header class="navbar">
        <div class="navbar-brand">
            <i class="fa-solid fa-graduation-cap text-indigo-500"></i>
            <span>SkillTrack</span>
        </div>
        <nav class="navbar-nav">
            <a href="student_panel.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="update_pass.php"><i class="fa-solid fa-key"></i> Passwords</a>
            <a href="student_assignments.php"><i class="fa-solid fa-file-signature"></i> Assignments</a>
            <a href="student_notices.php"><i class="fa-solid fa-bullhorn"></i> Notices</a>
            <a href="student_logout.php" class="btn btn-danger" style="color: white; padding: 6px 12px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </header>

    <?php if ($msg != ""): ?>
        <div class="card badge-<?php echo $msg_type; ?>" style="padding: 12px 20px; font-weight: 500; font-size: 0.95rem; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-info" style="margin-right: 8px;"></i>
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- User Brief Intro Banner -->
    <div class="card" style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <img class="profile-img-preview" style="margin-bottom: 0;" src="<?php echo $student_profile['profile_photo'] ? '../'.$student_profile['profile_photo'] : 'images_student/dp_student.png'; ?>" alt="DP">
        <div>
            <h2>Welcome back, <?php echo htmlspecialchars($student_basic['student_name']); ?>!</h2>
            <p style="margin-bottom: 0;">Student ID: <strong><?php echo htmlspecialchars($student_profile['student_id'] ?: 'Not Set'); ?></strong> | Department: <strong><?php echo htmlspecialchars($student_profile['department'] ?: 'Not Set'); ?></strong> | Year: <strong><?php echo htmlspecialchars($student_profile['year'] ?: 'Not Set'); ?></strong></p>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="openTab(event, 'tab-profile')"><i class="fa-solid fa-user"></i> My Profile</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-skills')"><i class="fa-solid fa-award"></i> Skill Set</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-certificates')"><i class="fa-solid fa-certificate"></i> Certifications</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-projects')"><i class="fa-solid fa-laptop-code"></i> Projects</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-internships')"><i class="fa-solid fa-briefcase"></i> Internship Jobs</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-recommendations')"><i class="fa-solid fa-wand-magic-sparkles"></i> Recommendations</button>
    </div>

    <!-- TAB 1: PROFILE -->
    <div id="tab-profile" class="tab-content active">
        <div class="card">
            <h3>Edit Student Profile Details</h3>
            <form action="student_panel.php" method="POST" enctype="multipart/form-data">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="student_name" value="<?php echo htmlspecialchars($student_basic['student_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student ID / Roll No</label>
                        <input type="text" class="form-control" name="student_id" value="<?php echo htmlspecialchars($student_profile['student_id']); ?>" placeholder="e.g. FY2026-092">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Department / Course</label>
                        <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($student_profile['department']); ?>" placeholder="e.g. Computer Applications">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year of Study</label>
                        <select class="form-control" name="year">
                            <option value="">Select Year</option>
                            <option value="First Year" <?php if($student_profile['year'] == 'First Year') echo 'selected'; ?>>First Year</option>
                            <option value="Second Year" <?php if($student_profile['year'] == 'Second Year') echo 'selected'; ?>>Second Year</option>
                            <option value="Third Year" <?php if($student_profile['year'] == 'Third Year') echo 'selected'; ?>>Third Year</option>
                            <option value="Final Year" <?php if($student_profile['year'] == 'Final Year') echo 'selected'; ?>>Final Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Contact</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($student_profile['phone']); ?>" placeholder="e.g. +91 98765 43210">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email (Account ID - Read Only)</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Profile Photo Upload (JPG / PNG)</label>
                    <input type="file" class="form-control" name="profile_photo" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary" name="update_profile"><i class="fa-solid fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>

    <!-- TAB 2: SKILLS -->
    <div id="tab-skills" class="tab-content">
        <div class="grid-sidebar">
            <div class="card">
                <h3>Add New Skill</h3>
                <form action="student_panel.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Skill Name</label>
                        <input type="text" class="form-control" name="skill_name" placeholder="e.g. JavaScript, PHP" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select class="form-control" name="category" required>
                            <option value="Programming">Programming</option>
                            <option value="Database">Database</option>
                            <option value="Web Development">Web Development</option>
                            <option value="Mobile Development">Mobile Development</option>
                            <option value="Networking">Networking</option>
                            <option value="Cloud">Cloud</option>
                            <option value="Soft Skills">Soft Skills</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Skill Level</label>
                        <select class="form-control" name="skill_level" required>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Expert">Expert</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Experience</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="number" class="form-control" name="exp_years" placeholder="Years" min="0" value="0" required>
                            <input type="number" class="form-control" name="exp_months" placeholder="Months" min="0" max="11" value="0" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success" name="add_skill"><i class="fa-solid fa-plus"></i> Add Skill</button>
                </form>
            </div>

            <div class="card">
                <h3>My Skills</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Skill Name</th>
                                <th>Category</th>
                                <th>Level</th>
                                <th>Experience</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $skills_query = mysqli_query($con, "SELECT * FROM `student_skills` WHERE `student_email` = '$email' ORDER BY `category` ASC");
                            if (mysqli_num_rows($skills_query) == 0) {
                                echo "<tr><td colspan='5' style='text-align:center;'>No skills added yet. Let's add some!</td></tr>";
                            } else {
                                while ($sk = mysqli_fetch_assoc($skills_query)) {
                                    $yrs = floor($sk['experience_months'] / 12);
                                    $mns = $sk['experience_months'] % 12;
                                    $exp_str = "";
                                    if ($yrs > 0) $exp_str .= $yrs . " Yr" . ($yrs > 1 ? "s" : "") . " ";
                                    if ($mns > 0) $exp_str .= $mns . " Mo" . ($mns > 1 ? "s" : "");
                                    if ($exp_str == "") $exp_str = "0 Mos";
                                    
                                    echo "<tr>";
                                    echo "<td><strong>" . htmlspecialchars($sk['skill_name']) . "</strong></td>";
                                    echo "<td><span class='badge badge-info'>" . htmlspecialchars($sk['category']) . "</span></td>";
                                    echo "<td>" . htmlspecialchars($sk['skill_level']) . "</td>";
                                    echo "<td>" . $exp_str . "</td>";
                                    echo "<td><a href='student_panel.php?delete_skill=" . $sk['id'] . "' onclick='return confirm(\"Remove this skill?\");' class='btn btn-danger' style='padding: 4px 8px; font-size: 0.8rem;'><i class='fa-solid fa-trash'></i></a></td>";
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

    <!-- TAB 3: CERTIFICATES -->
    <div id="tab-certificates" class="tab-content">
        <div class="grid-sidebar">
            <div class="card">
                <h3>Add Certification</h3>
                <form action="student_panel.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Certificate Name</label>
                        <input type="text" class="form-control" name="certificate_name" placeholder="e.g. AWS Solutions Architect" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Issuing Organization</label>
                        <input type="text" class="form-control" name="issuing_organization" placeholder="e.g. Amazon Web Services, Udemy" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Issue Date</label>
                        <input type="date" class="form-control" name="issue_date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Certificate ID / Number</label>
                        <input type="text" class="form-control" name="certificate_number" placeholder="e.g. CERT-98218-A">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Upload Certificate (PDF / Image)</label>
                        <input type="file" class="form-control" name="certificate_file" accept=".pdf,image/*" required>
                    </div>
                    <button type="submit" class="btn btn-success" name="add_certificate"><i class="fa-solid fa-upload"></i> Upload Certificate</button>
                </form>
            </div>

            <div class="card">
                <h3>My Certificates</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Certificate Name</th>
                                <th>Issuer</th>
                                <th>Issue Date</th>
                                <th>ID Number</th>
                                <th>File</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cert_query = mysqli_query($con, "SELECT * FROM `student_certificates` WHERE `student_email` = '$email' ORDER BY `issue_date` DESC");
                            if (mysqli_num_rows($cert_query) == 0) {
                                echo "<tr><td colspan='6' style='text-align:center;'>No certificates uploaded yet.</td></tr>";
                            } else {
                                while ($cert = mysqli_fetch_assoc($cert_query)) {
                                    $file_link = $cert['certificate_file'] ? "<a href='../" . $cert['certificate_file'] . "' target='_blank' class='btn btn-secondary' style='padding: 4px 8px; font-size: 0.8rem;'><i class='fa-solid fa-file-arrow-down'></i> View File</a>" : "No File";
                                    echo "<tr>";
                                    echo "<td><strong>" . htmlspecialchars($cert['certificate_name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($cert['issuing_organization']) . "</td>";
                                    echo "<td>" . $cert['issue_date'] . "</td>";
                                    echo "<td>" . htmlspecialchars($cert['certificate_number'] ?: 'N/A') . "</td>";
                                    echo "<td>" . $file_link . "</td>";
                                    echo "<td><a href='student_panel.php?delete_cert=" . $cert['id'] . "' onclick='return confirm(\"Delete certificate?\");' class='btn btn-danger' style='padding: 4px 8px; font-size: 0.8rem;'><i class='fa-solid fa-trash'></i></a></td>";
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

    <!-- TAB 4: PROJECTS -->
    <div id="tab-projects" class="tab-content">
        <div class="grid-sidebar">
            <div class="card">
                <h3>Add Portfolio Project</h3>
                <form action="student_panel.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Project Title</label>
                        <input type="text" class="form-control" name="project_title" placeholder="e.g. E-Commerce Platform" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Technologies Used</label>
                        <input type="text" class="form-control" name="technologies" placeholder="e.g. PHP, MySQL, CSS Bootstrap, React" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GitHub Repository URL</label>
                        <input type="url" class="form-control" name="github_url" placeholder="https://github.com/...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Live Demo URL</label>
                        <input type="url" class="form-control" name="demo_url" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Short Description</label>
                        <textarea class="form-control" name="description" placeholder="Briefly describe what this project does..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" name="add_project"><i class="fa-solid fa-plus"></i> Add Project</button>
                </form>
            </div>

            <div>
                <h3>My Project Portfolio</h3>
                <?php
                $proj_query = mysqli_query($con, "SELECT * FROM `student_projects` WHERE `student_email` = '$email' ORDER BY `id` DESC");
                if (mysqli_num_rows($proj_query) == 0) {
                    echo "<div class='card' style='text-align:center;'>No projects added to portfolio yet. Showcase your work here!</div>";
                } else {
                    while ($proj = mysqli_fetch_assoc($proj_query)) {
                        echo "<div class='card'>";
                        echo "<div style='display:flex; justify-content:space-between; align-items:center;'>";
                        echo "<h4>" . htmlspecialchars($proj['project_title']) . "</h4>";
                        echo "<a href='student_panel.php?delete_project=" . $proj['id'] . "' onclick='return confirm(\"Remove project?\");' class='btn btn-danger' style='padding: 6px 10px; font-size: 0.8rem;'><i class='fa-solid fa-trash'></i> Delete</a>";
                        echo "</div>";
                        echo "<p style='margin-bottom: 8px; font-size: 0.95rem;'>" . nl2br(htmlspecialchars($proj['description'])) . "</p>";
                        echo "<p style='font-size: 0.85rem;'>Technologies: <strong>" . htmlspecialchars($proj['technologies']) . "</strong></p>";
                        echo "<div style='display:flex; gap:12px; margin-top:12px;'>";
                        if ($proj['github_url']) {
                            echo "<a href='" . htmlspecialchars($proj['github_url']) . "' target='_blank' class='btn btn-secondary' style='font-size: 0.85rem; padding: 6px 12px;'><i class='fa-brands fa-github'></i> Repository</a>";
                        }
                        if ($proj['demo_url']) {
                            echo "<a href='" . htmlspecialchars($proj['demo_url']) . "' target='_blank' class='btn btn-primary' style='font-size: 0.85rem; padding: 6px 12px;'><i class='fa-solid fa-eye'></i> Live Demo</a>";
                        }
                        echo "</div>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- TAB 5: INTERNSHIP JOBS -->
    <div id="tab-internships" class="tab-content">
        <div class="grid-2">
            <!-- Available Internships -->
            <div class="card">
                <h3>Internship Opportunities</h3>
                <p>Browse and apply for placements posted by companies & placement officers.</p>
                
                <?php
                $job_query = mysqli_query($con, "SELECT * FROM `internship_opportunities` ORDER BY `application_deadline` ASC");
                if (mysqli_num_rows($job_query) == 0) {
                    echo "<p style='text-align:center;'>No internships available currently.</p>";
                } else {
                    while ($job = mysqli_fetch_assoc($job_query)) {
                        // Check if already applied
                        $job_id = $job['id'];
                        $app_chk = mysqli_query($con, "SELECT * FROM `internship_applications` WHERE `internship_id` = $job_id AND `student_email` = '$email'");
                        $is_applied = mysqli_num_rows($app_chk) > 0;
                        
                        echo "<div class='card' style='background-color: var(--bg-primary); border: 1px solid var(--bg-accent);'>";
                        echo "<h4>" . htmlspecialchars($job['position']) . "</h4>";
                        echo "<h5 style='color: var(--color-primary);'>" . htmlspecialchars($job['company']) . "</h5>";
                        echo "<p style='font-size: 0.9rem; margin-bottom: 8px;'>" . nl2br(htmlspecialchars($job['description'])) . "</p>";
                        echo "<p style='font-size: 0.85rem; margin-bottom: 4px;'>Required Skills: <strong>" . htmlspecialchars($job['required_skills']) . "</strong></p>";
                        echo "<p style='font-size: 0.85rem; margin-bottom: 12px;'>Location: <strong>" . htmlspecialchars($job['location']) . "</strong> | Deadline: <strong>" . $job['application_deadline'] . "</strong></p>";
                        
                        if ($is_applied) {
                            echo "<button class='btn btn-secondary' disabled><i class='fa-solid fa-check'></i> Applied</button>";
                        } else {
                            echo "<a href='student_panel.php?apply_internship=" . $job['id'] . "' class='btn btn-primary'><i class='fa-solid fa-paper-plane'></i> Apply Now</a>";
                        }
                        echo "</div>";
                    }
                }
                ?>
            </div>

            <!-- Applications Status -->
            <div class="card">
                <h3>My Applications</h3>
                <p>Track the progress of your submitted internship applications.</p>
                
                <?php
                $my_apps_query = mysqli_query($con, "SELECT ia.*, io.company, io.position, io.location 
                                                    FROM `internship_applications` ia
                                                    JOIN `internship_opportunities` io ON ia.internship_id = io.id
                                                    WHERE ia.student_email = '$email'
                                                    ORDER BY ia.id DESC");
                if (mysqli_num_rows($my_apps_query) == 0) {
                    echo "<p style='text-align:center;'>You haven't applied to any internships yet.</p>";
                } else {
                    while ($app = mysqli_fetch_assoc($my_apps_query)) {
                        $status_class = "badge-warning";
                        if ($app['application_status'] == 'Selected') $status_class = "badge-success";
                        if ($app['application_status'] == 'Rejected') $status_class = "badge-danger";
                        if ($app['application_status'] == 'Interview Scheduled') $status_class = "badge-info";
                        
                        echo "<div class='card' style='background-color: var(--bg-primary); border: 1px solid var(--bg-accent);'>";
                        echo "<h4>" . htmlspecialchars($app['position']) . "</h4>";
                        echo "<h5>" . htmlspecialchars($app['company']) . "</h5>";
                        echo "<div style='margin-bottom: 10px;'>";
                        echo "Status: <span class='badge " . $status_class . "'>" . htmlspecialchars($app['application_status']) . "</span>";
                        echo "</div>";
                        
                        if ($app['interview_date']) {
                            echo "<p style='font-size: 0.9rem; background-color: var(--bg-accent); padding: 8px; border-radius: 6px;'><i class='fa-solid fa-calendar-days' style='color: var(--color-primary); margin-right: 6px;'></i> Interview Scheduled: <strong>" . date('d M Y, h:i A', strtotime($app['interview_date'])) . "</strong></p>";
                        }
                        
                        echo "<div style='display:flex; justify-content:space-between; align-items:center; margin-top:12px;'>";
                        echo "<span style='font-size: 0.8rem; color: var(--text-secondary);'>Applied on: " . $app['applied_date'] . "</span>";
                        if ($app['application_status'] != 'Selected' && $app['application_status'] != 'Rejected') {
                            echo "<a href='student_panel.php?withdraw_internship=" . $app['id'] . "' onclick='return confirm(\"Withdraw this application?\");' class='btn btn-danger' style='padding: 6px 12px; font-size: 0.85rem;'><i class='fa-solid fa-times'></i> Withdraw</a>";
                        }
                        echo "</div>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- TAB 6: RECOMMENDATIONS -->
    <div id="tab-recommendations" class="tab-content">
        <div class="card">
            <h3>SkillTrack Smart Advice</h3>
            <p>Our rule-based recommendation system analyzes matching categories and current placement offers to suggest your next steps.</p>
            
            <?php
            // Extract student's skills
            $stud_skills = [];
            $s_q = mysqli_query($con, "SELECT `skill_name` FROM `student_skills` WHERE `student_email` = '$email'");
            while ($row = mysqli_fetch_assoc($s_q)) {
                $stud_skills[] = strtolower(trim($row['skill_name']));
            }
            
            // Extract all job posting requirements
            $skills_demand = [];
            $jobs = [];
            $j_q = mysqli_query($con, "SELECT * FROM `internship_opportunities`");
            while ($row = mysqli_fetch_assoc($j_q)) {
                $jobs[] = $row;
                $reqs = explode(',', $row['required_skills']);
                foreach ($reqs as $r) {
                    $clean_r = strtolower(trim($r));
                    if ($clean_r != "") {
                        if (!isset($skills_demand[$clean_r])) {
                            $skills_demand[$clean_r] = 0;
                        }
                        $skills_demand[$clean_r]++;
                    }
                }
            }
            arsort($skills_demand); // Sort high demand first
            ?>

            <div class="grid-2">
                <!-- High Demand Recommendations -->
                <div class="card" style="background-color: var(--bg-primary); border: 1px solid var(--bg-accent);">
                    <h4><i class="fa-solid fa-fire" style="color: var(--color-danger); margin-right: 8px;"></i> Recommended Skills to Learn</h4>
                    <p>These skills are heavily requested in active internship postings but are not yet on your profile:</p>
                    
                    <?php
                    $rec_count = 0;
                    foreach ($skills_demand as $sk => $demand) {
                        if (!in_array($sk, $stud_skills)) {
                            echo "<div class='rec-card'>";
                            echo "<strong>" . htmlspecialchars(ucwords($sk)) . "</strong>";
                            echo "<p style='margin-bottom:0; font-size:0.85rem;'>Requested by <strong>$demand</strong> internship posting" . ($demand > 1 ? "s" : "") . ".</p>";
                            echo "</div>";
                            $rec_count++;
                        }
                        if ($rec_count >= 3) break;
                    }
                    if ($rec_count == 0) {
                        echo "<p style='color: var(--color-success);'>🎉 Fantastic! You have added all currently requested skills to your profile.</p>";
                    }
                    ?>

                    <h4 style="margin-top: 25px;"><i class="fa-solid fa-lightbulb" style="color: var(--color-warning); margin-right: 8px;"></i> Related Career Recommendations</h4>
                    <ul>
                        <?php
                        $has_html_css = in_array('html', $stud_skills) || in_array('css', $stud_skills);
                        $has_js = in_array('javascript', $stud_skills) || in_array('js', $stud_skills);
                        $has_db = in_array('mysql', $stud_skills) || in_array('sql', $stud_skills) || in_array('database', $stud_skills);
                        $has_php = in_array('php', $stud_skills);
                        
                        $printed_rule = false;
                        if ($has_html_css && !$has_js) {
                            echo "<li style='margin-left: 20px; margin-bottom: 8px; color: var(--text-secondary);'>Since you know <strong>HTML/CSS</strong>, we recommend adding <strong>JavaScript</strong> next to complete your Web Development profile.</li>";
                            $printed_rule = true;
                        }
                        if ($has_db && !$has_php && !$has_js) {
                            echo "<li style='margin-left: 20px; margin-bottom: 8px; color: var(--text-secondary);'>Since you have <strong>Database / SQL</strong> experience, learning a backend language like <strong>PHP</strong> will enable you to build Full Stack Web Portals.</li>";
                            $printed_rule = true;
                        }
                        if ($has_js && !$has_html_css) {
                            echo "<li style='margin-left: 20px; margin-bottom: 8px; color: var(--text-secondary);'>Since you know <strong>JavaScript</strong>, make sure to add <strong>HTML/CSS</strong> and <strong>Web Development</strong> to showcase web projects.</li>";
                            $printed_rule = true;
                        }
                        if (!$printed_rule) {
                            echo "<li style='margin-left: 20px; margin-bottom: 8px; color: var(--text-secondary);'>Build a balanced profile by learning at least one core backend language (like <strong>PHP</strong> or <strong>Python</strong>) and a modern database engine (like <strong>MySQL</strong>).</li>";
                        }
                        ?>
                    </ul>
                </div>

                <!-- Internship Match Analysis -->
                <div class="card" style="background-color: var(--bg-primary); border: 1px solid var(--bg-accent);">
                    <h4><i class="fa-solid fa-puzzle-piece" style="color: var(--color-success); margin-right: 8px;"></i> Internship Opportunities Matching Your Skills</h4>
                    <p>We analyzed your current skills against existing postings to calculate your match rating:</p>
                    
                    <?php
                    if (empty($stud_skills)) {
                        echo "<p style='color: var(--color-warning);'>Please add your skills on the 'Skill Set' tab to view matching placements!</p>";
                    } else {
                        $matches_found = false;
                        foreach ($jobs as $job) {
                            $reqs = explode(',', $job['required_skills']);
                            $total_reqs = 0;
                            $matched_reqs = 0;
                            $missing = [];
                            
                            foreach ($reqs as $r) {
                                $clean_r = strtolower(trim($r));
                                if ($clean_r != "") {
                                    $total_reqs++;
                                    if (in_array($clean_r, $stud_skills)) {
                                        $matched_reqs++;
                                    } else {
                                        $missing[] = ucwords($clean_r);
                                    }
                                }
                            }
                            
                            if ($total_reqs > 0) {
                                $percent = round(($matched_reqs / $total_reqs) * 100);
                                if ($percent >= 30) {
                                    $matches_found = true;
                                    echo "<div class='rec-card' style='border-left-color: " . ($percent == 100 ? "var(--color-success)" : ($percent >= 60 ? "var(--color-warning)" : "var(--color-danger)")) . ";'>";
                                    echo "<div style='display:flex; justify-content:space-between;'>";
                                    echo "<strong>" . htmlspecialchars($job['position']) . " (" . htmlspecialchars($job['company']) . ")</strong>";
                                    echo "<span class='badge' style='background-color: var(--bg-primary);'>$percent% Match</span>";
                                    echo "</div>";
                                    if ($percent < 100) {
                                        echo "<p style='margin-bottom:0; font-size:0.85rem; margin-top:6px;'>Missing: <span style='color: var(--color-danger);'>" . implode(', ', $missing) . "</span></p>";
                                    } else {
                                        echo "<p style='margin-bottom:0; font-size:0.85rem; margin-top:6px; color: var(--color-success);'><strong>You are a 100% match! Apply now.</strong></p>";
                                    }
                                    echo "</div>";
                                }
                            }
                        }
                        if (!$matches_found) {
                            echo "<p style='color: var(--text-secondary);'>No close internship matches found. Add more skills to get recommendations!</p>";
                        }
                    }
                    ?>
                </div>
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
</script>
</body>
</html>