<?php
session_start();
require_once "data.php";

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $id_user = $_SESSION['user_id'];
    
    // Supprime l'historique de cet utilisateur
    $sql = "DELETE FROM vues_recentes WHERE id_user = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_user);
    
    if ($stmt->execute()) {
        // Redirige vers le dashboard avec un succès
        header("Location: ../ACCUEIL/client.php?status=cleared");
    } else {
        header("Location: ../ACCUEIL/client.php?status=error");
    }
} else {
    header("Location: ../CONNECTION/connexionUser.php");
}
exit();