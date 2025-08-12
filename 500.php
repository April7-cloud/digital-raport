<?php
http_response_code(500);
$pageTitle = '500 Server Error';
require_once 'config/config.php';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1><i class="fas fa-exclamation-triangle text-warning"></i> 500</h1>
                <h2>Internal Server Error</h2>
                <div class="error-details">
                    Oops! Something went wrong on our end. We're working to fix it.
                </div>
                <div class="error-actions mt-4">
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-home"></i> Return Home
                    </a>
                    <a href="mailto:<?php echo SITE_EMAIL; ?>?subject=500 Error" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-envelope"></i> Contact Support
                    </a>
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
