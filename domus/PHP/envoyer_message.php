<?php
session_start();
require_once "data.php";

// Vérifier les données requises
if(!isset($_POST['message']) || !isset($_POST['id_rdv']) || !isset($_SESSION['user_id'])) {
    http_response_code(400);
    exit('Données manquantes');
}

$id_rdv = intval($_POST['id_rdv']);
$exp_id = $_SESSION['user_id']; 
$role = $_SESSION['role']; 
$message = trim($_POST['message']);

if (empty($message)) {
    http_response_code(400);
    exit('Message vide');
}

// Protection des données sensibles
$email_pattern = '/[a-z0-9._%+-]+\s*(at|@|\[at\])\s*[a-z0-9.-]+\s*(\.|\[point\])\s*[a-z]{2,}/i';
$message = preg_replace($email_pattern, "[Email masqué]", $message);

$phone_pattern = '/(?:\+?\d[\s\.\-\(\)]*){8,}/';
$message = preg_replace($phone_pattern, "[Numéro masqué]", $message);

// Vérifier que le rendez-vous existe et que l'utilisateur y est associé
$check_sql = "SELECT r.id_rdv FROM rendez_vous r 
              WHERE r.id_rdv = ? 
              AND (r.id_client = ? OR r.id_maison IN (SELECT id_maison FROM maison WHERE id_pro = ?))";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("iii", $id_rdv, $exp_id, $exp_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    http_response_code(403);
    exit('Accès non autorisé à ce rendez-vous');
}

// Insertion du message avec requête préparée
$stmt = $db->prepare("INSERT INTO discussion_messages (id_rdv, id_expediteur, role_exp, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $id_rdv, $exp_id, $role, $message);

if ($stmt->execute()) {
    http_response_code(200);
    echo 'Message envoyé';
} else {
    http_response_code(500);
    echo 'Erreur lors de l\'envoi du message';
}
?>  