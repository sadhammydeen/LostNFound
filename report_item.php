<?php
/**
 * Report Lost or Found Item
 * Form to publish a lost/found report with optional photo attachment.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Must be logged in to report an item
require_login('login.php');

$page_title = "Report an Item";
$errors = [];

$type = 'lost';
$title = '';
$description = '';
$category = 'Electronics';
$location = '';
$date_reported = date('Y-m-d');

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type'] ?? 'lost');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $location = trim($_POST['location'] ?? '');
    $date_reported = trim($_POST['date_reported'] ?? date('Y-m-d'));

    // Validation
    if (!in_array($type, ['lost', 'found'])) {
        $errors[] = "Invalid item type selected.";
    }

    if (empty($title)) {
        $errors[] = "Item title is required.";
    } elseif (strlen($title) > 150) {
        $errors[] = "Title cannot exceed 150 characters.";
    }

    if (empty($location)) {
        $errors[] = "Location (e.g. Library 2nd Floor, Cafeteria) is required.";
    }

    if (empty($date_reported)) {
        $errors[] = "Date is required.";
    }

    // Image upload handling
    $image_path = null;
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_error = $_FILES['item_image']['error'];
        
        if ($file_error === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['item_image']['tmp_name'];
            $file_name = $_FILES['item_image']['name'];
            $file_size = $_FILES['item_image']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB limit

            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.";
            } elseif ($file_size > $max_size) {
                $errors[] = "Image size exceeds 5MB limit.";
            } else {
                // Ensure unique name
                $new_filename = uniqid('item_', true) . '.' . $file_ext;
                $target_dir = __DIR__ . '/uploads/';
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }

                $target_file = $target_dir . $new_filename;
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $image_path = 'uploads/' . $new_filename;
                } else {
                    $errors[] = "Failed to save uploaded image.";
                }
            }
        } else {
            $errors[] = "Error during image upload. Code: " . $file_error;
        }
    }

    // Insert into database if valid
    if (empty($errors)) {
        $user_id = $_SESSION['user_id'];
        $status = 'open';

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO items (user_id, type, title, description, category, location, date_reported, image_path, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "issssssss",
                $user_id,
                $type,
                $title,
                $description,
                $category,
                $location,
                $date_reported,
                $image_path,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {
                $new_item_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                $_SESSION['success_message'] = "Item reported successfully!";
                header("Location: item_detail.php?id=" . $new_item_id);
                exit;
            } else {
                $errors[] = "Database insert error: " . mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            $errors[] = "Database query preparation failed.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card form-container-card">
    <h2 class="card-title">Report an Item</h2>
    <p class="card-subtitle">Provide accurate details to help locate or return the item.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please fix the following issues:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="report_item.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Report Type *</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="type" value="lost" <?php echo $type === 'lost' ? 'checked' : ''; ?>>
                    <span class="badge badge-lost">Lost Item</span> (I lost something)
                </label>
                <label class="radio-label">
                    <input type="radio" name="type" value="found" <?php echo $type === 'found' ? 'checked' : ''; ?>>
                    <span class="badge badge-found">Found Item</span> (I found something)
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="title">Item Title *</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required placeholder="e.g. Blue Dell Laptop Charger or Black Leather Wallet">
        </div>

        <div class="form-row">
            <div class="form-group col-half">
                <label for="category">Category *</label>
                <select id="category" name="category" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-half">
                <label for="location">Location / Area *</label>
                <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" required placeholder="e.g. Central Library 2nd Floor, Table 4">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-half">
                <label for="date_reported">Date Lost / Found *</label>
                <input type="date" id="date_reported" name="date_reported" class="form-control" value="<?php echo htmlspecialchars($date_reported); ?>" max="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group col-half">
                <label for="item_image">Item Photo (Optional)</label>
                <input type="file" id="item_image" name="item_image" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif">
                <p class="form-help">Max 5MB. JPG, PNG, WEBP, or GIF.</p>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Detailed Description</label>
            <textarea id="description" name="description" class="form-control" rows="4" placeholder="Include distinctive features, serial numbers, brand names, color shades, or contents inside..."><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Submit Report</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
