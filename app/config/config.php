<?php
// app/config/config.php

// ─────────────────────────────────────────────────────────────────────────────
// 0) Site Information
// ─────────────────────────────────────────────────────────────────────────────
define('SITE_NAME', getenv('SITE_NAME') ?: 'EigaNights');

// ─────────────────────────────────────────────────────────────────────────────
// 1) DEV error reporting (Consider disabling display_errors in production on Herogu)
// ─────────────────────────────────────────────────────────────────────────────
// Herogu panel might control this. Forcing it here can be useful for debugging deployment.
ini_set('display_errors', 1); // SET TO 0 IN PRODUCTION after debugging
ini_set('display_startup_errors', 1); // SET TO 0 IN PRODUCTION
error_reporting(E_ALL);

// ─────────────────────────────────────────────────────────────────────────────
// 2) Session setup
// ─────────────────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Consider more secure session settings for production
    // session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => $_SERVER['HTTP_HOST'], 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ─────────────────────────────────────────────────────────────────────────────
// 3) Database settings
// ─────────────────────────────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'eiganights');
define('DB_USER', getenv('DB_USER') ?: 'Alfa345'); // Replace with your local fallback if needed
define('DB_PASS', getenv('DB_PASS') ?: 'GOON');    // Replace with your local fallback if needed

global $conn;
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($mysqli->connect_errno) {
    // For a 502 error, this die() will prevent the page from rendering,
    // and PHP-FPM might crash, leading to the Bad Gateway.
    // It's better to log and let the application try to handle it if possible,
    // or show a custom error page. However, for initial setup, this is direct.
    error_log("MySQL connect failed ({$mysqli->connect_errno}): {$mysqli->connect_error}");
    // In a production environment, you wouldn't die here but rather throw an exception
    // or set an error flag that your front controller/error handler can catch.
    // For now, to debug the 502, we need to know if this is the point of failure.
    die("Database connection error. Please check server logs. Error: ({$mysqli->connect_errno}) {$mysqli->connect_error}");
}
$mysqli->set_charset('utf8mb4');
$conn = $mysqli;


// ─────────────────────────────────────────────────────────────────────────────
// 4) TMDB API key
// ─────────────────────────────────────────────────────────────────────────────
define('TMDB_API_KEY', getenv('TMDB_API_KEY') ?: '94fc3b99fd623dc63ae00ab80ca1b255');

// ─────────────────────────────────────────────────────────────────────────────
// 5) Base URL helper
// ─────────────────────────────────────────────────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';

// If DocumentRoot is `/public`, SCRIPT_NAME is likely `/index.php`.
// dirname(SCRIPT_NAME) would be `/`.
// If site is in a subdirectory like `localhost/eiganights/public/` -> SCRIPT_NAME is `/eiganights/public/index.php`
// then dirname is `/eiganights/public`. We need to remove `/public`.
$script_name_path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_path = '';

if ($script_name_path !== '/' && $script_name_path !== '\\' && $script_name_path !== '.') {
    if (substr($script_name_path, -7) === '/public') {
        $base_path = substr($script_name_path, 0, -7);
    } else {
        $base_path = $script_name_path;
    }
}
$base_path = rtrim($base_path, '/'); // Remove trailing slash if any

define('BASE_URL', $protocol . '://' . $domain . $base_path . '/');


// ─────────────────────────────────────────────────────────────────────────────
// 6) Monetization Settings
// ─────────────────────────────────────────────────────────────────────────────
define('PLACEHOLDER_ADS_ENABLED', true);
define('RANDOM_GIF_ADS_DIRECTORY', 'assets/videos/');
define('DEFAULT_AD_GIF_ALT_TEXT', 'Publicité animée EigaNights');

define('DIRECT_STREAMING_LINKS_ENABLED', true);
define('ALLOWED_API_REGIONS', ['FR', 'US']);
define('STREAMING_PLATFORMS_OFFICIAL_LINKS', [
    8 => ['name' => 'Netflix', 'logo' => 'assets/images/netflix_logo.png', 'search_url_pattern' => 'https://www.netflix.com/search?q={MOVIE_TITLE_URL_ENCODED}'],
    10 => ['name' => 'Amazon Prime Video', 'logo' => 'assets/images/primevideo_logo.png', 'search_url_pattern' => 'https://www.primevideo.com/search/?phrase={MOVIE_TITLE_URL_ENCODED}'],
    337 => ['name' => 'Disney+', 'logo' => 'assets/images/disney_logo.png', 'search_url_pattern' => 'https://www.disneyplus.com/search?q={MOVIE_TITLE_URL_ENCODED}'],
    2 => ['name' => 'Apple TV', 'logo' => 'assets/images/appletv_logo.png', 'search_url_pattern' => 'https://tv.apple.com/search?term={MOVIE_TITLE_URL_ENCODED}'],
]);

// ─────────────────────────────────────────────────────────────────────────────
// 7) ReCAPTCHA & SMTP Settings (Prioritize Environment Variables)
// ─────────────────────────────────────────────────────────────────────────────
define('RECAPTCHA_SITE_KEY_V3', getenv('RECAPTCHA_SITE_KEY_V3') ?: ''); // Fallback to empty if not set
define('RECAPTCHA_SECRET_KEY_V3', getenv('RECAPTCHA_SECRET_KEY_V3') ?: '');
define('RECAPTCHA_SITE_KEY_V2', getenv('RECAPTCHA_SITE_KEY_V2') ?: '');
define('RECAPTCHA_SECRET_KEY_V2', getenv('RECAPTCHA_SECRET_KEY_V2') ?: '');

define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ? (int)getenv('SMTP_PORT') : 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls'); // e.g., 'tls' or 'ssl'
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: SITE_NAME);

// Helpers functions inclusion is handled by public/index.php
?>