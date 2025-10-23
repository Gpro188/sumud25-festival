<?php
session_start();

// Destroy all session data
session_destroy();

// Redirect to team leader login page
header('Location: team_leader_login.php?message=logged_out');
exit;
?>