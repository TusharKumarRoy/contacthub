<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager</title>
    <?php
    // Compute base path using the project folder name so links resolve correctly
    // regardless of the current script depth (works for pages in subfolders).
    // Example: if the project directory is 'contacthub', $base_path becomes '/contacthub'.
    $app_folder = basename(dirname(__DIR__));
    $base_path = '/' . $app_folder;
    ?>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/style.css">
    <script src="<?php echo $base_path; ?>/assets/js/tooltip-positioning.js"></script>
</head>
<body>
    <div class="navbar">
        <h1>📇 Contact Manager</h1>
        <nav>
            <a href="<?php echo $base_path; ?>/dashboard.php">Dashboard</a>
            <a href="<?php echo $base_path; ?>/contacts/list.php">Contacts</a>
            <a href="<?php echo $base_path; ?>/groups/list.php">Groups</a>
            <a href="<?php echo $base_path; ?>/reports/statistics.php">Reports</a>
            <a href="<?php echo $base_path; ?>/logout.php">Logout</a>
        </nav>
    </div>