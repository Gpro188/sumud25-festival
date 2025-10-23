<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Database connection
require_once '../includes/config.php';

// Handle deletion of results
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_result'])) {
    $result_id = isset($_POST['result_id']) ? (int)$_POST['result_id'] : 0;
    
    if ($result_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM results WHERE id = ?");
            $stmt->execute([$result_id]);
            $success_message = "Result deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting result: " . $e->getMessage();
        }
    }
}

// Fetch categories from database
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch competition types from database
$stmt = $pdo->query("SELECT * FROM competition_types ORDER BY name");
$competition_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch teams from database
$stmt = $pdo->query("SELECT * FROM teams ORDER BY name");
$teams = $stmt->fetchAll();

// Fetch team members from database
$stmt = $pdo->query("SELECT tm.*, t.name as team_name FROM team_members tm JOIN teams t ON tm.team_id = t.id ORDER BY t.name, tm.category, tm.name");
$all_team_members = $stmt->fetchAll();

// Group team members by team
$team_members = [];
foreach ($all_team_members as $member) {
    $team_members[$member['team_id']][] = $member;
}

// Fetch team leaders from database
$stmt = $pdo->query("SELECT tl.*, t.name as team_name FROM team_leaders tl JOIN teams t ON tl.team_id = t.id ORDER BY t.name");
$team_leaders = $stmt->fetchAll();

// Create a mapping of team leaders by team ID
$team_leaders_map = [];
foreach ($team_leaders as $leader) {
    $team_leaders_map[$leader['team_id']] = $leader;
}

// Fetch programs from database
$stmt = $pdo->query("SELECT p.*, c.name as category_name, ct.name as type_name FROM programs p JOIN categories c ON p.category_id = c.id JOIN competition_types ct ON p.type_id = ct.id ORDER BY c.name, p.name");
$programs_data = $stmt->fetchAll();

// Format programs for easier use
$programs = [];
foreach ($programs_data as $prog) {
    $programs[] = [
        'id' => $prog['id'],
        'name' => $prog['name'],
        'category' => $prog['category_name'],
        'type' => $prog['type_name']
    ];
}

// Fetch results from database
$stmt = $pdo->query("SELECT r.*, p.name as program_name, t.name as team_name, t.color as team_color FROM results r JOIN programs p ON r.program_id = p.id JOIN teams t ON r.team_id = t.id ORDER BY r.awarded_at DESC");
$results = $stmt->fetchAll();

// Calculate team points
$team_points = [];
foreach ($teams as $team) {
    $team_points[$team['id']] = 0;
}

// Calculate points for each result and team totals
foreach ($results as $result) {
    $team_points[$result['team_id']] += $result['points'];
}

// Sort teams by points
arsort($team_points);

// Handle form submission for adding a new result
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_result'])) {
    $program_id = isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0;
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $winner_name = isset($_POST['winner_name']) ? trim($_POST['winner_name']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';
    $grade = isset($_POST['grade']) ? trim($_POST['grade']) : '';
    
    if ($program_id && $team_id && $winner_name && $position && $grade) {
        try {
            // Get program type for points calculation
            $stmt = $pdo->prepare("SELECT ct.name as type_name FROM programs p JOIN competition_types ct ON p.type_id = ct.id WHERE p.id = ?");
            $stmt->execute([$program_id]);
            $program_type = $stmt->fetch();
            
            if ($program_type) {
                $points = calculatePoints($position, $grade, $program_type['type_name']);
                
                $stmt = $pdo->prepare("INSERT INTO results (program_id, team_id, winner_name, position, grade, points) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$program_id, $team_id, $winner_name, $position, $grade, $points]);
                $success_message = "Result added successfully!";
                
                // Refresh results
                $stmt = $pdo->query("SELECT r.*, p.name as program_name, t.name as team_name, t.color as team_color FROM results r JOIN programs p ON r.program_id = p.id JOIN teams t ON r.team_id = t.id ORDER BY r.awarded_at DESC");
                $results = $stmt->fetchAll();
                
                // Recalculate team points
                $team_points = [];
                foreach ($teams as $team) {
                    $team_points[$team['id']] = 0;
                }
                foreach ($results as $result) {
                    $team_points[$result['team_id']] += $result['points'];
                }
                arsort($team_points);
            } else {
                $error_message = "Invalid program selected.";
            }
        } catch (PDOException $e) {
            $error_message = "Error adding result: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Get program details by ID
function getProgramById($programs, $id) {
    foreach ($programs as $program) {
        if ($program['id'] == $id) {
            return $program;
        }
    }
    return null;
}

// Get team details by ID
function getTeamById($teams, $id) {
    foreach ($teams as $team) {
        if ($team['id'] == $id) {
            return $team;
        }
    }
    return null;
}

// Function to count wins for a team
function countTeamWins($results, $team_id) {
    $wins = 0;
    foreach ($results as $result) {
        if ($result['team_id'] == $team_id && $result['position'] == '1st') {
            $wins++;
        }
    }
    return $wins;
}

// Calculate points based on position, grade, and competition type
function calculatePoints($position, $grade, $type) {
    $points = 0;
    
    // Position points
    switch ($position) {
        case '1st': 
            switch ($type) {
                case 'Individual': $points = 5; break;
                case 'Group': $points = 7; break;
                case 'General': $points = 10; break;
            }
            break;
        case '2nd':
            switch ($type) {
                case 'Individual': $points = 3; break;
                case 'Group': $points = 5; break;
                case 'General': $points = 7; break;
            }
            break;
        case '3rd':
            switch ($type) {
                case 'Individual': $points = 1; break;
                case 'Group': $points = 2; break;
                case 'General': $points = 5; break;
            }
            break;
    }
    
    // Grade points
    $grade_points = ($grade == 'A') ? 5 : (($grade == 'B') ? 3 : 0);
    
    return $points + $grade_points;
}

// Search team members by name or chest number
function searchTeamMembers($team_members, $search_term) {
    $results = [];
    foreach ($team_members as $team_id => $members) {
        foreach ($members as $member) {
            // Check if search term matches name or chest number
            if (stripos($member['name'], $search_term) !== false || 
                stripos($member['chest_number'], $search_term) !== false) {
                $member['team_id'] = $team_id;
                $results[] = $member;
            }
        }
    }
    return $results;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results - SUMUD'25 Arts Festival</title>
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
        .search-container {
            margin: 10px 0;
        }
        .search-results {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 5px;
            display: none;
        }
        .search-result-item {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .search-result-item:hover {
            background-color: #f5f5f5;
        }
        .search-result-name {
            font-weight: bold;
        }
        .search-result-chest {
            color: #666;
            font-size: 0.9em;
        }
        .search-result-team {
            color: #007bff;
            font-size: 0.9em;
        }
        .member-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        /* Team leadership section */
        .team-leadership {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        .leader-info {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 150px;
        }
        .leader-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 8px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 12px;
        }
        .leader-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .leader-details {
            font-size: 0.9em;
        }
        .leader-name {
            font-weight: bold;
            display: block;
        }
        .leader-role {
            color: #666;
            font-size: 0.8em;
        }
        /* Team standings section */
        .team-standings-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-top: 5px solid #FF3B3F;
        }
        .team-standings-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .team-standing-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .team-info {
            display: flex;
            align-items: center;
        }
        .team-name {
            font-weight: bold;
        }
        .team-points {
            font-weight: bold;
            color: #007BFF;
        }
        .standing-rank {
            font-weight: bold;
            font-size: 1.2em;
            display: inline-block;
            width: 30px;
            color: #FF3B3F;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Results Management</h2>
        </div>
    </header>

    <div class="admin-header">
        <div class="container">
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="teams.php">Teams</a></li>
                    <li><a href="results.php" class="active">Results</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <main>
        <div class="container">
            <div class="results-header">
                <h2>Update Competition Results</h2>
                <p>Add and manage competition results</p>
            </div>

            <?php if (isset($success_message)): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <!-- Team Standings Section -->
            <section class="team-standings-section">
                <div class="team-standings-header">
                    <h3>🏆 Overall Team Standings</h3>
                </div>
                <div class="team-standings-list">
                    <?php 
                    $rank = 1;
                    foreach ($team_points as $team_id => $points): 
                        $rank_icon = '';
                        switch ($rank) {
                            case 1: $rank_icon = '🥇'; break;
                            case 2: $rank_icon = '🥈'; break;
                            case 3: $rank_icon = '🥉'; break;
                        }
                        $team_wins = countTeamWins($results, $team_id);
                        foreach ($teams as $team) {
                            if ($team['id'] == $team_id) {
                                echo '<div class="team-standing-item">';
                                echo '<div class="team-info">';
                                echo '<span class="standing-rank">' . $rank_icon . '</span>';
                                echo '<span class="team-color-box" style="background-color: ' . htmlspecialchars($team['color']) . '"></span>';
                                echo '<span class="team-name">' . htmlspecialchars($team['name']) . '</span>';
                                echo '</div>';
                                echo '<div class="team-points">' . $points . ' pts (' . $team_wins . ' wins)</div>';
                                echo '</div>';
                                
                                // Display team leadership information
                                echo '<div class="team-leadership">';
                                
                                // Team Leader
                                echo '<div class="leader-info">';
                                echo '<div class="leader-photo">';
                                // Check if team leader has a photo (from team members)
                                $leader_photo = null;
                                if (isset($team_members[$team_id])) {
                                    foreach ($team_members[$team_id] as $member) {
                                        if ($member['name'] == $team['leader'] && !empty($member['photo_path'])) {
                                            $leader_photo = $member['photo_path'];
                                            break;
                                        }
                                    }
                                }
                                if ($leader_photo) {
                                    echo '<img src="../assets/member_photos/' . htmlspecialchars($leader_photo) . '" alt="' . htmlspecialchars($team['leader']) . '">';
                                } else {
                                    echo '👤';
                                }
                                echo '</div>';
                                echo '<div class="leader-details">';
                                echo '<span class="leader-name">' . htmlspecialchars($team['leader']) . '</span>';
                                echo '<span class="leader-role">Team Leader</span>';
                                echo '</div>';
                                echo '</div>';
                                
                                // Team Manager
                                echo '<div class="leader-info">';
                                echo '<div class="leader-photo">';
                                // Check if team manager has a photo (from team members)
                                $manager_photo = null;
                                if (isset($team_members[$team_id])) {
                                    foreach ($team_members[$team_id] as $member) {
                                        if ($member['name'] == $team['manager'] && !empty($member['photo_path'])) {
                                            $manager_photo = $member['photo_path'];
                                            break;
                                        }
                                    }
                                }
                                if ($manager_photo) {
                                    echo '<img src="../assets/member_photos/' . htmlspecialchars($manager_photo) . '" alt="' . htmlspecialchars($team['manager']) . '">';
                                } else {
                                    echo '👤';
                                }
                                echo '</div>';
                                echo '<div class="leader-details">';
                                echo '<span class="leader-name">' . htmlspecialchars($team['manager']) . '</span>';
                                echo '<span class="leader-role">Manager</span>';
                                echo '</div>';
                                echo '</div>';
                                
                                echo '</div>'; // Close team-leadership
                                break;
                            }
                        }
                        if ($rank >= 3) break;
                        $rank++;
                    endforeach; 
                    ?>
                </div>
            </section>

            <section class="admin-form">
                <h3>Add New Result</h3>
                <form method="POST">
                    <input type="hidden" name="add_result" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="category">Category</label>
                            <select id="category" name="category" required onchange="filterPrograms()">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-col">
                            <label for="program_id">Program</label>
                            <select id="program_id" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['id']; ?>" data-category="<?php echo htmlspecialchars($program['category']); ?>" data-type="<?php echo htmlspecialchars($program['type']); ?>">
                                    <?php echo htmlspecialchars($program['name']); ?> (<?php echo htmlspecialchars($program['type']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-col">
                            <label for="team_id">Team</label>
                            <select id="team_id" name="team_id" required>
                                <option value="">-- Select Team --</option>
                                <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="winner_name">Winner Name or Chest Number</label>
                            <div class="search-container">
                                <input type="text" id="winner_name" name="winner_name" required placeholder="Enter name or chest number">
                                <div class="search-results" id="searchResults"></div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <label for="position">Position</label>
                            <select id="position" name="position" required>
                                <option value="">-- Select Position --</option>
                                <option value="1st">1st</option>
                                <option value="2nd">2nd</option>
                                <option value="3rd">3rd</option>
                            </select>
                        </div>
                        
                        <div class="form-col">
                            <label for="grade">Grade</label>
                            <select id="grade" name="grade" required>
                                <option value="">-- Select Grade --</option>
                                <option value="A">A Grade</option>
                                <option value="B">B Grade</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Result</button>
                    </div>
                </form>
            </section>

            <section>
                <h3>Existing Results</h3>
                <?php if (count($results) > 0): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Winner</th>
                            <th>Team</th>
                            <th>Position</th>
                            <th>Grade</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($result['program_name']); ?></td>
                            <td><?php 
                                // Get program category
                                $program = getProgramById($programs, $result['program_id']);
                                echo $program ? htmlspecialchars($program['category']) : 'N/A';
                            ?></td>
                            <td><?php 
                                echo $program ? htmlspecialchars($program['type']) : 'N/A';
                            ?></td>
                            <td>
                                <?php 
                                // Try to find member photo
                                $member_photo = null;
                                if (isset($team_members[$result['team_id']])) {
                                    foreach ($team_members[$result['team_id']] as $member) {
                                        if ($member['name'] == $result['winner_name'] && !empty($member['photo_path'])) {
                                            $member_photo = $member['photo_path'];
                                            break;
                                        }
                                    }
                                }
                                
                                if ($member_photo): ?>
                                    <img src="../assets/member_photos/<?php echo htmlspecialchars($member_photo); ?>" alt="<?php echo htmlspecialchars($result['winner_name']); ?>" class="member-photo">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($result['winner_name']); ?>
                            </td>
                            <td>
                                <?php 
                                echo '<span class="team-color-box" style="background-color: ' . htmlspecialchars($result['team_color']) . '"></span>';
                                echo htmlspecialchars($result['team_name']);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['position']); ?></td>
                            <td><?php echo htmlspecialchars($result['grade']); ?></td>
                            <td class="highlight"><?php echo $result['points']; ?></td>
                            <td>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $result['id']; ?>, 'result')">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No results have been added yet.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this result? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_result" value="1">
                <input type="hidden" name="result_id" id="deleteId">
                <button type="submit" class="btn-danger">Yes, Delete</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 SUMUD'25 Arts Festival. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Modal functions
        function confirmDelete(id, type) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking on X
        document.querySelector('.close').onclick = function() {
            closeModal();
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        function filterPrograms() {
            const categorySelect = document.getElementById('category');
            const programSelect = document.getElementById('program_id');
            const selectedCategory = categorySelect.value;
            
            // Store the current selected program
            const currentProgram = programSelect.value;
            
            // Clear the program options
            programSelect.innerHTML = '<option value="">-- Select Program --</option>';
            
            // Add back the programs that match the selected category
            <?php foreach ($programs as $program): ?>
            if (!selectedCategory || '<?php echo addslashes($program['category']); ?>' === selectedCategory) {
                const option = document.createElement('option');
                option.value = '<?php echo $program['id']; ?>';
                option.textContent = '<?php echo addslashes($program['name']); ?> (<?php echo addslashes($program['type']); ?>)';
                option.dataset.category = '<?php echo addslashes($program['category']); ?>';
                option.dataset.type = '<?php echo addslashes($program['type']); ?>';
                programSelect.appendChild(option);
            }
            <?php endforeach; ?>
            
            // Restore the selected program if it still matches
            programSelect.value = currentProgram;
        }
        
        // Search functionality for winner name or chest number
        document.getElementById('winner_name').addEventListener('input', function() {
            const searchTerm = this.value.trim();
            const searchResults = document.getElementById('searchResults');
            
            if (searchTerm.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            // In a real application, this would be an AJAX call to the server
            // For now, we'll simulate the search with our sample data
            const teamMembers = <?php echo json_encode($team_members); ?>;
            let results = [];
            
            // Search through all team members
            for (const teamId in teamMembers) {
                const members = teamMembers[teamId];
                for (const member of members) {
                    if (member.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                        member.chest_number.toLowerCase().includes(searchTerm.toLowerCase())) {
                        results.push({
                            name: member.name,
                            chest_number: member.chest_number,
                            team_id: parseInt(teamId),
                            team_name: member.team_name
                        });
                    }
                }
            }
            
            // Display results
            if (results.length > 0) {
                searchResults.innerHTML = '';
                results.forEach(result => {
                    const div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.innerHTML = `
                        <div class="search-result-name">${result.name}</div>
                        <div class="search-result-chest">Chest: ${result.chest_number}</div>
                        <div class="search-result-team">${result.team_name}</div>
                    `;
                    div.addEventListener('click', function() {
                        document.getElementById('winner_name').value = result.name;
                        document.getElementById('team_id').value = result.team_id;
                        searchResults.style.display = 'none';
                    });
                    searchResults.appendChild(div);
                });
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = '<div class="search-result-item">No matches found</div>';
                searchResults.style.display = 'block';
            }
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchContainer = document.querySelector('.search-container');
            if (!searchContainer.contains(event.target)) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });
    </script>
</body>
</html>