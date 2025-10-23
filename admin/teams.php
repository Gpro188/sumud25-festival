<?php
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

// Fetch teams from database
$stmt = $pdo->query("SELECT * FROM teams ORDER BY name");
$teams = $stmt->fetchAll();

// Fetch team members from database
$team_members = [];
foreach ($teams as $team) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY category, name");
    $stmt->execute([$team['id']]);
    $team_members[$team['id']] = $stmt->fetchAll();
}

// Fetch team leaders
$stmt = $pdo->query("SELECT tl.*, t.name as team_name FROM team_leaders tl LEFT JOIN teams t ON tl.team_id = t.id ORDER BY tl.team_id");
$team_leaders = $stmt->fetchAll();

// Handle form submission for adding a new team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_team'])) {
    $team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
    $team_leader = isset($_POST['team_leader']) ? trim($_POST['team_leader']) : '';
    $team_manager = isset($_POST['team_manager']) ? trim($_POST['team_manager']) : '';
    $team_color = isset($_POST['team_color']) ? trim($_POST['team_color']) : '#3498db';
    
    if ($team_name && $team_leader && $team_manager) {
        try {
            $stmt = $pdo->prepare("INSERT INTO teams (name, leader, manager, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$team_name, $team_leader, $team_manager, $team_color]);
            $success_message = "Team added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding team: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Handle form submission for adding team members
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
    $member_name = isset($_POST['member_name']) ? trim($_POST['member_name']) : '';
    $member_role = isset($_POST['member_role']) ? trim($_POST['member_role']) : 'Participant';
    $member_category = isset($_POST['member_category']) ? trim($_POST['member_category']) : '';
    $chest_number = isset($_POST['chest_number']) ? trim($_POST['chest_number']) : '';
    
    if ($team_id && $member_name && $member_category) {
        try {
            $stmt = $pdo->prepare("INSERT INTO team_members (team_id, name, role, category, chest_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$team_id, $member_name, $member_role, $member_category, $chest_number]);
            $success_message = "Team member added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding team member: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid team or missing member information.";
    }
}

// Handle form submission for adding team leaders
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_team_leader'])) {
    $username = isset($_POST['leader_username']) ? trim($_POST['leader_username']) : '';
    $password = isset($_POST['leader_password']) ? $_POST['leader_password'] : '';
    $full_name = isset($_POST['leader_full_name']) ? trim($_POST['leader_full_name']) : '';
    $team_id = isset($_POST['leader_team_id']) ? (int)$_POST['leader_team_id'] : 0;
    
    if ($username && $password && $full_name && $team_id) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO team_leaders (username, password_hash, full_name, team_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $full_name, $team_id]);
            $success_message = "Team leader added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Duplicate entry error
                $error_message = "Error: Username '{$username}' already exists. Please choose a different username.";
            } else {
                $error_message = "Error adding team leader: " . $e->getMessage();
            }
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}

// Handle form submission for updating team leader password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_leader_password'])) {
    $leader_id = isset($_POST['leader_id']) ? (int)$_POST['leader_id'] : 0;
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    
    if ($leader_id && $new_password) {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE team_leaders SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $leader_id]);
            $success_message = "Team leader password updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating password: " . $e->getMessage();
        }
    } else {
        $error_message = "Please provide a new password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams - SUMUD'25 Arts Festival</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
            border: 2px solid #ddd;
        }
        .team-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .team-color-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: inline-block;
        }
        .section-title {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .team-leaders-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .team-leaders-table th,
        .team-leaders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .team-leaders-table th {
            background-color: #f8f9fa;
        }
        .password-form {
            display: none;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
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
        .activate-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .activate-btn:hover {
            background-color: #218838;
        }
        .deactivate-btn {
            background-color: #ffc107;
            color: #212529;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .deactivate-btn:hover {
            background-color: #e0a800;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
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
        .close:hover {
            color: black;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Team Management</h2>
        </div>
    </header>

    <div class="admin-header">
        <div class="container">
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="teams.php" class="active">Teams</a></li>
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
                <h2>Manage Teams</h2>
                <p>Add new teams and manage team members</p>
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

            <section class="admin-form">
                <h3>Add New Team</h3>
                <form method="POST">
                    <input type="hidden" name="add_team" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="team_name">Team Name</label>
                            <input type="text" id="team_name" name="team_name" required>
                        </div>
                        <div class="form-col">
                            <label for="team_leader">Team Leader</label>
                            <input type="text" id="team_leader" name="team_leader" required>
                        </div>
                        <div class="form-col">
                            <label for="team_manager">Team Manager</label>
                            <input type="text" id="team_manager" name="team_manager" required>
                        </div>
                        <div class="form-col">
                            <label for="team_color">Team Color</label>
                            <input type="color" id="team_color" name="team_color" value="#3498db" style="height: 40px;">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Team</button>
                    </div>
                </form>
            </section>

            <section class="admin-form">
                <h3>Add Team Member</h3>
                <form method="POST">
                    <input type="hidden" name="add_member" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="team_id">Select Team</label>
                            <select id="team_id" name="team_id" required>
                                <option value="">-- Select Team --</option>
                                <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label for="member_name">Member Name</label>
                            <input type="text" id="member_name" name="member_name" required>
                        </div>
                        <div class="form-col">
                            <label for="member_role">Role</label>
                            <select id="member_role" name="member_role" required>
                                <option value="">-- Select Role --</option>
                                <option value="Participant">Participant</option>
                                <option value="Coordinator">Coordinator</option>
                                <option value="Assistant">Assistant</option>
                                <option value="Leader">Leader</option>
                                <option value="Manager">Manager</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="member_category">Category</label>
                            <select id="member_category" name="member_category" required>
                                <option value="">-- Select Category --</option>
                                <option value="BIDAYA">BIDAYA</option>
                                <option value="THANIYA">THANIYA</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label for="chest_number">Chest Number</label>
                            <input type="text" id="chest_number" name="chest_number" placeholder="e.g., B001, T001">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Member</button>
                    </div>
                </form>
            </section>

            <section class="admin-form">
                <h3 class="section-title">Add Team Leader</h3>
                <form method="POST">
                    <input type="hidden" name="add_team_leader" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="leader_username">Username</label>
                            <input type="text" id="leader_username" name="leader_username" required>
                        </div>
                        <div class="form-col">
                            <label for="leader_password">Password</label>
                            <input type="password" id="leader_password" name="leader_password" required>
                        </div>
                        <div class="form-col">
                            <label for="leader_full_name">Full Name</label>
                            <input type="text" id="leader_full_name" name="leader_full_name" required>
                        </div>
                        <div class="form-col">
                            <label for="leader_team_id">Assign to Team</label>
                            <select id="leader_team_id" name="leader_team_id" required>
                                <option value="">-- Select Team --</option>
                                <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Team Leader</button>
                    </div>
                </form>
            </section>

            <section>
                <h3 class="section-title">Team Leaders</h3>
                <?php if (count($team_leaders) > 0): ?>
                <table class="team-leaders-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Team</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team_leaders as $leader): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($leader['username']); ?></td>
                            <td><?php echo htmlspecialchars($leader['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($leader['team_name'] ?? 'Unassigned'); ?></td>
                            <td>
                                <span class="<?php echo $leader['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $leader['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="toggle_leader_status" value="1">
                                    <input type="hidden" name="leader_id" value="<?php echo $leader['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $leader['is_active']; ?>">
                                    <?php if ($leader['is_active']): ?>
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