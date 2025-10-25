<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$contact_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify contact belongs to user
$sql_check = "SELECT first_name, last_name FROM contacts WHERE contact_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $contact_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$contact = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // DELETE contact (CASCADE will automatically delete from contact_group_members)
    $sql_delete = "DELETE FROM contacts WHERE contact_id = ? AND user_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $contact_id, $user_id);
    
    if ($stmt_delete->execute()) {
        header("Location: list.php?deleted=1");
        exit();
    } else {
        $error = "Failed to delete contact.";
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>🗑️ Delete Contact</h1>
    
    <div class="table-container">
        <div class="error" style="background: #fee; border-left: 4px solid #c33;">
            <h2>⚠️ Confirm Deletion</h2>
            <p>Are you sure you want to delete this contact?</p>
            
            <div style="background: #fff; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <strong>Contact:</strong> <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
            </div>
            
            <p><strong>This action will:</strong></p>
            <ul style="margin-left: 20px; margin-bottom: 20px;">
                <li>❌ Permanently delete the contact</li>
                <li>❌ Remove contact from all groups (CASCADE)</li>
                <li>⚠️ Cannot be undone</li>
            </ul>
            
            <form method="POST" action="" style="display: inline;">
                <button type="submit" class="btn-small btn-delete" style="padding: 10px 20px;">
                    Yes, Delete Forever
                </button>
            </form>
            
            <a href="view.php?id=<?php echo $contact_id; ?>" class="btn-small btn-view" style="margin-left: 10px; padding: 10px 20px;">
                No, Keep Contact
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>