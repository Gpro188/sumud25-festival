<?php
session_start();
// Check if team leader is already logged in
if (isset($_SESSION['team_leader_logged_in']) && $_SESSION['team_leader_logged_in'] === true) {
    header('Location: team_leader_dashboard.php');
    exit;
}

// Database connection
require_once '../includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check credentials against team_leaders table
        $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name, team_id, is_active FROM team_leaders WHERE username = ?");
        $stmt->execute([$username]);
        $team_leader = $stmt->fetch();
        
        if ($team_leader) {
            // Check if team leader is active
            if (!$team_leader['is_active']) {
                $error = 'Your account has been deactivated. Please contact the administrator.';
            } else if (password_verify($password, $team_leader['password_hash'])) {
                // Login successful
                $_SESSION['team_leader_logged_in'] = true;
                $_SESSION['team_leader_id'] = $team_leader['id'];
                $_SESSION['team_leader_username'] = $team_leader['username'];
                $_SESSION['team_leader_full_name'] = $team_leader['full_name'];
                $_SESSION['team_leader_team_id'] = $team_leader['team_id'];
                
                header('Location: team_leader_dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Leader Login - SUMUD'25</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-form button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #d9534f;
            margin-bottom: 15px;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
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
            <h2>Team Leader Login</h2>
        </div>
    </header>

    <nav>
        <div class="container">
            <ul>
                <li><a href="../index.html">Home</a></li>
                <li><a href="../results.php">View Results</a></li>
                <li><a href="team_leader_login.php" class="active">Team Leader Panel</a></li>
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="login-container">
                <h2>Team Leader Login</h2>
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form class="login-form" method="POST">
                    <div>
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>
                <div class="login-links">
                    <p>Not a team leader? <a href="login.php">Admin Login</a></p>
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