<?php
session_start();
require_once "../PHP/data.php";

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);


    $db->query("DELETE FROM favoris WHERE id_cli = $id");
    

    $db->query("DELETE FROM rendez_vous WHERE id_client = $id"); 
    

    
    $db->query("DELETE FROM vues_recentes WHERE id_user = $id");

    // 2. SUPPRESSION DU CLIENT
    $sql = "DELETE FROM client WHERE id_cli = $id";

    if ($db->query($sql)) {
        header("Location: liste_clients.php?msg=deleted");
        exit();
    } else {
        echo "Erreur lors de la suppression : " . $db->error;
    }
} else {
    header("Location: ../Accueil/ADMIN.php");
    exit();
}
?>