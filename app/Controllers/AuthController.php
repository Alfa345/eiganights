<?php
// app/Controllers/AuthController.php
namespace App\Controllers;

use App\Core\Controller;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AuthController extends Controller {

    public function login() {
        global $conn; // from config.php

        if (isset($_SESSION['user_id'])) {
            $this->redirect('profile'); // Use router-friendly redirect
            return;
        }

        $pageTitle = "Connexion - " . SITE_NAME;
        $error_message = '';
        $username_value = '';
        $redirectAfterLogin = 'profile'; // Default redirect path

        if (isset($_GET['redirect']) && !empty(trim($_GET['redirect']))) {
            $postedRedirectUrl = trim($_GET['redirect']);
            // Basic validation: ensure it's a relative path or same host, and not a logout/login/register page
            if ((!parse_url($postedRedirectUrl, PHP_URL_HOST) || parse_url($postedRedirectUrl, PHP_URL_HOST) == $_SERVER['HTTP_HOST']) &&
                !preg_match('/(logout|register|login|forgot-password|reset-password)/i', $postedRedirectUrl)) {
                // Prepend BASE_URL if it's not already an absolute URL from our site
                if (!preg_match("~^(?:f|ht)tps?://~i", $postedRedirectUrl)) {
                     $redirectAfterLogin = ltrim($postedRedirectUrl, '/');
                } else {
                    // If it's an absolute URL to our site, just take the path part
                    $path = parse_url($postedRedirectUrl, PHP_URL_PATH);
                    $basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
                    if (strpos($path, $basePath) === 0) {
                        $redirectAfterLogin = ltrim(substr($path, strlen($basePath)), '/');
                    } else {
                         $redirectAfterLogin = ltrim($path, '/');
                    }
                }
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recaptcha_token = $_POST['g-recaptcha-response'] ?? null;
            $recaptcha_valid = false;
            $recaptcha_action = 'login';

            $recaptchaConfigured = defined('RECAPTCHA_SITE_KEY_V3') && RECAPTCHA_SITE_KEY_V3 &&
                                   defined('RECAPTCHA_SECRET_KEY_V3') && RECAPTCHA_SECRET_KEY_V3;

            if ($recaptchaConfigured) {
                if (!empty($recaptcha_token)) {
                    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
                    $recaptcha_secret = RECAPTCHA_SECRET_KEY_V3;
                    $recaptcha_data = ['secret' => $recaptcha_secret, 'response' => $recaptcha_token, 'remoteip' => $_SERVER['REMOTE_ADDR']];
                    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($recaptcha_data)]];
                    $context = stream_context_create($options);
                    $verify_response_json = @file_get_contents($recaptcha_url, false, $context);

                    if ($verify_response_json !== false) {
                        $verify_response_data = json_decode($verify_response_json);
                        if ($verify_response_data && $verify_response_data->success &&
                            isset($verify_response_data->score) && $verify_response_data->score >= 0.5 &&
                            isset($verify_response_data->action) && $verify_response_data->action == $recaptcha_action) {
                            $recaptcha_valid = true;
                        } else {
                            $error_message = "Vérification reCAPTCHA v3 échouée ou score trop bas.";
                            error_log("reCAPTCHA v3 login failed: Score: " . ($verify_response_data->score ?? 'N/A') . " Action: " . ($verify_response_data->action ?? 'N/A'));
                        }
                    } else { $error_message = "Impossible de vérifier le reCAPTCHA v3."; }
                } else { $error_message = "Token reCAPTCHA v3 manquant."; }
            } else {
                error_log("WARN: RECAPTCHA_SITE_KEY_V3 or RECAPTCHA_SECRET_KEY_V3 not configured.");
                $recaptcha_valid = true; // Bypass for dev if not configured
            }

            if ($recaptcha_valid) {
                if (!isset($_POST['username'], $_POST['password']) || empty(trim($_POST['username'])) || empty($_POST['password'])) {
                    $error_message = "Nom d'utilisateur et mot de passe requis.";
                } else {
                    $username = trim($_POST['username']);
                    $password = $_POST['password'];
                    $username_value = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

                    $sql = "SELECT id, username, password, role, is_banned FROM users WHERE username = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($user = $result->fetch_assoc()) {
                            if ($user['is_banned'] == 1) {
                                $error_message = "Votre compte a été suspendu.";
                            } elseif (password_verify($password, $user['password'])) {
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['role'] = $user['role'];
                                session_regenerate_id(true); // Security best practice
                                $this->redirect($redirectAfterLogin);
                                return;
                            } else { $error_message = "Nom d'utilisateur ou mot de passe incorrect."; }
                        } else { $error_message = "Nom d'utilisateur ou mot de passe incorrect."; }
                        $stmt->close();
                    } else { $error_message = "Erreur système (L01)."; error_log("DB Prepare Error: " . $conn->error); }
                }
            }
            if (!$recaptcha_valid && isset($_POST['username'])) { // Repopulate on reCAPTCHA fail
                $username_value = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
            }
        }

        $this->renderLayout('auth/login.php', [
            'pageTitle' => $pageTitle,
            'error_message' => $error_message,
            'username_value' => $username_value,
            'redirectAfterLoginQuery' => $redirectAfterLogin !== 'profile' ? '?redirect=' . urlencode($redirectAfterLogin) : '',
            'RECAPTCHA_SITE_KEY_V3' => defined('RECAPTCHA_SITE_KEY_V3') ? RECAPTCHA_SITE_KEY_V3 : null,
            'siteName' => SITE_NAME, // Pass site name for consistency if view needs it
        ]);
    }

    public function register() {
        global $conn;
        if (isset($_SESSION['user_id'])) {
            $this->redirect('profile');
            return;
        }

        $pageTitle = "Inscription - " . SITE_NAME;
        $error_message = '';
        $username_value = '';
        $email_value = '';
        $redirectAfterRegister = 'profile';

        if (isset($_GET['redirect']) && !empty(trim($_GET['redirect']))) {
            // Similar redirect logic as login...
            $postedRedirectUrl = trim($_GET['redirect']);
            if ((!parse_url($postedRedirectUrl, PHP_URL_HOST) || parse_url($postedRedirectUrl, PHP_URL_HOST) == $_SERVER['HTTP_HOST']) &&
                !preg_match('/(logout|register|login|forgot-password|reset-password)/i', $postedRedirectUrl)) {
                 if (!preg_match("~^(?:f|ht)tps?://~i", $postedRedirectUrl)) {
                     $redirectAfterRegister = ltrim($postedRedirectUrl, '/');
                } else {
                    $path = parse_url($postedRedirectUrl, PHP_URL_PATH);
                    $basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
                    if (strpos($path, $basePath) === 0) {
                        $redirectAfterRegister = ltrim(substr($path, strlen($basePath)), '/');
                    } else {
                         $redirectAfterRegister = ltrim($path, '/');
                    }
                }
            }
        }


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? null;
            $recaptcha_valid = false;
            $recaptchaConfigured = defined('RECAPTCHA_SITE_KEY_V2') && RECAPTCHA_SITE_KEY_V2 &&
                                   defined('RECAPTCHA_SECRET_KEY_V2') && RECAPTCHA_SECRET_KEY_V2;

            if ($recaptchaConfigured) {
                 if (!empty($recaptcha_response)) {
                    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
                    $recaptcha_secret = RECAPTCHA_SECRET_KEY_V2;
                    $recaptcha_data = ['secret' => $recaptcha_secret, 'response' => $recaptcha_response, 'remoteip' => $_SERVER['REMOTE_ADDR']];
                    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($recaptcha_data)]];
                    $context = stream_context_create($options);
                    $verify_response_json = @file_get_contents($recaptcha_url, false, $context);
                    if ($verify_response_json !== false) {
                        $verify_response_data = json_decode($verify_response_json);
                        if ($verify_response_data && $verify_response_data->success) {
                            $recaptcha_valid = true;
                        } else { $error_message = "Vérification reCAPTCHA v2 échouée."; error_log("reCAPTCHA v2 register failed: " . implode(', ', $verify_response_data->{'error-codes'} ?? []));}
                    } else { $error_message = "Impossible de vérifier le reCAPTCHA v2."; }
                } else { $error_message = "Veuillez compléter la vérification reCAPTCHA v2."; }
            } else {
                error_log("WARN: RECAPTCHA_SITE_KEY_V2 or RECAPTCHA_SECRET_KEY_V2 not configured.");
                $recaptcha_valid = true; // Bypass for dev if not configured
            }
            
            if ($recaptcha_valid) {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $password_confirm = $_POST['password_confirm'] ?? '';

                $username_value = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
                $email_value = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

                if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
                    $error_message = "Tous les champs sont requis.";
                } elseif (strlen($username) < 3 || strlen($username) > 50) {
                    $error_message = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères.";
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                    $error_message = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores (_).";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = "Veuillez fournir une adresse e-mail valide.";
                } elseif (strlen($password) < 6) {
                    $error_message = "Le mot de passe doit contenir au moins 6 caractères.";
                } elseif ($password !== $password_confirm) {
                    $error_message = "Les mots de passe ne correspondent pas.";
                } else {
                    // Check username existence
                    $stmtCheckUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $stmtCheckUser->bind_param("s", $username);
                    $stmtCheckUser->execute();
                    $stmtCheckUser->store_result();
                    if ($stmtCheckUser->num_rows > 0) $error_message = "Ce nom d'utilisateur est déjà pris.";
                    $stmtCheckUser->close();

                    // Check email existence
                    if (empty($error_message)) {
                        $stmtCheckEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $stmtCheckEmail->bind_param("s", $email);
                        $stmtCheckEmail->execute();
                        $stmtCheckEmail->store_result();
                        if ($stmtCheckEmail->num_rows > 0) $error_message = "Cette adresse e-mail est déjà utilisée.";
                        $stmtCheckEmail->close();
                    }

                    if (empty($error_message)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        if ($hashedPassword === false) {
                            $error_message = "Erreur de création de mot de passe."; error_log("password_hash failed");
                        } else {
                            $sqlInsert = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                            $stmtInsert = $conn->prepare($sqlInsert);
                            if ($stmtInsert) {
                                $stmtInsert->bind_param("sss", $username, $email, $hashedPassword);
                                if ($stmtInsert->execute()) {
                                    $newUserId = $conn->insert_id;
                                    $_SESSION['user_id'] = $newUserId;
                                    $_SESSION['username'] = $username;
                                    $_SESSION['role'] = 'user'; // Default role
                                    session_regenerate_id(true);
                                    $_SESSION['message'] = "Inscription réussie ! Bienvenue, " . htmlspecialchars($username) . ".";
                                    $this->redirect($redirectAfterRegister);
                                    return;
                                } else { $error_message = "Erreur création compte (R05)."; error_log("DB Insert Error: ".$stmtInsert->error); }
                                $stmtInsert->close();
                            } else { $error_message = "Erreur système (R04)."; error_log("DB Prepare Error: ".$conn->error); }
                        }
                    }
                }
            }
             if (empty($error_message) && !$recaptcha_valid) { // If other validations passed but recaptcha failed
                // Repopulate fields
                $username_value = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
                $email_value = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
            }
        }

        $this->renderLayout('auth/register.php', [
            'pageTitle' => $pageTitle,
            'error_message' => $error_message,
            'username_value' => $username_value,
            'email_value' => $email_value,
            'redirectAfterRegisterQuery' => $redirectAfterRegister !== 'profile' ? '?redirect=' . urlencode($redirectAfterRegister) : '',
            'RECAPTCHA_SITE_KEY_V2' => defined('RECAPTCHA_SITE_KEY_V2') ? RECAPTCHA_SITE_KEY_V2 : null,
            'siteName' => SITE_NAME,
        ]);
    }
    
    public function logout() {
        // This action simply redirects to the existing logout.php script
        // A "purer" MVC would have the logic here.
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        // Optional: Start a new session to set a message
        // session_start();
        // $_SESSION['message'] = "Vous avez été déconnecté.";
        $this->redirect(''); // Redirect to home
    }


    public function forgotPassword() {
        global $conn;
        $pageTitle = "Mot de Passe Oublié - " . SITE_NAME;
        $message = '';
        $error = '';
        $email_value = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
            $email = trim($_POST['email']);
            $email_value = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Veuillez fournir une adresse e-mail valide.";
            } else {
                $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? AND is_banned = 0");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($user = $result->fetch_assoc()) {
                        $user_id = $user['id'];
                        $token = bin2hex(random_bytes(32));
                        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                        $stmt_invalidate = $conn->prepare("UPDATE password_resets SET is_used = 1 WHERE user_id = ? AND is_used = 0 AND expires_at > NOW()");
                        if ($stmt_invalidate) { $stmt_invalidate->bind_param("i", $user_id); $stmt_invalidate->execute(); $stmt_invalidate->close(); }

                        $stmt_insert = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                        if ($stmt_insert) {
                            $stmt_insert->bind_param("iss", $user_id, $token, $expires_at);
                            if ($stmt_insert->execute()) {
                                $reset_link = BASE_URL . "reset-password?token=" . urlencode($token);
                                
                                // PHPMailer Logic
                                if (!defined('SMTP_HOST') || empty(SMTP_HOST)) {
                                     $error = "La configuration SMTP est manquante. Impossible d'envoyer l'e-mail.";
                                     error_log("SMTP_HOST not configured. Cannot send password reset email.");
                                } else {
                                    $mail = new PHPMailer(true);
                                    try {
                                        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // For debugging
                                        $mail->isSMTP();
                                        $mail->Host       = SMTP_HOST;
                                        $mail->SMTPAuth   = true;
                                        $mail->Username   = SMTP_USERNAME;
                                        $mail->Password   = SMTP_PASSWORD;
                                        if (defined('SMTP_SECURE') && !empty(SMTP_SECURE)) $mail->SMTPSecure = SMTP_SECURE == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
                                        $mail->Port       = SMTP_PORT;
                                        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                                        $mail->addAddress($email, $user['username']);
                                        $mail->isHTML(false);
                                        $mail->CharSet = 'UTF-8';
                                        $mail->Subject = 'Réinitialisation de mot de passe - ' . SITE_NAME;
                                        $mail->Body    = "Bonjour {$user['username']},\n\nPour réinitialiser votre mot de passe, veuillez cliquer sur ce lien:\n{$reset_link}\n\nCe lien expirera dans 1 heure.\n\nSi vous n'avez pas demandé ceci, veuillez ignorer cet e-mail.";
                                        $mail->send();
                                        $message = "Si un compte avec cet e-mail existe, un lien de réinitialisation a été envoyé.";
                                    } catch (Exception $e) {
                                        $error = "L'e-mail n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}";
                                        error_log("PHPMailer Error (Forgot Password): {$mail->ErrorInfo}");
                                    }
                                }
                            } else { $error = "Erreur lors de la création de la demande (FP01)."; error_log("DB Insert Error (password_resets): ".$stmt_insert->error); }
                            $stmt_insert->close();
                        } else { $error = "Erreur système (FP02)."; error_log("DB Prepare Error: ".$conn->error); }
                    } else { // User not found or banned - generic message
                        $message = "Si un compte avec cet e-mail existe et est actif, un lien de réinitialisation a été envoyé.";
                    }
                    $stmt->close();
                } else { $error = "Erreur système (FP03)."; error_log("DB Prepare Error: ".$conn->error); }
            }
        }
        $this->renderLayout('auth/forgot_password.php', [
            'pageTitle' => $pageTitle,
            'message' => $message,
            'error' => $error,
            'email_value' => $email_value,
            'siteName' => SITE_NAME,
        ]);
    }

    public function resetPassword() {
        global $conn;
        $pageTitle = "Réinitialiser Mot de Passe - " . SITE_NAME;
        $error = '';
        $message = '';
        $token_valid = false;
        $token_from_url = $_GET['token'] ?? null;
        $user_id_to_reset = null;

        if ($token_from_url) {
            $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ? AND is_used = 0");
            if ($stmt) {
                $stmt->bind_param("s", $token_from_url);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($reset_request = $result->fetch_assoc()) {
                    if (strtotime($reset_request['expires_at']) > time()) {
                        $token_valid = true;
                        $user_id_to_reset = $reset_request['user_id'];
                    } else { $error = "Ce lien de réinitialisation a expiré."; $conn->query("UPDATE password_resets SET is_used = 1 WHERE token = '".$conn->real_escape_string($token_from_url)."'"); }
                } else { $error = "Lien de réinitialisation invalide ou déjà utilisé."; }
                $stmt->close();
            } else { $error = "Erreur système (RP01)."; error_log("DB Prepare Error: ".$conn->error); }
        } else { $error = "Aucun token de réinitialisation fourni."; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            $submitted_token_form = $_POST['token'] ?? '';

            if ($submitted_token_form !== $token_from_url) {
                $error = "Incohérence du token."; $token_valid = false;
            } elseif (empty($password) || empty($password_confirm)) {
                $error = "Veuillez entrer et confirmer votre nouveau mot de passe.";
            } elseif (strlen($password) < 6) {
                $error = "Le mot de passe doit faire au moins 6 caractères.";
            } elseif ($password !== $password_confirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                if ($hashed_password === false) {
                    $error = "Erreur de hashage (RP02)."; error_log("password_hash failed for reset");
                } else {
                    $conn->begin_transaction();
                    try {
                        $stmt_update_user = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt_update_user->bind_param("si", $hashed_password, $user_id_to_reset);
                        $stmt_update_user->execute();
                        $stmt_update_user->close();

                        $stmt_mark_used = $conn->prepare("UPDATE password_resets SET is_used = 1 WHERE token = ?");
                        $stmt_mark_used->bind_param("s", $token_from_url);
                        $stmt_mark_used->execute();
                        $stmt_mark_used->close();
                        
                        $conn->commit();
                        $message = "Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous <a href='" . BASE_URL . "login'>connecter</a>.";
                        $token_valid = false; // Prevent form reshow
                    } catch (mysqli_sql_exception $e) {
                        $conn->rollback();
                        $error = "Erreur mise à jour mot de passe (RP03).";
                        error_log("Password reset transaction failed: " . $e->getMessage());
                    }
                }
            }
        }

        $this->renderLayout('auth/reset_password.php', [
            'pageTitle' => $pageTitle,
            'message' => $message,
            'error' => $error,
            'token_valid' => $token_valid,
            'token' => $token_from_url,
            'siteName' => SITE_NAME,
        ]);
    }
}