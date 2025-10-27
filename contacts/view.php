<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$contact_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get contact details with JOIN
$sql = "SELECT * FROM contacts WHERE contact_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $contact_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$contact = $result->fetch_assoc();

// Get groups this contact belongs to (INNER JOIN)
$sql_groups = "SELECT g.group_id, g.group_name 
               FROM contact_groups_table g
               INNER JOIN contact_group_members cgm ON g.group_id = cgm.group_id
               WHERE cgm.contact_id = ?";
$stmt_groups = $conn->prepare($sql_groups);
$stmt_groups->bind_param("i", $contact_id);
$stmt_groups->execute();
$groups = $stmt_groups->get_result();

include '../includes/header.php';
?>

<div class="container">
    <h1>👤 Contact Details</h1>
    
    <div class="table-container">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div>
                <h2>
                    <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                    <?php if ($contact['is_favorite']): ?>
                        <span class="badge badge-favorite">⭐ Favorite</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div>
                <a href="edit.php?id=<?php echo $contact_id; ?>" class="btn-small btn-edit">✏️ Edit</a>
                <a href="delete.php?id=<?php echo $contact_id; ?>" class="btn-small btn-delete" 
                   onclick="return confirm('Delete this contact?')">🗑️ Delete</a>
            </div>
        </div>
        
        <hr style="margin: 20px 0;">
        
        <h3>📧 Contact Information</h3>
        <table style="margin-bottom: 20px;">
            <tr>
                <td style="width: 200px;"><strong>Email:</strong></td>
                <td><?php echo htmlspecialchars($contact['email'] ?? 'Not provided'); ?></td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td><?php echo htmlspecialchars($contact['phone'] ?? 'Not provided'); ?></td>
            </tr>
            <tr>
                <td><strong>Company:</strong></td>
                <td><?php echo htmlspecialchars($contact['company'] ?? 'Not provided'); ?></td>
            </tr>
        </table>
        
        <h3>📁 Groups</h3>
        <div style="margin-bottom: 20px;">
            <?php if ($groups->num_rows > 0): ?>
                <?php while ($group = $groups->fetch_assoc()): ?>
                    <span class="badge badge-group"><?php echo htmlspecialchars($group['group_name']); ?></span>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Not assigned to any group. <a href="../groups/assign.php?contact_id=<?php echo $contact_id; ?>">Assign now</a></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($contact['notes'])): ?>
            <h3>📝 Notes</h3>
            <p style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <?php echo nl2br(htmlspecialchars($contact['notes'])); ?>
            </p>
        <?php endif; ?>
        
        <hr style="margin: 20px 0;">
        
        <div style="color: #888; font-size: 0.9em;">
            <p><strong>Created:</strong> <?php echo date('F j, Y g:i A', strtotime($contact['created_at'])); ?></p>
            <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($contact['updated_at'])); ?></p>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="list.php" class="btn-small btn-view">← Back to Contacts</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>