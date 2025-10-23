<?php
require_once 'includes/config.php';

// Fetch overall team standings with team leader photos
try {
    $stmt = $pdo->query("
        SELECT 
            t.id,
            t.name as team_name,
            t.category,
            COUNT(tm.id) as member_count,
            tl.full_name as leader_name,
            tl.photo_path as leader_photo
        FROM teams t
        LEFT JOIN team_members tm ON t.id = tm.team_id
        LEFT JOIN team_leaders tl ON t.id = tl.team_id
        GROUP BY t.id, t.name, t.category, tl.full_name, tl.photo_path
        ORDER BY t.category, t.name
    ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching team standings: " . $e->getMessage();
    $teams = [];
}

// Fetch program results with winner information and team leader photos
try {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name as program_name,
            p.date,
            p.time,
            p.venue,
            t.name as winning_team,
            t.category,
            tl.full_name as leader_name,
            tl.photo_path as leader_photo
        FROM programs p
        LEFT JOIN teams t ON p.team_id = t.id
        LEFT JOIN team_leaders tl ON t.id = tl.team_id
        WHERE p.team_id IS NOT NULL
        ORDER BY p.date, p.time
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching program results: " . $e->getMessage();
    $results = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - SUMUD'25 Festival</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .leader-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        .winner-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
        }
        .team-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">SUMUD'25 Festival Results</h1>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Overall Team Standings -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-trophy me-2"></i>Overall Team Standings</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teams)): ?>
                            <p>No teams found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Team Name</th>
                                            <th>Category</th>
                                            <th>Members</th>
                                            <th>Team Leader</th>
                                            <th>Leader Photo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teams as $team): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                                <td><?php echo htmlspecialchars($team['category']); ?></td>
                                                <td><?php echo $team['member_count']; ?></td>
                                                <td><?php echo htmlspecialchars($team['leader_name'] ?? 'No Leader Assigned'); ?></td>
                                                <td>
                                                    <?php if (!empty($team['leader_photo'])): ?>
                                                        <img src="uploads/<?php echo htmlspecialchars($team['leader_photo']); ?>" alt="Leader Photo" class="leader-photo">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center team-photo text-white">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Program Results -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0"><i class="fas fa-medal me-2"></i>Program Results</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($results)): ?>
                            <p>No results available yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Program</th>
                                            <th>Date & Time</th>
                                            <th>Venue</th>
                                            <th>Winning Team</th>
                                            <th>Category</th>
                                            <th>Team Leader</th>
                                            <th>Leader Photo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['program_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($result['date'])) . ' at ' . date('g:i A', strtotime($result['time'])); ?></td>
                                                <td><?php echo htmlspecialchars($result['venue']); ?></td>
                                                <td><?php echo htmlspecialchars($result['winning_team']); ?></td>
                                                <td><?php echo htmlspecialchars($result['category']); ?></td>
                                                <td><?php echo htmlspecialchars($result['leader_name'] ?? 'No Leader'); ?></td>
                                                <td>
                                                    <?php if (!empty($result['leader_photo'])): ?>
                                                        <img src="uploads/<?php echo htmlspecialchars($result['leader_photo']); ?>" alt="Leader Photo" class="winner-photo">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center winner-photo text-white">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>