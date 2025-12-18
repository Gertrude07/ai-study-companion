    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> AI Study Companion. Built for Web Technologies Summer 2025.</p>
        </div>
    </footer>

    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
