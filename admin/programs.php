<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Sample data - in a real application, this would come from a database
$categories = ['BIDAYA', 'THANIYA'];
$competition_types = ['Individual', 'Group', 'General'];

$programs = [
    ['id' => 1, 'name' => 'Quran Recitation', 'category' => 'BIDAYA', 'type' => 'Individual'],
    ['id' => 2, 'name' => 'Poetry Reading', 'category' => 'BIDAYA', 'type' => 'Individual'],
    ['id' => 3, 'name' => 'Group Dance', 'category' => 'THANIYA', 'type' => 'Group'],
    ['id' => 4, 'name' => 'Debate Competition', 'category' => 'THANIYA', 'type' => 'General'],
    ['id' => 5, 'name' => 'Solo Singing', 'category' => 'BIDAYA', 'type' => 'Individual'],
    ['id' => 6, 'name' => 'Drama Performance', 'category' => 'THANIYA', 'type' => 'Group']
];

// Handle form submission for adding a new program
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_program'])) {
    $program_name = isset($_POST['program_name']) ? trim($_POST['program_name']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    
    if ($program_name && $category && $type) {
        // In a real application, you would save this to a database
        $new_id = count($programs) + 1;
        $programs[] = [
            'id' => $new_id,
            'name' => $program_name,
            'category' => $category,
            'type' => $type
        ];
        $success_message = "Program added successfully!";
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Programs - SUMUD'25 Arts Festival</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>SUMUD'25 Arts Festival</h1>
            <h2>Program Management</h2>
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
                    <li><a href="programs.php" class="active">Programs</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <main>
        <div class="container">
            <div class="results-header">
                <h2>Manage Competition Programs</h2>
                <p>Add and manage competition programs</p>
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
                <h3>Add New Program</h3>
                <form method="POST">
                    <input type="hidden" name="add_program" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="program_name">Program Name</label>
                            <input type="text" id="program_name" name="program_name" required>
                        </div>
                        
                        <div class="form-col">
                            <label for="category">Category</label>
                            <select id="category" name="category" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-col">
                            <label for="type">Competition Type</label>
                            <select id="type" name="type" required>
                                <option value="">-- Select Type --</option>
                                <?php foreach ($competition_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Program</button>
                    </div>
                </form>
            </section>

            <section>
                <h3>Existing Programs</h3>
                <?php if (count($programs) > 0): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Program Name</th>
                            <th>Category</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programs as $program): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($program['name']); ?></td>
                            <td><?php echo htmlspecialchars($program['category']); ?></td>
                            <td><?php echo htmlspecialchars($program['type']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No programs have been added yet.</p>
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