<?php
session_start();
require_once 'connexion_bd.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$code = $_POST['code'] ?? '';
$telephone = $_SESSION['reset_telephone'] ?? '';

if (empty($code) || empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Données incomplètes']);
    exit;
}

// Vérifier format du code (6 chiffres)
if (!preg_match('/^\d{6}$/', $code)) {
    echo json_encode(['success' => false, 'message' => 'Le code doit contenir 6 chiffres']);
    exit;
}

global $db;

// Vérifier le code
$stmt = $db->prepare("SELECT * FROM password_resets 
                       WHERE telephone = ? AND code_otp = ? AND used = 0 AND expire_at > NOW() 
                       ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("ss", $telephone, $code);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();

if (!$reset) {
    echo json_encode(['success' => false, 'message' => 'Code invalide ou expiré']);
    exit;
}

// Marquer le code comme utilisé
$stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE id_reset = ?");
$stmt->bind_param("i", $reset['id_reset']);
$stmt->execute();

// Stocker que l'OTP est vérifié
$_SESSION['otp_verified'] = true;

echo json_encode(['success' => true, 'message' => 'Code vérifié avec succès']);
?>