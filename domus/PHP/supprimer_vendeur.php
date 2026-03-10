<?php
session_start();
require_once "../PHP/data.php";

// 1. Vérification de sécurité : l'ID doit être présent
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    // 2. NETTOYAGE DES DÉPENDANCES
    // On récupère d'abord les IDs des maisons du vendeur pour nettoyer les favoris et RDV liés à ses maisons
    $maisons_vendeur = $db->query("SELECT id_maison FROM maison WHERE id_pro = $id");
    while($m = $maisons_vendeur->fetch_assoc()) {
        $id_m = $m['id_maison'];
        $db->query("DELETE FROM favoris WHERE id_maison = $id_m");
        $db->query("DELETE FROM rendez_vous WHERE id_maison = $id_m");
    }

    // 3. SUPPRESSION DES MAISONS DU VENDEUR
    $db->query("DELETE FROM maison WHERE id_pro = $id");

    // 4. SUPPRESSION DU VENDEUR
    // Note : on utilise id_pro car c'est le nom de la colonne dans ta table 'proprietaire'
    $sql = "DELETE FROM proprietaire WHERE id_pro = $id";

    if ($db->query($sql)) {
        // Redirection vers la liste avec un message de succès
        header("Location: liste_vendeurs.php?msg=deleted");
        exit();
    } else {
        echo "Erreur lors de la suppression du vendeur : " . $db->error;
    }
} else {
    // Si pas d'ID, retour au dashboard
    header("Location: ADMIN.php");
    exit();
}
?>