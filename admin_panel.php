<?php
session_start();
include("db_connect.php");

// Remember me cookie check
$remembered_email = $_COOKIE['admin_remember_email'] ?? '';

// Login logic
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = md5($_POST['password']);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND password='$password' AND role='admin'");
    $admin = mysqli_fetch_assoc($res);
    if ($admin) {
        $_SESSION['admin'] = $admin;
        if (!empty($_POST['remember'])) {
            setcookie('admin_remember_email', $email, time() + (86400 * 30), "/");
        } else {
            setcookie('admin_remember_email', '', time() - 3600, "/");
        }
        header("Location: admin_panel.php");
        exit();
    } else {
        $login_error = "Invalid admin credentials. Please verify your email and password.";
    }
}

// Logout logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_panel.php");
    exit();
}

$alert_message = "";

// Admin Walk-in / Phone New Booking
if (isset($_SESSION['admin']) && isset($_POST['admin_book'])) {
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['customer_phone']));
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $room_id = intval($_POST['room_id']);
    $payment_status = $_POST['payment_status'] ?? 'pending';

    // Verify room availability
    $room_q = mysqli_query($conn, "SELECT room_number, price FROM rooms WHERE id = $room_id AND status = 'available'");
    if ($room_q && mysqli_num_rows($room_q) > 0) {
        $room = mysqli_fetch_assoc($room_q);
        
        // Calculate stay duration (number of nights / days)
        $cin_time = strtotime($check_in);
        $cout_time = strtotime($check_out);
        $days = 1;
        if ($cin_time && $cout_time) {
            $days = max(1, (int)round(($cout_time - $cin_time) / 86400));
        }
        $night_rate = floatval($room['price']);
        $total = $night_rate * $days;
        $nights_label = $days === 1 ? "1 night" : "{$days} nights";

        // Find or create customer account
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);
        $guest_email = "walkin_" . ($clean_phone ?: time()) . "@hotelparadise.com";
        $user_res = mysqli_query($conn, "SELECT id FROM users WHERE email='$guest_email'");
        
        if ($user_res && mysqli_num_rows($user_res) > 0) {
            $user_id = mysqli_fetch_assoc($user_res)['id'];
        } else {
            $guest_pass = md5("walkin" . time());
            mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$customer_name', '$guest_email', '$guest_pass', 'customer')");
            $user_id = mysqli_insert_id($conn);
        }

        // Insert reservation
        $res_insert = mysqli_query($conn, "INSERT INTO reservations (user_id, room_id, check_in, check_out, total) 
                                           VALUES ($user_id, $room_id, '$check_in', '$check_out', $total)");
        $reservation_id = mysqli_insert_id($conn);

        // Update room status
        mysqli_query($conn, "UPDATE rooms SET status = 'booked' WHERE id = $room_id");

        // If paid at desk, record billing and payment
        if ($payment_status === 'paid') {
            mysqli_query($conn, "INSERT INTO billing (reservation_id, total_amount, billing_date) 
                                 VALUES ($reservation_id, $total, CURDATE())");
            $bid = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO payment (billing_id, payment_mode, payment_date) 
                                 VALUES ($bid, 'Paid at Desk (Cash/Card)', CURDATE())");
        }

        $alert_message = "<div class='alert alert-success alert-dismissible fade show border-0' role='alert'>
            <i class='bi bi-check-circle-fill me-2'></i> <strong>Success!</strong> Room {$room['room_number']} booked for {$customer_name} ({$nights_label} &times; ₹" . number_format($night_rate, 2) . "/night = Total: <strong>₹" . number_format($total, 2) . "</strong>) (Res #{$reservation_id}).
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } else {
        $alert_message = "<div class='alert alert-danger alert-dismissible fade show border-0' role='alert'>
            <i class='bi bi-exclamation-triangle-fill me-2'></i> Selected room is no longer available.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// Check Out / Delete Reservation logic
if (isset($_SESSION['admin']) && (isset($_GET['checkout_id']) || isset($_GET['delete_reservation']))) {
    $reservation_id = intval($_GET['checkout_id'] ?? $_GET['delete_reservation']);

    $room_result = mysqli_query($conn, "SELECT room_id FROM reservations WHERE id = $reservation_id");
    if ($room_result && mysqli_num_rows($room_result)) {
        $room_row = mysqli_fetch_assoc($room_result);
        $room_id = $room_row['room_id'];

        // Clean up or keep billing records
        mysqli_query($conn, "DELETE FROM billing WHERE reservation_id = $reservation_id");
        mysqli_query($conn, "DELETE FROM reviews WHERE reservation_id = $reservation_id");
        mysqli_query($conn, "DELETE FROM reservations WHERE id = $reservation_id");

        // Mark room available
        mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE id = $room_id");

        $alert_message = "<div class='alert alert-info alert-dismissible fade show border-0' role='alert'>
            <i class='bi bi-check2-circle me-2'></i> Guest checked out successfully. Room status reverted to <strong>Available</strong>.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hotel Paradise</title>
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
            background: var(--bg-primary);
        }

        /* ========================================================
           LOGIN PAGE STYLES (When Not Authenticated)
           ======================================================== */
        body.login-view {
            background: var(--lobby-overlay), url('image/lobby.jpg') center center / cover no-repeat fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .login-top-bar {
            width: 100%;
            padding: 1.5rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--gold);
            background: rgba(255, 255, 255, 0.18);
            transform: translateX(-4px);
            border-color: var(--gold);
        }

        .login-main {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .portal-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 2.8rem 2.5rem;
            width: 100%;
            max-width: 460px;
            box-shadow: var(--shadow-card);
            text-align: center;
            animation: fadeInScale 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .shield-icon-wrapper {
            margin-bottom: 1.2rem;
            display: flex;
            justify-content: center;
        }

        .shield-badge {
            width: 68px;
            height: 68px;
            background: rgba(212, 175, 55, 0.12);
            border: 1px solid rgba(212, 175, 55, 0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--gold);
            box-shadow: 0 0 25px rgba(212, 175, 55, 0.25);
            transition: transform 0.3s ease;
        }

        .portal-card:hover .shield-badge {
            transform: scale(1.05) rotate(4deg);
        }

        .portal-title {
            font-family: 'Cinzel', serif;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--text-primary);
            margin-bottom: 1.8rem;
            text-transform: uppercase;
        }

        .field-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
            display: block;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .input-group-custom:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .input-icon {
            padding: 0 1rem;
            color: var(--text-muted);
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .btn-toggle-password {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 0 1rem;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .btn-toggle-password:hover {
            color: var(--gold);
        }

        .btn-access-dashboard {
            width: 100%;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 0.95rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.35);
            transition: all 0.3s ease;
        }

        .btn-access-dashboard:hover {
            background: linear-gradient(135deg, #f1c40f 0%, #d4af37 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(212, 175, 55, 0.5);
            color: #000000;
        }

        .restricted-notice {
            margin-top: 1.6rem;
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* ========================================================
           AUTHENTICATED DASHBOARD LAYOUT (Requested UI Format)
           ======================================================== */
        /* Top Navigation Header */
        .admin-header {
            background: var(--nav-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1020;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .admin-brand {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 1.5px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-box-wrapper {
            position: relative;
            width: 320px;
            max-width: 100%;
        }

        .search-input {
            width: 100%;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            padding: 0.55rem 1.2rem 0.55rem 2.4rem;
            font-size: 0.88rem;
            color: var(--text-primary);
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .btn-new-booking-cta {
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.55rem 1.3rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.25s ease;
        }

        .btn-new-booking-cta:hover {
            background: linear-gradient(135deg, #f1c40f 0%, #d4af37 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.45);
        }

        .admin-profile-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.45rem 1rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .admin-profile-pill:hover {
            border-color: var(--gold);
            color: var(--gold);
        }

        /* Two-Column App Layout */
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        /* Left Sidebar Navigation */
        .admin-sidebar {
            width: 250px;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            padding: 2rem 1.2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .sidebar-title {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 1.2rem;
            padding-left: 0.8rem;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link-custom:hover {
            color: var(--gold);
            background: rgba(212, 175, 55, 0.08);
            transform: translateX(4px);
        }

        .nav-link-custom.active {
            color: #0b0f19;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.25);
        }

        [data-theme="dark"] .nav-link-custom.active {
            color: #000000;
        }

        /* Main Workspace Container */
        .admin-content {
            flex-grow: 1;
            padding: 2rem 2.5rem 4rem;
            background: var(--bg-primary);
            overflow-y: auto;
        }

        .section-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.8rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-soft);
        }

        .section-label {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--gold);
            margin-bottom: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-subtle);
            padding-bottom: 0.8rem;
        }

        /* KPI Cards */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.4rem 1.6rem;
            box-shadow: var(--shadow-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.25s ease;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            border-color: rgba(212, 175, 55, 0.4);
        }

        .kpi-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }

        .kpi-value {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        /* Active Bookings Table */
        .bookings-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-primary);
            font-size: 0.92rem;
        }

        .bookings-table thead {
            background: var(--table-header-bg);
            color: var(--table-header-color);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }

        .bookings-table th, .bookings-table td {
            padding: 1rem 1.2rem;
            text-align: left;
            vertical-align: middle;
        }

        .bookings-table tbody tr {
            border-bottom: 1px solid var(--border-subtle);
            transition: background 0.2s ease;
        }

        .bookings-table tbody tr:hover {
            background: rgba(212, 175, 55, 0.05);
        }

        .btn-checkout {
            background: #dc3545;
            color: #ffffff;
            border: none;
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s ease;
        }

        .btn-checkout:hover {
            background: #bb2d3b;
            color: #ffffff;
            transform: scale(1.04);
        }

        /* Available Rooms Grid & Filter Tabs */
        .filter-tabs {
            display: inline-flex;
            gap: 6px;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
        }

        .filter-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.35rem 1rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-btn.active, .filter-btn:hover {
            background: var(--gold);
            color: #0b0f19;
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.1rem;
            margin-top: 1.2rem;
        }

        .room-card-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 1.2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .room-card-box:hover {
            border-color: rgba(212, 175, 55, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .room-number-tag {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 0.2rem;
        }

        .room-type-badge {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 0.6rem;
        }

        .badge-ac {
            background: rgba(13, 110, 253, 0.15);
            color: #3b82f6;
            border: 1px solid rgba(13, 110, 253, 0.3);
        }

        .badge-nonac {
            background: rgba(108, 117, 125, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }

        .room-price-tag {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 0.8rem;
        }

        .btn-room-book {
            width: 100%;
            background: rgba(212, 175, 55, 0.15);
            border: 1px solid var(--gold);
            color: var(--gold);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.4rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-room-book:hover {
            background: var(--gold);
            color: #0b0f19;
        }

        /* Billing Reports Overview */
        .billing-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 0.92rem;
            color: var(--text-secondary);
        }

        .billing-total-banner {
            margin-top: 1.2rem;
            padding: 1rem 1.4rem;
            border-radius: 12px;
            background: rgba(40, 167, 69, 0.12);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
            font-weight: 700;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ========================================================
           ADMIN BOOKING PORTAL MODAL (Exact Wireframe Format)
           ======================================================== */
        .modal-portal-content {
            background: var(--modal-bg);
            color: var(--modal-color);
            border: 1.5px solid var(--gold);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.7);
            overflow: hidden;
        }

        .modal-portal-header {
            background: rgba(212, 175, 55, 0.08);
            border-bottom: 1px solid var(--border-color);
            padding: 1.4rem 2rem;
            text-align: center;
            position: relative;
        }

        .portal-header-tagline {
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--gold);
            margin: 0;
        }

        .portal-header-sub {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .portal-input-group {
            display: flex;
            align-items: center;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .portal-input-group:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        .portal-icon {
            padding: 0 0.9rem;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .portal-field {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            padding: 0.75rem 0.8rem 0.75rem 0;
            color: var(--text-primary);
            font-size: 0.92rem;
        }

        .portal-select {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            font-size: 0.92rem;
            outline: none;
            transition: all 0.25s ease;
        }

        .portal-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .admin-sidebar {
                display: none;
            }
            .admin-content {
                padding: 1.5rem 1rem;
            }
            .search-box-wrapper {
                width: 200px;
            }
        }

        @media (max-width: 600px) {
            .admin-header {
                padding: 0.8rem 1rem;
                flex-wrap: wrap;
                gap: 10px;
            }
            .search-box-wrapper {
                order: 3;
                width: 100%;
            }
        }
    </style>
</head>

<?php if (!isset($_SESSION['admin'])): ?>
<!-- ========================================================
     ADMIN LOGIN SCREEN
     ======================================================== -->
<body class="login-view">
    <header class="login-top-bar">
        <a href="index.php" class="back-link">
            <span>←</span> Back to Public Site
        </a>
        <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
            <span class="theme-icon-container">
                <i class="bi bi-sun-fill icon-sun"></i>
                <i class="bi bi-moon-stars-fill icon-moon"></i>
            </span>
            <span class="theme-text">Mode</span>
        </button>
    </header>

    <main class="login-main">
        <div class="portal-card">
            <!-- Shield Icon Badge [ 🛡️ ] -->
            <div class="shield-icon-wrapper">
                <div class="shield-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
            </div>

            <h2 class="portal-title">SECURE ADMIN PORTAL</h2>

            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger text-start py-2 px-3 small border-0 mb-3" style="background: rgba(220, 53, 69, 0.25); color: #ff8b94;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_panel.php">
                <div class="mb-3 text-start">
                    <label class="field-label" for="adminEmail">Admin Email</label>
                    <div class="input-group-custom">
                        <span class="input-icon">✉️</span>
                        <input 
                            type="email" 
                            id="adminEmail" 
                            name="email" 
                            class="form-control-custom" 
                            placeholder="admin@paradise.com" 
                            value="<?php echo htmlspecialchars($remembered_email ?: 'admin@hotel.com'); ?>" 
                            required 
                            autofocus
                        >
                    </div>
                </div>

                <div class="mb-3 text-start">
                    <label class="field-label" for="adminPassword">Password</label>
                    <div class="input-group-custom">
                        <span class="input-icon">🔒</span>
                        <input 
                            type="password" 
                            id="adminPassword" 
                            name="password" 
                            class="form-control-custom" 
                            placeholder="••••••••••••••••" 
                            required
                        >
                        <button 
                            type="button" 
                            class="btn-toggle-password" 
                            onclick="togglePassword()" 
                            aria-label="Toggle password visibility"
                        >
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 options-row">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" <?php echo !empty($remembered_email) ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="forgot-link small" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot Access?</a>
                </div>

                <button type="submit" name="login" value="Login" class="btn-access-dashboard">
                    <span>Access Dashboard</span>
                </button>

                <div class="restricted-notice">
                    <span>🔒</span> Restricted Access Only
                </div>
            </form>
        </div>
    </main>

    <!-- Forgot Access Modal -->
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--modal-bg); border: 1px solid var(--border-color); border-radius: 18px; color: var(--modal-color);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" style="font-family: 'Cinzel', serif; color: var(--gold);">Admin Access Assistance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">Admin credentials are centrally managed by Hotel Operations.</p>
                    <div class="p-3 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color);">
                        <div class="small text-muted mb-1">Default System Credentials:</div>
                        <div><strong>Email:</strong> <code>admin@hotel.com</code></div>
                        <div><strong>Password:</strong> <code>admin123</code></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
<!-- ========================================================
     AUTHENTICATED ADMIN DASHBOARD (Requested Layout)
     ======================================================== -->
<body>
    <!-- Top Header Bar -->
    <header class="admin-header">
        <div class="d-flex align-items-center gap-3">
            <a href="admin_panel.php" class="admin-brand">
                <span>🏨</span> HOTEL ADMIN PANEL
            </a>
        </div>

        <!-- 🔍 Search guests/rooms... -->
        <div class="search-box-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="adminGlobalSearch" class="search-input" placeholder="Search guests/rooms..." autocomplete="off">
        </div>

        <!-- Right Header Actions -->
        <div class="d-flex align-items-center gap-2">
            <!-- Theme Toggle Button -->
            <button type="button" class="theme-toggle-btn me-1" aria-label="Toggle theme">
                <span class="theme-icon-container">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                </span>
            </button>

            <!-- [+] New Booking Button -->
            <button type="button" class="btn-new-booking-cta" data-bs-toggle="modal" data-bs-target="#adminBookingModal">
                <i class="bi bi-plus-lg"></i>
                <span>New Booking</span>
            </button>

            <!-- Admin Profile / Logout -->
            <div class="dropdown">
                <button class="admin-profile-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>👤</span> <?= htmlspecialchars($_SESSION['admin']['name'] ?? 'Admin') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="background: var(--modal-bg); border: 1px solid var(--border-color);">
                    <li><h6 class="dropdown-header small text-muted"><?= htmlspecialchars($_SESSION['admin']['email'] ?? '') ?></h6></li>
                    <li><a class="dropdown-item small" href="index.php"><i class="bi bi-house me-2"></i> Public Site</a></li>
                    <li><hr class="dropdown-divider" style="border-color: var(--border-color);"></li>
                    <li><a class="dropdown-item small text-danger" href="?logout=1"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- App Body: Sidebar + Main Content -->
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div>
                <div class="sidebar-title">NAVIGATION</div>
                <ul class="nav-menu">
                    <li>
                        <a href="#overview" class="nav-link-custom active">
                            <span>📊</span> Overview
                        </a>
                    </li>
                    <li>
                        <a href="#activeBookings" class="nav-link-custom">
                            <span>🛏️</span> Bookings
                        </a>
                    </li>
                    <li>
                        <a href="#availableRooms" class="nav-link-custom">
                            <span>🚪</span> Rooms
                        </a>
                    </li>
                    <li>
                        <a href="#billingReports" class="nav-link-custom">
                            <span>📑</span> Reports
                        </a>
                    </li>
                    <li>
                        <a href="#" class="nav-link-custom" data-bs-toggle="modal" data-bs-target="#settingsModal">
                            <span>⚙️</span> Settings
                        </a>
                    </li>
                </ul>
            </div>

            <div class="pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                <div class="small text-muted mb-2">Hotel Paradise v2.4</div>
                <a href="?logout=1" class="small text-danger text-decoration-none">
                    <i class="bi bi-box-arrow-left me-1"></i> Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="admin-content">
            <?php if (!empty($alert_message)) echo $alert_message; ?>

            <?php
            // KPI Summary Data Queries
            $booked_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM reservations");
            $rooms_booked_count = mysqli_fetch_assoc($booked_res)['count'] ?? 0;

            $avail_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms WHERE status='available'");
            $available_count = mysqli_fetch_assoc($avail_res)['count'] ?? 0;

            $rev_res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM billing");
            $total_revenue = mysqli_fetch_assoc($rev_res)['total'] ?? 0;
            
            // Format Revenue in ₹... or ₹K
            $formatted_rev = $total_revenue >= 1000 
                ? '₹' . (round($total_revenue / 1000, 1)) . 'K' 
                : '₹' . number_format($total_revenue);

            // Revenue received today
            $today_res = mysqli_query($conn, "SELECT SUM(total_amount) as today_total FROM billing WHERE billing_date = CURDATE()");
            $today_revenue = mysqli_fetch_assoc($today_res)['today_total'] ?? 0;
            ?>

            <!-- [ KPI SUMMARY ] -->
            <div id="overview" class="kpi-row">
                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">🔴 Rooms Booked</div>
                        <div class="kpi-value"><?= $rooms_booked_count ?></div>
                    </div>
                    <div style="font-size: 2rem; color: #ef4444;">🛏️</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">🟢 Available Rooms</div>
                        <div class="kpi-value text-success"><?= $available_count ?></div>
                    </div>
                    <div style="font-size: 2rem; color: #22c55e;">🚪</div>
                </div>

                <div class="kpi-card">
                    <div>
                        <div class="kpi-title">📈 Total Revenue</div>
                        <div class="kpi-value text-warning"><?= $formatted_rev ?></div>
                    </div>
                    <div style="font-size: 2rem; color: #eab308;">💰</div>
                </div>
            </div>

            <!-- [ ACTIVE BOOKINGS ] -->
            <section id="activeBookings" class="section-box">
                <div class="section-label">
                    <span><i class="bi bi-calendar2-check me-2"></i> ACTIVE BOOKINGS</span>
                    <span class="badge rounded-pill bg-danger"><?= $rooms_booked_count ?> Active</span>
                </div>

                <div class="bookings-table-wrapper">
                    <table class="bookings-table" id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $reservations_q = mysqli_query($conn, "SELECT r.id, r.check_in, r.check_out, r.total, u.name as customer_name, ro.room_number, ro.price 
                            FROM reservations r
                            JOIN users u ON u.id = r.user_id
                            JOIN rooms ro ON ro.id = r.room_id
                            ORDER BY r.id DESC");

                        if (mysqli_num_rows($reservations_q) > 0) {
                            while ($row = mysqli_fetch_assoc($reservations_q)) {
                                $cin = date("d-M-y", strtotime($row['check_in']));
                                $cout = date("d-M-y", strtotime($row['check_out']));
                                $cin_time = strtotime($row['check_in']);
                                $cout_time = strtotime($row['check_out']);
                                $days = 1;
                                if ($cin_time && $cout_time) {
                                    $days = max(1, (int)round(($cout_time - $cin_time) / 86400));
                                }
                                $days_lbl = $days === 1 ? "1 night" : "{$days} nights";
                                $formatted_tot = number_format($row['total'], 2);
                                $is_ac = floatval($row['price']) >= 1500;
                                $room_type_label = $is_ac ? 'AC' : 'Non-AC';
                                $badge_class = $is_ac ? 'badge-ac' : 'badge-nonac';

                                echo "<tr class='booking-row' data-search='" . strtolower("{$row['customer_name']} {$row['room_number']} {$room_type_label}") . "'>
                                    <td>
                                        <div class='fw-semibold'>{$row['customer_name']}</div>
                                        <small class='text-muted'>Res #{$row['id']} &bull; ₹{$formatted_tot} ({$days_lbl})</small>
                                    </td>
                                    <td>
                                        <div class='d-flex align-items-center gap-2'>
                                            <strong>Room {$row['room_number']}</strong>
                                            <span class='badge {$badge_class} rounded-pill px-2 py-0.5' style='font-size: 0.72rem; letter-spacing: 0.3px;'>{$room_type_label}</span>
                                        </div>
                                    </td>
                                    <td>{$cin}</td>
                                    <td>{$cout}</td>
                                    <td>
                                        <a href='?checkout_id={$row['id']}' onclick='return confirm(\"Check out {$row['customer_name']} from Room {$row['room_number']}?\");' class='btn-checkout'>
                                            <i class='bi bi-box-arrow-right'></i> Check Out
                                        </a>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No active bookings found. Click <strong>[+] New Booking</strong> to add a reservation.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- [ AVAILABLE ROOMS ] -->
            <section id="availableRooms" class="section-box">
                <div class="section-label">
                    <span><i class="bi bi-door-open me-2"></i> AVAILABLE ROOMS</span>
                    <!-- Filter: [All] [AC] [Non-AC] -->
                    <div class="filter-tabs">
                        <button type="button" class="filter-btn active" onclick="filterRooms('all', this)">All</button>
                        <button type="button" class="filter-btn" onclick="filterRooms('ac', this)">AC</button>
                        <button type="button" class="filter-btn" onclick="filterRooms('non-ac', this)">Non-AC</button>
                    </div>
                </div>

                <div class="rooms-grid" id="roomsGrid">
                    <?php
                    $avail_rooms_query = mysqli_query($conn, "SELECT * FROM rooms WHERE status='available' ORDER BY CAST(room_number AS UNSIGNED) ASC");
                    $room_index = 0;
                    while ($room = mysqli_fetch_assoc($avail_rooms_query)) {
                        $room_index++;
                        $is_ac = floatval($room['price']) >= 1500;
                        $type_cat = $is_ac ? 'ac' : 'non-ac';
                        $type_label = $is_ac ? 'AC Deluxe' : 'Non-AC';
                        $badge_class = $is_ac ? 'badge-ac' : 'badge-nonac';
                        $formatted_price = floatval($room['price']) >= 1000 
                            ? '₹' . (floatval($room['price']) / 1000) . 'K' 
                            : '₹' . number_format($room['price']);
                        
                        // Show first 16 cards initially, rest revealed via "View All Rooms"
                        $extra_class = $room_index > 12 ? 'room-card-hidden d-none' : '';

                        echo "<div class='room-card-box room-item {$extra_class}' data-category='{$type_cat}' data-search='room {$room['room_number']}'>
                            <div class='room-number-tag'>Room {$room['room_number']}</div>
                            <span class='room-type-badge {$badge_class}'>{$type_label}</span>
                            <div class='room-price-tag'>Price: {$formatted_price}</div>
                            <button type='button' class='btn-room-book' onclick=\"openBookingForRoom({$room['id']}, '{$room['room_number']}', '{$type_label}', {$room['price']})\">
                                [ Book ]
                            </button>
                        </div>";
                    }
                    ?>
                </div>

                <?php if ($room_index > 12): ?>
                <div class="text-end mt-3">
                    <button type="button" id="btnToggleAllRooms" class="btn btn-sm btn-link text-decoration-none fw-semibold" style="color: var(--gold);" onclick="toggleViewAllRooms()">
                        View All Rooms (<?= $room_index ?>) &gt;
                    </button>
                </div>
                <?php endif; ?>
            </section>

            <!-- [ BILLING REPORTS OVERVIEW ] -->
            <section id="billingReports" class="section-box">
                <div class="section-label">
                    <span><i class="bi bi-file-earmark-text me-2"></i> BILLING REPORTS OVERVIEW</span>
                    <span class="small text-muted">Latest Transactions</span>
                </div>

                <div class="billing-list">
                    <?php
                    $billing_q = mysqli_query($conn, "SELECT b.*, p.payment_mode FROM billing b 
                        LEFT JOIN payment p ON p.billing_id = b.id 
                        ORDER BY b.id DESC LIMIT 8");

                    if (mysqli_num_rows($billing_q) > 0) {
                        while ($b = mysqli_fetch_assoc($billing_q)) {
                            $mode = $b['payment_mode'] ?: 'Paid';
                            echo "<div class='billing-item'>
                                <span class='text-warning'>•</span>
                                <span>Reservation #{$b['reservation_id']}: <strong>₹" . number_format($b['total_amount'], 2) . "</strong> ({$mode})</span>
                                <small class='ms-auto text-muted'>{$b['billing_date']}</small>
                            </div>";
                        }
                    } else {
                        echo "<p class='text-muted small'>No billing records generated yet.</p>";
                    }
                    ?>
                </div>

                <div class="billing-total-banner">
                    <div>
                        <i class="bi bi-cash-stack me-2"></i>
                        <span>Total Amount Received Today: <strong>₹<?= number_format($today_revenue, 2) ?></strong></span>
                    </div>
                    <small class="text-muted">Lifetime: <strong>₹<?= number_format($total_revenue, 2) ?></strong></small>
                </div>
            </section>
        </main>
    </div>

    <!-- ========================================================
         ADMIN BOOKING PORTAL MODAL (Exact Wireframe Format)
         ======================================================== -->
    <div class="modal fade" id="adminBookingModal" tabindex="-1" aria-labelledby="adminBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content modal-portal-content">
                <div class="modal-portal-header">
                    <h5 class="portal-header-tagline" id="adminBookingModalLabel">--- ADMIN BOOKING PORTAL ---</h5>
                    <div class="portal-header-sub">Book Room for Walk-in / Phone Customer</div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="admin_panel.php" class="p-4">
                    <input type="hidden" name="admin_book" value="1">

                    <!-- Customer Name -->
                    <div class="mb-3">
                        <label class="field-label">Customer Name:</label>
                        <div class="portal-input-group">
                            <span class="portal-icon">👤</span>
                            <input type="text" name="customer_name" class="portal-field" placeholder="e.g. Rajesh Kumar" required>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label class="field-label">Phone Number:</label>
                        <div class="portal-input-group">
                            <span class="portal-icon">📞</span>
                            <input type="tel" name="customer_phone" class="portal-field" placeholder="+91 98765 43210" required>
                        </div>
                    </div>

                    <!-- Dates Row -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="field-label">Check-In Date:</label>
                            <div class="portal-input-group">
                                <span class="portal-icon">📅</span>
                                <input type="date" id="adminCheckIn" name="check_in" class="portal-field" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="field-label">Check-Out Date:</label>
                            <div class="portal-input-group">
                                <span class="portal-icon">📅</span>
                                <input type="date" id="adminCheckOut" name="check_out" class="portal-field" required>
                            </div>
                        </div>
                    </div>

                    <!-- Room Selected -->
                    <div class="mb-3">
                        <label class="field-label">Room Selected:</label>
                        <select name="room_id" id="adminRoomSelect" class="portal-select" required>
                            <option value="">Select an available room ▾</option>
                            <?php
                            $rooms_dropdown = mysqli_query($conn, "SELECT * FROM rooms WHERE status='available' ORDER BY CAST(room_number AS UNSIGNED) ASC");
                            while ($r = mysqli_fetch_assoc($rooms_dropdown)) {
                                $is_ac_opt = floatval($r['price']) >= 1500 ? 'AC' : 'Non-AC';
                                echo "<option value='{$r['id']}' data-price='{$r['price']}'>Room {$r['room_number']} ({$is_ac_opt}) — ₹" . number_format($r['price']) . "/night</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Live Price Calculation in Admin Modal -->
                    <div id="adminBookingSummaryBox" class="p-3 mb-3 rounded-3 d-none" style="background: rgba(212, 175, 55, 0.08); border: 1px dashed rgba(212, 175, 55, 0.4);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small text-muted">Duration:</span>
                                <strong id="adminSummaryDays" class="ms-1" style="color: var(--text-primary);">1 night</strong>
                            </div>
                            <div>
                                <span class="small text-muted">Total:</span>
                                <strong id="adminSummaryTotal" class="text-warning ms-1 fs-6">₹0.00</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="mb-4">
                        <label class="field-label">Payment Status:</label>
                        <div class="d-flex gap-4 pt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_status" id="payPending" value="pending" checked>
                                <label class="form-check-label" for="payPending">Pending</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_status" id="payDesk" value="paid">
                                <label class="form-check-label" for="payDesk">Paid at Desk</label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions [ Cancel ] [ Confirm Booking ] -->
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <button type="button" class="btn btn-secondary px-4 py-2 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-new-booking-cta px-4 py-2">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--modal-bg); border: 1px solid var(--border-color); border-radius: 18px; color: var(--modal-color);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" style="font-family: 'Cinzel', serif; color: var(--gold);"><i class="bi bi-gear me-2"></i> System Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="small text-muted mb-3">Hotel Paradise Management Console Settings</p>
                    <div class="list-group list-group-flush rounded" style="background: transparent;">
                        <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center" style="border-color: var(--border-subtle); color: var(--text-primary);">
                            <span>Database Status</span>
                            <span class="badge bg-success">Connected (Port 3307)</span>
                        </div>
                        <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center" style="border-color: var(--border-subtle); color: var(--text-primary);">
                            <span>Current Administrator</span>
                            <span class="text-warning fw-semibold"><?= htmlspecialchars($_SESSION['admin']['name'] ?? '') ?></span>
                        </div>
                        <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center" style="border-color: var(--border-subtle); color: var(--text-primary);">
                            <span>Currency</span>
                            <span>INR (₹)</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Password toggle function for login view
        function togglePassword() {
            const passwordInput = document.getElementById('adminPassword');
            const icon = document.getElementById('togglePasswordIcon');
            if (!passwordInput || !icon) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Pre-select room in modal when clicking [ Book ] from room cards
        function openBookingForRoom(roomId, roomNumber, roomType, price) {
            const select = document.getElementById('adminRoomSelect');
            if (select) {
                select.value = roomId;
            }
            updateAdminLiveEstimate();
            const modalEl = document.getElementById('adminBookingModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        // Live calculation for Admin booking modal
        function updateAdminLiveEstimate() {
            const inDate = document.getElementById('adminCheckIn');
            const outDate = document.getElementById('adminCheckOut');
            const roomSelect = document.getElementById('adminRoomSelect');
            const summaryBox = document.getElementById('adminBookingSummaryBox');
            const summaryDays = document.getElementById('adminSummaryDays');
            const summaryTotal = document.getElementById('adminSummaryTotal');

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
            if (summaryTotal) summaryTotal.textContent = '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
            summaryBox.classList.remove('d-none');
        }

        // Room filter function: All / AC / Non-AC
        function filterRooms(category, btnElement) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            if (btnElement) btnElement.classList.add('active');

            const roomItems = document.querySelectorAll('.room-item');
            roomItems.forEach(card => {
                const cat = card.getAttribute('data-category');
                if (category === 'all' || cat === category) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });
        }

        // Toggle "View All Rooms"
        let allRoomsExpanded = false;
        function toggleViewAllRooms() {
            const hiddenRooms = document.querySelectorAll('.room-card-hidden');
            const btn = document.getElementById('btnToggleAllRooms');
            allRoomsExpanded = !allRoomsExpanded;

            hiddenRooms.forEach(room => {
                if (allRoomsExpanded) {
                    room.classList.remove('d-none');
                } else {
                    room.classList.add('d-none');
                }
            });

            if (btn) {
                btn.innerHTML = allRoomsExpanded ? 'Show Less Rooms &lt;' : 'View All Rooms &gt;';
            }
        }

        // Real-time Global Search for Guests and Rooms
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('adminGlobalSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = this.value.toLowerCase().trim();

                    // Filter Bookings table
                    document.querySelectorAll('.booking-row').forEach(row => {
                        const searchData = row.getAttribute('data-search') || '';
                        if (!q || searchData.includes(q)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Filter Rooms Grid
                    document.querySelectorAll('.room-item').forEach(card => {
                        const searchData = card.getAttribute('data-search') || '';
                        if (!q || searchData.includes(q)) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Set default date limits for Admin booking modal
            const inDate = document.getElementById('adminCheckIn');
            const outDate = document.getElementById('adminCheckOut');
            const roomSelect = document.getElementById('adminRoomSelect');

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
                    updateAdminLiveEstimate();
                });

                outDate.addEventListener('change', updateAdminLiveEstimate);
            }

            if (roomSelect) {
                roomSelect.addEventListener('change', updateAdminLiveEstimate);
            }

            updateAdminLiveEstimate();
        });
    </script>
</body>
</html>
