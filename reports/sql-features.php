<?php
session_start();
require_once '../config/database.php';
require_login();

$user_id = $_SESSION['user_id'];
$notice = '';
$error = '';
$output = null;
$sql_display = '';
$current_action = '';

// Retrieve results from session if redirected from POST
if (isset($_SESSION['sql_result'])) {
    $result_data = $_SESSION['sql_result'];
    $output = $result_data['output'] ?? null;
    $sql_display = $result_data['sql_display'] ?? '';
    $error = $result_data['error'] ?? '';
    $current_action = $result_data['action'] ?? '';
    unset($_SESSION['sql_result']); // Clear after use
}

function render_table($result) {
    if (!$result) return '<div class="no-results"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><p>No result</p></div>';
    if ($result->num_rows == 0) return '<div class="no-results"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg><p>No rows returned</p></div>';
    
    $html = '<div class="result-table-wrapper"><table class="result-table"><thead><tr>';
    $first = $result->fetch_assoc();
    $cols = array_keys($first);
    foreach ($cols as $c) { $html .= '<th>' . htmlspecialchars($c) . '</th>'; }
    $html .= '</tr></thead><tbody>';
    $html .= '<tr>';
    foreach ($first as $v) { $html .= '<td>' . htmlspecialchars((string)$v) . '</td>'; }
    $html .= '</tr>';
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        foreach ($cols as $c) { $html .= '<td>' . htmlspecialchars((string)$row[$c]) . '</td>'; }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}

function render_table_from_array($data) {
    if (!$data || !is_array($data) || count($data) == 0) {
        return '<div class="no-results"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg><p>No rows returned</p></div>';
    }
    
    $html = '<div class="result-table-wrapper"><table class="result-table"><thead><tr>';
    $cols = array_keys($data[0]);
    foreach ($cols as $c) { $html .= '<th>' . htmlspecialchars($c) . '</th>'; }
    $html .= '</tr></thead><tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($cols as $c) { $html .= '<td>' . htmlspecialchars((string)($row[$c] ?? '')) . '</td>'; }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result_output = null;
    $result_sql = '';
    $result_error = '';

    if ($action === 'full_outer') {
        $left = isset($_POST['left']);
        $right = isset($_POST['right']);
        if (!$left && !$right) {
            $result_error = 'Select at least left or right side.';
        } else {
            if ($left && $right) {
                $sql = "(SELECT c.contact_id, c.first_name, c.last_name, g.group_id, g.group_name FROM contacts c LEFT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id LEFT JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ? WHERE c.user_id = ?) UNION (SELECT c.contact_id, c.first_name, c.last_name, g.group_id, g.group_name FROM contacts c RIGHT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id RIGHT JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ? WHERE c.user_id = ?)";
                $result_sql = $sql;
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
                $stmt->execute();
                $result_output = $stmt->get_result();
            } elseif ($left) {
                $sql = "SELECT c.contact_id, c.first_name, c.last_name, g.group_id, g.group_name FROM contacts c LEFT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id LEFT JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ? WHERE c.user_id = ?";
                $result_sql = $sql;
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $user_id, $user_id);
                $stmt->execute();
                $result_output = $stmt->get_result();
            } else {
                $sql = "SELECT c.contact_id, c.first_name, c.last_name, g.group_id, g.group_name FROM contacts c RIGHT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id RIGHT JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ? WHERE c.user_id = ?";
                $result_sql = $sql;
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $user_id, $user_id);
                $stmt->execute();
                $result_output = $stmt->get_result();
            }
        }
    }

    if ($action === 'union_examples') {
        $union_all = isset($_POST['union_all']) ? true : false;
        $op = $union_all ? 'UNION ALL' : 'UNION';
        $sql = "SELECT DISTINCT company AS value, 'company' AS source FROM contacts WHERE user_id = ? AND company IS NOT NULL $op SELECT DISTINCT group_name AS value, 'group' AS source FROM contact_groups_table WHERE user_id = ? LIMIT 100";
        $result_sql = $sql;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $result_output = $stmt->get_result();
    }

    if ($action === 'intersect') {
        $group_id = (int)($_POST['group_id'] ?? 0);
        if ($group_id <= 0) { $result_error = 'Select a group.'; }
        else {
            $sql = "SELECT c.contact_id, c.first_name, c.last_name, g.group_name FROM contacts c INNER JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id INNER JOIN contact_groups_table g ON cgm.group_id = g.group_id AND g.user_id = ? WHERE c.user_id = ? AND g.group_id = ? ORDER BY c.first_name, c.last_name LIMIT 200";
            $result_sql = $sql;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $user_id, $group_id);
            $stmt->execute();
            $result_output = $stmt->get_result();
        }
    }

    if ($action === 'minus') {
        $method = $_POST['minus_method'] ?? 'left';
        if ($method === 'left') {
            $sql = "SELECT c.contact_id, c.first_name, c.last_name FROM contacts c LEFT JOIN contact_group_members cgm ON c.contact_id = cgm.contact_id WHERE cgm.contact_id IS NULL AND c.user_id = ? ORDER BY c.first_name LIMIT 200";
            $result_sql = $sql;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result_output = $stmt->get_result();
        } else {
            $sql = "SELECT contact_id, first_name, last_name FROM contacts WHERE user_id = ? AND contact_id NOT IN (SELECT contact_id FROM contact_group_members) ORDER BY first_name LIMIT 200";
            $result_sql = $sql;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result_output = $stmt->get_result();
        }
    }

    if ($action === 'coalesce') {
        $col = 'phone';
        $fallback = clean_input($_POST['fallback'] ?? '—');
        $sql = "SELECT contact_id, first_name, last_name, COALESCE($col, ?) AS phone FROM contacts WHERE user_id = ? LIMIT 200";
        $result_sql = $sql;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $fallback, $user_id);
        $stmt->execute();
        $result_output = $stmt->get_result();
    }

    if ($action === 'between') {
        $min = (int)($_POST['min'] ?? 1);
        $max = (int)($_POST['max'] ?? 5);
        if ($min > $max) { $result_error = 'Min must be <= Max'; }
        else {
            $sql = "SELECT contact_id, first_name, last_name FROM contacts WHERE user_id = ? AND contact_id BETWEEN ? AND ?";
            $result_sql = $sql;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $min, $max);
            $stmt->execute();
            $result_output = $stmt->get_result();
        }
    }

    // Convert result to array for session storage
    $output_data = null;
    if ($result_output && $result_output->num_rows > 0) {
        $output_data = [];
        while ($row = $result_output->fetch_assoc()) {
            $output_data[] = $row;
        }
    } else if ($result_output) {
        $output_data = [];
    }

    // Store in session and redirect (POST-Redirect-GET pattern)
    $_SESSION['sql_result'] = [
        'output' => $output_data,
        'sql_display' => $result_sql,
        'error' => $result_error,
        'action' => $action
    ];
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

include '../includes/header.php';

$active_tables = [];
if ($current_action) {
    switch ($current_action) {
        case 'full_outer': $active_tables = ['contacts','contact_group_members','contact_groups_table']; break;
        case 'union_examples': $active_tables = ['contacts','contact_groups_table']; break;
        case 'intersect': $active_tables = ['contacts','contact_group_members','contact_groups_table']; break;
        case 'minus': $active_tables = ['contacts','contact_group_members']; break;
        case 'coalesce':
        case 'between': $active_tables = ['contacts']; break;
    }
}

$tables_to_show = ['users','contacts','contact_groups_table','contact_group_members'];
$tables_snapshot = [];
foreach ($tables_to_show as $tbl) {
    $rows = [];
    $cols = [];
    if ($tbl === 'contacts') {
        $q = $conn->prepare('SELECT * FROM contacts WHERE user_id = ? ORDER BY contact_id ASC');
        $q->bind_param('i', $user_id);
    } elseif ($tbl === 'contact_groups_table') {
        $q = $conn->prepare('SELECT * FROM contact_groups_table WHERE user_id = ? ORDER BY group_id ASC');
        $q->bind_param('i', $user_id);
    } elseif ($tbl === 'contact_group_members') {
        $q = $conn->prepare('SELECT cgm.* FROM contact_group_members cgm JOIN contacts c ON c.contact_id = cgm.contact_id WHERE c.user_id = ? ORDER BY cgm.group_id ASC');
        $q->bind_param('i', $user_id);
    } else {
        $q = $conn->prepare('SELECT user_id, username, email FROM users WHERE user_id = ? LIMIT 1');
        $q->bind_param('i', $user_id);
    }
    if ($q->execute()) {
        $res = $q->get_result();
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        if (!empty($rows)) $cols = array_keys($rows[0]);
    }
    $tables_snapshot[$tbl] = ['cols' => $cols, 'rows' => $rows];
}
?>

<style>
* { box-sizing: border-box; }
:root {
    --primary: #4f46e5;
    --primary-light: #6366f1;
    --primary-dark: #4338ca;
    --success: #10b981;
    --success-light: #34d399;
    --danger: #ef4444;
    --warning: #f59e0b;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

body {
    background: #f9fafb;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px;
}

.page-header {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 2rem;
    margin: 0 0 12px 0;
    color: var(--gray-900);
}

.page-header p {
    color: var(--gray-600);
    font-size: 1rem;
    margin: 0;
    line-height: 1.6;
}

.db-snapshot {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.db-snapshot h2 {
    font-size: 1.5rem;
    margin: 0 0 10px 0;
    color: var(--gray-900);
}

.db-snapshot > p {
    color: var(--gray-600);
    margin: 0 0 25px 0;
}

.db-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.db-table-card {
    background: var(--gray-50);
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.3s ease;
}

.db-table-card.active {
    border-color: var(--success);
    background: #ecfdf5;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.db-table-card h3 {
    margin: 0 0 12px 0;
    color: var(--gray-800);
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.db-table-card.active h3 {
    color: var(--success);
}

.table-icon {
    width: 20px;
    height: 20px;
}

.mini-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
    background: white;
    border-radius: 6px;
    overflow: hidden;
}

.mini-table th {
    background: var(--gray-700);
    color: white;
    text-align: left;
    padding: 6px 8px;
    font-weight: 600;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mini-table td {
    padding: 6px 8px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
}

.mini-table tr:last-child td {
    border-bottom: none;
}

.mini-table tr:hover td {
    background: var(--gray-50);
}

.no-rows {
    text-align: center;
    padding: 20px;
    color: var(--gray-500);
    font-size: 0.85rem;
}

.sql-operations {
    display: grid;
    gap: 25px;
}

.sql-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 0;
    overflow: hidden;
}

.sql-card:hover {
    border-color: var(--gray-300);
}

.sql-card-header {
    background: white;
    color: var(--gray-900);
    padding: 20px 30px;
    border-bottom: 1px solid var(--gray-200);
}

.sql-card-header h2 {
    margin: 0 0 8px 0;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sql-icon {
    width: 28px;
    height: 28px;
}

.table-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.table-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: white;
    color: var(--gray-700);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid var(--gray-300);
}

.badge-icon {
    width: 14px;
    height: 14px;
}

.sql-card-body {
    padding: 30px;
}

.form-section {
    margin-bottom: 25px;
}

.form-section label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: var(--gray-50);
    border: 2px solid var(--gray-200);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 10px;
    font-weight: 500;
    color: var(--gray-700);
}

.form-section label:hover {
    background: var(--gray-100);
    border-color: var(--gray-800);
}

.form-section input[type="checkbox"]:checked + label,
.form-section input[type="radio"]:checked + label {
    background: var(--gray-100);
    border-color: var(--gray-800);
    color: var(--gray-900);
}

.form-section input[type="checkbox"],
.form-section input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--gray-800);
}

.form-section input[type="text"],
.form-section input[type="number"],
.form-section select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--gray-200);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
}

.form-section input:focus,
.form-section select:focus {
    outline: none;
    border-color: var(--gray-800);
    box-shadow: 0 0 0 3px rgba(31, 41, 55, 0.1);
}

.inline-inputs {
    display: flex;
    gap: 15px;
    align-items: center;
}

.inline-inputs label {
    flex: 1;
    margin-bottom: 0;
}

.btn-run {
    width: 100%;
    padding: 14px 24px;
    background: var(--gray-800);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-run:hover {
    background: var(--gray-700);
}

.btn-run:active {
    background: var(--gray-900);
}

.btn-icon {
    width: 20px;
    height: 20px;
}

.query-display {
    background: var(--gray-900);
    color: #10b981;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    overflow-x: auto;
    border-left: 4px solid var(--success);
}

.result-section {
    margin-top: 25px;
}

.result-table-wrapper {
    overflow-x: auto;
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.result-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.result-table thead {
    background: var(--gray-800);
    color: white;
}

.result-table th {
    padding: 14px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.result-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
}

.result-table tbody tr:hover {
    background: var(--gray-50);
}

.result-table tbody tr:last-child td {
    border-bottom: none;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-500);
}

.no-results svg {
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-results p {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 500;
}

.error-alert {
    background: #fee2e2;
    border-left: 4px solid var(--danger);
    color: #991b1b;
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .page-header {
        padding: 25px 20px;
    }
    
    .page-header h1 {
        font-size: 1.75rem;
    }
    
    .db-grid {
        grid-template-columns: 1fr;
    }
    
    .inline-inputs {
        flex-direction: column;
    }
}
</style>

<div class="container">
    <div class="page-header">
        <h1>🧩 SQL Features — Interactive Demos</h1>
        <p>Explore powerful SQL operations with real-time examples from your contact database. Run queries safely and see the results instantly.</p>
    </div>

    <?php if ($error): ?>
        <div class="error-alert">
            <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="db-snapshot">
        <h2>📊 Database Snapshot</h2>
        <p>Current state of your database tables. Active tables are highlighted in green when you run a query.</p>
        
        <div class="db-grid">
            <?php
            function render_db_card($title, $data, $is_active) {
                $card_class = $is_active ? 'db-table-card active' : 'db-table-card';
                $html = "<div class=\"$card_class\">";
                $html .= "<h3>";
                $html .= '<svg class="table-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
                $html .= htmlspecialchars($title);
                $html .= "</h3>";
                if (empty($data['rows'])) {
                    $html .= '<div class="no-rows">No data available</div>';
                } else {
                    $html .= '<table class="mini-table"><thead><tr>';
                    foreach ($data['cols'] as $c) { 
                        $short_col = strlen($c) > 15 ? substr($c, 0, 12) . '...' : $c;
                        $html .= '<th>' . htmlspecialchars($short_col) . '</th>'; 
                    }
                    $html .= '</tr></thead><tbody>';
                    foreach ($data['rows'] as $r) {
                        $html .= '<tr>';
                        foreach ($data['cols'] as $c) {
                            $val = (string)($r[$c] ?? '');
                            $short_val = strlen($val) > 20 ? substr($val, 0, 17) . '...' : $val;
                            $html .= '<td>' . htmlspecialchars($short_val) . '</td>';
                        }
                        $html .= '</tr>';
                    }
                    $html .= '</tbody></table>';
                }
                $html .= '</div>';
                return $html;
            }

            echo render_db_card('users', $tables_snapshot['users'] ?? ['cols'=>[], 'rows'=>[]], in_array('users', $active_tables));
            echo render_db_card('contacts', $tables_snapshot['contacts'] ?? ['cols'=>[], 'rows'=>[]], in_array('contacts', $active_tables));
            echo render_db_card('contact_groups_table', $tables_snapshot['contact_groups_table'] ?? ['cols'=>[], 'rows'=>[]], in_array('contact_groups_table', $active_tables));
            echo render_db_card('contact_group_members', $tables_snapshot['contact_group_members'] ?? ['cols'=>[], 'rows'=>[]], in_array('contact_group_members', $active_tables));
            ?>
        </div>
    </div>

    <div class="sql-operations">
        <!-- FULL OUTER JOIN -->
        <div class="sql-card">
            <div class="sql-card-header">
                <h2>
                    <svg class="sql-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Full Outer Join Emulation
                </h2>
                <div class="table-badges">
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contact_group_members
                    </span>
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contact_groups_table
                    </span>
                </div>
            </div>
            <div class="sql-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="full_outer">
                    <div class="form-section">
                        <label>
                            <input type="checkbox" name="left" checked>
                            Include LEFT side (all contacts with their groups)
                        </label>
                        <label>
                            <input type="checkbox" name="right" checked>
                            Include RIGHT side (all groups with their contacts)
                        </label>
                    </div>
                    <button type="submit" class="btn-run">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run Query
                    </button>
                </form>
                <?php if ($sql_display && $current_action === 'full_outer'): ?>
                    <div class="result-section">
                        <pre class="query-display"><?php echo htmlspecialchars($sql_display); ?></pre>
                        <?php echo render_table_from_array($output); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- UNION / UNION ALL -->
        <div class="sql-card">
            <div class="sql-card-header">
                <h2>
                    <svg class="sql-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                    </svg>
                    UNION / UNION ALL
                </h2>
                <div class="table-badges">
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contacts
                    </span>
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contact_groups_table
                    </span>
                </div>
            </div>
            <div class="sql-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="union_examples">
                    <div class="form-section">
                        <label>
                            <input type="checkbox" name="union_all">
                            Use UNION ALL (include duplicates for better performance)
                        </label>
                    </div>
                    <button type="submit" class="btn-run">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run Query
                    </button>
                </form>
                <?php if ($sql_display && $current_action === 'union_examples'): ?>
                    <div class="result-section">
                        <pre class="query-display"><?php echo htmlspecialchars($sql_display); ?></pre>
                        <?php echo render_table_from_array($output); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- INTERSECT -->
        <div class="sql-card">
            <div class="sql-card-header">
                <h2>
                    <svg class="sql-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    INTERSECT Equivalent
                </h2>
                <div class="table-badges">
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contacts
                    </span>
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contact_group_members
                    </span>
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contact_groups_table
                    </span>
                </div>
            </div>
            <div class="sql-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="intersect">
                    <div class="form-section">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">
                            Select a group to view its contacts:
                        </label>
                        <select name="group_id">
                            <option value="0">-- Choose a group --</option>
                            <?php
                            $gq = $conn->prepare('SELECT group_id, group_name FROM contact_groups_table WHERE user_id = ? ORDER BY group_name');
                            $gq->bind_param('i', $user_id);
                            $gq->execute();
                            $grs = $gq->get_result();
                            while ($gr = $grs->fetch_assoc()): ?>
                                <option value="<?php echo (int)$gr['group_id']; ?>"><?php echo htmlspecialchars($gr['group_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-run">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run Query
                    </button>
                </form>
                <?php if ($sql_display && $current_action === 'intersect'): ?>
                    <div class="result-section">
                        <pre class="query-display"><?php echo htmlspecialchars($sql_display); ?></pre>
                        <?php echo render_table_from_array($output); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MINUS / EXCEPT -->
        <div class="sql-card">
            <div class="sql-card-header">
                <h2>
                    <svg class="sql-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/>
                    </svg>
                    MINUS / EXCEPT Equivalents
                </h2>
                <div class="table-badges">
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contacts
                    </span>
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contact_group_members
                    </span>
                </div>
            </div>
            <div class="sql-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="minus">
                    <div class="form-section">
                        <label>
                            <input type="radio" name="minus_method" value="left" checked>
                            LEFT JOIN ... IS NULL (recommended for performance)
                        </label>
                        <label>
                            <input type="radio" name="minus_method" value="notin">
                            NOT IN (subquery) approach
                        </label>
                    </div>
                    <button type="submit" class="btn-run">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run Query
                    </button>
                </form>
                <?php if ($sql_display && $current_action === 'minus'): ?>
                    <div class="result-section">
                        <pre class="query-display"><?php echo htmlspecialchars($sql_display); ?></pre>
                        <?php echo render_table_from_array($output); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        

        <!-- BETWEEN -->
        <div class="sql-card">
            <div class="sql-card-header">
                <h2>
                    <svg class="sql-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                    BETWEEN Operator
                </h2>
                <div class="table-badges">
                    <span class="table-badge">
                        <svg class="badge-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        contacts
                    </span>
                </div>
            </div>
            <div class="sql-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="between">
                    <div class="form-section">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700);">
                            Select contact ID range:
                        </label>
                        <div class="inline-inputs">
                            <label style="border: none; background: none; padding: 0;">
                                <span style="display: block; margin-bottom: 6px; font-size: 0.85rem; color: var(--gray-600);">Min ID</span>
                                <input type="number" name="min" value="1" min="1">
                            </label>
                            <label style="border: none; background: none; padding: 0;">
                                <span style="display: block; margin-bottom: 6px; font-size: 0.85rem; color: var(--gray-600);">Max ID</span>
                                <input type="number" name="max" value="5" min="1">
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn-run">
                        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Run Query
                    </button>
                </form>
                <?php if ($sql_display && $current_action === 'between'): ?>
                    <div class="result-section">
                        <pre class="query-display"><?php echo htmlspecialchars($sql_display); ?></pre>
                        <?php echo render_table_from_array($output); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>