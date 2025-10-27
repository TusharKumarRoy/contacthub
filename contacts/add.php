<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get all groups for assignment
$sql_groups = "SELECT group_id, group_name FROM contact_groups_table WHERE user_id = ? ORDER BY group_name";
$stmt = $conn->prepare($sql_groups);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$groups = $stmt->get_result();

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
    // Validation
    if (empty($first_name) || empty($last_name)) {
        $error = "First name and last name are required!";
=======
    // Convert empty strings to NULL for optional fields only
    $company = empty($company) ? NULL : $company;
    $notes = empty($notes) ? NULL : $notes;
    
    // Validation
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
        // INSERT INTO contacts
        $sql = "INSERT INTO contacts (user_id, first_name, last_name, email, phone, company, notes, is_favorite) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssi", $user_id, $first_name, $last_name, $email, $phone, $company, $notes, $is_favorite);
        
        if ($stmt->execute()) {
            $contact_id = $conn->insert_id;
            
            // Insert into contact_group_members (many-to-many)
            if (!empty($selected_groups)) {
                $sql_group = "INSERT INTO contact_group_members (contact_id, group_id) VALUES (?, ?)";
                $stmt_group = $conn->prepare($sql_group);
                
                foreach ($selected_groups as $group_id) {
                    $stmt_group->bind_param("ii", $contact_id, $group_id);
                    $stmt_group->execute();
                }
            }
            
            $success = "Contact added successfully!";
            header("Location: view.php?id=" . $contact_id);
            exit();
        } else {
            $error = "Failed to add contact. Please try again.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h1>➕ Add New Contact</h1>
    
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
                <span class="sql-label">SQL Concepts: INSERT INTO + Many-to-Many Relationship</span>
                <div class="sql-query">-- Step 1: Insert new contact
INSERT INTO contacts 
  (user_id, first_name, last_name, email, 
   phone, company, notes, is_favorite) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)

-- Step 2: Get auto-generated contact_id
$contact_id = $conn->insert_id;

-- Step 3: Assign to groups (many-to-many)
INSERT INTO contact_group_members 
  (contact_id, group_id) 
VALUES (?, ?)

-- Multiple INSERT statements for each group
-- Junction table links contacts ↔ groups</div>
            </div>
        </div>
        
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
        <form method="POST" action="">
            <h3>Basic Information</h3>
            
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="example@email.com">
<<<<<<< HEAD
=======
                <small style="color: #6b7280; font-size: 12px;">Leave empty if not available</small>
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
            </div>
            
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" placeholder="123-456-7890">
            </div>
            
            <h3>Company Information</h3>
            
            <div class="form-group">
                <label for="company">Company</label>
                <input type="text" id="company" name="company" placeholder="Company name">
            </div>
            
            <h3>Additional Details</h3>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Additional information..."></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_favorite">
                    ⭐ Mark as Favorite
                </label>
            </div>
            
            <h3>Assign to Groups</h3>
            
            <div class="form-group">
                <?php 
                // Reset pointer
                $groups->data_seek(0);
                if ($groups->num_rows > 0): 
                ?>
                    <?php while ($group = $groups->fetch_assoc()): ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="groups[]" value="<?php echo $group['group_id']; ?>">
                            <?php echo htmlspecialchars($group['group_name']); ?>
                        </label>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No groups available. <a href="../groups/add.php">Create a group first</a></p>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn-submit" style="width: auto; padding: 10px 30px;">💾 Save Contact</button>
                <a href="list.php" class="btn-small btn-delete" style="margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
<<<<<<< HEAD
=======
    
    <div style="margin-top: 20px; padding: 20px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 8px;">
        <h3 style="color: #065f46; margin-bottom: 10px;">💡 Understanding Many-to-Many Relationships</h3>
        <div style="color: #047857; line-height: 1.8;">
            <p><strong>Junction Table:</strong> The <code>contact_group_members</code> table connects contacts with groups.</p>
            <p style="margin-top: 10px;"><strong>One contact can belong to many groups.</strong> One group can have many contacts.</p>
            <p style="margin-top: 10px;"><strong>Primary Key:</strong> Composite key (contact_id, group_id) prevents duplicate assignments.</p>
        </div>
    </div>
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
</div>

<?php include '../includes/footer.php'; ?>