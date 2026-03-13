<?php
session_start();
require_once "data.php";

header('Content-Type: application/json');

if (!isset($_GET['id_rdv']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['messages' => [], 'total' => 0]);
    exit();
}

$id_rdv = intval($_GET['id_rdv']);
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
$user_id = $_SESSION['user_id'];

// Récupérer les nouveaux messages
$sql = "SELECT m.*, 
               c.nom_complet as nom_client, 
               p.nom_complet as nom_vendeur
        FROM chat_messages m
        JOIN rendez_vous r ON m.id_rdv = r.id_rdv
        LEFT JOIN client c ON r.id_client = c.id_cli
        LEFT JOIN maison ma ON r.id_maison = ma.id_maison
        LEFT JOIN proprietaire p ON ma.id_pro = p.id_pro
        WHERE m.id_rdv = ? AND m.id_msg > ?
        ORDER BY m.date_envoi ASC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ii", $id_rdv, $last_id);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id_msg'],
        'id_expediteur' => $row['id_expediteur'],
        'role_exp' => $row['role_exp'],
        'message' => nl2br(htmlspecialchars($row['message'])),
        'date_envoi' => $row['date_envoi'],
        'statut' => $row['statut'],
        'type_fichier' => $row['type_fichier'],
        'chemin_fichier' => $row['chemin_fichier'],
        'nom_fichier_original' => $row['nom_fichier_original'],
        'nom_client' => $row['nom_client'],
        'nom_vendeur' => $row['nom_vendeur']
    ];
}

// Compter le nombre total de messages
$count_sql = "SELECT COUNT(*) as total FROM chat_messages WHERE id_rdv = ?";
$count_stmt = $db->prepare($count_sql);
$count_stmt->bind_param("i", $id_rdv);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];

echo json_encode([
    'messages' => $messages,
    'total' => $total
]);
?>