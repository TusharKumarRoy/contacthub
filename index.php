<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="landing-container">
        <div class="landing-content">
            <h1>📇 Contact Manager</h1>
            <p class="tagline">Manage your contacts efficiently and professionally</p>
            
            <div class="features">
                <div class="feature">
                    <span class="icon">👥</span>
                    <h3>Organize Contacts</h3>
                    <p>Store and manage all your contacts in one place</p>
                </div>
                <div class="feature">
                    <span class="icon">📁</span>
                    <h3>Create Groups</h3>
                    <p>Categorize contacts into custom groups</p>
                </div>
                <div class="feature">
                    <span class="icon">🔍</span>
                    <h3>Search & Filter</h3>
                    <p>Find contacts quickly with powerful search</p>
                </div>
            </div>
            
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Register</a>
            </div>
        </div>
    </div>
</body>
</html>