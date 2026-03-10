<?php
session_start();
require_once "data.php";

// Activer le rapport d'erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$id_maison = intval($_GET['id']);
$ip_visiteur = $_SERVER['REMOTE_ADDR'];
$session_id = session_id();
$date_actuelle = date('Y-m-d H:i:s');
$date_jour = date('Y-m-d');

// Vérifier si la table vues_maison existe, sinon la créer
$db->query("CREATE TABLE IF NOT EXISTS vues_maison (
    id_vue INT AUTO_INCREMENT PRIMARY KEY,
    id_maison INT NOT NULL,
    ip_visiteur VARCHAR(45) NOT NULL,
    date_vue DATETIME NOT NULL,
    session_id VARCHAR(255),
    INDEX (id_maison),
    INDEX (ip_visiteur),
    UNIQUE KEY unique_vue_par_jour (id_maison, ip_visiteur, DATE(date_vue))
)");

// Vérifier si cette IP a déjà vu cette propriété aujourd'hui
$check_query = "SELECT id_vue FROM vues_maison 
                WHERE id_maison = ? AND ip_visiteur = ? AND DATE(date_vue) = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->bind_param("iss", $id_maison, $ip_visiteur, $date_jour);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    // Nouvelle vue unique aujourd'hui pour cette IP
    
    // 1. Enregistrer dans la table des vues
    $insert_query = "INSERT INTO vues_maison (id_maison, ip_visiteur, date_vue, session_id) 
                     VALUES (?, ?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bind_param("isss", $id_maison, $ip_visiteur, $date_actuelle, $session_id);
    
    if ($insert_stmt->execute()) {
        // 2. Incrémenter le compteur dans la table maison
        $update_query = "UPDATE maison SET vues = vues + 1 WHERE id_maison = ?";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("i", $id_maison);
        $update_stmt->execute();
        
        error_log("Nouvelle vue unique enregistrée - Propriété: $id_maison, IP: $ip_visiteur");
    }
} else {
    error_log("Vue déjà comptée aujourd'hui - Propriété: $id_maison, IP: $ip_visiteur");
}

// Rediriger vers la page de détails
header("Location: details.php?id=" . $id_maison);
exit();
?>