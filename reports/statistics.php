<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];

// Total contacts (COUNT)
$sql_total = "SELECT COUNT(*) as total FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_contacts = $stmt->get_result()->fetch_assoc()['total'];

// Favorites (SUM)
$sql_fav = "SELECT SUM(is_favorite) as favorites FROM contacts WHERE user_id = ?";
$stmt = $conn->prepare($sql_fav);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_favorites = $stmt->get_result()->fetch_assoc()['favorites'] ?? 0;

// Total groups (COUNT)
$sql_groups = "SELECT COUNT(*) as total FROM contact_groups_table WHERE user_id = ?";
$stmt = $conn->prepare($sql_groups);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_groups = $stmt->get_result()->fetch_assoc()['total'];

// MAX - Most recent contact (newest)
$sql_max = "SELECT first_name, last_name, created_at 
            FROM contacts 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1";
$stmt = $conn->prepare($sql_max);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$newest_contact = $stmt->get_result()->fetch_assoc();

// MIN - Oldest contact
$sql_min = "SELECT first_name, last_name, created_at 
            FROM contacts 
            WHERE user_id = ? 
            ORDER BY created_at ASC 
            LIMIT 1";
$stmt = $conn->prepare($sql_min);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$oldest_contact = $stmt->get_result()->fetch_assoc();

// AVG - Average contacts per group
$sql_avg = "SELECT AVG(member_count) as avg_members
            FROM (
                SELECT COUNT(cgm.contact_id) as member_count
                FROM contact_groups_table g
                LEFT JOIN contact_group_members cgm ON g.group_id = cgm.group_id
                WHERE g.user_id = ?
                GROUP BY g.group_id
            ) as group_stats";
$stmt = $conn->prepare($sql_avg);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$avg_result = $stmt->get_result()->fetch_assoc();
$avg_members = $avg_result['avg_members'] ? round($avg_result['avg_members'], 2) : 0;

// MAX - Largest group
$sql_max_group = "SELECT g.group_name, COUNT(cgm.contact_id) as member_count
                  FROM contact_groups_table g
                  LEFT JOIN contact_group_members cgm ON g.group_id = cgm.group_id
                  WHERE g.user_id = ?
                  GROUP BY g.group_id, g.group_name
                  ORDER BY member_count DESC
                  LIMIT 1";
$stmt = $conn->prepare($sql_max_group);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$largest_group = $stmt->get_result()->fetch_assoc();

// MIN - Smallest group (with at least one member)
$sql_min_group = "SELECT g.group_name, COUNT(cgm.contact_id) as member_count
                  FROM contact_groups_table g
                  LEFT JOIN contact_group_members cgm ON g.group_id = cgm.group_id
                  WHERE g.user_id = ?
                  GROUP BY g.group_id, g.group_name
                  HAVING COUNT(cgm.contact_id) > 0
                  ORDER BY member_count ASC
                  LIMIT 1";
$stmt = $conn->prepare($sql_min_group);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$smallest_group = $stmt->get_result()->fetch_assoc();

// Contacts per company (GROUP BY + COUNT)
$sql_companies = "SELECT company, COUNT(*) as cnt 
                  FROM contacts 
                  WHERE user_id = ? AND company IS NOT NULL 
                  GROUP BY company 
                  ORDER BY cnt DESC 
                  LIMIT 10";
$stmt = $conn->prepare($sql_companies);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$companies = $stmt->get_result();

// Activity stats - contacts added per month (GROUP BY with date functions)
$sql_activity = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as contacts_added,
                    SUM(is_favorite) as favorites_added
                 FROM contacts 
                 WHERE user_id = ?
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC
                 LIMIT 6";
$stmt = $conn->prepare($sql_activity);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$activity = $stmt->get_result();

include '../includes/header.php';
?>

<div class="container">
    <h1>📊 Advanced Statistics Dashboard</h1>

    <!-- Basic Statistics (COUNT, SUM) -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: COUNT(*)</span>
                    <div class="sql-query">SELECT COUNT(*) as total 
FROM contacts 
WHERE user_id = ?

-- Counts all rows matching condition
-- Most basic aggregate function</div>
                </div>
            </div>
            <div class="icon">📇</div>
            <div class="number"><?php echo $total_contacts; ?></div>
            <div class="label">Total Contacts</div>
        </div>

        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: SUM()</span>
                    <div class="sql-query">SELECT SUM(is_favorite) as favorites 
FROM contacts 
WHERE user_id = ?

-- Sums boolean values (0 or 1)
-- Counts total favorites efficiently</div>
                </div>
            </div>
            <div class="icon">⭐</div>
            <div class="number"><?php echo $total_favorites; ?></div>
            <div class="label">Favorites</div>
        </div>

        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: COUNT with Filtering</span>
                    <div class="sql-query">SELECT COUNT(*) as total 
FROM contact_groups_table 
WHERE user_id = ?

-- Counts groups for specific user</div>
                </div>
            </div>
            <div class="icon">📁</div>
            <div class="number"><?php echo $total_groups; ?></div>
            <div class="label">Groups</div>
        </div>

        <div class="stat-card">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: AVG() with Subquery</span>
                    <div class="sql-query">SELECT AVG(member_count) as avg_members
FROM (
    SELECT COUNT(cgm.contact_id) as member_count
    FROM contact_groups_table g
    LEFT JOIN contact_group_members cgm 
      ON g.group_id = cgm.group_id
    WHERE g.user_id = ?
    GROUP BY g.group_id
) as group_stats

-- Calculates average contacts per group
-- Uses subquery to get counts first</div>
                </div>
            </div>
            <div class="icon">📈</div>
            <div class="number"><?php echo $avg_members; ?></div>
            <div class="label">Avg Contacts/Group</div>
        </div>
    </div>

    <!-- MAX/MIN Examples -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        
        <!-- Newest Contact (MAX) -->
        <div class="table-container">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: MAX (Most Recent Date)</span>
                    <div class="sql-query">-- Method 1: Using ORDER BY + LIMIT
SELECT first_name, last_name, created_at 
FROM contacts 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 1

-- Method 2: Using MAX directly
SELECT first_name, last_name, created_at
FROM contacts
WHERE user_id = ?
  AND created_at = (
    SELECT MAX(created_at) 
    FROM contacts 
    WHERE user_id = ?
  )

-- Both return most recent contact</div>
                </div>
            </div>
            <h2>🆕 Newest Contact (MAX)</h2>
            <?php if ($newest_contact): ?>
                <div style="padding: 20px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; color: #065f46;">
                        <?php echo htmlspecialchars($newest_contact['first_name'] . ' ' . $newest_contact['last_name']); ?>
                    </h3>
                    <p style="margin: 0; color: #047857;">
                        Added: <?php echo date('F j, Y g:i A', strtotime($newest_contact['created_at'])); ?>
                    </p>
                </div>
            <?php else: ?>
                <p>No contacts yet.</p>
            <?php endif; ?>
        </div>

        <!-- Oldest Contact (MIN) -->
        <div class="table-container">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: MIN (Earliest Date)</span>
                    <div class="sql-query">-- Method 1: Using ORDER BY + LIMIT
SELECT first_name, last_name, created_at 
FROM contacts 
WHERE user_id = ? 
ORDER BY created_at ASC 
LIMIT 1

-- Method 2: Using MIN directly
SELECT first_name, last_name, created_at
FROM contacts
WHERE user_id = ?
  AND created_at = (
    SELECT MIN(created_at) 
    FROM contacts 
    WHERE user_id = ?
  )

-- Both return oldest contact</div>
                </div>
            </div>
            <h2>⏰ Oldest Contact (MIN)</h2>
            <?php if ($oldest_contact): ?>
                <div style="padding: 20px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; color: #92400e;">
                        <?php echo htmlspecialchars($oldest_contact['first_name'] . ' ' . $oldest_contact['last_name']); ?>
                    </h3>
                    <p style="margin: 0; color: #b45309;">
                        Added: <?php echo date('F j, Y g:i A', strtotime($oldest_contact['created_at'])); ?>
                    </p>
                </div>
            <?php else: ?>
                <p>No contacts yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Group Size Analysis (MAX/MIN with GROUP BY) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        
        <!-- Largest Group (MAX) -->
        <div class="table-container">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: MAX with GROUP BY</span>
                    <div class="sql-query">SELECT g.group_name, 
       COUNT(cgm.contact_id) as member_count
FROM contact_groups_table g
LEFT JOIN contact_group_members cgm 
  ON g.group_id = cgm.group_id
WHERE g.user_id = ?
GROUP BY g.group_id, g.group_name
ORDER BY member_count DESC
LIMIT 1

-- Groups contacts by group
-- Counts members in each group
-- Returns group with MAX count</div>
                </div>
            </div>
            <h2>📊 Largest Group (MAX)</h2>
            <?php if ($largest_group): ?>
                <div style="padding: 20px; background: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; color: #1e3a8a;">
                        <?php echo htmlspecialchars($largest_group['group_name']); ?>
                    </h3>
                    <p style="margin: 0; color: #1e40af; font-size: 1.2rem; font-weight: bold;">
                        <?php echo $largest_group['member_count']; ?> members
                    </p>
                </div>
            <?php else: ?>
                <p>No groups yet.</p>
            <?php endif; ?>
        </div>

        <!-- Smallest Group (MIN) -->
        <div class="table-container">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concept: MIN with GROUP BY + HAVING</span>
                    <div class="sql-query">SELECT g.group_name, 
       COUNT(cgm.contact_id) as member_count
FROM contact_groups_table g
LEFT JOIN contact_group_members cgm 
  ON g.group_id = cgm.group_id
WHERE g.user_id = ?
GROUP BY g.group_id, g.group_name
HAVING COUNT(cgm.contact_id) > 0
ORDER BY member_count ASC
LIMIT 1

-- HAVING filters groups after grouping
-- Returns group with MIN count (>0)</div>
                </div>
            </div>
            <h2>📉 Smallest Group (MIN)</h2>
            <?php if ($smallest_group): ?>
                <div style="padding: 20px; background: #fce7f3; border-left: 4px solid #ec4899; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; color: #831843;">
                        <?php echo htmlspecialchars($smallest_group['group_name']); ?>
                    </h3>
                    <p style="margin: 0; color: #9f1239; font-size: 1.2rem; font-weight: bold;">
                        <?php echo $smallest_group['member_count']; ?> member<?php echo $smallest_group['member_count'] != 1 ? 's' : ''; ?>
                    </p>
                </div>
            <?php else: ?>
                <p>No groups with members yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Companies (GROUP BY + COUNT + ORDER BY) -->
    <div class="table-container" style="margin-top: 30px;">
        <div class="sql-info-icon">
            ℹ️
            <div class="sql-tooltip">
                <span class="sql-label">SQL Concepts: GROUP BY + COUNT + ORDER BY + LIMIT</span>
                <div class="sql-query">SELECT company, COUNT(*) as cnt 
FROM contacts 
WHERE user_id = ? 
  AND company IS NOT NULL
GROUP BY company 
ORDER BY cnt DESC 
LIMIT 10

-- Groups contacts by company
-- Counts contacts per company
-- Orders by count (highest first)
-- Limits to top 10 results</div>
            </div>
        </div>
        
        <h2>🏢 Top Companies by Contact Count</h2>
        <?php if ($companies->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contacts</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $companies->fetch_assoc()): ?>
                        <?php $percentage = $total_contacts > 0 ? round(($c['cnt'] / $total_contacts) * 100, 1) : 0; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['company']); ?></td>
                            <td><strong><?php echo $c['cnt']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e5e7eb; border-radius: 999px; height: 8px; overflow: hidden;">
                                        <div style="background: #10b981; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                    <span><?php echo $percentage; ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No company data yet.</p>
        <?php endif; ?>
    </div>

    <!-- Activity Timeline (GROUP BY with Date Functions) -->
    <?php if ($activity->num_rows > 0): ?>
        <div class="table-container" style="margin-top: 30px;">
            <div class="sql-info-icon">
                ℹ️
                <div class="sql-tooltip">
                    <span class="sql-label">SQL Concepts: DATE_FORMAT + GROUP BY + Multiple Aggregates</span>
                    <div class="sql-query">SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as contacts_added,
    SUM(is_favorite) as favorites_added
FROM contacts 
WHERE user_id = ?
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month DESC
LIMIT 6

-- DATE_FORMAT extracts year-month
-- Groups by month
-- Multiple aggregate functions
-- Shows activity over time</div>
                </div>
            </div>
            
            <h2>📅 Activity Timeline (Last 6 Months)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Contacts Added</th>
                        <th>Favorites Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($act = $activity->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($act['month'] . '-01')); ?></td>
                            <td><strong><?php echo $act['contacts_added']; ?></strong></td>
                            <td>⭐ <?php echo $act['favorites_added']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    
</div>

<?php include '../includes/footer.php'; ?>