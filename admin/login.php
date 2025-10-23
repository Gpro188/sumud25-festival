<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // In a real application, you would validate against a database
    // For this demo, we'll use hardcoded credentials
    if ($username === 'admin' && $password === 'sumud25') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - SUMUD'25 Arts Festival</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .login-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .login-links a {
            color: #007bff;
            text-decoration: none;
            margin: 0 10px;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Admin Panel Login</h2>
        </div>
    </header>

    <nav>
        <div class="container">
            <ul>
                <li><a href="../index.html">Home</a></li>
                <li><a href="../results.php">View Results</a></li>
                <li><a href="login.php" class="active">Admin Panel</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="login-container">
                <h2>Admin Login</h2>
                
                <?php if ($error): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit">Login</button>
                    </div>
                </form>
                
                <div style="margin-top: 1rem; text-align: center;">
                    <p>Demo credentials: admin / sumud25</p>
                </div>
                
                <div class="login-links">
                    <p>Not an admin? <a href="team_leader_login.php">Team Leader Login</a></p>
                    <p><a href="../index.php">← Back to Main Site</a></p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 SUMUD'25 Arts Festival. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>