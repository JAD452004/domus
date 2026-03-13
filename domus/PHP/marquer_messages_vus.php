<?php
session_start();
require_once "data.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['id_rdv'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$id_rdv = intval($_POST['id_rdv']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Vérifier l'accès à ce rendez-vous
if ($role === 'client') {
    $check_sql = "SELECT id_rdv FROM rendez_vous WHERE id_rdv = ? AND id_client = ?";
} else {
    $check_sql = "SELECT r.id_rdv FROM rendez_vous r 
                  JOIN maison m ON r.id_maison = m.id_maison 
                  WHERE r.id_rdv = ? AND m.id_pro = ?";
}

$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("ii", $id_rdv, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Marquer tous les messages non expédiés par l'utilisateur comme "délivré" d'abord
$update_delivre = "UPDATE chat_messages 
                   SET statut = 'delivre' 
                   WHERE id_rdv = ? 
                   AND id_expediteur != ? 
                   AND statut = 'envoye'";
$stmt_delivre = $db->prepare($update_delivre);
$stmt_delivre->bind_param("ii", $id_rdv, $user_id);
$stmt_delivre->execute();

// Puis marquer comme "vu" si l'utilisateur est actif
$update_vu = "UPDATE chat_messages 
              SET statut = 'vu', date_vu = NOW() 
              WHERE id_rdv = ? 
              AND id_expediteur != ? 
              AND statut != 'vu'";
$stmt_vu = $db->prepare($update_vu);
$stmt_vu->bind_param("ii", $id_rdv, $user_id);
$stmt_vu->execute();

echo json_encode([
    'success' => true,
    'delivre' => $stmt_delivre->affected_rows,
    'vu' => $stmt_vu->affected_rows
]);
?>