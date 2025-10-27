<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Get group details
$sql = "SELECT * FROM contact_groups_table WHERE group_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$group = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = clean_input($_POST['group_name']);
    
    if (empty($group_name)) {
        $error = "Group name is required!";
    } else {
        // UPDATE group
        $sql_update = "UPDATE contact_groups_table SET group_name = ? WHERE group_id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sii", $group_name, $group_id, $user_id);
        
        if ($stmt_update->execute()) {
            header("Location: list.php");
            exit();
        } else {
            $error = "Failed to update group.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>✏️ Edit Group</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="table-container">
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Group Name *</label>
                <input type="text" id="group_name" name="group_name" 
                       value="<?php echo htmlspecialchars($group['group_name']); ?>" required>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn-submit" style="width: auto; padding: 10px 30px;">💾 Update Group</button>
                <a href="list.php" class="btn-small btn-delete" style="margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>