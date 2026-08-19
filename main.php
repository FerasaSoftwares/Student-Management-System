<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillTrack | Student Skill & Internship Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background: radial-gradient(circle at top right, #1e1b4b, var(--bg-primary));
        }
        .main-container {
            max-width: 900px;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 40px;
        }
        .left-banner {
            flex: 1;
        }
        .right-portal {
            flex: 1;
        }
        .role-btn {
            width: 100%;
            padding: 14px 20px;
            margin-bottom: 12px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: flex-start;
        }
        .sidenav-links {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            gap: 16px;
        }
        .sidenav-links a {
            background-color: var(--bg-secondary);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        @media screen and (max-width: 750px) {
            .main-container {
                flex-direction: column;
                text-align: center;
            }
            .role-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <div class="sidenav-links">
        <a href="about_us.php"><i class="fa-solid fa-circle-info"></i> About Us</a>
        <a href="contact_us.php"><i class="fa-solid fa-envelope"></i> Contact Us</a>
    </div>

    <div class="main-container">
        <!-- Left Illustration/Intro -->
        <div class="left-banner">
            <h1 style="font-size: 2.75rem; line-height: 1.2; margin-bottom: 16px;">
                Welcome to <span style="color: var(--color-primary);">SkillTrack</span>
            </h1>
            <p style="font-size: 1.1rem; margin-bottom: 24px;">
                SkillTrack – Student Skill & Internship Management System is a premium placement portal designed to bridge the gap between classroom skill development and professional corporate internships.
            </p>
            <div style="display: flex; gap: 24px; color: var(--text-secondary); font-size: 0.9rem;">
                <div><i class="fa-solid fa-briefcase" style="color: var(--color-success); margin-right: 6px;"></i> Placements</div>
                <div><i class="fa-solid fa-award" style="color: var(--color-primary); margin-right: 6px;"></i> Skill Analytics</div>
                <div><i class="fa-solid fa-code" style="color: var(--color-warning); margin-right: 6px;"></i> Portfolio</div>
            </div>
        </div>

        <!-- Right Portal Access -->
        <div class="right-portal">
            <div class="card" style="margin-bottom: 0; padding: 32px;">
                <h3 style="margin-bottom: 24px; text-align: center;">Sign In to Portal</h3>
                
                <form action="Admin/Admin.php" method="post">
                    <button class="btn btn-primary role-btn" type="submit" name="btnAdmin">
                        <i class="fa-solid fa-user-shield" style="font-size: 1.2rem;"></i> Admin / Placement Officer
                    </button>
                </form>

                <form action="Faculty/Faculty.php" method="post">
                    <button class="btn btn-secondary role-btn" type="submit" name="btnFaculty">
                        <i class="fa-solid fa-chalkboard-user" style="font-size: 1.2rem;"></i> Faculty Portal
                    </button>
                </form>

                <form action="Student/Student.php" method="post">
                    <button class="btn btn-success role-btn" type="submit" name="btnStudent">
                        <i class="fa-solid fa-user-graduate" style="font-size: 1.2rem;"></i> Student Placement Dashboard
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
