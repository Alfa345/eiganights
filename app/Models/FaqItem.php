<?php
// app/Models/FaqItem.php
namespace App\Models;

use App\Core\Model;

class FaqItem extends Model {
    public static function getAll() {
        $conn = self::getDB();
        $faqs = [];
        $sql = "SELECT question, answer FROM faq_items ORDER BY sort_order ASC, id ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $faqs[] = $row;
            }
            $result->free();
        } else {
            error_log("Erreur lors de la récupération des FAQs: " . $conn->error);
            // Vous pourriez lancer une exception ou retourner un indicateur d'erreur
        }
        return $faqs;
    }

    // ... (autres méthodes CRUD pour les FAQs si gérées par admin)
}