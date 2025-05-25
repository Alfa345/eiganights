<?php
// app/Controllers/HomeController.php
namespace App\Controllers;

use App\Core\Controller;
// Vous aurez besoin d'un modèle pour interagir avec TMDB si ce n'est pas fait dans une fonction helper globale
// use App\Models\TmdbMovie; // Exemple

class HomeController extends Controller {

    public function index() {
        $pageTitle = "Accueil - " . (defined('SITE_NAME') ? SITE_NAME : "EigaNights");
        $number_of_movies_per_section = 12;

        // La logique de fetch_tmdb_movies et display_movie_grid_section
        // devrait être encapsulée. fetch_tmdb_movies pourrait être dans un modèle
        // ou un service. display_movie_grid_section est un helper de vue.

        // Supposons que fetch_tmdb_movies est dans app/Helpers/functions.php et inclus.
        // Ou mieux, dans un modèle TmdbMovieModel::fetchList('trending/movie/week', [], $limit)
        $trendingMovies = []; $popularMovies = []; $topRatedMovies = []; $upcomingMovies = []; $searchResults = [];
        $searchQueryDisplay = '';
        $fetchError = null;

        try {
            if (function_exists('fetch_tmdb_movies')) {
                if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                    $searchQueryParam = trim($_GET['search']);
                    $searchQueryDisplay = htmlspecialchars($searchQueryParam, ENT_QUOTES, 'UTF-8');
                    $pageTitle = "Recherche: " . $searchQueryDisplay . " - " . (defined('SITE_NAME') ? SITE_NAME : "EigaNights");
                    $searchResults = fetch_tmdb_movies('search/movie', ['query' => $searchQueryParam], 20);
                } else {
                    $trendingMovies = fetch_tmdb_movies('trending/movie/week', [], $number_of_movies_per_section);
                    $popularMovies = fetch_tmdb_movies('movie/popular', [], $number_of_movies_per_section);
                    $topRatedMovies = fetch_tmdb_movies('movie/top_rated', [], $number_of_movies_per_section);
                    $upcomingMovies = fetch_tmdb_movies('movie/upcoming', ['region' => 'FR'], $number_of_movies_per_section);
                }
            } else {
                $fetchError = "Helper fetch_tmdb_movies non disponible.";
                error_log($fetchError);
            }
        } catch (\Exception $e) {
            $fetchError = "Erreur lors de la récupération des films TMDB: " . $e->getMessage();
            error_log($fetchError);
        }


        $this->renderLayout('home/index.php', [
            'pageTitle' => $pageTitle,
            'trendingMovies' => $trendingMovies,
            'popularMovies' => $popularMovies,
            'topRatedMovies' => $topRatedMovies,
            'upcomingMovies' => $upcomingMovies,
            'searchResults' => $searchResults,
            'searchQueryDisplay' => $searchQueryDisplay,
            'fetchError' => $fetchError
            // La fonction display_movie_grid_section sera appelée directement dans la vue,
            // ou vous pourriez créer une vue partielle pour la grille de films.
        ]);
    }
}