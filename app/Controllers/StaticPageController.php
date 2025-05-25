<?php
// app/Controllers/StaticPageController.php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\FaqItem;

class StaticPageController extends Controller {

    public function faq() {
        $pageTitle = "FAQ - " . (defined('SITE_NAME') ? SITE_NAME : "EigaNights");
        
        // Utiliser le modèle pour récupérer les FAQs
        $faqsFromDb = FaqItem::getAll(); // Supposons que FaqItem::getAll() existe
        $fetch_error = null; // Le modèle pourrait gérer l'état d'erreur

        if (empty($faqsFromDb) && !$fetch_error) {
            $faqs = [ /* Vos FAQs par défaut comme dans l'original faq.php */ 
                ['question' => "Comment noter un film ?", 'answer' => "..."],
                // ... autres faqs par défaut ...
            ];
        } else {
            $faqs = $faqsFromDb;
        }

        $this->renderLayout('static/faq.php', [
            'pageTitle' => $pageTitle,
            'faqs' => $faqs,
            'fetch_error' => $fetch_error 
        ]);
    }

    public function terms() {
        $pageTitle = "Conditions Générales d'Utilisation - " . (defined('SITE_NAME') ? SITE_NAME : "EigaNights");
        $siteName = defined('SITE_NAME') ? SITE_NAME : "EigaNights";
        
        $termsContent = null;
        // Le chemin est relatif à la racine du projet où se trouve le dossier 'content'
        $contentFilePath = __DIR__ . '/../../content/terms_content.html'; // Ajustez si 'content' est ailleurs
        $usingFileContent = false;
        $termsDisplayTitle = "Conditions Générales d'Utilisation"; // Titre par défaut

        if (file_exists($contentFilePath) && is_readable($contentFilePath)) {
            $fileContent = @file_get_contents($contentFilePath);
            if ($fileContent !== false && !empty(trim($fileContent))) {
                $termsContent = $fileContent;
                $usingFileContent = true;
            }
        }

        if ($termsContent === null) {
            // Contenu par défaut des termes comme dans votre terms.php original
            $termsContent = '
                <p><strong>Dernière mise à jour :</strong> '.date('d F Y').'</p>
                <hr>
                <p>Bienvenue sur '.htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8').' ! ...</p>
                '; // Raccourci pour l'exemple
        }

        $this->renderLayout('static/terms.php', [
            'pageTitle'         => $pageTitle,
            'termsContent'      => $termsContent,
            'usingFileContent'  => $usingFileContent,
            'termsDisplayTitle' => $termsDisplayTitle
        ]);
    }

    public function contact() {
        $pageTitle = "Contactez-nous - Eiganights";
        $message_sent = false;
        $error_message = '';
        $form_data = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Mettez ici votre logique de traitement du formulaire de contact.
            // Idéalement, utilisez un service de messagerie ou un modèle pour cela.
            $form_data['name'] = trim($_POST['name'] ?? '');
            // ... (reste de votre logique de contact.php)

            // Exemple simple:
            if (empty($form_data['name']) /* ... */) {
                $error_message = "Tous les champs sont requis.";
            } else {
                // Simuler l'envoi
                $message_sent = true;
                 $_SESSION['contact_message_log'] = "Message de: {$form_data['name']}...";
                $form_data = ['name' => '', 'email' => '', 'subject' => '', 'message' => '']; // Reset
            }
        }

        $this->renderLayout('static/contact.php', [
            'pageTitle' => $pageTitle,
            'message_sent' => $message_sent,
            'error_message' => $error_message,
            'form_data' => $form_data
        ]);
    }
}