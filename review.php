<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['customer'])) {
    header("Location: customer_panel.php");
    exit();
}

$reservation_id = $_GET['reservation_id'] ?? ($_POST['reservation_id'] ?? '');

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);
    $responses = [];
    for ($i = 1; $i <= 10; $i++) {
        $responses["q$i"] = mysqli_real_escape_string($conn, $_POST["q$i"] ?? '');
    }

    // Check if review already submitted
    $check = mysqli_query($conn, "SELECT * FROM reviews WHERE reservation_id = $reservation_id");
    if (mysqli_num_rows($check)) {
        $message = "<div class='alert alert-warning text-center'>Review has already been submitted for this reservation.</div>";
    } else {
        $query = "INSERT INTO reviews (reservation_id, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10)
                  VALUES ($reservation_id, '" . implode("','", $responses) . "')";
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success text-center'>Thank you for your valuable feedback! We hope to welcome you again soon.</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>Something went wrong while submitting your review. Please try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Review - Hotel Paradise</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Theme Styles & Script -->
    <link rel="stylesheet" href="theme.css">
    <script src="theme.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            background: var(--hero-overlay), url('IMAGE/REGISTRATION.jpeg') center center / cover no-repeat fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .top-navbar {
            padding: 1.2rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
            background: var(--nav-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .brand-logo {
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .review-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 3rem;
            margin: 2.5rem auto;
            border-radius: 20px;
            box-shadow: var(--shadow-card);
            backdrop-filter: blur(16px);
            max-width: 850px;
            width: 92%;
        }

        .review-title {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 0.5rem;
        }

        .question-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            transition: all 0.25s ease;
        }

        .question-card:hover {
            border-color: rgba(212, 175, 55, 0.4);
            background: rgba(212, 175, 55, 0.04);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.8rem;
            font-size: 1rem;
        }

        .form-check-label {
            color: var(--text-secondary);
            font-size: 0.95rem;
            cursor: pointer;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: var(--border-color);
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--gold);
            border-color: var(--gold);
        }

        .footer-accent {
            padding: 1.2rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <a href="index.php" class="brand-logo">
            <span>🏨</span> HOTEL PARADISE
        </a>
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                <span class="theme-icon-container">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                </span>
                <span class="theme-text">Mode</span>
            </button>
            <a href="customer_panel.php" class="btn btn-outline-light btn-sm rounded-pill px-3" style="color: var(--text-primary); border-color: var(--border-color);">
                ← Back to Portal
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="container-fluid py-4">
        <div class="review-container">
            <h2 class="review-title">Customer Feedback Experience</h2>
            <p class="text-center text-muted small mb-4">Please rate your experience with Reservation #<?= htmlspecialchars($reservation_id) ?></p>

            <?php if (!empty($message)) echo $message; ?>

            <form method="POST">
                <input type="hidden" name="reservation_id" value="<?= htmlspecialchars($reservation_id) ?>">

                <?php
                $questions = [
                    "How clean was your room?",
                    "Was the staff helpful and friendly?",
                    "Was the check-in / check-out process smooth?",
                    "How comfortable was your bed and bedding?",
                    "How satisfied were you with the room amenities?",
                    "Was the room service timely and courteous?",
                    "How was the food and culinary quality (if availed)?",
                    "Did you feel safe and secure during your stay?",
                    "Was the hotel location and environment pleasant?",
                    "Would you recommend Hotel Paradise to friends and family?"
                ];

                $options = ["Strongly Agree", "Agree", "Disagree", "Strongly Disagree"];

                foreach ($questions as $index => $question) {
                    $qnum = $index + 1;
                    echo "<div class='question-card'>";
                    echo "<label class='form-label d-block'><strong>Q$qnum.</strong> $question</label>";
                    echo "<div class='d-flex flex-wrap gap-3 pt-1'>";
                    foreach ($options as $opt) {
                        $id = "q{$qnum}" . str_replace(' ', '', strtolower($opt));
                        echo "<div class='form-check form-check-inline'>
                                <input class='form-check-input' type='radio' name='q$qnum' id='$id' value='$opt' required>
                                <label class='form-check-label' for='$id'>$opt</label>
                              </div>";
                    }
                    echo "</div>";
                    echo "</div>";
                }
                ?>

                <div class="text-center mt-4 pt-2">
                    <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-semibold me-2">
                        <i class="bi bi-send me-1"></i> Submit Review
                    </button>
                    <a href="customer_panel.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer Space -->
    <footer class="footer-accent">
        &copy; <?php echo date("Y"); ?> Hotel Paradise. All rights reserved.
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
