<?php
session_start();
require_once "data.php"; 

// Vérification stricte
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['user_id'])) { 
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// Vérifier que les paramètres existent
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    header("Location: mes_rendez_vous.php?error=Paramètres manquants");
    exit();
}

$id = intval($_GET['id']);
$id_client = $_SESSION['user_id'];
$action = $_GET['action'];

// Vérifier que ce rendez-vous appartient bien à ce client
$check_sql = "SELECT id_rdv, statut FROM rendez_vous WHERE id_rdv = ? AND id_client = ?";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("ii", $id, $id_client);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header("Location: mes_rendez_vous.php?error=Vous n'avez pas accès à ce rendez-vous");
    exit();
}

$rdv = $check_result->fetch_assoc();

// Gestion des différentes actions
if ($action === 'cancel') {
    // Annuler un rendez-vous (changer le statut)
    
    // Vérifier que le rendez-vous n'est pas déjà annulé ou refusé
    if (in_array($rdv['statut'], ['annule', 'refuse'])) {
        header("Location: mes_rendez_vous.php?error=Ce rendez-vous est déjà annulé");
        exit();
    }
    
    $sql = "UPDATE rendez_vous SET statut = 'annule' WHERE id_rdv = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: mes_rendez_vous.php?success=Rendez-vous annulé avec succès");
    } else {
        header("Location: mes_rendez_vous.php?error=Erreur lors de l'annulation");
    }
} 
elseif ($action === 'delete') {
   
 
    if (!in_array($rdv['statut'], ['annule', 'refuse'])) {
        header("Location: mes_rendez_vous.php?error=Seuls les rendez-vous annulés peuvent être supprimés");
        exit();
    }
    
    
    $sql = "DELETE FROM rendez_vous WHERE id_rdv = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: mes_rendez_vous.php?success=Rendez-vous supprimé définitivement");
    } else {
        header("Location: mes_rendez_vous.php?error=Erreur lors de la suppression");
    }
} 
else {
    header("Location: mes_rendez_vous.php?error=Action non reconnue");
}
exit();
?>