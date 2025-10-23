<?php
// Database configuration
require_once 'includes/config.php';

// Fetch active gallery photos
try {
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE is_active = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $gallery_photos = $stmt->fetchAll();
} catch (Exception $e) {
    $gallery_photos = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUMUD'25 Arts Festival - Results</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .festival-logo {
            max-height: 80px;
            width: auto;
        }
        
        .header-content {
            text-align: center;
        }
        
        .header-content h1 {
            margin: 10px 0 5px 0;
        }
        
        .header-content h2 {
            margin: 5px 0;
            font-size: 1.3rem;
        }
        
        /* Gallery Slideshow Styles */
        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .slideshow-slide {
            display: none;
            width: 100%;
        }
        
        .slideshow-slide.active {
            display: block;
        }
        
        .slideshow-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .slideshow-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .slideshow-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
        }
        
        .slideshow-nav button {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
        }
        
        .slideshow-nav button:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .slideshow-dots {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .slideshow-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #bbb;
            margin: 0 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .slideshow-dot.active {
            background: #007BFF;
        }
        
        /* Login Buttons Styles */
        .login-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .login-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .login-btn {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
        }
        
        .admin-login-btn {
            background: #222222; /* Charcoal Black */
            color: white;
        }
        
        .admin-login-btn:hover {
            background: #007BFF; /* Ocean Blue */
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .team-leader-login-btn {
            background: #007BFF; /* Ocean Blue */
            color: white;
        }
        
        .team-leader-login-btn:hover {
            background: #222222; /* Charcoal Black */
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .logo-container {
                flex-direction: column;
                text-align: center;
            }
            
            .festival-logo {
                max-height: 60px;
            }
            
            .slideshow-image {
                height: 250px;
            }
            
            .login-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .login-btn {
                width: 80%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo-container">
                <img src="assets/logo.png" alt="SUMUD'25 Arts Festival Logo" class="festival-logo">
                <div class="header-content">
                    <h1>SUMUD'25 Arts Festival</h1>
                    <h2>Results Publishing System</h2>
                </div>
            </div>
        </div>
    </header>

    <nav>
        <div class="container">
            <ul>
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="results.php">View Results</a></li>
                <!-- Admin link is intentionally not visible on public pages -->
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <section class="hero">
                <h2>Welcome to SUMUD'25 Arts Festival Results</h2>
                <p>View competition results, team standings, and individual achievements.</p>
                <a href="results.php" class="btn">View All Results</a>
            </section>

            <!-- Login Section -->
            <section class="login-section">
                <h3>Administrator & Team Leader Access</h3>
                <p>Login to manage teams, update results, and access administrative features</p>
                <div class="login-buttons">
                    <a href="admin/login.php" class="login-btn admin-login-btn">Administrator Login</a>
                    <a href="admin/team_leader_login.php" class="login-btn team-leader-login-btn">Team Leader Login</a>
                </div>
            </section>

            <!-- Gallery Slideshow -->
            <?php if (count($gallery_photos) > 0): ?>
            <section class="slideshow-container">
                <?php foreach ($gallery_photos as $index => $photo): ?>
                <div class="slideshow-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($photo['image_path']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" class="slideshow-image">
                    <div class="slideshow-caption">
                        <h3><?php echo htmlspecialchars($photo['title']); ?></h3>
                        <p><?php echo htmlspecialchars($photo['description']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="slideshow-nav">
                    <button onclick="changeSlide(-1)">❮</button>
                    <button onclick="changeSlide(1)">❯</button>
                </div>
                
                <div class="slideshow-dots">
                    <?php for ($i = 0; $i < count($gallery_photos); $i++): ?>
                    <span class="slideshow-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $i + 1; ?>)"></span>
                    <?php endfor; ?>
                </div>
            </section>
            <?php else: ?>
            <!-- Default slideshow if no photos uploaded -->
            <section class="slideshow-container">
                <div class="slideshow-slide active">
                    <img src="assets/gallery/default1.jpg" alt="Festival Moment 1" class="slideshow-image">
                    <div class="slideshow-caption">
                        <h3>Welcome to SUMUD'25</h3>
                        <p>Experience the vibrant arts festival</p>
                    </div>
                </div>
                <div class="slideshow-slide">
                    <img src="assets/gallery/default2.jpg" alt="Festival Moment 2" class="slideshow-image">
                    <div class="slideshow-caption">
                        <h3>Creative Talents</h3>
                        <p>Showcasing outstanding performances</p>
                    </div>
                </div>
                <div class="slideshow-slide">
                    <img src="assets/gallery/default3.jpg" alt="Festival Moment 3" class="slideshow-image">
                    <div class="slideshow-caption">
                        <h3>Celebrating Excellence</h3>
                        <p>Recognizing achievements and talents</p>
                    </div>
                </div>
                
                <div class="slideshow-nav">
                    <button onclick="changeSlide(-1)">❮</button>
                    <button onclick="changeSlide(1)">❯</button>
                </div>
                
                <div class="slideshow-dots">
                    <span class="slideshow-dot active" onclick="currentSlide(1)"></span>
                    <span class="slideshow-dot" onclick="currentSlide(2)"></span>
                    <span class="slideshow-dot" onclick="currentSlide(3)"></span>
                </div>
            </section>
            <?php endif; ?>

            <section class="features">
                <div class="feature">
                    <h3>BIDAYA Category</h3>
                    <p>Competitions for younger participants</p>
                </div>
                <div class="feature">
                    <h3>THANIYA Category</h3>
                    <p>Competitions for older participants</p>
                </div>
                <div class="feature">
                    <h3>Team Standings</h3>
                    <p>See overall team performance</p>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 SUMUD'25 Arts Festival. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let slideIndex = 0;
        const slides = document.querySelectorAll('.slideshow-slide');
        const dots = document.querySelectorAll('.slideshow-dot');
        
        // Auto slide change every 5 seconds
        setInterval(() => {
            changeSlide(1);
        }, 5000);
        
        function changeSlide(n) {
            slideIndex += n;
            
            if (slideIndex >= slides.length) {
                slideIndex = 0;
            }
            
            if (slideIndex < 0) {
                slideIndex = slides.length - 1;
            }
            
            // Hide all slides
            for (let i = 0; i < slides.length; i++) {
                slides[i].classList.remove('active');
            }
            
            // Remove active class from all dots
            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove('active');
            }
            
            // Show current slide and activate current dot
            slides[slideIndex].classList.add('active');
            if (dots[slideIndex]) {
                dots[slideIndex].classList.add('active');
            }
        }
        
        function currentSlide(n) {
            slideIndex = n - 1;
            changeSlide(0);
        }
    </script>
</body>
</html>