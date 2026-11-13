<!-- EXPLORE PAGE (explore.php) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Hotel Paradise - Luxury Amenities</title>
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
            background: var(--explore-bg);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
            padding: 1.5rem 2rem 2.5rem;
        }

        /* Top Navigation Bar */
        .top-bar {
            width: 100%;
            max-width: 1300px;
            margin: 0 auto 1.5rem;
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
            padding: 0.6rem 1.3rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--gold);
            background: rgba(255, 255, 255, 0.16);
            transform: translateX(-4px);
            border-color: var(--gold);
        }

        /* Header Section */
        .page-header {
            text-align: center;
            max-width: 800px;
            margin: 0.5rem auto 2.5rem;
        }

        .page-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(2rem, 4.5vw, 3.2rem);
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 0.6rem;
            background: linear-gradient(180deg, var(--text-primary) 40%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .page-subtitle {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--text-muted);
            font-weight: 300;
            letter-spacing: 0.3px;
        }

        /* Showcase Carousel Wrapper */
        .carousel-outer {
            max-width: 1300px;
            margin: 0 auto;
            position: relative;
            padding: 0 4rem;
        }

        .explore-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-card);
        }

        .explore-card:hover {
            transform: translateY(-8px);
            border-color: rgba(212, 175, 55, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25), 0 0 20px rgba(212, 175, 55, 0.2);
        }

        .card-img-wrapper {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .explore-card:hover .card-img-wrapper img {
            transform: scale(1.08);
        }

        .card-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(11, 15, 25, 0.75);
            color: var(--gold);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            backdrop-filter: blur(4px);
        }

        .card-body-custom {
            padding: 1.5rem 1.6rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
        }

        .card-title-custom {
            font-family: 'Cinzel', serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .card-desc-custom {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-desc-custom i {
            color: var(--gold);
            font-size: 1rem;
        }

        /* Carousel Navigation Controls [<] [>] */
        .carousel-control-prev,
        .carousel-control-next {
            width: 52px;
            height: 52px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            top: 45%;
            transform: translateY(-50%);
            opacity: 1;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }

        .carousel-control-prev {
            left: 0;
        }

        .carousel-control-next {
            right: 0;
        }

        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: var(--gold);
            color: #0b0f19;
            border-color: var(--gold);
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
        }

        .carousel-control-prev:hover .nav-icon,
        .carousel-control-next:hover .nav-icon {
            color: #0b0f19;
        }

        .nav-icon {
            font-size: 1.4rem;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }

        /* Carousel Dot Indicators ( • ) ( ) ( ) */
        .carousel-indicators-custom {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin: 2.5rem 0 1.8rem;
        }

        .dot-indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: transparent;
            border: 2px solid var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
        }

        .dot-indicator:hover {
            border-color: var(--gold);
        }

        .dot-indicator.active {
            background: var(--gold);
            border-color: var(--gold);
            transform: scale(1.25);
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.6);
        }

        /* Bottom Action Button */
        .bottom-action-wrapper {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .btn-amenities {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: #0b0f19;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-decoration: none;
            padding: 0.95rem 2.4rem;
            border-radius: 50px;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
            transition: all 0.35s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
        }

        .btn-amenities .arrow {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-amenities:hover {
            background: linear-gradient(135deg, #f1c40f 0%, #d4af37 100%);
            color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(212, 175, 55, 0.45);
        }

        .btn-amenities:hover .arrow {
            transform: translateX(5px);
        }

        /* Amenities Modal */
        .modal-content-luxury {
            background: var(--modal-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            color: var(--modal-color);
            box-shadow: var(--shadow-card);
        }

        .modal-header-luxury {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
        }

        .modal-title-luxury {
            font-family: 'Cinzel', serif;
            font-size: 1.6rem;
            color: var(--gold);
        }

        .amenity-item {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }

        .amenity-item:hover {
            border-color: rgba(212, 175, 55, 0.4);
            background: rgba(212, 175, 55, 0.08);
            transform: translateY(-3px);
        }

        .amenity-icon {
            font-size: 1.8rem;
            color: var(--gold);
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .carousel-outer {
                padding: 0 3.2rem;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1.2rem 1rem;
            }
            .carousel-outer {
                padding: 0 0.5rem;
            }
            .carousel-control-prev,
            .carousel-control-next {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation / Back to Welcome & Theme Toggle -->
    <header class="top-bar">
        <a href="index.php" class="back-link">
            <span>←</span> Back to Welcome
        </a>
        <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
            <span class="theme-icon-container">
                <i class="bi bi-sun-fill icon-sun"></i>
                <i class="bi bi-moon-stars-fill icon-moon"></i>
            </span>
            <span class="theme-text">Mode</span>
        </button>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Explore Hotel Paradise</h1>
            <p class="page-subtitle">Discover the luxury and comfort of our hotel.</p>
        </div>

        <!-- 3-Card Carousel Container -->
        <div class="carousel-outer">
            <div id="exploreCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                <div class="carousel-inner">

                    <!-- Slide 1 (Lobby, Suites, Pool) -->
                    <div class="carousel-item active">
                        <div class="row g-4 justify-content-center">
                            <!-- Card 1: Lobby -->
                            <div class="col-12 col-md-4">
                                <div class="explore-card">
                                    <div class="card-img-wrapper">
                                        <img src="IMAGE/lobby.jpg" alt="Grand Lobby">
                                        <span class="card-badge">Featured</span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div>
                                            <h3 class="card-title-custom">Grand Lobby</h3>
                                            <p class="card-desc-custom">
                                                <i class="bi bi-clock-history"></i> 24/7 Reception
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Deluxe Suites -->
                            <div class="col-12 col-md-4">
                                <div class="explore-card">
                                    <div class="card-img-wrapper">
                                        <img src="IMAGE/room1.jpg" alt="Deluxe Suites">
                                        <span class="card-badge">Luxury</span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div>
                                            <h3 class="card-title-custom">Deluxe Suites</h3>
                                            <p class="card-desc-custom">
                                                <i class="bi bi-water"></i> Ocean View
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Oasis Pool -->
                            <div class="col-12 col-md-4">
                                <div class="explore-card">
                                    <div class="card-img-wrapper">
                                        <img src="IMAGE/pool.jpg" alt="Oasis Pool">
                                        <span class="card-badge">Premium</span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div>
                                            <h3 class="card-title-custom">Oasis Pool</h3>
                                            <p class="card-desc-custom">
                                                <i class="bi bi-thermometer-half"></i> Temp Controlled
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 (Restaurant, Spa, Garden) -->
                    <div class="carousel-item">
                        <div class="row g-4 justify-content-center">
                            <!-- Card 4: Restaurant -->
                            <div class="col-12 col-md-4">
                                <div class="explore-card">
                                    <div class="card-img-wrapper">
                                        <img src="IMAGE/restaurant.jpg" alt="Gourmet Dining">
                                        <span class="card-badge">Michelin Star</span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div>
                                            <h3 class="card-title-custom">Gourmet Dining</h3>
                                            <p class="card-desc-custom">
                                                <i class="bi bi-cup-hot"></i> Fine Cuisine & Bar
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 5: Spa & Wellness -->
                            <div class="col-12 col-md-4">
                                <div class="explore-card">
                                    <div class="card-img-wrapper">
                                        <img src="IMAGE/spa.jpg" alt="Wellness Spa">
                                        <span class="card-badge">Holistic</span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div>
                                            <h3 class="card-title-custom">Wellness Spa</h3>
                                            <p class="card-desc-custom">
                                                <i class="bi bi-flower1"></i> Rejuvenation & Sauna
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 6: Zen Garden -->
                            <div class="col-12 col-md-4">
                                <div class="explore-card">
                                    <div class="card-img-wrapper">
                                        <img src="IMAGE/garden.jpg" alt="Zen Garden">
                                        <span class="card-badge">Tranquil</span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div>
                                            <h3 class="card-title-custom">Zen Garden</h3>
                                            <p class="card-desc-custom">
                                                <i class="bi bi-tree"></i> Peaceful Nature Walk
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Carousel Controls [<] and [>] -->
                <button class="carousel-control-prev" type="button" data-bs-target="#exploreCarousel" data-bs-slide="prev" aria-label="Previous">
                    <span class="nav-icon">&#10094;</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#exploreCarousel" data-bs-slide="next" aria-label="Next">
                    <span class="nav-icon">&#10095;</span>
                </button>
            </div>

            <!-- Custom Dots Pagination ( • ) ( ) ( ) -->
            <div class="carousel-indicators-custom">
                <button type="button" class="dot-indicator active" onclick="goToSlide(0)" aria-label="Slide 1"></button>
                <button type="button" class="dot-indicator" onclick="goToSlide(1)" aria-label="Slide 2"></button>
            </div>

            <!-- Primary Bottom Action Button -->
            <div class="bottom-action-wrapper">
                <button type="button" class="btn-amenities" data-bs-toggle="modal" data-bs-target="#amenitiesModal">
                    <span>View All Amenities</span>
                    <span class="arrow">➔</span>
                </button>
            </div>
        </div>
    </main>

    <!-- Amenities Luxury Modal -->
    <div class="modal fade" id="amenitiesModal" tabindex="-1" aria-labelledby="amenitiesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-content-luxury">
                <div class="modal-header modal-header-luxury">
                    <h5 class="modal-title modal-title-luxury" id="amenitiesModalLabel">✨ Hotel Paradise Amenities</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="amenity-item">
                                <i class="bi bi-wifi amenity-icon"></i>
                                <div>
                                    <h6 class="mb-1">Ultra High-Speed Wi-Fi</h6>
                                    <small class="text-secondary">Complimentary in all suites and public areas.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="amenity-item">
                                <i class="bi bi-water amenity-icon"></i>
                                <div>
                                    <h6 class="mb-1">Infinity & Heated Pools</h6>
                                    <small class="text-secondary">Temperature controlled with ocean panoramas.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="amenity-item">
                                <i class="bi bi-clock-history amenity-icon"></i>
                                <div>
                                    <h6 class="mb-1">24/7 Concierge Service</h6>
                                    <small class="text-secondary">Personalized dining, transport & excursion bookings.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="amenity-item">
                                <i class="bi bi-flower1 amenity-icon"></i>
                                <div>
                                    <h6 class="mb-1">Full-Service Spa & Sauna</h6>
                                    <small class="text-secondary">Holistic massage therapies and herbal baths.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="amenity-item">
                                <i class="bi bi-cup-hot amenity-icon"></i>
                                <div>
                                    <h6 class="mb-1">Fine Dining Restaurants</h6>
                                    <small class="text-secondary">Curated international menus and artisan mixology.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="amenity-item">
                                <i class="bi bi-car-front amenity-icon"></i>
                                <div>
                                    <h6 class="mb-1">Valet Parking & Chauffeur</h6>
                                    <small class="text-secondary">Private airport shuttle and secure parking.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 justify-content-center">
                    <a href="customer_panel.php" class="btn-amenities">
                        <span>Book Your Stay Now</span>
                        <span class="arrow">➔</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const carouselElement = document.getElementById('exploreCarousel');
        const dots = document.querySelectorAll('.dot-indicator');
        const carouselInstance = bootstrap.Carousel.getOrCreateInstance(carouselElement);

        function goToSlide(index) {
            carouselInstance.to(index);
        }

        carouselElement.addEventListener('slide.bs.carousel', function (e) {
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === e.to);
            });
        });
    </script>
</body>
</html>
