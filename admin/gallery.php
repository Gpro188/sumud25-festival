<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Database configuration
require_once '../includes/config.php';

// Handle form submission for adding a new photo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_photo'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../assets/gallery/';
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['photo']['name']);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['photo']['tmp_name']);
        if ($check !== false) {
            // Check file size (5MB limit)
            if ($_FILES['photo']['size'] < 5000000) {
                // Allow certain file formats
                if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                        // Save to database
                        $stmt = $pdo->prepare("INSERT INTO gallery (title, description, image_path) VALUES (?, ?, ?)");
                        if ($stmt->execute([$title, $description, 'assets/gallery/' . $file_name])) {
                            $success_message = "Photo uploaded successfully!";
                        } else {
                            $error_message = "Database error occurred.";
                        }
                    } else {
                        $error_message = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $error_message = "Sorry, your file is too large. Maximum 5MB allowed.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    } else {
        $error_message = "Please select a photo to upload.";
    }
}

// Handle photo deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Get photo path before deleting
    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
    $stmt->execute([$delete_id]);
    $photo = $stmt->fetch();
    
    if ($photo) {
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            // Delete file from server
            if (file_exists('../' . $photo['image_path'])) {
                unlink('../' . $photo['image_path']);
            }
            $success_message = "Photo deleted successfully!";
        } else {
            $error_message = "Database error occurred.";
        }
    }
}

// Handle photo status update
if (isset($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    
    // Get current status
    $stmt = $pdo->prepare("SELECT is_active FROM gallery WHERE id = ?");
    $stmt->execute([$toggle_id]);
    $photo = $stmt->fetch();
    
    if ($photo) {
        $new_status = !$photo['is_active'];
        $stmt = $pdo->prepare("UPDATE gallery SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $toggle_id])) {
            $success_message = "Photo status updated successfully!";
        } else {
            $error_message = "Database error occurred.";
        }
    }
}

// Fetch all gallery photos
$stmt = $pdo->prepare("SELECT * FROM gallery ORDER BY created_at DESC");
$stmt->execute();
$photos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - SUMUD'25 Arts Festival</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
        }
        
        .gallery-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .gallery-title {
            font-weight: bold;
            margin: 10px 0;
        }
        
        .gallery-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }
        
        .gallery-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 0.9em;
        }
        
        .status-active {
            color: green;
            font-weight: bold;
        }
        
        .status-inactive {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Admin Gallery Management</h2>
        </div>
    </header>

    <div class="admin-header">
        <div class="container">
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="teams.php">Teams</a></li>
                    <li><a href="results.php">Results</a></li>
                    <li><a href="gallery.php" class="active">Gallery</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <main>
        <div class="container">
            <div class="results-header">
                <h2>Manage Gallery Photos</h2>
                <p>Upload and manage festival photos for the gallery</p>
            </div>

            <?php if (isset($success_message)): ?>
            <div class="admin-form" style="border-top-color: #28a745;">
                <p style="color: #28a745; text-align: center; font-weight: bold;"><?php echo htmlspecialchars($success_message); ?></p>
            </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
            <div class="admin-form" style="border-top-color: #dc3545;">
                <p style="color: #dc3545; text-align: center; font-weight: bold;"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
            <?php endif; ?>

            <section class="admin-form">
                <h3>Add New Photo</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="photo">Photo</label>
                            <input type="file" id="photo" name="photo" accept="image/*" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_photo" class="btn-primary">Upload Photo</button>
                    </div>
                </form>
            </section>

            <section class="admin-form">
                <h3>Gallery Photos</h3>
                <?php if (count($photos) > 0): ?>
                <div class="gallery-container">
                    <?php foreach ($photos as $photo): ?>
                    <div class="gallery-item">
                        <img src="../<?php echo htmlspecialchars($photo['image_path']); ?>" alt="<?php echo htmlspecialchars($photo['title']); ?>" class="gallery-image">
                        <div class="gallery-title"><?php echo htmlspecialchars($photo['title']); ?></div>
                        <div class="gallery-description"><?php echo htmlspecialchars($photo['description']); ?></div>
                        <div class="gallery-status <?php echo $photo['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $photo['is_active'] ? 'Active' : 'Inactive'; ?>
                        </div>
                        <div class="gallery-actions">
                            <a href="gallery.php?toggle_id=<?php echo $photo['id']; ?>" class="btn btn-small <?php echo $photo['is_active'] ? 'btn-secondary' : 'btn-primary'; ?>">
                                <?php echo $photo['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="gallery.php?delete_id=<?php echo $photo['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this photo?')">
                                Delete
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>No photos have been uploaded yet.</p>
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