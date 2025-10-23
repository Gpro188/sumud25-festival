<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../includes/config.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle adding a new team
    if (isset($_POST['add_team'])) {
        $team_name = trim($_POST['team_name']);
        $category = $_POST['category'];
        
        if (!empty($team_name) && !empty($category)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO teams (name, category) VALUES (?, ?)");
                $stmt->execute([$team_name, $category]);
                $success_message = "Team added successfully!";
            } catch (PDOException $e) {
                $error_message = "Error adding team: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all fields.";
        }
    }
    
    // Handle adding a team leader
    elseif (isset($_POST['add_leader'])) {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $team_id = (int)$_POST['team_id'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate inputs
        if (empty($username) || empty($full_name) || empty($password) || empty($confirm_password)) {
            $error_message = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM team_leaders WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error_message = "Username already exists. Please choose a different username.";
                } else {
                    // Hash password and insert team leader
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO team_leaders (username, password_hash, full_name, team_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $full_name, $team_id]);
                    $success_message = "Team leader added successfully!";
                }
            } catch (PDOException $e) {
                $error_message = "Error adding team leader: " . $e->getMessage();
            }
        }
    }
    
    // Handle resetting team leader password
    elseif (isset($_POST['reset_password'])) {
        $leader_id = (int)$_POST['leader_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = "Both password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            try {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE team_leaders SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $leader_id]);
                $success_message = "Password reset successfully!";
            } catch (PDOException $e) {
                $error_message = "Error resetting password: " . $e->getMessage();
            }
        }
    }
    
    // Handle deleting a team leader
    elseif (isset($_POST['delete_leader'])) {
        $leader_id = (int)$_POST['leader_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM team_leaders WHERE id = ?");
            $stmt->execute([$leader_id]);
            $success_message = "Team leader deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting team leader: " . $e->getMessage();
        }
    }
    
    // Handle toggling team leader status (active/inactive)
    elseif (isset($_POST['toggle_leader_status'])) {
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
    
    // Handle uploading/updating team leader photo
    elseif (isset($_POST['update_leader_photo'])) {
        $leader_id = (int)$_POST['leader_id'];
        
        if (isset($_FILES['leader_photo']) && $_FILES['leader_photo']['error'] == 0) {
            $file_name = $_FILES['leader_photo']['name'];
            $file_tmp = $_FILES['leader_photo']['tmp_name'];
            $file_size = $_FILES['leader_photo']['size'];
            $file_type = $_FILES['leader_photo']['type'];
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
            } elseif ($file_size > $max_size) {
                $error_message = "File size exceeds 5MB limit.";
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = 'leader_' . $leader_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    try {
                        // Update database with photo path
                        $stmt = $pdo->prepare("UPDATE team_leaders SET photo_path = ? WHERE id = ?");
                        $stmt->execute([$new_filename, $leader_id]);
                        $success_message = "Leader photo updated successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error updating photo in database: " . $e->getMessage();
                        // Delete uploaded file if database update fails
                        if (file_exists($upload_path)) {
                            unlink($upload_path);
                        }
                    }
                } else {
                    $error_message = "Error uploading file.";
                }
            }
        } else {
            $error_message = "Please select a photo to upload.";
        }
    }
    
    // Handle revealing team leader password (for admin only)
    elseif (isset($_POST['reveal_password'])) {
        $leader_id = (int)$_POST['leader_id'];
        
        try {
            $stmt = $pdo->prepare("SELECT username, password_hash FROM team_leaders WHERE id = ?");
            $stmt->execute([$leader_id]);
            $leader = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($leader) {
                // Store the revealed password in session for display (temporary)
                $_SESSION['revealed_password_' . $leader_id] = 'Temporary password for ' . $leader['username'];
                $success_message = "Password revealed temporarily. For security, actual passwords are hashed and cannot be displayed.";
            } else {
                $error_message = "Team leader not found.";
            }
        } catch (PDOException $e) {
            $error_message = "Error fetching team leader: " . $e->getMessage();
        }
    }
}

// Fetch teams for dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching teams: " . $e->getMessage();
    $teams = [];
}

// Fetch team leaders with their teams
try {
    $stmt = $pdo->query("
        SELECT tl.*, t.name as team_name 
        FROM team_leaders tl 
        LEFT JOIN teams t ON tl.team_id = t.id 
        ORDER BY tl.full_name
    ");
    $team_leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching team leaders: " . $e->getMessage();
    $team_leaders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .leader-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .status-active {
            color: #28a745;
        }
        .status-inactive {
            color: #dc3545;
        }
        .password-toggle {
            cursor: pointer;
            margin-left: 5px;
        }
        .credential-info {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Teams</h1>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Add New Team Section -->
                <div class="form-section">
                    <h4>Add New Team</h4>
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label for="team_name" class="form-label">Team Name</label>
                            <input type="text" class="form-control" id="team_name" name="team_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Dance">Dance</option>
                                <option value="Music">Music</option>
                                <option value="Drama">Drama</option>
                                <option value="Sports">Sports</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_team" class="btn btn-primary form-control">Add Team</button>
                        </div>
                    </form>
                </div>
                
                <!-- Add Team Leader Section -->
                <div class="form-section">
                    <h4>Add Team Leader</h4>
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-3">
                            <label for="team_id" class="form-label">Team</label>
                            <select class="form-select" id="team_id" name="team_id" required>
                                <option value="">Select Team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_leader" class="btn btn-success form-control">Add Leader</button>
                        </div>
                    </form>
                    <div class="credential-info">
                        <small><i class="fas fa-info-circle"></i> When adding a team leader, please note down the username and password as they will be needed for the team leader to log in.</small>
                    </div>
                </div>
                
                <!-- Reset Password Section -->
                <div class="form-section">
                    <h4>Reset Team Leader Password</h4>
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label for="reset_leader_id" class="form-label">Team Leader</label>
                            <select class="form-select" id="reset_leader_id" name="leader_id" required>
                                <option value="">Select Team Leader</option>
                                <?php foreach ($team_leaders as $leader): ?>
                                    <option value="<?php echo $leader['id']; ?>">
                                        <?php echo htmlspecialchars($leader['full_name'] . ' (' . $leader['username'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="col-md-3">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_password" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="reset_password" class="btn btn-warning form-control">Reset Password</button>
                        </div>
                    </form>
                </div>
                
                <!-- Team Leaders Table -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="mb-0">Team Leaders</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($team_leaders)): ?>
                            <p>No team leaders found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Team</th>
                                            <th>Status</th>
                                            <th>Credentials</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($team_leaders as $leader): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($leader['photo_path'])): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($leader['photo_path']); ?>" alt="Photo" class="leader-photo">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center leader-photo text-white">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($leader['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($leader['username']); ?></td>
                                                <td><?php echo htmlspecialchars($leader['team_name'] ?? 'No Team'); ?></td>
                                                <td>
                                                    <span class="<?php echo $leader['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo $leader['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#credentialsModal<?php echo $leader['id']; ?>">
                                                        <i class="fas fa-key"></i> View
                                                    </button>
                                                    
                                                    <!-- Credentials Modal -->
                                                    <div class="modal fade" id="credentialsModal<?php echo $leader['id']; ?>" tabindex="-1" aria-labelledby="credentialsModalLabel<?php echo $leader['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="credentialsModalLabel<?php echo $leader['id']; ?>">Credentials for <?php echo htmlspecialchars($leader['full_name']); ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><strong>Username:</strong></label>
                                                                        <div class="form-control-plaintext"><?php echo htmlspecialchars($leader['username']); ?></div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><strong>Password:</strong></label>
                                                                        <div class="form-control-plaintext">
                                                                            <em>Passwords are securely hashed and cannot be displayed for security reasons.</em>
                                                                        </div>
                                                                    </div>
                                                                    <div class="alert alert-warning">
                                                                        <small>
                                                                            <i class="fas fa-exclamation-triangle"></i> 
                                                                            For security purposes, actual passwords are never stored in plain text. 
                                                                            If you need to provide login credentials to the team leader, 
                                                                            please use the "Reset Password" feature to set a new password.
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- Activate/Deactivate Button -->
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="leader_id" value="<?php echo $leader['id']; ?>">
                                                            <input type="hidden" name="current_status" value="<?php echo $leader['is_active']; ?>">
                                                            <button type="submit" name="toggle_leader_status" class="btn btn-sm <?php echo $leader['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>">
                                                                <?php echo $leader['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Delete Button -->
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this team leader? This action cannot be undone.')">
                                                            <input type="hidden" name="leader_id" value="<?php echo $leader['id']; ?>">
                                                            <button type="submit" name="delete_leader" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Update Photo Button -->
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#photoModal<?php echo $leader['id']; ?>">
                                                            <i class="fas fa-camera"></i> Photo
                                                        </button>
                                                        
                                                        <!-- Photo Modal -->
                                                        <div class="modal fade" id="photoModal<?php echo $leader['id']; ?>" tabindex="-1" aria-labelledby="photoModalLabel<?php echo $leader['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="photoModalLabel<?php echo $leader['id']; ?>">Update Photo for <?php echo htmlspecialchars($leader['full_name']); ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <form method="POST" enctype="multipart/form-data">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="leader_id" value="<?php echo $leader['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label for="leader_photo_<?php echo $leader['id']; ?>" class="form-label">Select Photo</label>
                                                                                <input type="file" class="form-control" id="leader_photo_<?php echo $leader['id']; ?>" name="leader_photo" accept="image/*" required>
                                                                                <div class="form-text">Allowed formats: JPG, PNG, GIF. Max size: 5MB</div>
                                                                            </div>
                                                                            <?php if (!empty($leader['photo_path'])): ?>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Current Photo</label>
                                                                                    <br>
                                                                                    <img src="../uploads/<?php echo htmlspecialchars($leader['photo_path']); ?>" alt="Current Photo" class="img-thumbnail" style="max-height: 150px;">
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_leader_photo" class="btn btn-primary">Update Photo</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>