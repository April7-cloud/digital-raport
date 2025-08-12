<?php
http_response_code(403);
$pageTitle = '403 Forbidden';
require_once 'config/config.php';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1><i class="fas fa-ban text-danger"></i> 403</h1>
                <h2>Access Forbidden</h2>
                <div class="error-details">
                    You don't have permission to access this page.
                </div>
                <div class="error-actions mt-4">
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-home"></i> Return Home
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-success btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-template {padding: 40px 15px; text-align: center;}
.error-actions {margin-top:15px; margin-bottom:15px;}
.error-actions .btn {margin: 0 5px;}
</style>

<?php require_once 'includes/footer.php'; ?>
