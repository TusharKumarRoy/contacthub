<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Get search and filter parameters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$company_filter = isset($_GET['company']) ? clean_input($_GET['company']) : '';
$group_filter = isset($_GET['group']) ? clean_input($_GET['group']) : '';
$favorite_only = isset($_GET['favorite']) ? 1 : 0;
$sort_by = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'first_name';
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';

// Build query with WHERE, LIKE, ORDER BY
$sql = "SELECT DISTINCT c.contact_id, c.first_name, c.last_name, c.email, c.phone, c.company, c.is_favorite 
        FROM contacts c
        LEFT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id
        WHERE c.user_id = ?";

$params = [$user_id];
$types = "i";

// LIKE with wildcard for search
if (!empty($search)) {
    $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

// Filter by company
if (!empty($company_filter)) {
    $sql .= " AND c.company = ?";
    $params[] = $company_filter;
    $types .= "s";
}

// Filter by group (using IN with subquery concept)
if (!empty($group_filter)) {
    $sql .= " AND cgm.group_id = ?";
    $params[] = $group_filter;
    $types .= "i";
}

// Filter favorites only
if ($favorite_only) {
    $sql .= " AND c.is_favorite = 1";
}

// ORDER BY
$sql .= " ORDER BY c.$sort_by $order";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$contacts = $stmt->get_result();

// Get all companies for filter (DISTINCT)
$sql_companies = "SELECT DISTINCT company FROM contacts WHERE user_id = ? AND company IS NOT NULL ORDER BY company";
$stmt_companies = $conn->prepare($sql_companies);
$stmt_companies->bind_param("i", $user_id);
$stmt_companies->execute();
$companies = $stmt_companies->get_result();

// Get all groups for filter
$sql_groups = "SELECT group_id, group_name FROM contact_groups_table WHERE user_id = ? ORDER BY group_name";
$stmt_groups = $conn->prepare($sql_groups);
$stmt_groups->bind_param("i", $user_id);
$stmt_groups->execute();
$groups = $stmt_groups->get_result();

include '../includes/header.php';
?>

<div class="container">
    <h1>📇 My Contacts</h1>
    
    <!-- Search and Filter Form -->
    <div class="table-container" style="margin-bottom: 20px;">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                
                <!-- Search Box (LIKE) -->
                <div class="form-group">
                    <label>🔍 Search</label>
                    <input type="text" name="search" placeholder="Name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <!-- Company Filter -->
                <div class="form-group">
                    <label>🏢 Company</label>
                    <select name="company">
                        <option value="">All Companies</option>
                        <?php while ($comp = $companies->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($comp['company']); ?>" 
                                <?php echo $company_filter == $comp['company'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($comp['company']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Group Filter -->
                <div class="form-group">
                    <label>📁 Group</label>
                    <select name="group">
                        <option value="">All Groups</option>
                        <?php while ($grp = $groups->fetch_assoc()): ?>
                            <option value="<?php echo $grp['group_id']; ?>" 
                                <?php echo $group_filter == $grp['group_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grp['group_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Sort By -->
                <div class="form-group">
                    <label>📊 Sort By</label>
                    <select name="sort">
                        <option value="first_name" <?php echo $sort_by == 'first_name' ? 'selected' : ''; ?>>First Name</option>
                        <option value="last_name" <?php echo $sort_by == 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                        <option value="company" <?php echo $sort_by == 'company' ? 'selected' : ''; ?>>Company</option>
                        <option value="created_at" <?php echo $sort_by == 'created_at' ? 'selected' : ''; ?>>Date Added</option>
                    </select>
                </div>
                
                <!-- Order -->
                <div class="form-group">
                    <label>⬆️ Order</label>
                    <select name="order">
                        <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <label>
                    <input type="checkbox" name="favorite" <?php echo $favorite_only ? 'checked' : ''; ?>>
                    ⭐ Favorites Only
                </label>
                <button type="submit" class="btn-submit" style="width: auto; padding: 8px 20px;">Apply Filters</button>
                <a href="list.php" class="btn-small btn-edit">Clear</a>
                <a href="add.php" class="btn-small btn-view" style="margin-left: auto;">➕ Add New Contact</a>
            </div>
        </form>
    </div>
    
    <!-- Contacts Table -->
    <div class="table-container">
        <h2>Contacts (<?php echo $contacts->num_rows; ?> found)</h2>
        
        <?php if ($contacts->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($contact = $contacts->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?>
                                <?php if ($contact['is_favorite']): ?>
                                    <span class="badge badge-favorite">⭐</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($contact['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($contact['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($contact['company'] ?? 'N/A'); ?></td>
                            <td><span class="badge badge-group">Active</span></td>
                            <td>
                                <a href="view.php?id=<?php echo $contact['contact_id']; ?>" class="btn-small btn-view">View</a>
                                <a href="edit.php?id=<?php echo $contact['contact_id']; ?>" class="btn-small btn-edit">Edit</a>
                                <a href="delete.php?id=<?php echo $contact['contact_id']; ?>" class="btn-small btn-delete" 
                                   onclick="return confirm('Delete this contact?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No contacts found. <a href="add.php">Add your first contact</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>