<?php // app/Views/error/error.php ?>
<main class="container static-page">
    <h1>Erreur <?php echo isset($errorCode) ? htmlspecialchars($errorCode) : ''; ?></h1>
    <div class="alert alert-danger">
        <p><?php echo isset($errorMessage) ? htmlspecialchars($errorMessage) : 'Une erreur inattendue est survenue.'; ?></p>
    </div>
    <p><a href="<?php echo BASE_URL; ?>" class="button">Retour à l'accueil</a></p>
</main>