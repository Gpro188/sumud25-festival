<?php
session_start();

// Check if team leader is logged in
if (!isset($_SESSION['team_leader_logged_in']) || $_SESSION['team_leader_logged_in'] !== true) {
    header('Location: team_leader_login.php');
    exit;
}

// Database connection
require_once '../includes/config.php';

// Get team leader's team
$team_leader_id = $_SESSION['team_leader_id'];
$team_id = $_SESSION['team_leader_team_id'];

// Check if team leader is still active
$stmt = $pdo->prepare("SELECT is_active FROM team_leaders WHERE id = ?");
$stmt->execute([$team_leader_id]);
$team_leader_status = $stmt->fetch();

if (!$team_leader_status || !$team_leader_status['is_active']) {
    // Team leader is deactivated, log them out
    session_destroy();
    header('Location: team_leader_login.php?error=deactivated');
    exit;
}

// Fetch team information
$stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

if (!$team) {
    session_destroy();
    header('Location: team_leader_login.php?error=invalid_team');
    exit;
}

// Fetch team members
$stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY category, name");
$stmt->execute([$team_id]);
$team_members = $stmt->fetchAll();

// Handle form submission for adding team members
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    // Check if team leader is still active before allowing to add members
    $stmt = $pdo->prepare("SELECT is_active FROM team_leaders WHERE id = ?");
    $stmt->execute([$team_leader_id]);
    $current_status = $stmt->fetch();
    
    if (!$current_status || !$current_status['is_active']) {
        $error_message = "Your account has been deactivated. You cannot add members.";
    } else {
        $member_name = isset($_POST['member_name']) ? trim($_POST['member_name']) : '';
        $member_role = isset($_POST['member_role']) ? trim($_POST['member_role']) : 'Participant';
        $member_category = isset($_POST['member_category']) ? trim($_POST['member_category']) : '';
        $chest_number = isset($_POST['chest_number']) ? trim($_POST['chest_number']) : '';
        
        if ($member_name && $member_category) {
            try {
                $stmt = $pdo->prepare("INSERT INTO team_members (team_id, name, role, category, chest_number) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$team_id, $member_name, $member_role, $member_category, $chest_number]);
                $success_message = "Team member added successfully!";
                
                // Refresh team members list
                $stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY category, name");
                $stmt->execute([$team_id]);
                $team_members = $stmt->fetchAll();
            } catch (PDOException $e) {
                $error_message = "Error adding team member: " . $e->getMessage();
            }
        } else {
            $error_message = "Please fill in all required fields.";
        }
    }
}

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_photo'])) {
    // Check if team leader is still active before allowing to upload photos
    $stmt = $pdo->prepare("SELECT is_active FROM team_leaders WHERE id = ?");
    $stmt->execute([$team_leader_id]);
    $current_status = $stmt->fetch();
    
    if (!$current_status || !$current_status['is_active']) {
        $error_message = "Your account has been deactivated. You cannot upload photos.";
    } else {
        $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
        
        if ($member_id && isset($_FILES['member_photo']) && $_FILES['member_photo']['error'] == 0) {
            $upload_dir = '../assets/member_photos/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['member_photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'member_' . $member_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['member_photo']['tmp_name'], $upload_path)) {
                    // Update database with photo path
                    $stmt = $pdo->prepare("UPDATE team_members SET photo_path = ? WHERE id = ? AND team_id = ?");
                    $stmt->execute([$new_filename, $member_id, $team_id]);
                    
                    $success_message = "Photo uploaded successfully!";
                    
                    // Refresh team members list
                    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? ORDER BY category, name");
                    $stmt->execute([$team_id]);
                    $team_members = $stmt->fetchAll();
                } else {
                    $error_message = "Error uploading photo.";
                }
            } else {
                $error_message = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF files.";
            }
        } else {
            $error_message = "Please select a photo to upload.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Leader Dashboard - SUMUD'25</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .team-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .team-color-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .member-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .member-photo {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        .member-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .photo-upload-form {
            margin-top: 10px;
        }
        .photo-upload-form input[type="file"] {
            margin-bottom: 10px;
            width: 100%;
        }
        .home-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .home-link:hover {
            text-decoration: underline;
        }
        .deactivated-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Team Leader Dashboard</h2>
        </div>
    </header>

    <div class="admin-header">
        <div class="container">
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['team_leader_full_name']); ?>!</div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="team_leader_dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="team_leader_logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <main>
        <div class="container">
            <a href="../index.php" class="home-link">← Back to Main Site</a>
            
            <div class="dashboard-header">
                <h2><?php echo htmlspecialchars($team['name']); ?> Dashboard</h2>
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

            <div class="team-info">
                <h3>Team Information</h3>
                <p>
                    <span class="team-color-box" style="background-color: <?php echo htmlspecialchars($team['color']); ?>;"></span>
                    <strong>Team:</strong> <?php echo htmlspecialchars($team['name']); ?> | 
                    <strong>Leader:</strong> <?php echo htmlspecialchars($team['leader']); ?> | 
                    <strong>Manager:</strong> <?php echo htmlspecialchars($team['manager']); ?>
                </p>
            </div>

            <section class="admin-form">
                <h3>Add New Team Member</h3>
                <form method="POST">
                    <input type="hidden" name="add_member" value="1">
                    <div class="form-row">
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

            <section>
                <h3>Team Members</h3>
                <?php if (count($team_members) > 0): ?>
                <div class="members-grid">
                    <?php foreach ($team_members as $member): ?>
                    <div class="member-card">
                        <h4><?php echo htmlspecialchars($member['name']); ?></h4>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($member['role']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($member['category']); ?></p>
                        <p><strong>Chest Number:</strong> <?php echo htmlspecialchars($member['chest_number']); ?></p>
                        
                        <div class="member-photo">
                            <?php if (!empty($member['photo_path'])): ?>
                                <img src="../assets/member_photos/<?php echo htmlspecialchars($member['photo_path']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                            <?php else: ?>
                                <span>No Photo</span>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="photo-upload-form">
                            <input type="hidden" name="upload_photo" value="1">
                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                            <input type="file" name="member_photo" accept="image/*">
                            <button type="submit" class="btn-primary" style="width: 100%;">Upload Photo</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>No members have been added to your team yet.</p>
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