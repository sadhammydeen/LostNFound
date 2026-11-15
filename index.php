<?php
/**
 * Portal Landing & Public Feed
 * Browse open lost and found items with search, type filter, and category filter.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Browse Items";

// Filter parameters
$search_query = trim($_GET['q'] ?? '');
$filter_type = trim($_GET['type'] ?? 'all');
$filter_category = trim($_GET['category'] ?? 'all');

// Build query safely with prepared statements
$where_clauses = ["status = 'open'"];
$types_str = "";
$params = [];

if (in_array($filter_type, ['lost', 'found'])) {
    $where_clauses[] = "type = ?";
    $types_str .= "s";
    $params[] = $filter_type;
}

if (!empty($filter_category) && $filter_category !== 'all') {
    $where_clauses[] = "category = ?";
    $types_str .= "s";
    $params[] = $filter_category;
}

if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $types_str .= "sss";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql = "SELECT items.*, users.name AS reporter_name 
        FROM items 
        JOIN users ON items.user_id = users.id 
        WHERE " . implode(" AND ", $where_clauses) . " 
        ORDER BY items.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types_str, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}
mysqli_stmt_close($stmt);

// Category options for filter
$categories = [
    'Electronics',
    'Books & Stationery',
    'IDs & Cards',
    'Clothing & Accessories',
    'Keys',
    'Bags & Wallets',
    'Bottles & Utensils',
    'Other'
];

include __DIR__ . '/includes/header.php';
?>

<div class="hero-section">
    <h1>Campus Lost &amp; Found Portal</h1>
    <p>Report misplaced belongings or help reconnect items found across the college campus.</p>
</div>

<!-- Search & Filter Controls -->
<div class="filter-box card">
    <form action="index.php" method="GET" class="filter-form">
        <div class="filter-input-group">
            <input type="text" name="q" class="form-control search-input" placeholder="Search by title, location, description..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>

        <div class="filter-dropdown-group">
            <select name="type" class="form-control select-control">
                <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="lost" <?php echo $filter_type === 'lost' ? 'selected' : ''; ?>>Lost Items Only</option>
                <option value="found" <?php echo $filter_type === 'found' ? 'selected' : ''; ?>>Found Items Only</option>
            </select>

            <select name="category" class="form-control select-control">
                <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if (!empty($search_query) || $filter_type !== 'all' || $filter_category !== 'all'): ?>
                <a href="index.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Items Grid -->
<div class="section-header">
    <h2>Open Campus Listings (<?php echo count($items); ?>)</h2>
</div>

<?php if (empty($items)): ?>
    <div class="card empty-state">
        <p class="empty-text">No open items found matching your filter criteria.</p>
        <?php if (is_logged_in()): ?>
            <a href="report_item.php" class="btn btn-primary">Report an Item</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="items-grid">
        <?php foreach ($items as $item): ?>
            <div class="item-card card">
                <?php if (!empty($item['image_path'])): ?>
                    <div class="item-card-image">
                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    </div>
                <?php else: ?>
                    <div class="item-card-image-placeholder">
                        <span>📦 No Image</span>
                    </div>
                <?php endif; ?>

                <div class="item-card-body">
                    <div class="item-card-badges">
                        <span class="badge badge-<?php echo htmlspecialchars($item['type']); ?>">
                            <?php echo strtoupper(htmlspecialchars($item['type'])); ?>
                        </span>
                        <span class="badge badge-category">
                            <?php echo htmlspecialchars($item['category']); ?>
                        </span>
                    </div>

                    <h3 class="item-card-title">
                        <a href="item_detail.php?id=<?php echo (int)$item['id']; ?>">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </a>
                    </h3>

                    <p class="item-card-meta">
                        📍 <strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?><br>
                        📅 <strong>Date:</strong> <?php echo htmlspecialchars($item['date_reported']); ?><br>
                        👤 <strong>Reported by:</strong> <?php echo htmlspecialchars($item['reporter_name']); ?>
                    </p>

                    <p class="item-card-desc">
                        <?php 
                            $desc = htmlspecialchars($item['description'] ?? '');
                            echo strlen($desc) > 90 ? substr($desc, 0, 90) . '...' : ($desc ?: '<em>No additional description provided.</em>');
                        ?>
                    </p>

                    <div class="item-card-footer">
                        <a href="item_detail.php?id=<?php echo (int)$item['id']; ?>" class="btn btn-sm btn-outline">View Details &rarr;</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';
?>
