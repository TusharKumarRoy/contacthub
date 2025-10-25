<?php
session_start();
require_once 'config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get statistics using aggregate functions
// COUNT total contacts
$sql_total = "SELECT COUNT(*) as total FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_contacts = $stmt->get_result()->fetch_assoc()['total'];

// COUNT favorites using SUM
$sql_favorites = "SELECT SUM(is_favorite) as favorites FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql_favorites);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_favorites = $stmt->get_result()->fetch_assoc()['favorites'] ?? 0;

// COUNT groups
$sql_groups = "SELECT COUNT(*) as total FROM contact_groups_table WHERE user_id = ?";
$stmt = $conn->prepare($sql_groups);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_groups = $stmt->get_result()->fetch_assoc()['total'];

// Get recent contacts (ORDER BY with LIMIT)
$sql_recent = "SELECT contact_id, first_name, last_name, email, company, phone, is_favorite 
               FROM contacts 
               WHERE user_id = ? 
               ORDER BY created_at DESC 
               LIMIT 5";
$stmt = $conn->prepare($sql_recent);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_contacts = $stmt->get_result();

// Get contacts by company (GROUP BY with COUNT)
$sql_companies = "SELECT company, COUNT(*) as count 
                  FROM contacts 
                  WHERE user_id = ? AND company IS NOT NULL
                  GROUP BY company 
                  ORDER BY count DESC 
                  LIMIT 5";
$stmt = $conn->prepare($sql_companies);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$companies = $stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>! 👋</h1>
    
    <!-- Statistics Cards -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">-- SQL Concept: COUNT(*)

SELECT COUNT(*) as total 
FROM contacts 
WHERE user_id = ?

-- Counts all contact rows for this user
-- Returns total number of contacts</div>
            </div>
            <div class="icon">📇</div>
            <div class="number"><?php echo $total_contacts; ?></div>
            <div class="label">Total Contacts</div>
        </div>
        
        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">-- SQL Concept: SUM()

SELECT SUM(is_favorite) as favorites 
FROM contacts 
WHERE user_id = ?

-- is_favorite is boolean (0 or 1)
-- SUM adds up all 1s = count of favorites
-- More efficient than COUNT with WHERE</div>
            </div>
            <div class="icon">⭐</div>
            <div class="number"><?php echo $total_favorites; ?></div>
            <div class="label">Favorites</div>
        </div>
        
        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">-- SQL Concept: COUNT with WHERE

SELECT COUNT(*) as total 
FROM contact_groups_table 
WHERE user_id = ?

-- Counts groups owned by this user
-- Filters by user_id before counting</div>
            </div>
            <div class="icon">📁</div>
            <div class="number"><?php echo $total_groups; ?></div>
            <div class="label">Groups</div>
        </div>
    </div>
    
    <!-- Recent Contacts -->
    <div class="table-container">
        <div class="sql-info-icon">
            ℹ️
            <div class="sql-tooltip">-- SQL Concept: ORDER BY + LIMIT

SELECT contact_id, first_name, last_name, 
       email, company, phone, is_favorite 
FROM contacts 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 5

-- ORDER BY: Sorts by creation date
-- DESC: Newest first
-- LIMIT 5: Returns only first 5 results
-- Shows most recently added contacts</div>
        </div>
        <h2>Recent Contacts</h2>
        <?php if ($recent_contacts->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($contact = $recent_contacts->fetch_assoc()): ?>
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
                            <td>
                                <a href="contacts/view.php?id=<?php echo $contact['contact_id']; ?>" class="btn-small btn-view">View</a>
                                <a href="contacts/edit.php?id=<?php echo $contact['contact_id']; ?>" class="btn-small btn-edit">Edit</a>
                                <a href="contacts/delete.php?id=<?php echo $contact['contact_id']; ?>" class="btn-small btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No contacts yet. <a href="contacts/add.php">Add your first contact</a></p>
        <?php endif; ?>
    </div>
    
    <!-- Top Companies -->
    <?php if ($companies->num_rows > 0): ?>
        <div class="table-container" style="margin-top: 30px;">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">-- SQL Concept: GROUP BY + COUNT + ORDER BY

SELECT company, COUNT(*) as count 
FROM contacts 
WHERE user_id = ? 
  AND company IS NOT NULL
GROUP BY company 
ORDER BY count DESC 
LIMIT 5

-- GROUP BY: Groups contacts by company name
-- COUNT(*): Counts contacts in each group
-- ORDER BY count DESC: Highest counts first
-- LIMIT 5: Top 5 companies only
-- Shows which companies have most contacts</div>
            </div>
            <h2>Contacts by Company</h2>
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Number of Contacts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($company = $companies->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['company']); ?></td>
                            <td><strong><?php echo $company['count']; ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>