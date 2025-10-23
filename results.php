<?php
// Database connection
require_once 'includes/config.php';

// Fetch categories from database
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

// Fetch results from database with program details
$stmt = $pdo->query("SELECT r.*, p.name as program_name, p.category_id, c.name as category_name, ct.name as type_name, t.name as team_name, t.color as team_color FROM results r JOIN programs p ON r.program_id = p.id JOIN categories c ON p.category_id = c.id JOIN competition_types ct ON p.type_id = ct.id JOIN teams t ON r.team_id = t.id ORDER BY r.awarded_at DESC");
$results_data = $stmt->fetchAll();

// Format results for easier use
$results = [];
foreach ($results_data as $res) {
    $results[] = [
        'id' => $res['id'],
        'program_name' => $res['program_name'],
        'category' => $res['category_name'],
        'type' => $res['type_name'],
        'winner' => $res['winner_name'],
        'team_id' => $res['team_id'],
        'position' => $res['position'],
        'grade' => $res['grade'],
        'points' => $res['points']
    ];
}

// Calculate team points
$team_points = [];
foreach ($teams as $team) {
    $team_points[$team['id']] = 0;
}

// Function to calculate points based on position, grade, and competition type
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

// Calculate points for each result and team totals
foreach ($results as &$result) {
    $team_points[$result['team_id']] += $result['points'];
}

// Sort teams by points
arsort($team_points);

// Filter results based on search parameters
$filtered_results = $results;
$search_category = isset($_GET['category']) ? $_GET['category'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

if ($search_category) {
    $filtered_results = array_filter($filtered_results, function($result) use ($search_category) {
        return $result['category'] == $search_category;
    });
}

if ($search_term) {
    $filtered_results = array_filter($filtered_results, function($result) use ($search_term, $team_members) {
        // Check if search term matches program name, winner name, or chest number
        $matches_program = stripos($result['program_name'], $search_term) !== false;
        $matches_winner = stripos($result['winner'], $search_term) !== false;
        
        // Check if search term matches any chest number for the winner
        $matches_chest = false;
        foreach ($team_members as $team_id => $members) {
            foreach ($members as $member) {
                if ($member['name'] == $result['winner'] && stripos($member['chest_number'], $search_term) !== false) {
                    $matches_chest = true;
                    break 2;
                }
            }
        }
        
        return $matches_program || $matches_winner || $matches_chest;
    });
}

// Function to get position icon
function getPositionIcon($position) {
    switch ($position) {
        case '1st':
            return '🏆'; // Trophy
        case '2nd':
            return '🥈'; // Silver medal
        case '3rd':
            return '🥉'; // Bronze medal
        default:
            return '';
    }
}

// Function to get grade icon
function getGradeIcon($grade) {
    switch ($grade) {
        case 'A':
            return '🥇'; // Gold medal
        case 'B':
            return '🥈'; // Silver medal
        default:
            return '';
    }
}

// Function to get team by ID
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

// Function to calculate category points for a team
function calculateCategoryPoints($results, $team_id, $category) {
    $points = 0;
    foreach ($results as $result) {
        if ($result['team_id'] == $team_id && $result['category'] == $category) {
            $points += $result['points'];
        }
    }
    return $points;
}

// Function to find a team member by name
function findTeamMemberByName($team_members, $team_id, $name) {
    if (isset($team_members[$team_id])) {
        foreach ($team_members[$team_id] as $member) {
            if ($member['name'] == $name) {
                return $member;
            }
        }
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - SUMUD'25 Arts Festival</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .team-color-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }
        .team-standings .team-card ol li {
            padding: 5px 0;
            font-size: 1.1em;
        }
        .position-icon {
            font-size: 1.2em;
            margin-right: 5px;
        }
        .grade-icon {
            font-size: 1.2em;
            margin-left: 5px;
        }
        .results-table td, .results-table th {
            text-align: center;
        }
        .results-table td:first-child, .results-table th:first-child,
        .results-table td:nth-child(4), .results-table th:nth-child(4) {
            text-align: left;
        }
        .standing-rank {
            font-weight: bold;
            font-size: 1.2em;
            display: inline-block;
            width: 30px;
        }
        .overall-standing {
            background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%); /* Ocean Blue gradient */
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 2px solid #FF3B3F; /* Watermelon Red border */
        }
        .overall-standing h3 {
            margin-top: 0;
            font-size: 1.5em;
        }
        .champion-team {
            font-size: 1.8em;
            font-weight: bold;
            margin: 10px 0;
        }
        .champion-team .team-color-box {
            width: 20px;
            height: 20px;
        }
        .category-winners {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .category-winner {
            flex: 1;
            min-width: 250px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .category-winner h4 {
            margin-top: 0;
            color: #007BFF;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 5px;
        }
        /* Card-based results layout */
        .results-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .result-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease;
            border-top: 5px solid #007BFF; /* Ocean Blue accent */
        }
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .result-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .result-program {
            font-weight: bold;
            font-size: 1.2em;
            color: #007BFF; /* Ocean Blue */
        }
        .result-category {
            background: #007BFF; /* Ocean Blue */
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .result-details {
            margin-bottom: 15px;
        }
        .result-detail {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        .result-detail-label {
            font-weight: bold;
            color: #666;
        }
        .result-winner {
            font-size: 1.1em;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #FFD700; /* Sun Yellow highlight */
        }
        .result-team {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .result-points {
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            color: #007BFF; /* Ocean Blue */
            margin-top: 10px;
            background: #FFD700; /* Sun Yellow background */
            padding: 10px;
            border-radius: 10px;
            display: inline-block;
            width: 100%;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        /* Champion section at top */
        .champion-section {
            margin-bottom: 30px;
        }
        /* Standings section under champion */
        .standings-section {
            margin-bottom: 30px;
        }
        /* Filter section styling */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-top: 5px solid #FF3B3F; /* Watermelon Red accent */
        }
        .filter-section h3 {
            color: #007BFF; /* Ocean Blue */
            margin-bottom: 1rem;
        }
        .filter-form select,
        .filter-form input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
            min-width: 200px;
        }
        .filter-form button {
            background: #007BFF; /* Ocean Blue */
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        /* Enhanced standings styling */
        .enhanced-standings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .standing-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }
        .standing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .standing-card h3 {
            color: #007BFF;
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007BFF;
            text-align: center;
        }
        .standing-stats {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            text-align: center;
        }
        .stat-item {
            padding: 10px;
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #007BFF;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
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
        .category-standing-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            margin: 3px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .member-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
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
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Competition Results</h2>
        </div>
    </header>

    <nav>
        <div class="container">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="results.php" class="active">View Results</a></li>
                <!-- Admin link is intentionally not visible on public pages -->
            </ul>
        </div>
    </nav>

    <main>
        <div class="container">
            <div class="results-header">
                <h2>Competition Results</h2>
                <p>Search and filter results by category, program, participant name, or chest number</p>
            </div>

            <!-- Overall Champion Team (At the Top) -->
            <section class="overall-standing champion-section">
                <h3>🏆 Overall Champion Team 🏆</h3>
                <div class="champion-team">
                    <?php 
                    $champion_team_id = key($team_points);
                    foreach ($teams as $team) {
                        if ($team['id'] == $champion_team_id) {
                            echo '<span class="team-color-box" style="background-color: ' . htmlspecialchars($team['color']) . '"></span>';
                            echo htmlspecialchars($team['name']);
                            break;
                        }
                    }
                    ?>
                    <div>with <?php echo current($team_points); ?> points</div>
                </div>
            </section>

            <!-- Enhanced Overall Team Standings and Category Winners -->
            <section class="standings-section">
                <div class="enhanced-standings">
                    <div class="standing-card">
                        <h3>🏆 Overall Team Standings</h3>
                        <div class="standing-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($teams); ?></div>
                                <div class="stat-label">Teams</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($results); ?></div>
                                <div class="stat-label">Results</div>
                            </div>
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
                                            echo '<img src="assets/member_photos/' . htmlspecialchars($leader_photo) . '" alt="' . htmlspecialchars($team['leader']) . '">';
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
                                            echo '<img src="assets/member_photos/' . htmlspecialchars($manager_photo) . '" alt="' . htmlspecialchars($team['manager']) . '">';
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
                    </div>
                    
                    <div class="standing-card">
                        <h3>🏅 Category Standings</h3>
                        <div class="category-standings">
                            <?php foreach ($categories as $category): ?>
                            <div class="category-section">
                                <h4 style="color: #007BFF; margin: 15px 0 10px 0; border-bottom: 1px solid #eee; padding-bottom: 5px;"><?php echo $category; ?></h4>
                                <?php
                                // Calculate points per team for this category
                                $category_team_points = [];
                                foreach ($teams as $team) {
                                    $category_team_points[$team['id']] = calculateCategoryPoints($results, $team['id'], $category);
                                }
                                
                                // Sort and display top teams
                                arsort($category_team_points);
                                $rank = 1;
                                foreach ($category_team_points as $team_id => $points) {
                                    if ($points > 0) {
                                        $rank_icon = '';
                                        switch ($rank) {
                                            case 1: $rank_icon = '🥇'; break;
                                            case 2: $rank_icon = '🥈'; break;
                                            case 3: $rank_icon = '🥉'; break;
                                        }
                                        foreach ($teams as $team) {
                                            if ($team['id'] == $team_id) {
                                                echo '<div class="category-standing-item">';
                                                echo '<div class="team-info">';
                                                echo '<span class="standing-rank">' . $rank_icon . '</span>';
                                                echo '<span class="team-color-box" style="background-color: ' . htmlspecialchars($team['color']) . '"></span>';
                                                echo '<span class="team-name">' . htmlspecialchars($team['name']) . '</span>';
                                                echo '</div>';
                                                echo '<div class="team-points">' . $points . ' pts</div>';
                                                echo '</div>';
                                                break;
                                            }
                                        }
                                        if ($rank >= 3) break;
                                        $rank++;
                                    }
                                }
                                ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filter Section (Under Standings) -->
            <section class="filter-section">
                <h3>🔍 Filter Results</h3>
                <form class="filter-form" method="GET">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($search_category == $category) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" name="search" placeholder="Search program, participant, or chest number" value="<?php echo htmlspecialchars($search_term); ?>">
                    
                    <button type="submit">Search</button>
                    <button type="button" onclick="window.location.href='results.php'">Clear</button>
                </form>
            </section>

            <?php if (count($filtered_results) > 0): ?>
            <section class="results-section">
                <div class="results-cards">
                    <?php foreach ($filtered_results as $result): ?>
                    <?php 
                        $team = getTeamById($teams, $result['team_id']);
                        $position_icon = getPositionIcon($result['position']);
                        $grade_icon = getGradeIcon($result['grade']);
                        
                        // Try to find member photo
                        $member_photo = null;
                        if (isset($team_members[$result['team_id']])) {
                            foreach ($team_members[$result['team_id']] as $member) {
                                if ($member['name'] == $result['winner'] && !empty($member['photo_path'])) {
                                    $member_photo = $member['photo_path'];
                                    break;
                                }
                            }
                        }
                    ?>
                    <div class="result-card">
                        <div class="result-card-header">
                            <div class="result-program"><?php echo htmlspecialchars($result['program_name']); ?></div>
                            <div class="result-category"><?php echo htmlspecialchars($result['category']); ?></div>
                        </div>
                        
                        <div class="result-details">
                            <div class="result-detail">
                                <span class="result-detail-label">Type:</span>
                                <span><?php echo htmlspecialchars($result['type']); ?></span>
                            </div>
                            
                            <div class="result-detail">
                                <span class="result-detail-label">Position:</span>
                                <span>
                                    <span class="position-icon"><?php echo $position_icon; ?></span>
                                    <?php echo htmlspecialchars($result['position']); ?>
                                </span>
                            </div>
                            
                            <div class="result-detail">
                                <span class="result-detail-label">Grade:</span>
                                <span>
                                    <?php echo htmlspecialchars($result['grade']); ?>
                                    <span class="grade-icon"><?php echo $grade_icon; ?></span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="result-winner">
                            <strong>Winner:</strong> 
                            <?php if ($member_photo): ?>
                                <img src="assets/member_photos/<?php echo htmlspecialchars($member_photo); ?>" alt="<?php echo htmlspecialchars($result['winner']); ?>" class="member-photo">
                            <?php endif; ?>
                            <?php echo htmlspecialchars($result['winner']); ?>
                        </div>
                        
                        <div class="result-team">
                            <span class="team-color-box" style="background-color: <?php echo $team ? htmlspecialchars($team['color']) : '#666'; ?>;"></span>
                            <?php echo $team ? htmlspecialchars($team['name']) : 'Unknown Team'; ?>
                        </div>
                        
                        <div class="result-points">
                            <?php echo htmlspecialchars($result['points']); ?> Points
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php else: ?>
            <section class="results-section">
                <div class="no-results">
                    <h3>No Results Found</h3>
                    <p>No results match your current filter criteria.</p>
                    <button onclick="window.location.href='results.php'" class="btn">Clear Filters</button>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 SUMUD'25 Arts Festival. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>