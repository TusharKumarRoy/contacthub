<footer style="text-align: center; padding: 20px; margin-top: 50px; color: #888;">
        <p>&copy; <?php echo date('Y'); ?> Contact Manager. All rights reserved.</p>
    </footer>
    <?php
    // Compute base path for scripts
    $app_folder = basename(dirname(__DIR__));
    $base_path = '/' . $app_folder;
    ?>
    <script src="<?php echo $base_path; ?>/assets/js/tooltip-portal.js"></script>
</body>
</html>