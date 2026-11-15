<?php
/**
 * User Login
 * Authenticates user credentials using password_verify() and initializes session.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$page_title = "Login";
$errors = [];
$email = '';

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email)) {
        $errors[] = "Email address is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        // Query user record by email
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                // Verify hashed password
                if (password_verify($password, $row['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = (int)$row['id'];
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['role'] = $row['role'];

                    mysqli_stmt_close($stmt);

                    // Redirect admin to dashboard, student to home
                    if ($row['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                } else {
                    $errors[] = "Invalid email or password.";
                }
            } else {
                $errors[] = "Invalid email or password.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database query failed.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-box card">
    <h2 class="card-title">Sign In</h2>
    <p class="card-subtitle">Access your account to report items and manage claims.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Login Failed:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="email">Campus Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required placeholder="e.g. alex@college.edu">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <div class="auth-footer">
        Don't have an account? <a href="register.php">Register as Student</a>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
?>
