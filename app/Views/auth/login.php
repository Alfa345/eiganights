<?php // app/Views/auth/login.php - View part
// Variables like $pageTitle, $error_message, $username_value, $redirectAfterLoginQuery, $RECAPTCHA_SITE_KEY_V3, $siteName are passed from AuthController.
?>
<main class="container auth-form-container">
    <h1>Connexion</h1>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['message'])): // General session messages ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['login_required_message'])): ?>
        <div class="alert alert-info">
            <?php echo htmlspecialchars($_SESSION['login_required_message']); unset($_SESSION['login_required_message']); ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="<?php echo BASE_URL . 'login' . htmlspecialchars($redirectAfterLoginQuery ?? ''); ?>" novalidate
        <?php if (!empty($RECAPTCHA_SITE_KEY_V3)): ?>
            onsubmit="onSubmitLoginForm(event)"
        <?php endif; ?>
    >
        <div class="form-group">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" placeholder="Votre nom d'utilisateur" value="<?php echo htmlspecialchars($username_value ?? ''); ?>" required autofocus />
        </div>
        <div class="form-group">
            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" placeholder="Votre mot de passe" required />
        </div>
        
        <?php if (empty($RECAPTCHA_SITE_KEY_V3)): ?>
        <div class="form-group">
            <p class="alert alert-warning">reCAPTCHA v3 n'est pas configuré. La soumission pourrait ne pas être protégée.</p>
        </div>
        <?php endif; ?>
        <!-- g-recaptcha-response field will be added by JS -->

        <div class="form-group">
            <input type="submit" value="Se connecter" class="button-primary" />
        </div>
    </form>
    <p class="auth-links">
        Pas encore de compte ? <a href="<?php echo BASE_URL . 'register' . htmlspecialchars($redirectAfterLoginQuery ?? ''); ?>">Inscrivez-vous ici</a>.<br>
        <a href="<?php echo BASE_URL . 'forgot-password'; ?>">Mot de passe oublié ?</a>
    </p>
</main>

<?php if (!empty($RECAPTCHA_SITE_KEY_V3)): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($RECAPTCHA_SITE_KEY_V3); ?>"></script>
    <script>
    function onSubmitLoginForm(event) {
        event.preventDefault(); 
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo htmlspecialchars($RECAPTCHA_SITE_KEY_V3); ?>', {action: 'login'}).then(function(token) {
                var form = document.getElementById('loginForm');
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'g-recaptcha-response');
                hiddenInput.setAttribute('value', token);
                form.appendChild(hiddenInput);
                form.submit();
            });
        });
    }
    </script>
<?php endif; ?>