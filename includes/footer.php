<?php
/**
 * footer.php — Pie de página compartido
 * Sistema SaaS Restaurante | R.DEV
 */
?>
    <!-- Scripts globales -->
    <script src="/sistema_restaurante/assets/js/main.js"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ((array)$extraJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($inlineJS)): ?>
        <script><?= $inlineJS ?></script>
    <?php endif; ?>
</body>
</html>
