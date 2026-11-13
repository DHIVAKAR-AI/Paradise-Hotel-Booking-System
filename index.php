<!-- WELCOME PAGE (index.php) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Paradise - Luxury & Comfort</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            background: var(--hero-overlay), url('image/HOTEL1.jpeg') center center / cover no-repeat fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
        }

        /* Top Navigation Bar */
        .top-navbar {
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: var(--nav-bg);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand-logo {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: color 0.3s ease;
        }

        .brand-logo:hover {
            color: var(--gold);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-nav-customer {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .btn-nav-customer:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-nav-admin {
            background: transparent;
            color: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(212, 175, 55, 0.5);
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-nav-admin:hover {
            background: var(--gold);
            color: #0b0f19;
            font-weight: 600;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.35);
        }

        /* Hero Content Section */
        .hero-section {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .hero-content {
            max-width: 850px;
            animation: fadeIn 1s ease-out;
        }

        .hero-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(2.4rem, 6vw, 4.2rem);
            font-weight: 800;
            letter-spacing: 1.5px;
            line-height: 1.2;
            margin-bottom: 1.2rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
            background: linear-gradient(180deg, #ffffff 30%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.4rem);
            font-weight: 300;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 2.8rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        /* Primary Explore Button */
        .btn-explore {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-decoration: none;
            padding: 1.1rem 2.8rem;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.35);
            transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-explore .arrow {
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }

        .btn-explore:hover {
            background: linear-gradient(135deg, #f1c40f 0%, #d4af37 100%);
            color: #000000;
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 14px 30px rgba(212, 175, 55, 0.5);
        }

        .btn-explore:hover .arrow {
            transform: translateX(6px);
        }

        /* Footer Accent */
        .footer-accent {
            padding: 1.2rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .top-navbar {
                padding: 1rem 1.5rem;
                flex-direction: column;
                gap: 15px;
            }
            .nav-actions {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn-nav-customer, .btn-nav-admin {
                padding: 0.5rem 1.1rem;
                font-size: 0.85rem;
            }
            .btn-explore {
                padding: 0.95rem 2.2rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <a href="index.php" class="brand-logo">
            <span>🏨</span> HOTEL PARADISE
        </a>
        <nav class="nav-actions">
            <!-- Theme Toggle Button -->
            <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                <span class="theme-icon-container">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                </span>
                <span class="theme-text">Mode</span>
            </button>
            <a href="customer_panel.php" class="btn-nav-customer">Customer</a>
            <a href="admin_panel.php" class="btn-nav-admin">Admin Login</a>
        </nav>
    </header>

    <!-- Hero Section with subtle overlay over hero image -->
    <main class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Welcome to Hotel Paradise</h1>
            <p class="hero-subtitle">Experience luxury and comfort like never before.</p>
            <a href="explore.php" class="btn-explore">
                <span>EXPLORE HOTEL</span>
                <span class="arrow">➔</span>
            </a>
        </div>
    </main>

    <!-- Footer Space / Accent -->
    <footer class="footer-accent">
        &copy; <?php echo date("Y"); ?> Hotel Paradise. All rights reserved.
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
