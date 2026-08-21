<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | SkillTrack</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="container" style="max-width: 600px; margin-top: 40px;">
        <!-- Back to home -->
        <a href="index.php" class="btn btn-secondary" style="margin-bottom: 24px;"><i
                class="fa-solid fa-arrow-left"></i> Back to Portal</a>

        <?php
        $success = false;
        if (isset($_POST['btngo'])) {
            $success = true;
        }
        ?>

        <?php if ($success): ?>
            <div class="card badge-success"
                style="padding: 16px; font-weight: 500; margin-bottom: 24px; text-align: center;">
                <i class="fa-solid fa-heart" style="margin-right: 8px;"></i>
                Thanks for reaching out! We'll get back to you shortly. ❤️
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 style="text-align: center; margin-bottom: 8px;">Get in Touch 👋</h2>
            <p style="text-align: center; margin-bottom: 24px;">Have questions about SkillTrack? Drop us a line!</p>

            <form action="contact_us.php" method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" name="name" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Your Email</label>
                        <input type="email" class="form-control" name="email" placeholder="john@example.com" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Interested In</label>
                        <select class="form-control" name="interest">
                            <option>General Inquiry</option>
                            <option>Placements Feedback</option>
                            <option>Corporate Partnership</option>
                            <option>Report a Bug</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Urgency</label>
                        <select class="form-control" name="urgency">
                            <option>Low</option>
                            <option>Medium</option>
                            <option>High</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Message Details</label>
                    <textarea class="form-control" name="message" placeholder="How can we help you today?"
                        required></textarea>
                </div>

                <button type="submit" class="btn btn-primary" name="btngo" style="width: 100%;"><i
                        class="fa-solid fa-paper-plane"></i> Send Message</button>
            </form>
        </div>
    </div>

</body>

</html>