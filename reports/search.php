<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = null;

if ($q !== '') {
	$like = "%" . $q . "%";
	$sql = "SELECT DISTINCT c.contact_id, c.first_name, c.last_name, c.email, c.phone, c.company
			FROM contacts c
			LEFT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id
			LEFT JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ?
			WHERE c.user_id = ? AND (
				c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company LIKE ? OR g.group_name LIKE ?
			)
			ORDER BY c.first_name, c.last_name";

	$stmt = $conn->prepare($sql);
	// bind: user_id, user_id, then 6 like params
	$types = 'iissssss';
	$stmt->bind_param($types, $user_id, $user_id, $like, $like, $like, $like, $like, $like);
	$stmt->execute();
	$results = $stmt->get_result();
}

include '../includes/header.php';
?>

<div class="container">
	<h1>🔎 Reports — Search</h1>
	<p>Search contacts (name, email, phone, company or group).</p>

	<div class="table-container" style="margin-bottom: 20px;">
        <div class="sql-info-icon">
            ℹ️
            <div class="sql-tooltip">
                <span class="sql-label">SQL Concepts: LIKE with Wildcards + OR Conditions</span>
                <div class="sql-query">SELECT DISTINCT c.contact_id, c.first_name, 
       c.last_name, c.email, c.phone, c.company
FROM contacts c
LEFT JOIN contact_group_members cgm 
  ON c.contact_id = cgm.contact_id
LEFT JOIN contact_groups_table g 
  ON cgm.group_id = g.group_id AND g.user_id = ?
WHERE c.user_id = ? 
  AND (
    c.first_name LIKE ? OR 
    c.last_name LIKE ? OR 
    c.email LIKE ? OR 
    c.phone LIKE ? OR 
    c.company LIKE ? OR 
    g.group_name LIKE ?
  )
ORDER BY c.first_name, c.last_name

-- LIKE: Pattern matching with wildcards
-- %search%: Matches anywhere in the string
-- OR: Returns rows matching ANY condition
-- DISTINCT: Removes duplicate contacts