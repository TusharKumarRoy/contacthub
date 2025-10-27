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
		<form method="GET" action="">
			<div style="display:flex; gap:10px; align-items:center;">
				<input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search..." style="flex:1; padding:8px;">
				<button type="submit" class="btn-submit" style="padding:8px 16px;">Search</button>
				<a href="search.php" class="btn-small btn-edit">Clear</a>
			</div>
		</form>
	</div>

	<?php if ($q === ''): ?>
		<div class="table-container">
			<p>Enter a search term above to find contacts.</p>
		</div>
	<?php else: ?>
		<div class="table-container">
			<h2>Results (<?php echo $results ? $results->num_rows : 0; ?>)</h2>
			<?php if ($results && $results->num_rows > 0): ?>
				<table>
					<thead>
						<tr>
							<th>Name</th>
							<th>Email</th>
							<th>Phone</th>
							<th>Company</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php while ($r = $results->fetch_assoc()): ?>
							<tr>
								<td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
								<td><?php echo htmlspecialchars($r['email'] ?? 'N/A'); ?></td>
								<td><?php echo htmlspecialchars($r['phone'] ?? 'N/A'); ?></td>
								<td><?php echo htmlspecialchars($r['company'] ?? 'N/A'); ?></td>
								<td>
									<a href="../contacts/view.php?id=<?php echo $r['contact_id']; ?>" class="btn-small btn-view">View</a>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			<?php else: ?>
				<p>No results for <strong><?php echo htmlspecialchars($q); ?></strong>.</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

