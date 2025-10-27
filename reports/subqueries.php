<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Contacts not assigned to any group (NOT IN subquery)
$sql_unassigned = "SELECT contact_id, first_name, last_name, email FROM contacts WHERE user_id = ? AND contact_id NOT IN (SELECT contact_id FROM contact_group_members) ORDER BY first_name";
$stmt = $conn->prepare($sql_unassigned);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$unassigned = $stmt->get_result();

// Find group with most members (subquery in ORDER BY)
$sql_top_group = "SELECT g.group_id, g.group_name
				  FROM contact_groups_table g
				  WHERE g.user_id = ?
				  ORDER BY (SELECT COUNT(*) FROM contact_group_members cgm WHERE cgm.group_id = g.group_id) DESC
				  LIMIT 1";
$stmt = $conn->prepare($sql_top_group);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$top_group = $stmt->get_result()->fetch_assoc();

$top_members = null;
if ($top_group) {
	$sql_members = "SELECT c.contact_id, c.first_name, c.last_name FROM contacts c INNER JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id WHERE cgm.group_id = ? AND c.user_id = ? ORDER BY c.first_name";
	$stmt = $conn->prepare($sql_members);
	$stmt->bind_param('ii', $top_group['group_id'], $user_id);
	$stmt->execute();
	$top_members = $stmt->get_result();
}

include '../includes/header.php';
?>

<div class="container">
	<h1>📌 Subqueries Report</h1>
	<p>Examples of queries using subqueries: unassigned contacts and the largest group.</p>

	<div class="table-container">
		<h2>Contacts not in any group</h2>
		<?php if ($unassigned->num_rows > 0): ?>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($c = $unassigned->fetch_assoc()): ?>
						<tr>
							<td><?php echo htmlspecialchars($c['first_name'].' '.$c['last_name']); ?></td>
							<td><?php echo htmlspecialchars($c['email'] ?? 'N/A'); ?></td>
							<td><a href="../contacts/view.php?id=<?php echo $c['contact_id']; ?>" class="btn-small btn-view">View</a></td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p>All contacts are assigned to groups.</p>
		<?php endif; ?>
	</div>

	<?php if ($top_group): ?>
		<div class="table-container" style="margin-top:20px;">
			<h2>Largest Group: <?php echo htmlspecialchars($top_group['group_name']); ?></h2>
			<?php if ($top_members && $top_members->num_rows > 0): ?>
				<table>
					<thead>
						<tr>
							<th>Name</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php while ($m = $top_members->fetch_assoc()): ?>
							<tr>
								<td><?php echo htmlspecialchars($m['first_name'].' '.$m['last_name']); ?></td>
								<td><a href="../contacts/view.php?id=<?php echo $m['contact_id']; ?>" class="btn-small btn-view">View</a></td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			<?php else: ?>
				<p>No members in this group.</p>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="table-container" style="margin-top:20px;">
			<p>No groups found for your account.</p>
		</div>
	<?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

