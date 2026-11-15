<?php
/**
 * Admin Manage Claims
 * View claims with status filtering, inspect claim messages, and approve or reject claims.
 * - Approve -> claim status 'approved', item status 'resolved'
 * - Reject -> claim status 'rejected', item status reverts to 'open'
 */

define('IN_ADMIN', true);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = "Manage Claims - Admin";

// Handle Approve / Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $claim_id = (int)($_POST['claim_id'] ?? 0);
    $item_id = (int)($_POST['item_id'] ?? 0);
    $action = $_POST['action'];

    if ($claim_id > 0 && $item_id > 0) {
        if ($action === 'approve') {
            // Set this claim to 'approved'
            $stmt1 = mysqli_prepare($conn, "UPDATE claims SET status = 'approved' WHERE id = ?");
            mysqli_stmt_bind_param($stmt1, "i", $claim_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            // Reject any other pending claims on this same item
            $stmt2 = mysqli_prepare($conn, "UPDATE claims SET status = 'rejected' WHERE item_id = ? AND id != ? AND status = 'pending'");
            mysqli_stmt_bind_param($stmt2, "ii", $item_id, $claim_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            // Set item status to 'resolved'
            $stmt3 = mysqli_prepare($conn, "UPDATE items SET status = 'resolved' WHERE id = ?");
            mysqli_stmt_bind_param($stmt3, "i", $item_id);
            mysqli_stmt_execute($stmt3);
            mysqli_stmt_close($stmt3);

            $_SESSION['success_message'] = "Claim #{$claim_id} approved. Item marked as Resolved!";
        } elseif ($action === 'reject') {
            // Set this claim to 'rejected'
            $stmt1 = mysqli_prepare($conn, "UPDATE claims SET status = 'rejected' WHERE id = ?");
            mysqli_stmt_bind_param($stmt1, "i", $claim_id);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            // Check if there are other pending claims on this item; if none, revert item status to 'open'
            $stmt_chk = mysqli_prepare($conn, "SELECT id FROM claims WHERE item_id = ? AND status = 'pending' LIMIT 1");
            mysqli_stmt_bind_param($stmt_chk, "i", $item_id);
            mysqli_stmt_execute($stmt_chk);
            mysqli_stmt_store_result($stmt_chk);
            $has_other_pending = mysqli_stmt_num_rows($stmt_chk) > 0;
            mysqli_stmt_close($stmt_chk);

            if (!$has_other_pending) {
                $stmt2 = mysqli_prepare($conn, "UPDATE items SET status = 'open' WHERE id = ?");
                mysqli_stmt_bind_param($stmt2, "i", $item_id);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            $_SESSION['success_message'] = "Claim #{$claim_id} has been rejected. Item status reverted to Open.";
        }
    }
    header("Location: manage_claims.php");
    exit;
}

// Filter by claim status
$filter_status = trim($_GET['status'] ?? 'pending');

$where_clauses = [];
$types_str = "";
$params = [];

if (in_array($filter_status, ['pending', 'approved', 'rejected'])) {
    $where_clauses[] = "claims.status = ?";
    $types_str .= "s";
    $params[] = $filter_status;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$sql = "SELECT claims.*, 
               items.title AS item_title, 
               items.type AS item_type, 
               items.category AS item_category,
               items.location AS item_location, 
               items.image_path AS item_image,
               items.status AS current_item_status,
               claimants.name AS claimant_name, 
               claimants.email AS claimant_email,
               reporters.name AS reporter_name,
               reporters.email AS reporter_email
        FROM claims 
        JOIN items ON claims.item_id = items.id 
        JOIN users AS claimants ON claims.claimant_id = claimants.id 
        JOIN users AS reporters ON items.user_id = reporters.id 
        $where_sql 
        ORDER BY claims.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types_str, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$claims = [];
while ($row = mysqli_fetch_assoc($result)) {
    $claims[] = $row;
}
mysqli_stmt_close($stmt);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h2>Review Claims &amp; Verification</h2>
        <p class="text-muted">Evaluate ownership proof and approve or reject claims to resolve items.</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    </div>
</div>

<!-- Filter Tabs / Dropdown -->
<div class="filter-box card">
    <form action="manage_claims.php" method="GET" class="filter-form">
        <div class="filter-dropdown-group">
            <label style="font-weight: 600; font-size: 0.9rem;">Status Filter:</label>
            
            <select name="status" class="form-control select-control">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Claims</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Action Only</option>
                <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>

            <button type="submit" class="btn btn-primary">Filter Claims</button>
            <?php if ($filter_status !== 'pending'): ?>
                <a href="manage_claims.php" class="btn btn-outline">Reset to Pending</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (empty($claims)): ?>
    <div class="card empty-state">
        <p class="empty-text">No claims found for the selected status.</p>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Item Details</th>
                        <th>Claimant (Buyer/Finder)</th>
                        <th>Reporter</th>
                        <th>Claim Proof / Message</th>
                        <th>Status</th>
                        <th>Date Filed</th>
                        <th>Decision Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($claims as $c): ?>
                        <tr>
                            <td>#<?php echo (int)$c['id']; ?></td>
                            <td>
                                <div class="table-item-cell">
                                    <?php if (!empty($c['item_image'])): ?>
                                        <img src="<?php echo htmlspecialchars('../' . $c['item_image']); ?>" alt="thumb" class="table-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong>
                                            <a href="../item_detail.php?id=<?php echo (int)$c['item_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($c['item_title']); ?>
                                            </a>
                                        </strong>
                                        <br>
                                        <span class="badge badge-<?php echo htmlspecialchars($c['item_type']); ?>">
                                            <?php echo strtoupper(htmlspecialchars($c['item_type'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($c['claimant_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($c['claimant_email']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($c['reporter_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($c['reporter_email']); ?></small>
                            </td>
                            <td>
                                <div class="claim-message-preview" style="max-width: 250px;">
                                    <?php echo nl2br(htmlspecialchars($c['message'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status-<?php echo htmlspecialchars($c['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($c['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($c['status'] === 'pending'): ?>
                                    <div class="action-buttons" style="flex-direction: column; gap: 0.35rem;">
                                        <form action="manage_claims.php" method="POST" onsubmit="return confirm('Approve this claim and resolve the item?');" style="width: 100%;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="claim_id" value="<?php echo (int)$c['id']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo (int)$c['item_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" style="width: 100%;">Approve</button>
                                        </form>

                                        <form action="manage_claims.php" method="POST" onsubmit="return confirm('Reject this claim and reopen the item?');" style="width: 100%;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="claim_id" value="<?php echo (int)$c['id']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo (int)$c['item_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" style="width: 100%;">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted text-small">Decision Finalized</span>
                                <?php endif; ?>
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
