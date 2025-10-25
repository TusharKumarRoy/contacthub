<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = clean_input($_POST['group_name']);
    
    if (empty($group_name)) {
        $error = "Group name is required!";
    } else {
        // INSERT new group
        $sql = "INSERT INTO contact_groups_table (user_id, group_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $group_name);
        
        if ($stmt->execute()) {
            $group_id = $conn->insert_id;
            header("Location: list.php");
            exit();
        } else {
            $error = "Failed to create group. Please try again.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>➕ Create New Group</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="table-container">
        <form method="POST" action="">
            <div class="form-group">
                <label for="group_name">Group Name *</label>
                <input type="text" id="group_name" name="group_name" 
                       placeholder="e.g., Family, Work, Friends" required>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn-submit" style="width: auto; padding: 10px 30px;">💾 Create Group</button>
                <a href="list.php" class="btn-small btn-delete" style="margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>