<?php
session_start();
require_once "../PHP/data.php"; 

// Vérification stricte
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur' || !isset($_SESSION['user_id'])) { 
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// Vérifier que les paramètres existent
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    header("Location: liste_rdv.php?error=Paramètres manquants");
    exit();
}

$id = intval($_GET['id']);
$id_pro = $_SESSION['user_id'];

// Vérifier que ce rendez-vous appartient bien à ce vendeur
$check_sql = "SELECT r.id_rdv FROM rendez_vous r 
              JOIN maison m ON r.id_maison = m.id_maison 
              WHERE r.id_rdv = ? AND m.id_pro = ?";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("ii", $id, $id_pro);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header("Location: liste_rdv.php?error=Vous n'avez pas accès à ce rendez-vous");
    exit();
}

if ($_GET['action'] === 'status' && isset($_GET['statut'])) {
    // Convertir les valeurs de l'interface vers celles de la base de données
    if ($_GET['statut'] === 'accepte') {
        $statut_final = 'confirme';
        $message = 'Rendez-vous accepté';
    } elseif ($_GET['statut'] === 'refuse') {
        $statut_final = 'refuse'; // Changé de 'annule' à 'refuse' pour cohérence
        $message = 'Rendez-vous refusé';
    } else {
        header("Location: liste_rdv.php?error=Statut invalide");
        exit();
    }
    
    $sql = "UPDATE rendez_vous SET statut = ? WHERE id_rdv = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $statut_final, $id);
    
    if ($stmt->execute()) {
        header("Location: liste_rdv.php?success=" . urlencode($message));
    } else {
        header("Location: liste_rdv.php?error=Erreur lors de la mise à jour");
    }
} 
elseif ($_GET['action'] === 'delete') {
    $sql = "DELETE FROM rendez_vous WHERE id_rdv = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: liste_rdv.php?success=Rendez-vous supprimé");
    } else {
        header("Location: liste_rdv.php?error=Erreur lors de la suppression");
    }
} else {
    header("Location: liste_rdv.php?error=Action non reconnue");
}
exit();
?>