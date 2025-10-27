<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Get all groups with member count (GROUP BY with COUNT)
$sql = "SELECT 
            g.group_id, 
            g.group_name, 
            g.created_at,
            COUNT(cgm.contact_id) as member_count
        FROM contact_groups_table g
        LEFT JOIN contact_group_members cgm ON g.group_id = cgm.group_id
        WHERE g.user_id = ?
        GROUP BY g.group_id, g.group_name, g.created_at
        ORDER BY g.group_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$groups = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container">
    <h1>📁 My Groups</h1>
    
    <div style="margin-bottom: 20px;">
        <a href="add.php" class="btn-small btn-view" style="padding: 10px 20px;">➕ Create New Group</a>
    </div>
    
    <div class="table-container">
        <h2>Groups (<?php echo $groups->num_rows; ?> total)</h2>
        
        <?php if ($groups->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Members</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($group = $groups->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($group['group_name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-group">
                                    <?php echo $group['member_count']; ?> contact<?php echo $group['member_count'] != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($group['created_at'])); ?></td>
                            <td>
                                <a href="assign.php?id=<?php echo $group['group_id']; ?>" class="btn-small btn-view">Manage Members</a>
                                <a href="edit.php?id=<?php echo $group['group_id']; ?>" class="btn-small btn-edit">Edit</a>
<<<<<<< HEAD
                                <a href="delete.php?id=<?php echo $group['group_id']; ?>" class="btn-small btn-delete" 
                                   onclick="return confirm('Delete this group? (Contacts will not be deleted)')">Delete</a>
=======
                                <a href="delete.php?id=<?php echo $group['group_id']; ?>" class="btn-small btn-delete">Delete</a>
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No groups yet. <a href="add.php">Create your first group</a></p>
        <?php endif; ?>
    </div>
    
    <!-- Statistics Section (using HAVING) -->
    <?php
    // Get groups with more than 2 members (using HAVING clause)
    $sql_popular = "SELECT 
                        g.group_name, 
                        COUNT(cgm.contact_id) as member_count
                    FROM contact_groups_table g
                    LEFT JOIN contact_group_members cgm ON g.group_id = cgm.group_id
                    WHERE g.user_id = ?
                    GROUP BY g.group_id, g.group_name
                    HAVING COUNT(cgm.contact_id) > 2
                    ORDER BY member_count DESC";
    
    $stmt_popular = $conn->prepare($sql_popular);
    $stmt_popular->bind_param("i", $user_id);
    $stmt_popular->execute();
    $popular_groups = $stmt_popular->get_result();
    ?>
    
    <?php if ($popular_groups->num_rows > 0): ?>
        <div class="table-container" style="margin-top: 30px;">
            <h2>📊 Popular Groups (More than 2 members)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Members</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pop = $popular_groups->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pop['group_name']); ?></td>
                            <td><strong><?php echo $pop['member_count']; ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>