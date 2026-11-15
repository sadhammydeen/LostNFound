<?php
/**
 * Admin Manage Items
 * View all items, filter by status/type, and delete inappropriate posts.
 */

define('IN_ADMIN', true);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = "Manage Items - Admin";

// Handle admin item deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $item_id = (int)($_POST['item_id'] ?? 0);

    if ($item_id > 0) {
        // Fetch image path to delete file
        $get_stmt = mysqli_prepare($conn, "SELECT image_path FROM items WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($get_stmt, "i", $item_id);
        mysqli_stmt_execute($get_stmt);
        $res = mysqli_stmt_get_result($get_stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($get_stmt);

        if ($row) {
            // Delete claims referencing item first
            $del_claims = mysqli_prepare($conn, "DELETE FROM claims WHERE item_id = ?");
            mysqli_stmt_bind_param($del_claims, "i", $item_id);
            mysqli_stmt_execute($del_claims);
            mysqli_stmt_close($del_claims);

            // Delete item
            $del_item = mysqli_prepare($conn, "DELETE FROM items WHERE id = ?");
            mysqli_stmt_bind_param($del_item, "i", $item_id);
            if (mysqli_stmt_execute($del_item)) {
                if (!empty($row['image_path']) && file_exists(__DIR__ . '/../' . $row['image_path'])) {
                    unlink(__DIR__ . '/../' . $row['image_path']);
                }
                $_SESSION['success_message'] = "Item #{$item_id} and all related claims were deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete item.";
            }
            mysqli_stmt_close($del_item);
        }
    }
    header("Location: manage_items.php");
    exit;
}

// Filters
$filter_status = trim($_GET['status'] ?? 'all');
$filter_type = trim($_GET['type'] ?? 'all');

$where_clauses = [];
$types_str = "";
$params = [];

if (in_array($filter_status, ['open', 'claimed', 'resolved'])) {
    $where_clauses[] = "items.status = ?";
    $types_str .= "s";
    $params[] = $filter_status;
}

if (in_array($filter_type, ['lost', 'found'])) {
    $where_clauses[] = "items.type = ?";
    $types_str .= "s";
    $params[] = $filter_type;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$sql = "SELECT items.*, users.name AS reporter_name, users.email AS reporter_email,
               (SELECT COUNT(*) FROM claims WHERE claims.item_id = items.id) AS claim_count
        FROM items 
        JOIN users ON items.user_id = users.id 
        $where_sql 
        ORDER BY items.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types_str, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$all_items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $all_items[] = $row;
}
mysqli_stmt_close($stmt);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h2>Manage All Campus Items</h2>
        <p class="text-muted">Review, filter, inspect, and moderate all reported lost &amp; found items.</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-box card">
    <form action="manage_items.php" method="GET" class="filter-form">
        <div class="filter-dropdown-group">
            <label style="font-weight: 600; font-size: 0.9rem;">Filter by:</label>
            
            <select name="type" class="form-control select-control">
                <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="lost" <?php echo $filter_type === 'lost' ? 'selected' : ''; ?>>Lost</option>
                <option value="found" <?php echo $filter_type === 'found' ? 'selected' : ''; ?>>Found</option>
            </select>

            <select name="status" class="form-control select-control">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                <option value="open" <?php echo $filter_status === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="claimed" <?php echo $filter_status === 'claimed' ? 'selected' : ''; ?>>Claimed</option>
                <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            </select>

            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <?php if ($filter_type !== 'all' || $filter_status !== 'all'): ?>
                <a href="manage_items.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (empty($all_items)): ?>
    <div class="card empty-state">
        <p class="empty-text">No items found matching the selected filters.</p>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Details</th>
                        <th>Type</th>
                        <th>Category &amp; Location</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Claims</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_items as $item): ?>
                        <tr>
                            <td>#<?php echo (int)$item['id']; ?></td>
                            <td>
                                <div class="table-item-cell">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars('../' . $item['image_path']); ?>" alt="thumb" class="table-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong>
                                            <a href="../item_detail.php?id=<?php echo (int)$item['id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($item['type']); ?>">
                                    <?php echo strtoupper(htmlspecialchars($item['type'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item['category']); ?><br>
                                <small class="text-muted">📍 <?php echo htmlspecialchars($item['location']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item['reporter_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($item['reporter_email']); ?></small>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?php echo htmlspecialchars($item['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($item['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="counter-badge"><?php echo (int)$item['claim_count']; ?></span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($item['date_reported']); ?></small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="../item_detail.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-outline" target="_blank">View</a>
                                    <form action="manage_items.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this item report?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/../includes/footer.php';
?>
