<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify group belongs to user and get member count
$sql_check = "SELECT g.group_name, COUNT(cgm.contact_id) as member_count
              FROM contact_groups_table g
              LEFT JOIN contact_group_members cgm ON g.group_id = cgm.group_id
              WHERE g.group_id = ? AND g.user_id = ?
              GROUP BY g.group_id, g.group_name";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$group = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // DELETE group (CASCADE will automatically delete from contact_group_members)
    $sql_delete = "DELETE FROM contact_groups_table WHERE group_id = ? AND user_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $group_id, $user_id);
    
    if ($stmt_delete->execute()) {
        header("Location: list.php?deleted=1");
        exit();
    } else {
        $error = "Failed to delete group.";
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>🗑️ Delete Group</h1>
    
    <div class="table-container">
        <div class="error" style="background: #fee; border-left: 4px solid #c33;">
            <h2>⚠️ Confirm Deletion</h2>
            <p>Are you sure you want to delete this group?</p>
            
            <div style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <strong>Group:</strong> <?php echo htmlspecialchars($group['group_name']); ?><br>
                <strong>Members:</strong> <?php echo $group['member_count']; ?> contact<?php echo $group['member_count'] != 1 ? 's' : ''; ?>
            </div>
            
            <p><strong>This action will:</strong></p>
            <ul style="margin-left: 20px; margin-bottom: 20px;">
                <li>❌ Permanently delete the group</li>
                <li>❌ Remove all contact assignments from this group (CASCADE)</li>
                <li>✅ Contacts themselves will NOT be deleted</li>
                <li>⚠️ Cannot be undone</li>
            </ul>
            
            <form method="POST" action="" style="display: inline;">
                <button type="submit" class="btn-small btn-delete" style="padding: 10px 20px;">
                    Yes, Delete Group
                </button>
            </form>
            
            <a href="list.php" class="btn-small btn-view" style="margin-left: 10px; padding: 10px 20px;">
                No, Keep Group
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>