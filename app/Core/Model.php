<?php
// app/Core/Model.php
namespace App\Core;

abstract class Model {
    protected static $db = null;

    /**
     * Récupère la connexion à la base de données.
     * @return \mysqli L'objet de connexion mysqli.
     * @throws \Exception si la connexion échoue.
     */
    protected static function getDB() {
        if (self::$db === null) {
            // $conn est global dans config.php
            global $conn; // Accéder à la variable globale $conn définie dans config.php
            if ($conn instanceof \mysqli && $conn->ping()) {
                self::$db = $conn;
            } else {
                // Tentative de re-connexion si $conn n'est pas valide ou non défini globalement ici
                $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
                if ($mysqli->connect_errno) {
                    error_log("MySQL connect failed in Model ({$mysqli->connect_errno}): {$mysqli->connect_error}");
                    throw new \Exception("Database connection error in Model.");
                }
                $mysqli->set_charset('utf8mb4');
                self::$db = $mysqli;
                // Optionnel: réassigner à $conn global si d'autres parties du code y comptent
                // $conn = self::$db; 
            }
        }
        return self::$db;
    }
}