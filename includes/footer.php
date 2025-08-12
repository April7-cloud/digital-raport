<?php
// Prevent direct access
if (!defined('BASE_PATH')) {
    http_response_code(403);
    die('Forbidden');
}
?>
            </div> <!-- End of main content -->
        </div> <!-- End of content area -->
    </div> <!-- End of d-flex -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts -->
    <script>
        // Add any custom JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Set active nav item based on current page
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.includes(href) && href !== '<?php echo BASE_URL; ?>/') {
                    link.classList.add('active');
                } else if (currentPath === '<?php echo BASE_URL; ?>/' && href === '<?php echo BASE_URL; ?>/') {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>