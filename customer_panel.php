<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include("db_connect.php");

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$alert_message = "";

// Registration
if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $raw_pass = $_POST['password'] ?? '';
    $raw_confirm = $_POST['confirm_password'] ?? '';

    if (!empty($raw_confirm) && $raw_pass !== $raw_confirm) {
        $alert_message = "<div class='alert alert-danger text-center'>Passwords do not match. Please verify your passwords.</div>";
        $_GET['page'] = 'register';
    } else {
        $password = md5($raw_pass);
        
        // Check if email already exists
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $alert_message = "<div class='alert alert-danger text-center'>Email is already registered. Please login.</div>";
            $_GET['page'] = 'login';
        } else {
            mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', 'customer')");
            $alert_message = "<div class='alert alert-success text-center'>Account registered successfully! Please log in below.</div>";
            $_GET['page'] = 'login';
        }
    }
}

// Login
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = md5($_POST['password']);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND password='$password' AND role='customer'");
    $cust = mysqli_fetch_assoc($res);
    if ($cust) {
        $_SESSION['customer'] = $cust;
        header("Location: customer_panel.php");
        exit();
    } else {
        $alert_message = "<div class='alert alert-danger text-center'>Invalid email or password. Please try again.</div>";
    }
}

$isLoggedIn = isset($_SESSION['customer']);

// Booking
if ($isLoggedIn && isset($_POST['book'])) {
    $user_id = $_SESSION['customer']['id'];
    $room_id = intval($_POST['room_id']);
    $in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $out = mysqli_real_escape_string($conn, $_POST['check_out']);
    
    // Calculate stay duration (number of nights / days)
    $cin_time = strtotime($in);
    $cout_time = strtotime($out);
    $days = 1;
    if ($cin_time && $cout_time) {
        $days = max(1, (int)round(($cout_time - $cin_time) / 86400));
    }
    
    $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number, price FROM rooms WHERE id=$room_id AND status='available'"));
    if ($room) {
        $night_rate = floatval($room['price']);
        $total = $night_rate * $days;
        $nights_label = $days === 1 ? "1 night" : "{$days} nights";

        mysqli_query($conn, "INSERT INTO reservations (user_id, room_id, check_in, check_out, total) 
                   VALUES ($user_id, $room_id, '$in', '$out', $total)");
        mysqli_query($conn, "UPDATE rooms SET status='booked' WHERE id=$room_id");
        $alert_message = "<div class='alert alert-success alert-dismissible fade show border-0' role='alert'>
            <i class='bi bi-check-circle-fill me-2'></i> Room <strong>{$room['room_number']}</strong> booked successfully! <strong>{$nights_label}</strong> &times; ₹" . number_format($night_rate, 2) . "/night = Total: <strong>₹" . number_format($total, 2) . "</strong>. Complete payment below to confirm your stay.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } else {
        $alert_message = "<div class='alert alert-danger alert-dismissible fade show border-0' role='alert'>
            <i class='bi bi-exclamation-triangle-fill me-2'></i> Selected room is no longer available. Please choose another room.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// Delete Reservation
if ($isLoggedIn && isset($_POST['delete_reservation'])) {
    $id = intval($_POST['delete_reservation']);
    $user_id = $_SESSION['customer']['id'];
    $res = mysqli_query($conn, "SELECT room_id FROM reservations WHERE id=$id AND user_id=$user_id");
    if ($res && mysqli_num_rows($res)) {
        $room_id = mysqli_fetch_assoc($res)['room_id'];
        mysqli_query($conn, "DELETE FROM billing WHERE reservation_id=$id");
        mysqli_query($conn, "DELETE FROM reviews WHERE reservation_id=$id");
        mysqli_query($conn, "DELETE FROM reservations WHERE id=$id");
        mysqli_query($conn, "UPDATE rooms SET status='available' WHERE id=$room_id");
        $alert_message = "<div class='alert alert-warning alert-dismissible fade show border-0' role='alert'>
            <i class='bi bi-info-circle-fill me-2'></i> Reservation #$id cancelled successfully.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// Payment
if ($isLoggedIn && isset($_POST['payment_mode']) && isset($_POST['reservation_id'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $payment_mode = mysqli_real_escape_string($conn, $_POST['payment_mode']);
    $billingCheck = mysqli_query($conn, "SELECT * FROM billing WHERE reservation_id=$reservation_id");

    if (mysqli_num_rows($billingCheck) == 0) {
        $resQuery = mysqli_query($conn, "SELECT total FROM reservations WHERE id=$reservation_id");
        if ($resQuery && mysqli_num_rows($resQuery)) {
            $res = mysqli_fetch_assoc($resQuery);
            $total = $res['total'];

            mysqli_query($conn, "INSERT INTO billing (reservation_id, total_amount, billing_date) 
                                 VALUES ($reservation_id, $total, CURDATE())");
            $bid = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO payment (billing_id, payment_mode, payment_date) 
                                 VALUES ($bid, '$payment_mode', CURDATE())");

            $alert_message = "<div class='alert alert-success alert-dismissible fade show border-0' role='alert'>
                <i class='bi bi-check-circle-fill me-2'></i> Payment of <strong>₹" . number_format($total, 2) . "</strong> completed successfully via <strong>$payment_mode</strong>.
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
        } else {
            $alert_message = "<div class='alert alert-danger text-center'>Reservation not found. Payment failed.</div>";
        }
    } else {
        $alert_message = "<div class='alert alert-warning text-center'>Payment has already been completed for this reservation.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal - Paradise Hotel</title>
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
            overflow-x: hidden;
        }

        /* Top Bar */
        .customer-top-navbar {
            padding: 1rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--nav-bg);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .brand-logo-text {
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .brand-logo-text:hover {
            color: var(--gold);
        }

        .user-welcome-badge {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout-custom {
            background: transparent;
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.5);
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-logout-custom:hover {
            background: #ef4444;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        /* Main Workspace */
        .customer-main-content {
            flex-grow: 1;
            padding: 2.2rem 2.5rem 3.5rem;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        /* Section Card Container */
        .cust-section-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem 2.4rem;
            margin-bottom: 2.2rem;
            box-shadow: var(--shadow-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .cust-section-header {
            font-family: 'Cinzel', serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--gold);
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--border-subtle);
            margin-bottom: 1.6rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form Inputs */
        .form-label-custom {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        .form-select-custom, .form-input-custom {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.25s ease;
        }

        .form-select-custom:focus, .form-input-custom:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .btn-book-room-cta {
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 0.8rem 2.2rem;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-book-room-cta:hover {
            background: linear-gradient(135deg, #f1c40f 0%, #d4af37 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(212, 175, 55, 0.45);
            color: #000000;
        }

        /* Reservations Table */
        .reservations-table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 14px;
        }

        .res-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            color: var(--text-primary);
            font-size: 0.92rem;
        }

        .res-table thead {
            background: var(--table-header-bg);
            color: var(--table-header-color);
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
        }

        .res-table th, .res-table td {
            padding: 1.1rem 1.2rem;
            vertical-align: middle;
        }

        .res-table tbody tr {
            border-bottom: 1px solid var(--border-subtle);
            transition: background 0.2s ease;
        }

        .res-table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        /* Status Badges */
        .badge-status-paid {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #22c55e;
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-status-due {
            background: rgba(249, 115, 22, 0.15);
            border: 1px solid rgba(249, 115, 22, 0.4);
            color: #f97316;
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Actions Buttons */
        .btn-table-review {
            background: rgba(212, 175, 55, 0.15);
            border: 1px solid var(--gold);
            color: var(--gold);
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .btn-table-review:hover {
            background: var(--gold);
            color: #0b0f19;
        }

        .btn-table-pay {
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.38rem 1rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.25);
        }

        .btn-table-pay:hover {
            background: linear-gradient(135deg, #f1c40f 0%, #d4af37 100%);
            transform: translateY(-1px);
        }

        .btn-table-cancel {
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #ef4444;
            padding: 0.32rem 0.65rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            margin-left: 6px;
            transition: all 0.2s ease;
        }

        .btn-table-cancel:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* ========================================================
           PAYMENT MODAL (Exact Wireframe Format)
           ======================================================== */
        .modal-payment-content {
            background: var(--modal-bg);
            color: var(--modal-color);
            border: 1.5px solid var(--gold);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }

        .modal-payment-header {
            background: rgba(212, 175, 55, 0.08);
            border-bottom: 1px solid var(--border-color);
            padding: 1.4rem 2rem;
            position: relative;
        }

        .payment-modal-tagline {
            font-family: 'Cinzel', serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--gold);
            text-align: center;
            margin-bottom: 0.4rem;
        }

        .payment-summary-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.8rem;
            border-top: 1px solid var(--border-subtle);
            font-size: 0.95rem;
        }

        .payment-qr-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            margin-top: 1rem;
        }

        .upi-qr-image {
            max-width: 160px;
            width: 100%;
            border-radius: 12px;
            border: 3px solid var(--gold);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            margin: 1rem 0;
        }

        .upi-id-badge {
            display: inline-block;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.35);
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--gold);
        }

        /* Unauthenticated Auth Form Box (from previous turn) */
        .auth-card-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            padding: 2.8rem 2.6rem;
            width: 100%;
            max-width: 520px;
            box-shadow: var(--shadow-card);
            margin: 2rem auto;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .input-group-icon {
            padding: 0 0.95rem;
            color: var(--text-muted);
            font-size: 1.15rem;
        }

        .form-control-custom {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            padding: 0.85rem 0.6rem 0.85rem 0;
            font-size: 0.95rem;
        }

        .btn-auth-primary {
            width: 100%;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 0.95rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.35);
            margin-top: 0.8rem;
        }

        .footer-accent {
            padding: 1.2rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .customer-top-navbar {
                padding: 1rem 1.2rem;
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            .customer-main-content {
                padding: 1.5rem 1rem;
            }
            .cust-section-card {
                padding: 1.5rem 1.2rem;
            }
        }
    </style>
</head>
<body>

<?php if ($isLoggedIn): ?>
    <!-- ========================================================
         AUTHENTICATED CUSTOMER PAGE (Requested Layout)
         ======================================================== -->
    <!-- Top Bar: 🏨 PARADISE HOTEL | 👤 Welcome, [NAME] [Logout] -->
    <header class="customer-top-navbar">
        <a href="index.php" class="brand-logo-text">
            <span>🏨</span> PARADISE HOTEL
        </a>

        <div class="d-flex align-items-center gap-3">
            <!-- Theme Toggle Button -->
            <button type="button" class="theme-toggle-btn me-2" aria-label="Toggle theme">
                <span class="theme-icon-container">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                </span>
            </button>

            <!-- User Welcome -->
            <span class="user-welcome-badge">
                <span>👤</span> Welcome, <strong><?= strtoupper(htmlspecialchars($_SESSION['customer']['name'])) ?></strong>
            </span>

            <!-- Logout Button -->
            <a href="?logout=1" class="btn-logout-custom">
                [Logout]
            </a>
        </div>
    </header>

    <!-- Main Customer Body -->
    <main class="customer-main-content">
        <?php if (!empty($alert_message)) echo $alert_message; ?>

        <!-- [+] NEW BOOKING CARD -->
        <section class="cust-section-card">
            <div class="cust-section-header">
                <span>[+] NEW BOOKING</span>
            </div>

            <form method="POST" action="customer_panel.php">
                <input type="hidden" name="book" value="1">
                
                <div class="row g-4 align-items-center mb-4">
                    <!-- Room Category Dropdown -->
                    <div class="col-lg-4 col-md-12">
                        <label class="form-label-custom">Room Category</label>
                        <select name="room_id" id="customerRoomSelect" class="form-select-custom" required>
                            <option value="">Select Category ▾</option>
                            <?php
                            $rooms = mysqli_query($conn, "SELECT * FROM rooms WHERE status='available' ORDER BY CAST(room_number AS UNSIGNED) ASC");
                            while ($room = mysqli_fetch_assoc($rooms)) {
                                $is_ac = floatval($room['price']) >= 1500;
                                $category_name = $is_ac ? 'Deluxe AC' : 'Standard Non-AC';
                                echo "<option value='{$room['id']}' data-price='{$room['price']}' data-room='{$room['room_number']}'>Room {$room['room_number']} — {$category_name} (₹" . number_format($room['price'], 2) . "/night)</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Check-In Date -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label-custom">Check-In</label>
                        <input type="date" name="check_in" id="customerCheckIn" class="form-input-custom" required>
                    </div>

                    <!-- Check-Out Date -->
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label-custom">Check-Out</label>
                        <input type="date" name="check_out" id="customerCheckOut" class="form-input-custom" required>
                    </div>
                </div>

                <!-- Live Price Calculation Summary -->
                <div id="bookingSummaryBox" class="p-3 mb-4 rounded-3 d-none" style="background: rgba(212, 175, 55, 0.08); border: 1px dashed rgba(212, 175, 55, 0.4);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-muted small">Stay Duration:</span>
                            <strong id="summaryDays" class="ms-1" style="color: var(--text-primary);">1 night</strong>
                            <span class="text-muted small ms-2">(Rate: <span id="summaryRate">₹0.00</span>/night)</span>
                        </div>
                        <div class="fs-5">
                            <span class="text-muted small">Total Amount:</span>
                            <strong id="summaryTotal" class="text-warning ms-1">₹0.00</strong>
                        </div>
                    </div>
                </div>

                <!-- Submit Button: [ Book Room ➔ ] -->
                <div class="text-end">
                    <button type="submit" class="btn-book-room-cta">
                        <span>Book Room ➔</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- 📋 YOUR RESERVATIONS CARD -->
        <section class="cust-section-card">
            <div class="cust-section-header">
                <span>📋 YOUR RESERVATIONS</span>
            </div>

            <div class="reservations-table-wrapper">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $user_id = $_SESSION['customer']['id'];
                    $res_query = mysqli_query($conn, "SELECT r.room_number, res.* FROM reservations res 
                                                JOIN rooms r ON res.room_id = r.id 
                                                WHERE res.user_id = $user_id ORDER BY res.id DESC");

                    if (mysqli_num_rows($res_query) > 0) {
                        while ($row = mysqli_fetch_assoc($res_query)) {
                            $reservation_id = $row['id'];
                            $billing = mysqli_query($conn, "SELECT * FROM billing WHERE reservation_id=$reservation_id");
                            $paid = mysqli_num_rows($billing) > 0;

                            $cin = date("d-M-Y", strtotime($row['check_in']));
                            $cout = date("d-M-Y", strtotime($row['check_out']));
                            $cin_time = strtotime($row['check_in']);
                            $cout_time = strtotime($row['check_out']);
                            $days = 1;
                            if ($cin_time && $cout_time) {
                                $days = max(1, (int)round(($cout_time - $cin_time) / 86400));
                            }
                            $nights_text = $days === 1 ? "1 night" : "{$days} nights";
                            $formatted_total = "₹" . number_format($row['total'], 2);

                            echo "<tr>
                                <td><strong>{$row['room_number']}</strong></td>
                                <td>{$cin}</td>
                                <td>{$cout}</td>
                                <td>
                                    <div class='fw-bold text-warning'>{$formatted_total}</div>
                                    <small class='text-muted'>{$nights_text}</small>
                                </td>
                                <td>";
                            
                            if ($paid) {
                                echo "<span class='badge-status-paid'>[🟢 Paid]</span>";
                            } else {
                                echo "<span class='badge-status-due'>[🟠 Due ]</span>";
                            }

                            echo "</td>
                                <td>";

                            if ($paid) {
                                echo "<a href='review.php?reservation_id={$reservation_id}' class='btn-table-review'>[Review]</a>";
                            } else {
                                echo "<button type='button' class='btn-table-pay' onclick=\"openPaymentModal({$reservation_id}, '{$row['room_number']}', '{$row['total']}')\">[Pay Now]</button>
                                      <form method='POST' class='d-inline' onsubmit=\"return confirm('Cancel this reservation for Room {$row['room_number']}?');\">
                                          <input type='hidden' name='delete_reservation' value='{$reservation_id}'>
                                          <button type='submit' class='btn-table-cancel' title='Cancel Reservation'>[x]</button>
                                      </form>";
                            }

                            echo "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No reservations recorded yet. Book your room above!</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ========================================================
         PAYMENT MODAL (POP-UP) - Exact Wireframe Format
         ======================================================== -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content modal-payment-content">
                <!-- Modal Header -->
                <div class="modal-payment-header">
                    <div class="payment-modal-tagline">--- PAYMENT MODAL (POP-UP) ---</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0" id="paymentModalLabel">Complete Your Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="payment-summary-bar mt-2">
                        <span id="payModalRoom">Reservation: Room ...</span>
                        <span id="payModalTotal" class="fw-bold text-warning">Total: ₹0.00</span>
                    </div>
                </div>

                <form method="POST" action="customer_panel.php">
                    <input type="hidden" name="reservation_id" id="payReservationId" value="">

                    <div class="p-4">
                        <!-- Select Payment Method -->
                        <label class="form-label-custom mb-2">Select Payment Method:</label>
                        <div class="d-flex flex-wrap gap-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_mode" id="payModeCard" value="Credit Card" onchange="switchPaymentTab('card')">
                                <label class="form-check-label" for="payModeCard">Credit Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_mode" id="payModeUPI" value="UPI" checked onchange="switchPaymentTab('upi')">
                                <label class="form-check-label" for="payModeUPI">UPI</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_mode" id="payModeCash" value="Cash at Reception" onchange="switchPaymentTab('cash')">
                                <label class="form-check-label" for="payModeCash">Cash at Reception</label>
                            </div>
                        </div>

                        <!-- 📱 Scan to Pay via UPI Container -->
                        <div id="tabUPI" class="payment-qr-card">
                            <div class="fw-semibold mb-1">📱 Scan to Pay via UPI</div>
                            <div class="text-center">
                                <img src="IMAGE/qr.jpeg" alt="UPI QR Code" class="upi-qr-image">
                            </div>
                            <div class="mt-1">
                                <span class="upi-id-badge">UPI ID: <code>hotelparadise@sbi</code></span>
                            </div>
                        </div>

                        <!-- Credit Card Container -->
                        <div id="tabCard" class="payment-qr-card d-none text-start">
                            <div class="fw-semibold mb-3 text-center">💳 Credit / Debit Card Payment</div>
                            <div class="mb-2">
                                <label class="small text-muted">Card Number</label>
                                <input type="text" class="form-input-custom py-2" placeholder="4532 •••• •••• 8890" maxlength="19">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="small text-muted">Expiry</label>
                                    <input type="text" class="form-input-custom py-2" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted">CVV</label>
                                    <input type="password" class="form-input-custom py-2" placeholder="•••" maxlength="3">
                                </div>
                            </div>
                        </div>

                        <!-- Cash Container -->
                        <div id="tabCash" class="payment-qr-card d-none text-center">
                            <div class="fw-semibold mb-2">💵 Pay Cash at Reception</div>
                            <p class="small text-muted mb-0">You can pay directly in cash at the hotel front desk during your check-in. Your reservation is reserved.</p>
                        </div>
                    </div>

                    <!-- Modal Actions [ Cancel ] [ Confirm Payment ] -->
                    <div class="d-flex justify-content-between align-items-center p-4 pt-0">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-book-room-cta px-4">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================================
         UNAUTHENTICATED CUSTOMER LOGIN / SIGN UP
         ======================================================== -->
    <header class="customer-top-navbar">
        <a href="index.php" class="brand-logo-text">
            <span>🏨</span> PARADISE HOTEL &amp; RESORT
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3" style="color: var(--text-primary); border-color: var(--border-color);">
                ← Back to Home
            </a>
            <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                <span class="theme-icon-container">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                </span>
            </button>
        </div>
    </header>

    <main class="customer-main-content d-flex align-items-center justify-content-center">
        <div class="auth-card-box">
            <?php if (!empty($alert_message)) echo $alert_message; ?>

            <?php if (isset($_GET['page']) && $_GET['page'] === 'register'): ?>
                <!-- Sign Up Form -->
                <h2 class="text-center fw-bold mb-1" style="font-family: 'Cinzel', serif;">Create Your Account</h2>
                <p class="text-center text-muted small mb-4">Join us to experience luxury and comfort.</p>

                <form method="POST" action="customer_panel.php?page=register">
                    <div class="mb-3">
                        <label class="form-label-custom">Full Name</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon">👤</span>
                            <input type="text" name="name" class="form-control-custom" placeholder="e.g. Rohan Sharma" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Email Address</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon">✉️</span>
                            <input type="email" name="email" class="form-control-custom" placeholder="rohan@example.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Password</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon">🔒</span>
                            <input type="password" id="regPassword" name="password" class="form-control-custom" placeholder="••••••••••••" required>
                            <button type="button" class="btn btn-sm text-muted" onclick="togglePass('regPassword')"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Confirm Password</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon">🔒</span>
                            <input type="password" id="regConfirmPassword" name="confirm_password" class="form-control-custom" placeholder="••••••••••••" required>
                            <button type="button" class="btn btn-sm text-muted" onclick="togglePass('regConfirmPassword')"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" name="register" value="Register" class="btn-auth-primary">
                        Register
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        Already have an account? <a href="customer_panel.php" class="text-warning fw-semibold text-decoration-none">Log in</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- Login Form -->
                <h2 class="text-center fw-bold mb-1" style="font-family: 'Cinzel', serif;">Customer Login</h2>
                <p class="text-center text-muted small mb-4">Welcome back! Access your reservations and stays.</p>

                <form method="POST" action="customer_panel.php">
                    <div class="mb-3">
                        <label class="form-label-custom">Email Address</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon">✉️</span>
                            <input type="email" name="email" class="form-control-custom" placeholder="rohan@example.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Password</label>
                        <div class="input-group-custom">
                            <span class="input-group-icon">🔒</span>
                            <input type="password" id="loginPassword" name="password" class="form-control-custom" placeholder="••••••••••••" required>
                            <button type="button" class="btn btn-sm text-muted" onclick="togglePass('loginPassword')"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <button type="submit" name="login" value="Login" class="btn-auth-primary">
                        Log In
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        Don't have an account? <a href="customer_panel.php?page=register" class="text-warning fw-semibold text-decoration-none">Sign up</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
<?php endif; ?>

    <!-- Footer Space -->
    <footer class="footer-accent">
        &copy; <?php echo date("Y"); ?> Paradise Hotel &amp; Resort. All rights reserved.
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Password toggle for login/register
        function togglePass(inputId) {
            const el = document.getElementById(inputId);
            if (el) {
                el.type = el.type === 'password' ? 'text' : 'password';
            }
        }

        // Open Payment Modal popup
        function openPaymentModal(resId, roomNumber, total) {
            const resField = document.getElementById('payReservationId');
            const roomField = document.getElementById('payModalRoom');
            const totalField = document.getElementById('payModalTotal');
            
            if (resField) resField.value = resId;
            if (roomField) roomField.textContent = 'Reservation: Room ' + roomNumber;
            if (totalField) totalField.textContent = 'Total: ₹' + parseFloat(total).toFixed(2);

            // Default to UPI
            const upiRadio = document.getElementById('payModeUPI');
            if (upiRadio) upiRadio.checked = true;
            switchPaymentTab('upi');

            const modalEl = document.getElementById('paymentModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        // Switch Payment Tabs in Modal
        function switchPaymentTab(mode) {
            const upiBox = document.getElementById('tabUPI');
            const cardBox = document.getElementById('tabCard');
            const cashBox = document.getElementById('tabCash');

            if (upiBox) upiBox.classList.add('d-none');
            if (cardBox) cardBox.classList.add('d-none');
            if (cashBox) cashBox.classList.add('d-none');

            if (mode === 'upi' && upiBox) upiBox.classList.remove('d-none');
            if (mode === 'card' && cardBox) cardBox.classList.remove('d-none');
            if (mode === 'cash' && cashBox) cashBox.classList.remove('d-none');
        }

        // Default Dates Setup & Live Price Calculation
        document.addEventListener('DOMContentLoaded', function () {
            const inDate = document.getElementById('customerCheckIn');
            const outDate = document.getElementById('customerCheckOut');
            const roomSelect = document.getElementById('customerRoomSelect');
            const summaryBox = document.getElementById('bookingSummaryBox');
            const summaryDays = document.getElementById('summaryDays');
            const summaryRate = document.getElementById('summaryRate');
            const summaryTotal = document.getElementById('summaryTotal');

            function updateLiveEstimate() {
                if (!inDate || !outDate || !roomSelect || !summaryBox) return;

                const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                const price = parseFloat(selectedOption?.dataset?.price || 0);

                if (!inDate.value || !outDate.value || !price) {
                    summaryBox.classList.add('d-none');
                    return;
                }

                const inTime = new Date(inDate.value).getTime();
                const outTime = new Date(outDate.value).getTime();
                let days = Math.round((outTime - inTime) / (1000 * 60 * 60 * 24));
                if (isNaN(days) || days <= 0) days = 1;

                const total = price * days;
                if (summaryDays) summaryDays.textContent = days === 1 ? '1 night' : days + ' nights';
                if (summaryRate) summaryRate.textContent = '₹' + price.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                if (summaryTotal) summaryTotal.textContent = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                summaryBox.classList.remove('d-none');
            }

            if (inDate && outDate) {
                const today = new Date().toISOString().split('T')[0];
                inDate.min = today;
                if (!inDate.value) inDate.value = today;

                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                outDate.min = today;
                if (!outDate.value) outDate.value = tomorrow.toISOString().split('T')[0];

                inDate.addEventListener('change', function () {
                    if (inDate.value) {
                        outDate.min = inDate.value;
                        if (outDate.value <= inDate.value) {
                            const next = new Date(inDate.value);
                            next.setDate(next.getDate() + 1);
                            outDate.value = next.toISOString().split('T')[0];
                        }
                    }
                    updateLiveEstimate();
                });

                outDate.addEventListener('change', updateLiveEstimate);
            }

            if (roomSelect) {
                roomSelect.addEventListener('change', updateLiveEstimate);
            }

            updateLiveEstimate();
        });
    </script>
</body>
</html>
