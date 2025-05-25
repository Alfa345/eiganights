<?php
// app/Controllers/MovieController.php
namespace App\Controllers;
use App\Core\Controller;

class MovieController extends Controller {

    public function details($id) {
        global $conn; // from config.php, needed for now
        $movieId = (int)$id;
        
        // The extensive logic from the existing movie_details.php needs to be moved here.
        // This includes:
        // 1. Fetching TMDB data (details, credits, videos, providers)
        // 2. Fetching local data (watchlist status, user rating/comment, public comments, scene annotations)
        // 3. Preparing all data for the view.
        // 4. Handling errors from TMDB or DB.

        // For now, a simplified pass-through to illustrate:
        $pageTitle = "Détails du Film - " . SITE_NAME;
        $movieDetailsFromFile = null; // This would be the data fetched

        // --- Simulate fetching or indicate logic needs to be moved ---
        // This is a placeholder. The actual movie_details.php logic is complex.
        // The original movie_details.php script should eventually become the view file 'movie/details.php'
        // and its PHP logic moved into this controller action.
        
        // --- START of logic adapted from movie_details.php (highly abridged for stub) ---
        $pageError = null;
        $movieDetailsAPI = null;
        $posterUrl = BASE_URL . "assets/images/no_poster_available.png";
        $displayTitle = "Film " . $movieId;

        if (!defined('TMDB_API_KEY') || empty(TMDB_API_KEY) || TMDB_API_KEY === 'YOUR_ACTUAL_TMDB_API_KEY') {
            $pageError = "Erreur de configuration TMDB API.";
        } else {
            $tmdbApiKey = urlencode(TMDB_API_KEY);
            $detailsUrl = "https://api.themoviedb.org/3/movie/{$movieId}?api_key={$tmdbApiKey}&language=fr-FR&append_to_response=credits,videos,watch/providers";
            
            // Simplified TMDB fetch (error handling should be robust as in original file)
            $responseJson = @file_get_contents($detailsUrl);
            if ($responseJson) {
                $data = json_decode($responseJson, true);
                if (isset($data['id'])) {
                    $movieDetailsAPI = $data; // This contains all appended responses too
                    $displayTitle = htmlspecialchars($movieDetailsAPI['title'] ?? 'Titre inconnu');
                    $pageTitle = $displayTitle . " - " . SITE_NAME;
                    if(!empty($movieDetailsAPI['poster_path'])) {
                        $posterUrl = "https://image.tmdb.org/t/p/w500" . htmlspecialchars($movieDetailsAPI['poster_path']);
                    }
                } else {
                    $pageError = "Film non trouvé ou erreur API TMDB.";
                    if (isset($data['status_message'])) $pageError = htmlspecialchars($data['status_message']);
                }
            } else {
                $pageError = "Impossible de contacter le service de films (TMDB).";
            }
        }
        // ... (More data fetching: watchlist, ratings, comments, scene annotations from DB) ...
        // For the sake of this stub, we'll pass minimal data.
        // The view movie/details.php will need to be adapted to use these passed variables.
        // --- END of abridged logic ---

        $this->renderLayout('movie/details.php', [
            'pageTitle' => $pageTitle,
            'pageError' => $pageError,
            'movieId' => $movieId,
            'movieDetailsAPI' => $movieDetailsAPI, // Contains ['credits'], ['videos'], ['watch/providers']
            'displayTitle' => $displayTitle,
            'posterUrl' => $posterUrl, // Example, view needs more
            // Pass other necessary data like $isInWatchlist, $userRating, etc.
            'loggedInUserId' => $_SESSION['user_id'] ?? null,
            'isInWatchlist' => false, // Placeholder
            'userRating' => null, // Placeholder
            'userCommentText' => '', // Placeholder
            'publicComments' => [], // Placeholder
            'sceneAnnotationThreads' => [], // Placeholder
            'siteName' => SITE_NAME,
            // These are specifically used by movie_details.php view
            'movieCreditsAPI' => $movieDetailsAPI['credits'] ?? null,
            'movieVideosAPI' => $movieDetailsAPI['videos']['results'] ?? null,
            'movieWatchProvidersAPI' => $movieDetailsAPI['watch/providers']['results'] ?? null,
            'posterPath' => $movieDetailsAPI['poster_path'] ?? null,
            'releaseYear' => !empty($movieDetailsAPI['release_date']) ? substr($movieDetailsAPI['release_date'], 0, 4) : 'N/A',
            'tagline' => !empty($movieDetailsAPI['tagline']) ? htmlspecialchars($movieDetailsAPI['tagline']) : '',
            'genres' => !empty($movieDetailsAPI['genres']) ? htmlspecialchars(implode(', ', array_column($movieDetailsAPI['genres'], 'name'))) : 'N/A',
            'runtimeMinutes' => $movieDetailsAPI['runtime'] ?? 0,
            'runtime' => ($movieDetailsAPI['runtime'] ?? 0) > 0 ? $movieDetailsAPI['runtime'] . ' minutes' : 'N/A',
            'tmdbVoteAverage' => !empty($movieDetailsAPI['vote_average']) && $movieDetailsAPI['vote_average'] > 0 ? number_format($movieDetailsAPI['vote_average'], 1) . '/10' : 'N/A',
            'tmdbVoteCount' => (int)($movieDetailsAPI['vote_count'] ?? 0),
            'overview' => !empty($movieDetailsAPI['overview']) ? nl2br(htmlspecialchars($movieDetailsAPI['overview'])) : 'Synopsis non disponible.',
            'trailerKey' => null, // Placeholder, logic to find it needed
            'directorsFormatted' => 'N/A', // Placeholder
            'cast' => !empty($movieDetailsAPI['credits']['cast']) ? array_slice($movieDetailsAPI['credits']['cast'], 0, 10) : [], // Placeholder
        ]);
    }
    // TODO: Add actions for addToWatchlist, removeFromWatchlist, rateOrComment
    // These would involve moving logic from add.php, remove_from_watchlist.php, rate_comment.php
}