<?php
require __DIR__ . '/db_config.php';
$DATABASE = $DATABASE_NAME;

// 1. Connect to the server
$con = mysqli_connect($HOST, $USER, $PASSWORD);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database (only possible locally; on InfinityFree create it in the control panel)
if ($IS_LOCAL) {
    $sql = "CREATE DATABASE IF NOT EXISTS `$DATABASE`";
    if (mysqli_query($con, $sql)) {
        echo "Database created or already exists.<br>";
    } else {
        die("Error creating database: " . mysqli_error($con));
    }
}

// 2. Connect to the database
if (!mysqli_select_db($con, $DATABASE)) {
    die("Cannot open database '$DATABASE': " . mysqli_error($con));
}

// Table definitions
$queries = [];

// admin table
$queries['admin'] = "CREATE TABLE IF NOT EXISTS `admin` (
    `admin_name` VARCHAR(50),
    `admin_contact` VARCHAR(20) UNIQUE,
    `admin_email` VARCHAR(256) PRIMARY KEY,
    `admin_password` TEXT
)";

// faculty_add table
$queries['faculty_add'] = "CREATE TABLE IF NOT EXISTS `faculty_add` (
    `faculty_mno` BIGINT UNIQUE,
    `faculty_name` VARCHAR(100),
    `faculty_email` VARCHAR(256) PRIMARY KEY,
    `faculty_password` TEXT,
    `faculty_qualification` TEXT
)";

// student_add table
$queries['student_add'] = "CREATE TABLE IF NOT EXISTS `student_add` (
    `student_mno` BIGINT UNIQUE,
    `student_name` VARCHAR(100),
    `student_email` VARCHAR(256) PRIMARY KEY,
    `student_password` TEXT,
    `student_result` FLOAT DEFAULT 0
)";

// student_profiles table
$queries['student_profiles'] = "CREATE TABLE IF NOT EXISTS `student_profiles` (
    `student_email` VARCHAR(256) PRIMARY KEY,
    `student_id` VARCHAR(50),
    `department` VARCHAR(100),
    `year` VARCHAR(20),
    `phone` VARCHAR(20),
    `profile_photo` VARCHAR(256) DEFAULT NULL,
    FOREIGN KEY (`student_email`) REFERENCES `student_add` (`student_email`) ON DELETE CASCADE
)";

// student_skills table
$queries['student_skills'] = "CREATE TABLE IF NOT EXISTS `student_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_email` VARCHAR(256),
    `skill_name` VARCHAR(100),
    `category` VARCHAR(100),
    `skill_level` VARCHAR(50),
    `experience_months` INT DEFAULT 0,
    FOREIGN KEY (`student_email`) REFERENCES `student_add` (`student_email`) ON DELETE CASCADE
)";

// student_certificates table
$queries['student_certificates'] = "CREATE TABLE IF NOT EXISTS `student_certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_email` VARCHAR(256),
    `certificate_name` VARCHAR(150),
    `issuing_organization` VARCHAR(150),
    `issue_date` DATE,
    `certificate_number` VARCHAR(100),
    `certificate_file` VARCHAR(256),
    FOREIGN KEY (`student_email`) REFERENCES `student_add` (`student_email`) ON DELETE CASCADE
)";

// student_projects table
$queries['student_projects'] = "CREATE TABLE IF NOT EXISTS `student_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_email` VARCHAR(256),
    `project_title` VARCHAR(150),
    `description` TEXT,
    `technologies` VARCHAR(256),
    `github_url` VARCHAR(256),
    `demo_url` VARCHAR(256),
    FOREIGN KEY (`student_email`) REFERENCES `student_add` (`student_email`) ON DELETE CASCADE
)";

// internship_opportunities table
$queries['internship_opportunities'] = "CREATE TABLE IF NOT EXISTS `internship_opportunities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company` VARCHAR(150),
    `position` VARCHAR(150),
    `description` TEXT,
    `required_skills` VARCHAR(256),
    `location` VARCHAR(150),
    `application_deadline` DATE
)";

// internship_applications table
$queries['internship_applications'] = "CREATE TABLE IF NOT EXISTS `internship_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `internship_id` INT,
    `student_email` VARCHAR(256),
    `applied_date` DATE,
    `application_status` VARCHAR(50) DEFAULT 'Pending',
    `interview_date` DATETIME DEFAULT NULL,
    `selection_status` VARCHAR(50) DEFAULT 'Pending',
    FOREIGN KEY (`internship_id`) REFERENCES `internship_opportunities` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_email`) REFERENCES `student_add` (`student_email`) ON DELETE CASCADE
)";

// Notices & Assignments for compatibility
$queries['fc_notice_student'] = "CREATE TABLE IF NOT EXISTS `fc_notice_student` (
    `fc_student_notice_date` DATE,
    `fc_student_notice_name` TEXT,
    `fc_student_notice_des` TEXT
)";

$queries['faculty_notice_add'] = "CREATE TABLE IF NOT EXISTS `faculty_notice_add` (
    `faculty_notice_date` DATE,
    `faculty_notice_name` TEXT,
    `faculty_notice_des` TEXT
)";

$queries['student_notice_add'] = "CREATE TABLE IF NOT EXISTS `student_notice_add` (
    `student_notice_date` DATE,
    `student_notice_name` TEXT,
    `student_notice_des` TEXT
)";

$assignments = ['java_assignments', 'python_assignments', 'kotlin_assignments', 'c_assignments', 'php_assignments', 'cs_assignments'];
foreach ($assignments as $a) {
    $prefix = explode('_', $a)[0];
    $queries[$a] = "CREATE TABLE IF NOT EXISTS `$a` (
        `{$prefix}_ch` TEXT,
        `{$prefix}_des` DATE
    )";
}

// Execute Table Creations
foreach ($queries as $table => $q) {
    if (mysqli_query($con, $q)) {
        echo "Table '$table' created or verified.<br>";
    } else {
        echo "Error creating table '$table': " . mysqli_error($con) . "<br>";
    }
}

// 3. Seed initial data
// Admin Seed
$admin_email = 'admin@skilltrack.com';
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$check_admin = mysqli_query($con, "SELECT * FROM `admin` WHERE `admin_email` = '$admin_email'");
if (mysqli_num_rows($check_admin) == 0) {
    $ins = "INSERT INTO `admin` VALUES ('Admin Officer', '9999999999', '$admin_email', '$admin_pass')";
    mysqli_query($con, $ins);
    echo "Default Admin account seeded.<br>";
}

// Faculty Seed
$faculty_email = 'faculty@skilltrack.com';
$faculty_pass = password_hash('faculty123', PASSWORD_DEFAULT);
$check_faculty = mysqli_query($con, "SELECT * FROM `faculty_add` WHERE `faculty_email` = '$faculty_email'");
if (mysqli_num_rows($check_faculty) == 0) {
    $ins = "INSERT INTO `faculty_add` VALUES (8888888888, 'Dr. Sarah Connor', '$faculty_email', '$faculty_pass', 'PhD in Computer Science')";
    mysqli_query($con, $ins);
    echo "Default Faculty account seeded.<br>";
}

// Student Seed
$student_email = 'student@skilltrack.com';
$student_pass = password_hash('student123', PASSWORD_DEFAULT);
$check_student = mysqli_query($con, "SELECT * FROM `student_add` WHERE `student_email` = '$student_email'");
if (mysqli_num_rows($check_student) == 0) {
    $ins = "INSERT INTO `student_add` VALUES (7777777777, 'John Doe', '$student_email', '$student_pass', 8.5)";
    mysqli_query($con, $ins);
    
    // Seed Student Profile
    $ins_prof = "INSERT INTO `student_profiles` VALUES ('$student_email', 'STU2026001', 'Computer Science', 'Final Year', '7777777777', NULL)";
    mysqli_query($con, $ins_prof);
    
    echo "Default Student account seeded.<br>";
}

// Seed Internship Opportunities
$check_internships = mysqli_query($con, "SELECT COUNT(*) as count FROM `internship_opportunities`");
$row = mysqli_fetch_assoc($check_internships);
if ($row['count'] == 0) {
    $internships = [
        [
            'company' => 'Google',
            'position' => 'Software Engineer Intern',
            'description' => 'Work with our core infrastructure team to design, build, and deploy large scale software projects.',
            'required_skills' => 'Python, Go, Cloud, Networking',
            'location' => 'Bangalore, India',
            'deadline' => '2026-10-31'
        ],
        [
            'company' => 'Meta',
            'position' => 'Frontend Developer Intern',
            'description' => 'Help build the next generation of social interaction portals using modern web standards.',
            'required_skills' => 'JavaScript, HTML/CSS, Web Development',
            'location' => 'Remote',
            'deadline' => '2026-09-30'
        ],
        [
            'company' => 'Stripe',
            'position' => 'Full-Stack Developer Intern',
            'description' => 'Develop secure, scalable payment flow systems. Experience with databases and server side languages is highly valued.',
            'required_skills' => 'PHP, MySQL, JavaScript, Web Development',
            'location' => 'Mumbai, India',
            'deadline' => '2026-09-15'
        ]
    ];
    
    foreach ($internships as $intern) {
        $ins = "INSERT INTO `internship_opportunities` (`company`, `position`, `description`, `required_skills`, `location`, `application_deadline`)
                VALUES (
                    '".mysqli_real_escape_string($con, $intern['company'])."',
                    '".mysqli_real_escape_string($con, $intern['position'])."',
                    '".mysqli_real_escape_string($con, $intern['description'])."',
                    '".mysqli_real_escape_string($con, $intern['required_skills'])."',
                    '".mysqli_real_escape_string($con, $intern['location'])."',
                    '{$intern['deadline']}'
                )";
        mysqli_query($con, $ins);
    }
    echo "Sample Internship Opportunities seeded.<br>";
}

echo "Database setup and seeding completed successfully!<br>";
?>
