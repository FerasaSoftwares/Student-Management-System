<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | SkillTrack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .team-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 30px;
        }

        .team-card {
            text-align: center;
            padding: 30px;
        }

        .team-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-primary);
            margin: 0 auto 15px auto;
        }

        @media screen and (max-width: 600px) {
            .team-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="container" style="max-width: 800px; margin-top: 40px;">
        <!-- Back to home -->
        <a href="index.php" class="btn btn-secondary" style="margin-bottom: 24px;"><i
                class="fa-solid fa-arrow-left"></i> Back to Portal</a>

        <div class="card" style="text-align: center; padding: 40px 20px;">
            <h1 style="color: var(--color-primary); margin-bottom: 16px;">About SkillTrack</h1>
            <p style="font-size: 1.1rem; max-width: 600px; margin: 0 auto 20px auto;">
                SkillTrack is an advanced Student Skill & Internship Management System designed to help departments
                track student technical expertise, list corporate internship matches, and enable placing officers to run
                real-time analytics.
            </p>
            <p style="color: var(--text-secondary);">
                Developed as a Final Year BCA Project by Piyush Makwana & Rajat Kevat.
            </p>
        </div>

        <h2 style="text-align: center; margin-top: 40px; margin-bottom: 20px;">Development Team</h2>
        <div class="team-grid">
            <div class="card team-card">
                <img class="team-photo" src="piyush.jpg" alt="Piyush">
                <h3>Piyush Makwana</h3>
                <p style="color: var(--color-primary); font-weight: 500; font-size: 0.9rem;">Lead Developer</p>
                <p style="font-size: 0.9rem;">Built core database components, secure authentications, and role
                    protection workflows.</p>
                <p style="font-size: 0.85rem; font-style: italic;">insider0piyush@gmail.com</p>
            </div>

            <div class="card team-card">
                <img class="team-photo" src="rajat.jpg" alt="Rajat">
                <h3>Rajat Kevat</h3>
                <p style="color: var(--color-primary); font-weight: 500; font-size: 0.9rem;">Lead Designer</p>
                <p style="font-size: 0.9rem;">Designed user dashboards, responsive grid systems, and custom dashboard
                    layouts.</p>
                <p style="font-size: 0.85rem; font-style: italic;">rajat.kevat.7@facebook.com</p>
            </div>
        </div>
    </div>

</body>

</html>