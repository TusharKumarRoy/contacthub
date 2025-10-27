<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Contacts with their groups (GROUP_CONCAT using LEFT JOIN)
$sql = "SELECT c.contact_id, c.first_name, c.last_name, 
			   GROUP_CONCAT(DISTINCT g.group_name SEPARATOR ', ') AS groups
		FROM contacts c
		LEFT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id
		LEFT JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ?
		WHERE c.user_id = ?
		GROUP BY c.contact_id, c.first_name, c.last_name
		ORDER BY c.first_name, c.last_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$contacts = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container">
	<h1>🔗 JOINs Report</h1>
	<p>Demonstrates joining contacts with groups (LEFT JOIN + GROUP_CONCAT).</p>

	<div class="table-container">
		<?php if ($contacts->num_rows > 0): ?>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Groups</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = $contacts->fetch_assoc()): ?>
						<tr>
							<td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
							<td><?php echo htmlspecialchars($row['groups'] ?? '—'); ?></td>
							<td>
								<a href="../contacts/view.php?id=<?php echo $row['contact_id']; ?>" class="btn-small btn-view">View</a>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		<?php else: ?>
			<p>No contacts found.</p>
		<?php endif; ?>
	</div>
</div>

<?php include '../includes/footer.php'; ?>

