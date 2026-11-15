<?php
/**
 * User Filed Claims
 * Track current status of claims submitted by the logged-in student.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

require_login('login.php');

$claimant_id = (int)$_SESSION['user_id'];
$page_title = "My Claims";

// Fetch claims made by the current user
$stmt = mysqli_prepare(
    $conn,
    "SELECT claims.*, 
            items.title AS item_title, 
            items.type AS item_type, 
            items.category AS item_category,
            items.location AS item_location, 
            items.image_path AS item_image,
            items.status AS item_status,
            reporters.name AS reporter_name
     FROM claims 
     JOIN items ON claims.item_id = items.id 
     JOIN users AS reporters ON items.user_id = reporters.id 
     WHERE claims.claimant_id = ? 
     ORDER BY claims.created_at DESC"
);
mysqli_stmt_bind_param($stmt, "i", $claimant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$claims = [];
while ($row = mysqli_fetch_assoc($result)) {
    $claims[] = $row;
}
mysqli_stmt_close($stmt);

include __DIR__ . '/includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h2>My Submitted Claims</h2>
        <p class="text-muted">Track the progress of claims you have filed for lost or found items.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline">&larr; Browse More Items</a>
    </div>
</div>

<?php if (empty($claims)): ?>
    <div class="card empty-state">
        <p class="empty-text">You have not submitted any claims yet.</p>
        <a href="index.php" class="btn btn-primary">Browse Open Items</a>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Claimed Item</th>
                        <th>Item Type</th>
                        <th>Claim Message / Proof</th>
                        <th>Submitted On</th>
                        <th>Claim Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td>
                                <div class="table-item-cell">
                                    <?php if (!empty($claim['item_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($claim['item_image']); ?>" alt="thumbnail" class="table-thumb">
                                    <?php endif; ?>
                                    <div>
                                        <strong>
                                            <a href="item_detail.php?id=<?php echo (int)$claim['item_id']; ?>">
                                                <?php echo htmlspecialchars($claim['item_title']); ?>
                                            </a>
                                        </strong>
                                        <br>
                                        <small class="text-muted">📍 <?php echo htmlspecialchars($claim['item_location']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo htmlspecialchars($claim['item_type']); ?>">
                                    <?php echo strtoupper(htmlspecialchars($claim['item_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="claim-message-preview">
                                    <?php echo nl2br(htmlspecialchars($claim['message'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($claim['created_at'])); ?>
                            </td>
                            <td>
                                <?php 
                                    $status_badge_class = 'badge-status-open';
                                    if ($claim['status'] === 'pending') {
                                        $status_badge_class = 'badge-status-claimed';
                                    } elseif ($claim['status'] === 'approved') {
                                        $status_badge_class = 'badge-status-open'; // green
                                    } elseif ($claim['status'] === 'rejected') {
                                        $status_badge_class = 'badge-status-resolved'; // gray/red
                                    }
                                ?>
                                <span class="badge badge-status <?php echo $status_badge_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($claim['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="item_detail.php?id=<?php echo (int)$claim['item_id']; ?>" class="btn btn-sm btn-outline">View Item</a>
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
