<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$contact_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Get contact details
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

// Get all groups
$sql_groups = "SELECT group_id, group_name FROM contact_groups_table WHERE user_id = ? ORDER BY group_name";
$stmt_groups = $conn->prepare($sql_groups);
$stmt_groups->bind_param("i", $user_id);
$stmt_groups->execute();
$groups = $stmt_groups->get_result();

// Get current group assignments
$sql_current = "SELECT group_id FROM contact_group_members WHERE contact_id = ?";
$stmt_current = $conn->prepare($sql_current);
$stmt_current->bind_param("i", $contact_id);
$stmt_current->execute();
$current_groups_result = $stmt_current->get_result();
$current_groups = [];
while ($row = $current_groups_result->fetch_assoc()) {
    $current_groups[] = $row['group_id'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $company = clean_input($_POST['company']);
    $notes = clean_input($_POST['notes']);
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    $selected_groups = isset($_POST['groups']) ? $_POST['groups'] : [];
    
<<<<<<< HEAD
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required!";
=======
    // Convert empty strings to NULL for optional fields only
    $company = empty($company) ? NULL : $company;
    $notes = empty($notes) ? NULL : $notes;
    
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required!";
    } elseif (empty($email)) {
        $error = "Email is required!";
    } elseif (strpos($email, '@') === false) {
        $error = "Please enter a valid email address with @ symbol!";
    } elseif (empty($phone)) {
        $error = "Phone number is required!";
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
    } else {
        // UPDATE contact
        $sql_update = "UPDATE contacts 
                       SET first_name = ?, last_name = ?, email = ?, phone = ?, 
<<<<<<< HEAD
                           company = ?, notes = ?, is_favorite = ?
=======
                           company = ?, notes = ?, is_favorite = ?, updated_at = NOW()
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
                       WHERE contact_id = ? AND user_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssssiii", $first_name, $last_name, $email, $phone, 
                                  $company, $notes, $is_favorite, $contact_id, $user_id);
        
        if ($stmt_update->execute()) {
            // Update group assignments
            // First, DELETE all current assignments
            $sql_delete_groups = "DELETE FROM contact_group_members WHERE contact_id = ?";
            $stmt_delete = $conn->prepare($sql_delete_groups);
            $stmt_delete->bind_param("i", $contact_id);
            $stmt_delete->execute();
            
            // Then INSERT new assignments
            if (!empty($selected_groups)) {
                $sql_insert_group = "INSERT INTO contact_group_members (contact_id, group_id) VALUES (?, ?)";
                $stmt_insert = $conn->prepare($sql_insert_group);
                
                foreach ($selected_groups as $group_id) {
                    $stmt_insert->bind_param("ii", $contact_id, $group_id);
                    $stmt_insert->execute();
                }
            }
            
            $success = "Contact updated successfully!";
            header("Location: view.php?id=" . $contact_id);
            exit();
        } else {
            $error = "Failed to update contact.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>✏️ Edit Contact</h1>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="table-container">
<<<<<<< HEAD
=======
        <div class="sql-info-icon">
            ℹ️
            <div class="sql-tooltip">
                <span class="sql-label">SQL Concepts: UPDATE + DELETE + INSERT Pattern</span>
                <div class="sql-query">-- Step 1: Update contact information
UPDATE contacts 
SET first_name = ?, last_name = ?, 
    email = ?, phone = ?, company = ?, 
    notes = ?, is_favorite = ?
WHERE contact_id = ? AND user_id = ?

-- Step 2: Remove all current group assignments
DELETE FROM contact_group_members 
WHERE contact_id = ?

-- Step 3: Insert new group assignments
INSERT INTO contact_group_members 
  (contact_id, group_id) 
VALUES (?, ?)

-- This pattern ensures group assignments
-- are completely synchronized with form data</div>
            </div>
        </div>
        
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
        <form method="POST" action="">
            <h3>Basic Information</h3>
            
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" 
                       value="<?php echo htmlspecialchars($contact['first_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" 
                       value="<?php echo htmlspecialchars($contact['last_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>">
<<<<<<< HEAD
=======
                <small style="color: #6b7280; font-size: 12px;">Leave empty if not available</small>
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
            </div>
            
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($contact['phone'] ?? ''); ?>">
            </div>
            
            <h3>Company Information</h3>
            
            <div class="form-group">
                <label for="company">Company</label>
                <input type="text" id="company" name="company" 
                       value="<?php echo htmlspecialchars($contact['company'] ?? ''); ?>">
            </div>
            
            <h3>Additional Details</h3>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($contact['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_favorite" <?php echo $contact['is_favorite'] ? 'checked' : ''; ?>>
                    ⭐ Mark as Favorite
                </label>
            </div>
            
            <h3>Assign to Groups</h3>
            
            <div class="form-group">
                <?php 
                $groups->data_seek(0);
                if ($groups->num_rows > 0): 
                ?>
                    <?php while ($group = $groups->fetch_assoc()): ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="groups[]" value="<?php echo $group['group_id']; ?>"
                                <?php echo in_array($group['group_id'], $current_groups) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </label>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No groups available. <a href="../groups/add.php">Create a group first</a></p>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn-submit" style="width: auto; padding: 10px 30px;">💾 Update Contact</button>
                <a href="view.php?id=<?php echo $contact_id; ?>" class="btn-small btn-delete" style="margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
<<<<<<< HEAD
=======
    
    <div style="margin-top: 20px; padding: 20px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 8px;">
        <h3 style="color: #065f46; margin-bottom: 10px;">💡 Update Strategy: Delete + Insert</h3>
        <div style="color: #047857; line-height: 1.8;">
            <p><strong>Why DELETE then INSERT?</strong> This ensures the group assignments match exactly what's selected in the form.</p>
            <p style="margin-top: 10px;"><strong>Alternative approaches:</strong> Compare old vs new and insert/delete differences, but DELETE+INSERT is simpler and safer.</p>
            <p style="margin-top: 10px;"><strong>Transaction safety:</strong> In production, this should be wrapped in a database transaction.</p>
        </div>
    </div>
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
</div>

<?php include '../includes/footer.php'; ?>