<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Database configuration
require_once '../includes/config.php';

// Sample data - in a real application, this would come from a database
$teams = [
    ['id' => 1, 'name' => 'Team Alpha', 'leader' => 'Ahmed Ali', 'manager' => 'Fatima Khan', 'color' => '#3498db'],
    ['id' => 2, 'name' => 'Team Beta', 'leader' => 'Mohammed Saleh', 'manager' => 'Aisha Patel', 'color' => '#e74c3c'],
    ['id' => 3, 'name' => 'Team Gamma', 'leader' => 'Youssef Nasser', 'manager' => 'Layla Abbas', 'color' => '#2ecc71']
];

$programs = [
    ['id' => 1, 'name' => 'Quran Recitation', 'category' => 'BIDAYA', 'type' => 'Individual'],
    ['id' => 2, 'name' => 'Poetry Reading', 'category' => 'BIDAYA', 'type' => 'Individual'],
    ['id' => 3, 'name' => 'Group Dance', 'category' => 'THANIYA', 'type' => 'Group'],
    ['id' => 4, 'name' => 'Debate Competition', 'category' => 'THANIYA', 'type' => 'General'],
    ['id' => 5, 'name' => 'Solo Singing', 'category' => 'BIDAYA', 'type' => 'Individual'],
    ['id' => 6, 'name' => 'Drama Performance', 'category' => 'THANIYA', 'type' => 'Group']
];

// Sample results data
$results = [
    [
        'id' => 1,
        'program_id' => 1,
        'team_id' => 1,
        'winner_name' => 'Ali Hassan',
        'position' => '1st',
        'grade' => 'A'
    ],
    [
        'id' => 2,
        'program_id' => 2,
        'team_id' => 2,
        'winner_name' => 'Sarah Mohammed',
        'position' => '2nd',
        'grade' => 'B'
    ]
];

// Calculate statistics
$total_teams = count($teams);
$total_programs = count($programs);
$total_results = count($results);

// Fetch gallery photos count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM gallery WHERE is_active = 1");
    $stmt->execute();
    $gallery_count = $stmt->fetch()['count'];
} catch (Exception $e) {
    $gallery_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SUMUD'25 Arts Festival</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .team-color-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Admin Dashboard</h2>
        </div>
    </header>

    <div class="admin-header">
        <div class="container">
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="teams.php">Teams</a></li>
                    <li><a href="results.php">Results</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <main>
        <div class="container">
            <div class="results-header">
                <h2>Dashboard Overview</h2>
                <p>Manage teams, programs, and competition results</p>
            </div>

            <section class="dashboard-stats">
                <div class="stat-card">
                    <h3>Teams</h3>
                    <div class="number"><?php echo $total_teams; ?></div>
                    <p>Registered teams</p>
                </div>
                
                <div class="stat-card">
                    <h3>Programs</h3>
                    <div class="number"><?php echo $total_programs; ?></div>
                    <p>Competition programs</p>
                </div>
                
                <div class="stat-card">
                    <h3>Results</h3>
                    <div class="number"><?php echo $total_results; ?></div>
                    <p>Published results</p>
                </div>
                
                <div class="stat-card">
                    <h3>Gallery Photos</h3>
                    <div class="number"><?php echo $gallery_count; ?></div>
                    <p>Active photos</p>
                </div>
            </section>

            <section class="admin-form">
                <h3>Quick Actions</h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="teams.php" class="btn">Manage Teams</a>
                    <a href="results.php" class="btn">Update Results</a>
                    <a href="programs.php" class="btn">Manage Programs</a>
                    <a href="gallery.php" class="btn">Manage Gallery</a>
                </div>
            </section>

            <section class="admin-form">
                <h3>Recent Results</h3>
                <?php if (count($results) > 0): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Category</th>
                            <th>Winner</th>
                            <th>Team</th>
                            <th>Position</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($results, 0, 5) as $result): ?>
                        <tr>
                            <td>
                                <?php 
                                foreach ($programs as $program) {
                                    if ($program['id'] == $result['program_id']) {
                                        echo htmlspecialchars($program['name']);
                                        break;
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                foreach ($programs as $program) {
                                    if ($program['id'] == $result['program_id']) {
                                        echo htmlspecialchars($program['category']);
                                        break;
                                    }
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['winner_name']); ?></td>
                            <td>
                                <?php 
                                foreach ($teams as $team) {
                                    if ($team['id'] == $result['team_id']) {
                                        echo '<span class="team-color-box" style="background-color: ' . htmlspecialchars($team['color']) . '"></span>';
                                        echo htmlspecialchars($team['name']);
                                        break;
                                    }
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['position']); ?></td>
                            <td><?php echo htmlspecialchars($result['grade']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No results have been published yet.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 SUMUD'25 Arts Festival. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>