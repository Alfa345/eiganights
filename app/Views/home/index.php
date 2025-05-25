<?php // app/Views/home/index.php
// $pageTitle, $trendingMovies, $popularMovies, etc. sont passés par le contrôleur.
// BASE_URL est défini dans config.php

// Réutiliser votre fonction display_movie_grid_section si elle est globale
// Si elle est dans app/Helpers/functions.php et ce fichier est inclus:
if (!function_exists('display_movie_grid_section')) {
    // Définition ici temporairement, ou s'assurer qu'elle est incluse globalement
    function display_movie_grid_section($title, $movies, $section_id = '') {
        if (empty($movies)) return;
        $section_id_attr = $section_id ? 'id="' . htmlspecialchars($section_id) . '"' : '';
        echo '<section class="movie-list-section card" ' . $section_id_attr . ' aria-labelledby="' . htmlspecialchars(strtolower(str_replace(' ', '-', $title))) . '-heading">';
        echo '  <h2 id="' . htmlspecialchars(strtolower(str_replace(' ', '-', $title))) . '-heading">' . htmlspecialchars($title) . '</h2>';
        echo '  <div class="movies-grid homepage-grid">';
        foreach ($movies as $movie) {
            if (empty($movie['id']) || empty($movie['title'])) continue;
            $movie_id = (int)$movie['id'];
            $movie_title_html = htmlspecialchars($movie['title']);
            $poster_path = $movie['poster_path'] ?? null;
            $release_year = !empty($movie['release_date']) ? substr($movie['release_date'], 0, 4) : '';
            $link_title_attr = htmlspecialchars($movie_title_html . ($release_year ? " ({$release_year})" : ''));
            $poster_url = $poster_path
                ? "https://image.tmdb.org/t/p/w300" . htmlspecialchars($poster_path)
                : BASE_URL . "assets/images/no_poster_available.png"; // Utilisez BASE_URL
            $poster_alt = $poster_path ? "Affiche de " . $movie_title_html : "Pas d'affiche disponible";
            
            echo '<article class="movie-item">';
            // Notez l'utilisation de BASE_URL et de la nouvelle structure de route 'movie/details/'
            echo '  <a href="' . BASE_URL . 'movie/details/' . $movie_id . '" title="' . $link_title_attr . '" aria-label="Détails pour ' . $link_title_attr . '" class="movie-poster-link">';
            echo '    <img src="' . $poster_url . '" alt="' . htmlspecialchars($poster_alt) . '" loading="lazy" class="movie-poster-grid"/>';
            echo '  </a>';
            echo '  <div class="movie-item-info">';
            echo '    <h3 class="movie-item-title"><a href="' . BASE_URL . 'movie/details/' . $movie_id . '">' . $movie_title_html . '</a></h3>';
            if ($release_year) {
                echo '    <p class="movie-item-year">' . $release_year . '</p>';
            }
            echo '  </div>';
            echo '</article>';
        }
        echo '  </div>';
        echo '</section>';
    }
}
?>
<main class="container homepage-content">
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (isset($fetchError) && $fetchError): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($fetchError); ?></div>
    <?php endif; ?>

    <?php if (!empty($searchResults)): ?>
        <?php display_movie_grid_section('Résultats pour "' . $searchQueryDisplay . '"', $searchResults, 'search-results'); ?>
    <?php else: ?>
        <?php display_movie_grid_section('Films à la Tendance cette semaine', $trendingMovies, 'trending-movies'); ?>
        <?php display_movie_grid_section('Films Populaires du Moment', $popularMovies, 'popular-movies'); ?>
        <?php display_movie_grid_section('Films les Mieux Notés', $topRatedMovies, 'top-rated-movies'); ?>
        <?php display_movie_grid_section('Prochaines Sorties en France', $upcomingMovies, 'upcoming-movies'); ?>
    <?php endif; ?>
</main>