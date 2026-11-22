<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/admin.php';

if (admin_is_authenticated()) {
    redirect('index.php');
}

$configured = admin_password_hash() !== null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$configured) {
        $error = 'Admin login is not configured. Follow the setup steps in README.md.';
    } else {
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        if (password_verify($password, admin_password_hash())) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            redirect('index.php');
        }

        $error = 'The password was not recognized.';
    }
}

$pageTitle = 'Admin login — Soko';
require __DIR__ . '/../partials/header.php';
?>
<section class="admin-page admin-login-page">
    <p class="eyebrow">Store administration</p>
    <h1>Admin login</h1>
    <?php if ($error !== ''): ?>
        <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!$configured): ?>
        <div class="flash flash-error" role="status">Admin password hash is not configured. Add <code>ADMIN_PASSWORD_HASH</code> in your Apache environment after following README.md.</div>
    <?php endif; ?>
    <form class="checkout-form admin-login-form" method="post" action="login.php">
        <?= csrf_field() ?>
        <div class="checkout-field">
            <label for="password">Admin password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button checkout-button" type="submit" <?= !$configured ? 'disabled' : '' ?>>Sign in</button>
    </form>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
