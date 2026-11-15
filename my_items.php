<?php
/**
 * User Reported Items
 * Manage personal reports with status indicators and delete options.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login('login.php');

$user_id = (int)$_SESSION['user_id'];
$page_title = "My Reported Items";

// Handle item deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $item_id = (int)($_POST['item_id'] ?? 0);

    if ($item_id > 0) {
        // Verify ownership and check if active claim exists
        $check_stmt = mysqli_prepare(
            $conn, 
            "SELECT id, image_path, status FROM items WHERE id = ? AND user_id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($check_stmt, "ii", $item_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $res = mysqli_stmt_get_result($check_stmt);
        $item_to_delete = mysqli_fetch_assoc($res);
        mysqli_stmt_close($check_stmt);

        if (!$item_to_delete) {
            $_SESSION['error_message'] = "Item not found or you do not have permission to delete it.";
        } elseif ($item_to_delete['status'] === 'claimed') {
            $_SESSION['error_message'] = "Cannot delete item with an active pending claim.";
        } else {
            // Delete associated claims if any
            $del_claims = mysqli_prepare($conn, "DELETE FROM claims WHERE item_id = ?");
            mysqli_stmt_bind_param($del_claims, "i", $item_id);
            mysqli_stmt_execute($del_claims);
            mysqli_stmt_close($del_claims);

            // Delete item
            $del_item = mysqli_prepare($conn, "DELETE FROM items WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($del_item, "ii", $item_id, $user_id);
            if (mysqli_stmt_execute($del_item)) {
                // Remove image file from server if it exists
                if (!empty($item_to_delete['image_path']) && file_exists(__DIR__ . '/' . $item_to_delete['image_path'])) {
                    unlink(__DIR__ . '/' . $item_to_delete['image_path']);
                }
                $_SESSION['success_message'] = "Item deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete item from database.";
            }
            mysqli_stmt_close($del_item);
        }
    }
    header("Location: my_items.php");
    exit;
}

// Fetch all items reported by current user
$stmt = mysqli_prepare(
    $conn,
    "SELECT items.*, 
            (SELECT COUNT(*) FROM claims WHERE claims.item_id = items.id) AS total_claims
     FROM items 
     WHERE user_id = ? 
     ORDER BY created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$my_items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $my_items[] = $row;
}
mysqli_stmt_close($stmt);

include __DIR__ . '/includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h2>My Reported Items</h2>
        <p class="text-muted">Track status of items you have reported lost or found on campus.</p>
    </div>
    <div>
        <a href="report_item.php" class="btn btn-primary">+ Report New Item</a>
    </div>
</div>

<?php if (empty($my_items)): ?>
    <div class="card empty-state">
        <p class="empty-text">You haven't reported any items yet.</p>
        <a href="report_item.php" class="btn btn-primary">Report Your First Item</a>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Date Reported</th>
                        <th>Status</th>
                        <th>Claims</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_items as $item): ?>
                        <tr>
                            <td>
                                <div class="table-item-cell">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="thumbnail" class="table-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong>
                                            <a href="item_detail.php?id=<?php echo (int)$item['id']; ?>">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </strong>
                                        <br>
                                        <small class="text-muted">📍 <?php echo htmlspecialchars($item['location']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($item['type']); ?>">
                                    <?php echo strtoupper(htmlspecialchars($item['type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['date_reported']); ?></td>
                            <td>
                                <span class="badge badge-status badge-status-<?php echo htmlspecialchars($item['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($item['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="counter-badge"><?php echo (int)$item['total_claims']; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="item_detail.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    <?php if ($item['status'] !== 'claimed'): ?>
                                        <form action="my_items.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this report?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted text-small" title="Cannot delete item with active claim">Locked</span>
                                    <?php endif; ?>
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
include __DIR__ . '/includes/footer.php';
?>
