<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Total contacts
$sql_total = "SELECT COUNT(*) as total FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_contacts = $stmt->get_result()->fetch_assoc()['total'];

// Favorites
$sql_fav = "SELECT SUM(is_favorite) as favorites FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql_fav);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_favorites = $stmt->get_result()->fetch_assoc()['favorites'] ?? 0;

// Total groups
$sql_groups = "SELECT COUNT(*) as total FROM contact_groups_table WHERE user_id = ?";
$stmt = $conn->prepare($sql_groups);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_groups = $stmt->get_result()->fetch_assoc()['total'];

// Contacts per company
$sql_companies = "SELECT company, COUNT(*) as cnt FROM contacts WHERE user_id = ? AND company IS NOT NULL GROUP BY company ORDER BY cnt DESC LIMIT 10";
$stmt = $conn->prepare($sql_companies);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$companies = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container">
	<h1>📊 Statistics</h1>
	<p>Overview of your contacts and groups.</p>

	<div class="dashboard-stats">
		<div class="stat-card">
			<div class="icon">📇</div>
			<div class="number"><?php echo $total_contacts; ?></div>
			<div class="label">Total Contacts</div>
		</div>

		<div class="stat-card">
			<div class="icon">⭐</div>
			<div class="number"><?php echo $total_favorites; ?></div>
			<div class="label">Favorites</div>
		</div>

		<div class="stat-card">
			<div class="icon">📁</div>
			<div class="number"><?php echo $total_groups; ?></div>
			<div class="label">Groups</div>
		</div>
	</div>

	<div class="table-container" style="margin-top: 30px;">
		<h2>Top Companies</h2>
		<?php if ($companies->num_rows > 0): ?>
			<table>
				<thead>
					<tr>
						<th>Company</th>
						<th>Contacts</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($c = $companies->fetch_assoc()): ?>
						<tr>
							<td><?php echo htmlspecialchars($c['company']); ?></td>
							<td><strong><?php echo $c['cnt']; ?></strong></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p>No company data yet.</p>
		<?php endif; ?>
	</div>
</div>

<?php include '../includes/footer.php'; ?>

