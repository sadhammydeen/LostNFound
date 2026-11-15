<?php
/**
 * Admin Dashboard
 * Displays portal overview analytics, status breakdown counts, and quick management links.
 */

define('IN_ADMIN', true);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Strict admin guard
require_admin();

$page_title = "Admin Dashboard";

// Fetch counts using prepared statements / safe count queries
$total_items = 0;
$open_items = 0;
$claimed_items = 0;
$resolved_items = 0;
$pending_claims = 0;
$total_users = 0;

// Total items
$res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM items");
if ($row = mysqli_fetch_assoc($res)) { $total_items = (int)$row['cnt']; }

// Items by status
$res = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM items GROUP BY status");
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['status'] === 'open') $open_items = (int)$row['cnt'];
    if ($row['status'] === 'claimed') $claimed_items = (int)$row['cnt'];
    if ($row['status'] === 'resolved') $resolved_items = (int)$row['cnt'];
}

// Pending claims count
$res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM claims WHERE status = 'pending'");
if ($row = mysqli_fetch_assoc($res)) { $pending_claims = (int)$row['cnt']; }

// Total registered users
$res = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users");
if ($row = mysqli_fetch_assoc($res)) { $total_users = (int)$row['cnt']; }

// Fetch recent 5 items
$recent_items_res = mysqli_query(
    $conn, 
    "SELECT items.*, users.name AS reporter_name 
     FROM items 
     JOIN users ON items.user_id = users.id 
     ORDER BY items.created_at DESC 
     LIMIT 5"
);
$recent_items = [];
while ($row = mysqli_fetch_assoc($recent_items_res)) {
    $recent_items[] = $row;
}

// Fetch recent 5 pending claims
$recent_claims_res = mysqli_query(
    $conn,
    "SELECT claims.*, items.title AS item_title, items.type AS item_type, users.name AS claimant_name 
     FROM claims 
     JOIN items ON claims.item_id = items.id 
     JOIN users ON claims.claimant_id = users.id 
     WHERE claims.status = 'pending' 
     ORDER BY claims.created_at DESC 
     LIMIT 5"
);
$recent_claims = [];
while ($row = mysqli_fetch_assoc($recent_claims_res)) {
    $recent_claims[] = $row;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-header-row">
    <div>
        <h2>Administrator Control Panel</h2>
        <p class="text-muted">Overview of campus items, pending verifications, and resolution status.</p>
    </div>
    <div class="admin-quick-actions">
        <a href="manage_claims.php" class="btn btn-primary">Review Claims (<?php echo $pending_claims; ?>)</a>
        <a href="manage_items.php" class="btn btn-outline">Manage All Items</a>
    </div>
</div>

<!-- Stats Counter Grid -->
<div class="stats-grid">
    <div class="stat-card card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
            <span class="stat-number"><?php echo $total_items; ?></span>
            <span class="stat-label">Total Reported Items</span>
        </div>
    </div>

    <div class="stat-card card">
        <div class="stat-icon" style="background-color: #dcfce7; color: #15803d;">🟢</div>
        <div class="stat-info">
            <span class="stat-number"><?php echo $open_items; ?></span>
            <span class="stat-label">Open / Unclaimed</span>
        </div>
    </div>

    <div class="stat-card card">
        <div class="stat-icon" style="background-color: #ffedd5; color: #c2410c;">⏳</div>
        <div class="stat-info">
            <span class="stat-number"><?php echo $claimed_items; ?></span>
            <span class="stat-label">Under Claim Review</span>
        </div>
    </div>

    <div class="stat-card card">
        <div class="stat-icon" style="background-color: #f1f5f9; color: #475569;">✅</div>
        <div class="stat-info">
            <span class="stat-number"><?php echo $resolved_items; ?></span>
            <span class="stat-label">Resolved / Returned</span>
        </div>
    </div>

    <div class="stat-card card highlight-stat">
        <div class="stat-icon" style="background-color: #fee2e2; color: #b91c1c;">📋</div>
        <div class="stat-info">
            <span class="stat-number"><?php echo $pending_claims; ?></span>
            <span class="stat-label">Pending Claim Actions</span>
        </div>
    </div>

    <div class="stat-card card">
        <div class="stat-icon" style="background-color: #e0e7ff; color: #4338ca;">👥</div>
        <div class="stat-info">
            <span class="stat-number"><?php echo $total_users; ?></span>
            <span class="stat-label">Registered Users</span>
        </div>
    </div>
</div>

<div class="admin-split-grid">
    <!-- Pending Claims Preview -->
    <div class="card table-card">
        <div class="card-header-bar">
            <h3>Pending Claims Requiring Action</h3>
            <a href="manage_claims.php" class="view-all-link">View All &rarr;</a>
        </div>
        <?php if (empty($recent_claims)): ?>
            <p class="text-muted text-center" style="padding: 1.5rem 0;">No pending claims awaiting action.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Claimant</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_claims as $rc): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rc['item_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($rc['claimant_name']); ?></td>
                                <td><?php echo date('M d', strtotime($rc['created_at'])); ?></td>
                                <td>
                                    <a href="manage_claims.php" class="btn btn-sm btn-primary">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Reported Items -->
    <div class="card table-card">
        <div class="card-header-bar">
            <h3>Recent Item Reports</h3>
            <a href="manage_items.php" class="view-all-link">View All &rarr;</a>
        </div>
        <?php if (empty($recent_items)): ?>
            <p class="text-muted text-center" style="padding: 1.5rem 0;">No items reported yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_items as $ri): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ri['title']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($ri['type']); ?>">
                                        <?php echo strtoupper(htmlspecialchars($ri['type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-status badge-status-<?php echo htmlspecialchars($ri['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($ri['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../item_detail.php?id=<?php echo (int)$ri['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include __DIR__ . '/../includes/footer.php';
?>
