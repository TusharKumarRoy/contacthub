<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

// Verify group belongs to user
$sql_group = "SELECT group_name FROM contact_groups_table WHERE group_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql_group);
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: list.php");
    exit();
}

$group = $result->fetch_assoc();

// Handle add/remove actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_contact'])) {
        $contact_id = (int)$_POST['contact_id'];
        
        // INSERT into junction table
        $sql_add = "INSERT IGNORE INTO contact_group_members (contact_id, group_id) VALUES (?, ?)";
        $stmt_add = $conn->prepare($sql_add);
        $stmt_add->bind_param("ii", $contact_id, $group_id);
        
        if ($stmt_add->execute()) {
            $message = "Contact added to group!";
        }
    } elseif (isset($_POST['remove_contact'])) {
        $contact_id = (int)$_POST['contact_id'];
        
        // DELETE from junction table
        $sql_remove = "DELETE FROM contact_group_members WHERE contact_id = ? AND group_id = ?";
        $stmt_remove = $conn->prepare($sql_remove);
        $stmt_remove->bind_param("ii", $contact_id, $group_id);
        
        if ($stmt_remove->execute()) {
            $message = "Contact removed from group!";
        }
    }
}

// Get contacts NOT IN this group (using NOT IN or LEFT JOIN with NULL)
$sql_available = "SELECT c.contact_id, c.first_name, c.last_name, c.company
                  FROM contacts c
                  WHERE c.user_id = ? 
                  AND c.contact_id NOT IN (
                      SELECT contact_id FROM contact_group_members WHERE group_id = ?
                  )
                  ORDER BY c.first_name, c.last_name";
$stmt_available = $conn->prepare($sql_available);
$stmt_available->bind_param("ii", $user_id, $group_id);
$stmt_available->execute();
$available_contacts = $stmt_available->get_result();

// Get contacts IN this group (using INNER JOIN)
$sql_members = "SELECT c.contact_id, c.first_name, c.last_name, c.company
                FROM contacts c
                INNER JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id
                WHERE cgm.group_id = ?
                ORDER BY c.first_name, c.last_name";
$stmt_members = $conn->prepare($sql_members);
$stmt_members->bind_param("i", $group_id);
$stmt_members->execute();
$current_members = $stmt_members->get_result();

include '../includes/header.php';
?>

<div class="container">
    <h1>👥 Manage Group Members: <?php echo htmlspecialchars($group['group_name']); ?></h1>
    
    <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- Available Contacts (NOT IN group) -->
        <div class="table-container">
            <h2>Available Contacts (<?php echo $available_contacts->num_rows; ?>)</h2>
            
            <?php if ($available_contacts->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($contact = $available_contacts->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['company'] ?? 'N/A'); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="contact_id" value="<?php echo $contact['contact_id']; ?>">
                                        <button type="submit" name="add_contact" class="btn-small btn-view">
                                            ➕ Add
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>All contacts are already in this group!</p>
            <?php endif; ?>
        </div>
        
        <!-- Current Members (IN group) -->
        <div class="table-container">
            <h2>Current Members (<?php echo $current_members->num_rows; ?>)</h2>
            
            <?php if ($current_members->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $current_members->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['company'] ?? 'N/A'); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="contact_id" value="<?php echo $member['contact_id']; ?>">
                                        <button type="submit" name="remove_contact" class="btn-small btn-delete">
                                            ❌ Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No members in this group yet. Add contacts from the left.</p>
            <?php endif; ?>
        </div>
        
    </div>
    
    <div style="margin-top: 20px;">
        <a href="list.php" class="btn-small btn-view">← Back to Groups</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>