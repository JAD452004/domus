<?php
session_start();
require_once "data.php"; 

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // On exécute la suppression
    $db->query("DELETE FROM maison WHERE id_maison = $id");
}

// --- LOGIQUE DE REDIRECTION ---
// Si une session "proprietaire" existe, on le renvoie vers son espace
if (isset($_SESSION['id_pro'])) {
    header("Location: ../Accueil/propriete.php"); 
} 
// Sinon, c'est l'admin, on le renvoie vers le dashboard
else {
    header("Location: ../Accueil/ADMIN.php");
}
exit();