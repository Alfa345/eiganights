<?php
// add.php
require_once __DIR__ . '/app/config/config.php'; // Use the MVC config
// Session is started in config.php

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login'); // Redirect to MVC login route
    exit;
}
// ... rest of add.php logic ...
// Ensure redirects use BASE_URL and point to appropriate routes or full scripts
// e.g., header('Location: ' . BASE_URL . 'movie/details/' . $movieId);
// or header('Location: ' . $redirectUrl); if $redirectUrl is correctly formed with BASE_URL

$redirectUrl = BASE_URL . 'index'; // Default redirect to home (MVC route)
if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
    $postedRedirect = $_POST['redirect_url'];
    // Basic validation for local redirect
    if (strpos($postedRedirect, BASE_URL) === 0 || (!preg_match("~^(?:f|ht)tps?://~i", $postedRedirect) && substr($postedRedirect, 0, 1) !== '/' && substr($postedRedirect, 0, 1) !== '.')) {
        // If it's already a full BASE_URL path, or a relative path not starting with /, assume it's a route
        if (strpos($postedRedirect, BASE_URL) !== 0 && substr($postedRedirect, 0, 1) !== '/') {
             $redirectUrl = BASE_URL . $postedRedirect;
        } else {
            $redirectUrl = $postedRedirect; // Assumes it's correctly formed
        }
    } elseif (substr($postedRedirect, 0, 1) === '/') { // If it's an absolute path from web root
         $redirectUrl = BASE_URL . ltrim($postedRedirect, '/');
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $movieId = (int)$_POST['movie_id']; // Ensure movie_id is integer
    $movieTitle = $conn->real_escape_string($_POST['movie_title']); // Sanitize
    $posterPath = $conn->real_escape_string($_POST['poster_path']); // Sanitize

    $stmtCheck = $conn->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
    if ($stmtCheck) {
        $stmtCheck->bind_param("ii", $userId, $movieId);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows === 0) {
            $stmtInsert = $conn->prepare("INSERT INTO watchlist (user_id, movie_id, movie_title, poster_path) VALUES (?, ?, ?, ?)");
            if ($stmtInsert) {
                $stmtInsert->bind_param("iiss", $userId, $movieId, $movieTitle, $posterPath);
                if ($stmtInsert->execute()) {
                    $_SESSION['message'] = "Film ajouté à votre watchlist.";
                } else {
                    $_SESSION['error'] = "Erreur lors de l'ajout du film: " . $stmtInsert->error;
                    error_log("Watchlist insert failed: " . $stmtInsert->error);
                }
                $stmtInsert->close();
            } else {
                 $_SESSION['error'] = "Erreur préparation insertion watchlist.";
                 error_log("Watchlist insert prepare failed: " . $conn->error);
            }
        } else {
            $_SESSION['message'] = "Ce film est déjà dans votre watchlist.";
        }
        $stmtCheck->close();
    } else {
        $_SESSION['error'] = "Erreur préparation vérification watchlist.";
        error_log("Watchlist check prepare failed: " . $conn->error);
    }
} 

header('Location: ' . $redirectUrl);
exit;