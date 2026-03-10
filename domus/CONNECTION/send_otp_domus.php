<?php
session_start();
require_once 'config_sms.php';
require_once 'connexion_bd.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$telephone = $_POST['telephone'] ?? '';

if (empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone requis']);
    exit;
}

// Utiliser votre connexion mysqli
global $db;

// Vérifier si le numéro existe dans client ou proprietaire
$stmt = $db->prepare("SELECT id_cli as id, 'client' as type, nom_complet FROM client WHERE telephone = ? 
                        UNION 
                        SELECT id_pro as id, 'proprietaire' as type, nom_complet FROM proprietaire WHERE telephone = ?");
$stmt->bind_param("ss", $telephone, $telephone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Aucun compte trouvé avec ce numéro']);
    exit;
}

// Générer un code OTP à 6 chiffres
$code_otp = generateOTP();

// Supprimer les anciens codes non utilisés
$stmt = $db->prepare("DELETE FROM password_resets WHERE telephone = ? AND used = 0");
$stmt->bind_param("s", $telephone);
$stmt->execute();

// Insérer le nouveau code (expire dans 10 minutes)
$expire_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$stmt = $db->prepare("INSERT INTO password_resets (telephone, code_otp, expire_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $telephone, $code_otp, $expire_at);
$stmt->execute();

// Stocker le téléphone en session pour la vérification
$_SESSION['reset_telephone'] = $telephone;

// Préparer le message SMS
$message = "DOMUS: Votre code de verification est: $code_otp. Valable 10 minutes.";

// ENVOI RÉEL DU SMS VIA TEXTBEE
$smsResult = sendSMSviaTextBee($telephone, $message);

if ($smsResult['success']) {
    // SUCCÈS - Le SMS a été envoyé pour de vrai
    echo json_encode([
        'success' => true,
        'message' => 'Code de vérification envoyé par SMS'
        // PLUS DE DEBUG_CODE - Le code n'est pas renvoyé
    ]);
} else {
    // ÉCHEC - Le SMS n'a pas pu être envoyé
    echo json_encode([
        'success' => false,
        'message' => "Erreur lors de l'envoi du SMS. Veuillez réessayer dans quelques instants."
    ]);
}
?>