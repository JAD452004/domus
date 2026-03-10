<?php
session_start();
require_once "data.php";

// Vérification : l'utilisateur doit être connecté et l'ID de la maison présent
if (isset($_SESSION['user_id']) && isset($_GET['id'])) {
    $id_maison = (int)$_GET['id'];
    $id_pro = $_SESSION['user_id'];

    // 1. NETTOYAGE DES DÉPENDANCES
    // On supprime d'abord les rendez-vous et favoris liés à cette maison
    // pour éviter que SQL ne bloque la suppression de la maison elle-même.
    $db->query("DELETE FROM rendez_vous WHERE id_maison = $id_maison");
    $db->query("DELETE FROM favoris WHERE id_maison = $id_maison");
    $db->query("DELETE FROM vues_recentes WHERE id_maison = $id_maison");

    // 2. SUPPRESSION DE LA MAISON
    // On vérifie bien que l'id_pro correspond pour qu'un vendeur ne puisse pas supprimer la maison d'un autre
    $stmt = $db->prepare("DELETE FROM maison WHERE id_maison = ? AND id_pro = ?");
    $stmt->bind_param("ii", $id_maison, $id_pro);
    
    if ($stmt->execute()) {
        // Succès
        header("Location: ../Accueil/propriete.php?msg=deleted");
    } else {
        // Erreur
        echo "Erreur lors de la suppression : " . $db->error;
    }
} else {
    header("Location: ../Accueil/propriete.php");
}
exit();