<?php
// Fixed teams.php file with proper handling of is_active field
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include configuration to access categories
include '../includes/config.php';

// Handle deletion of team members
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_member'])) {
    $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
    if ($member_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
            $stmt->execute([$member_id]);
            $success_message = "Team member deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting team member: " . $e->getMessage();
        }
    }
}

// Handle deletion of team leaders
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_leader'])) {
    $leader_id = isset($_POST['leader_id']) ? (int)$_POST['leader_id'] : 0;
    if ($leader_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM team_leaders WHERE id = ?");
            $stmt->execute([$leader_id]);
            $success_message = "Team leader deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting team leader: " . $e->getMessage();
        }
    }
}

// Handle activation/deactivation of team leaders
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_leader_status'])) {
    $leader_id = isset($_POST['leader_id']) ? (int)$_POST['leader_id'] : 0;
    $current_status = isset($_POST['current_status']) ? (int)$_POST['current_status'] : 1;
    
    if ($leader_id) {
        try {
            $new_status = $current_status ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE team_leaders SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $leader_id]);
            
            $status_text = $new_status ? 'activated' : 'deactivated';
            $success_message = "Team leader $status_text successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating team leader status: " . $e->getMessage();
        }
    }
}

// Handle password update for team leaders
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_leader_password'])) {
    $leader_id = isset($_POST['leader_id']) ? (int)$_POST['leader_id'] : 0;
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if ($leader_id && !empty($new_password)) {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE team_leaders SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $leader_id]);
            $success_message = "Team leader password updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating team leader password: " . $e->getMessage();
        }
    }
}

// Handle form submission for adding a new team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_team'])) {
    $team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
    $leader = isset($_POST['leader']) ? trim($_POST['leader']) : '';
    $manager = isset($_POST['manager']) ? trim($_POST['manager']) : '';
    $color = isset($_POST['color']) ? $_POST['color'] : '#3498db';
    
    if (!empty($team_name) && !empty($leader) && !empty($manager)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO teams (name, leader, manager, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$team_name, $leader, $manager, $color]);
            $success_message = "Team added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding team: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Handle form submission for adding a team leader
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_leader'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!empty($username) && !empty($full_name) && !empty($password) && $team_id > 0) {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM team_leaders WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = "Username already exists. Please choose a different username.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO team_leaders (username, password_hash, full_name, team_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $full_name, $team_id]);
                $success_message = "Team leader added successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error adding team leader: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Handle form submission for adding a team member
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'Participant';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $chest_number = isset($_POST['chest_number']) ? trim($_POST['chest_number']) : '';
    
    if ($team_id > 0 && !empty($name) && !empty($category) && !empty($chest_number)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO team_members (team_id, name, role, category, chest_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$team_id, $name, $role, $category, $chest_number]);
            $success_message = "Team member added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding team member: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Fetch teams for dropdowns
try {
    $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name");
    $teams = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching teams: " . $e->getMessage();
    $teams = [];
}

// Fetch team leaders with team names
try {
    // Updated query to explicitly select is_active column
    $stmt = $pdo->query("SELECT tl.*, t.name as team_name FROM team_leaders tl LEFT JOIN teams t ON tl.team_id = t.id ORDER BY tl.team_id");
    $team_leaders = $stmt->fetchAll();
    
    // Add default is_active value if column doesn't exist
    foreach ($team_leaders as &$leader) {
        if (!isset($leader['is_active'])) {
            $leader['is_active'] = 1; // Default to active if column doesn't exist
        }
    }
} catch (PDOException $e) {
    $error_message = "Error fetching team leaders: " . $e->getMessage();
    $team_leaders = [];
}

// Fetch teams with their members
try {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY name");
    $teams = $stmt->fetchAll();
    
    $team_members = [];
    foreach ($teams as $team) {
        $stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY name");
        $stmt->execute([$team['id']]);
        $team_members[$team['id']] = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error_message = "Error fetching teams and members: " . $e->getMessage();
    $teams = [];
    $team_members = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams - Admin Panel</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
        }
        
        .admin-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .team-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .team-color-box {
            width: 30px;
            height: 30px;
            border-radius: 4px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .results-table th,
        .results-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .results-table th {
            background-color: #f2f2f2;
        }
        
        .results-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .activate-btn {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .deactivate-btn {
            background-color: #f39c12;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .password-form {
            display: none;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: bold;
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
        
        .close:hover,
        .close:focus {
            color: black;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        
        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival - Admin Panel</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="teams.php" class="active">Manage Teams</a></li>
                    <li><a href="programs.php">Manage Programs</a></li>
                    <li><a href="results.php">Update Results</a></li>
                    <li><a href="gallery.php">Manage Gallery</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="admin-container">
            <h2>Manage Teams</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <section>
                <h3 class="section-title">Add New Team</h3>
                <form method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="team_name">Team Name *</label>
                        <input type="text" id="team_name" name="team_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="leader">Team Leader *</label>
                        <input type="text" id="leader" name="leader" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="manager">Team Manager *</label>
                        <input type="text" id="manager" name="manager" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Team Color</label>
                        <input type="color" id="color" name="color" value="#3498db">
                    </div>
                    
                    <button type="submit" name="add_team" class="btn btn-primary">Add Team</button>
                </form>
            </section>
            
            <section>
                <h3 class="section-title">Add Team Leader</h3>
                <form method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="team_id">Team *</label>
                        <select id="team_id" name="team_id" required>
                            <option value="">Select Team</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="add_leader" class="btn btn-primary">Add Team Leader</button>
                </form>
            </section>
            
            <section>
                <h3 class="section-title">Add Team Member</h3>
                <form method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="member_team_id">Team *</label>
                        <select id="member_team_id" name="team_id" required>
                            <option value="">Select Team</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Member Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" name="role" value="Participant">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="BIDAYA">BIDAYA</option>
                            <option value="THANIYA">THANIYA</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="chest_number">Chest Number *</label>
                        <input type="text" id="chest_number" name="chest_number" required>
                    </div>
                    
                    <button type="submit" name="add_member" class="btn btn-primary">Add Member</button>
                </form>
            </section>
            
            <section>
                <h3 class="section-title">Team Leaders</h3>
                <?php if (count($team_leaders) > 0): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Team</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_leaders as $leader): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($leader['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($leader['username']); ?></td>
                            <td><?php echo htmlspecialchars($leader['team_name'] ?? 'Unassigned'); ?></td>
                            <td>
                                <span class="<?php echo isset($leader['is_active']) && $leader['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo isset($leader['is_active']) && $leader['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="toggle_leader_status" value="1">
                                    <input type="hidden" name="leader_id" value="<?php echo $leader['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo isset($leader['is_active']) ? $leader['is_active'] : 1; ?>">
                                    <?php if (isset($leader['is_active']) && $leader['is_active']): ?>
                                        <button type="submit" class="deactivate-btn" onclick="return confirm('Are you sure you want to deactivate this team leader?')">Deactivate</button>
                                    <?php else: ?>
                                        <button type="submit" class="activate-btn" onclick="return confirm('Are you sure you want to activate this team leader?')">Activate</button>
                                    <?php endif; ?>
                                </form>
                                <button onclick="showPasswordForm(<?php echo $leader['id']; ?>)">Reset Password</button>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $leader['id']; ?>, 'leader')">Delete</button>
                            </td>
                        </tr>
                        <tr id="password-form-<?php echo $leader['id']; ?>" class="password-form">
                            <td colspan="5">
                                <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                    <input type="hidden" name="update_leader_password" value="1">
                                    <input type="hidden" name="leader_id" value="<?php echo $leader['id']; ?>">
                                    <label for="new_password_<?php echo $leader['id']; ?>">New Password:</label>
                                    <input type="password" id="new_password_<?php echo $leader['id']; ?>" name="new_password" required>
                                    <button type="submit" class="btn-primary">Update</button>
                                    <button type="button" onclick="hidePasswordForm(<?php echo $leader['id']; ?>)">Cancel</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No team leaders have been added yet.</p>
                <?php endif; ?>
            </section>

            <section>
                <h3 class="section-title">Existing Teams</h3>
                <?php if (count($teams) > 0): ?>
                    <?php foreach ($teams as $team): ?>
                    <div class="admin-form" style="margin-bottom: 2rem;">
                        <div class="team-header">
                            <h4><?php echo htmlspecialchars($team['name']); ?></h4>
                            <div class="team-color-box" style="background-color: <?php echo htmlspecialchars($team['color']); ?>;"></div>
                        </div>
                        <p><strong>Leader:</strong> <?php echo htmlspecialchars($team['leader']); ?> | 
                           <strong>Manager:</strong> <?php echo htmlspecialchars($team['manager']); ?></p>
                        <h5>Team Members:</h5>
                        <?php if (isset($team_members[$team['id']]) && count($team_members[$team['id']]) > 0): ?>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Chest Number</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($team_members[$team['id']] as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['chest_number']); ?></td>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['role']); ?></td>
                                    <td><?php echo htmlspecialchars($member['category']); ?></td>
                                    <td>
                                        <button class="delete-btn" onclick="confirmDelete(<?php echo $member['id']; ?>, 'member')">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>No members added to this team yet.</p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p>No teams have been added yet.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Confirm Deletion</h3>
            <p id="deleteMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_type" id="deleteType">
                <input type="hidden" name="delete_id" id="deleteId">
                <button type="submit" class="btn-danger">Yes, Delete</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showPasswordForm(leaderId) {
            document.getElementById('password-form-' + leaderId).style.display = 'table-row';
        }

        function hidePasswordForm(leaderId) {
            document.getElementById('password-form-' + leaderId).style.display = 'none';
        }

        // Modal functions
        function confirmDelete(id, type) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteType').value = type;
            
            // Set appropriate message
            if (type === 'member') {
                document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this team member? This action cannot be undone.';
                document.getElementById('deleteForm').innerHTML = `
                    <input type="hidden" name="delete_member" value="1">
                    <input type="hidden" name="member_id" value="${id}">
                    <button type="submit" class="btn-danger">Yes, Delete</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                `;
            } else if (type === 'leader') {
                document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this team leader? This action cannot be undone.';
                document.getElementById('deleteForm').innerHTML = `
                    <input type="hidden" name="delete_leader" value="1">
                    <input type="hidden" name="leader_id" value="${id}">
                    <button type="submit" class="btn-danger">Yes, Delete</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                `;
            }

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
    </script>

    <footer>
        <div class="container">
            <p>&copy; 2025 SUMUD'25 Arts Festival. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>