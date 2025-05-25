<?php /* ... */ ?>
</div> <!-- Closing .container .page-content -->
<footer class="site-footer-main">
    <div class="container footer-content">
        <p>© <?php echo date("Y"); ?> <?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'EigaNights'; ?> - Tous droits réservés.</p>
        <nav class="footer-nav" aria-label="Navigation de pied de page">
            <ul>
                <li><a href="<?php echo BASE_URL . 'faq'; ?>">FAQ</a></li>
                <li><a href="<?php echo BASE_URL . 'terms'; ?>">Conditions d'Utilisation</a></li>
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><span class="footer-nav-separator"></span> <a href="<?php echo BASE_URL . 'admin/manage-file-terms'; ?>" class="admin-link-footer">Gérer les CGU (fichier)</a></li>
                <?php endif; ?>
                <li><span class="footer-nav-separator"></span> <a href="<?php echo BASE_URL . 'contact'; ?>">Contactez-nous</a></li>
            </ul>
        </nav>
    </div>
</footer>
</body>
</html>