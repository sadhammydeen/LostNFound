<?php
/**
 * Item Details & Claim Interface
 * Displays full item information, reporter info, photo, and claim submission form.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id <= 0) {
    $_SESSION['error_message'] = "Invalid item specified.";
    header("Location: index.php");
    exit;
}

$claim_errors = [];

// Handle claim submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_claim') {
    require_login('login.php');

    $claimant_id = (int)$_SESSION['user_id'];
    $claim_message = trim($_POST['claim_message'] ?? '');

    if (empty($claim_message)) {
        $claim_errors[] = "Please provide verification details or proof of ownership in your message.";
    }

    // Check item validity & status
    $check_item_stmt = mysqli_prepare($conn, "SELECT id, user_id, status FROM items WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($check_item_stmt, "i", $item_id);
    mysqli_stmt_execute($check_item_stmt);
    $check_item_res = mysqli_stmt_get_result($check_item_stmt);
    $item_row = mysqli_fetch_assoc($check_item_res);
    mysqli_stmt_close($check_item_stmt);

    if (!$item_row) {
        $claim_errors[] = "Item not found.";
    } elseif ($item_row['status'] !== 'open') {
        $claim_errors[] = "This item is not open for claims (Current status: " . htmlspecialchars($item_row['status']) . ").";
    } elseif ((int)$item_row['user_id'] === $claimant_id) {
        $claim_errors[] = "You cannot submit a claim on an item you reported yourself.";
    }

    // Check for duplicate pending claim by the same user
    if (empty($claim_errors)) {
        $dup_stmt = mysqli_prepare($conn, "SELECT id FROM claims WHERE item_id = ? AND claimant_id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($dup_stmt, "ii", $item_id, $claimant_id);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);
        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            $claim_errors[] = "You already have an active pending claim for this item.";
        }
        mysqli_stmt_close($dup_stmt);
    }

    // Insert claim and update item status to 'claimed'
    if (empty($claim_errors)) {
        $claim_status = 'pending';
        $insert_claim = mysqli_prepare($conn, "INSERT INTO claims (item_id, claimant_id, message, status) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_claim, "iiss", $item_id, $claimant_id, $claim_message, $claim_status);

        if (mysqli_stmt_execute($insert_claim)) {
            mysqli_stmt_close($insert_claim);

            // Update item status to claimed
            $update_item = mysqli_prepare($conn, "UPDATE items SET status = 'claimed' WHERE id = ?");
            mysqli_stmt_bind_param($update_item, "i", $item_id);
            mysqli_stmt_execute($update_item);
            mysqli_stmt_close($update_item);

            $_SESSION['success_message'] = "Your claim has been submitted successfully! An administrator will review it.";
            header("Location: my_claims.php");
            exit;
        } else {
            $claim_errors[] = "Failed to submit claim: " . mysqli_stmt_error($insert_claim);
            mysqli_stmt_close($insert_claim);
        }
    }
}

// Fetch item with reporter details
$stmt = mysqli_prepare(
    $conn,
    "SELECT items.*, users.name AS reporter_name, users.email AS reporter_email 
     FROM items 
     JOIN users ON items.user_id = users.id 
     WHERE items.id = ? 
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$item) {
    $_SESSION['error_message'] = "Item not found.";
    header("Location: index.php");
    exit;
}

$page_title = $item['title'];
$is_reporter = is_logged_in() && ((int)$_SESSION['user_id'] === (int)$item['user_id']);

// Check if current logged-in user has already submitted a claim on this item
$user_has_claimed = false;
$user_claim_status = null;
if (is_logged_in() && !$is_reporter) {
    $c_stmt = mysqli_prepare($conn, "SELECT status FROM claims WHERE item_id = ? AND claimant_id = ? ORDER BY created_at DESC LIMIT 1");
    $uid = (int)$_SESSION['user_id'];
    mysqli_stmt_bind_param($c_stmt, "ii", $item_id, $uid);
    mysqli_stmt_execute($c_stmt);
    $c_res = mysqli_stmt_get_result($c_stmt);
    if ($c_row = mysqli_fetch_assoc($c_res)) {
        $user_has_claimed = true;
        $user_claim_status = $c_row['status'];
    }
    mysqli_stmt_close($c_stmt);
}

include __DIR__ . '/includes/header.php';
?>

<div class="item-detail-container">
    <div class="detail-back-nav">
        <a href="index.php">&larr; Back to all listings</a>
    </div>

    <div class="detail-card card">
        <div class="detail-grid">
            <div class="detail-media">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="detail-image">
                <?php else: ?>
                    <div class="detail-no-image">
                        <span>📦 No Photo Attached</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-content">
                <div class="detail-header-badges">
                    <span class="badge badge-<?php echo htmlspecialchars($item['type']); ?>">
                        <?php echo strtoupper(htmlspecialchars($item['type'])); ?>
                    </span>
                    <span class="badge badge-status badge-status-<?php echo htmlspecialchars($item['status']); ?>">
                        Status: <?php echo ucfirst(htmlspecialchars($item['status'])); ?>
                    </span>
                </div>

                <h1 class="detail-title"><?php echo htmlspecialchars($item['title']); ?></h1>

                <div class="detail-meta-table">
                    <div class="meta-row">
                        <span class="meta-label">Category:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($item['category']); ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Location:</span>
                        <span class="meta-value">📍 <?php echo htmlspecialchars($item['location']); ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Date Reported:</span>
                        <span class="meta-value">📅 <?php echo htmlspecialchars($item['date_reported']); ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Reported By:</span>
                        <span class="meta-value">👤 <?php echo htmlspecialchars($item['reporter_name']); ?></span>
                    </div>
                </div>

                <div class="detail-description-block">
                    <h3>Description</h3>
                    <p>
                        <?php 
                            echo !empty($item['description']) 
                                ? nl2br(htmlspecialchars($item['description'])) 
                                : '<em>No additional details provided.</em>'; 
                        ?>
                    </p>
                </div>

                <!-- Claim action / notice -->
                <div class="detail-actions-box" id="claim-section">
                    <?php if (!empty($claim_errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Unable to process claim:</strong>
                            <ul>
                                <?php foreach ($claim_errors as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($item['status'] === 'open'): ?>
                        <?php if (!is_logged_in()): ?>
                            <div class="alert alert-info">
                                <a href="login.php"><strong>Log in</strong></a> to submit a claim for this item.
                            </div>
                        <?php elseif ($is_reporter): ?>
                            <div class="alert alert-info">
                                You reported this item. You can manage it from <a href="my_items.php"><strong>My Reports</strong></a>.
                            </div>
                        <?php else: ?>
                            <div class="claim-form-card">
                                <h3>Raise a Claim for this Item</h3>
                                <p class="form-help">Provide specific details (e.g. wallpaper description, unique scratch, phone unlock PIN description, receipt, or matching contents) to prove you are the rightful owner.</p>
                                
                                <form action="item_detail.php?id=<?php echo (int)$item['id']; ?>" method="POST">
                                    <input type="hidden" name="action" value="submit_claim">
                                    <div class="form-group">
                                        <label for="claim_message">Proof of Ownership / Details *</label>
                                        <textarea id="claim_message" name="claim_message" class="form-control" rows="4" required placeholder="Explain why this item belongs to you with verifiable details..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Submit this claim for admin verification?');">Submit Claim</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($item['status'] === 'claimed'): ?>
                        <?php if ($user_has_claimed && $user_claim_status === 'pending'): ?>
                            <div class="alert alert-warning">
                                <strong>Your Claim is Pending:</strong> You have submitted a claim for this item. An administrator is reviewing your proof of ownership. Check <a href="my_claims.php"><strong>My Claims</strong></a> for updates.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>Pending Verification:</strong> A claim has been raised for this item and is currently under review by the administrator.
                            </div>
                        <?php endif; ?>
                    <?php elseif ($item['status'] === 'resolved'): ?>
                        <div class="alert alert-success">
                            <strong>Resolved:</strong> This item has been verified and safely returned to its owner.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
