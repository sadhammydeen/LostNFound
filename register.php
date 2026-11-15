<?php
/**
 * User Registration
 * Handles student account creation and credential validation using password_hash().
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$page_title = "Register";
$errors = [];
$name = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = "An account with this email already exists.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database query preparation failed.";
        }
    }

    // Insert new user if no validation errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'student'; // Default role

        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $role);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                
                $_SESSION['success_message'] = "Registration successful! Please log in with your credentials.";
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Failed to register account: " . mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            $errors[] = "Failed to prepare insert query.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-box card">
    <h2 class="card-title">Student Registration</h2>
    <p class="card-subtitle">Create an account to report lost/found items and raise claims.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required placeholder="e.g. Alex Johnson">
        </div>

        <div class="form-group">
            <label for="email">Campus Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required placeholder="e.g. alex@college.edu">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Re-enter your password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <div class="auth-footer">
        Already have an account? <a href="login.php">Log In</a>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
