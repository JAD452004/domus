<?php
session_start();
require_once 'connexion_bd.php';

// Activation des logs
error_log("=== DÉBUT RESET PASSWORD ===");
error_log("Session: " . print_r($_SESSION, true));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    error_log("ERREUR: OTP non vérifié");
    echo json_encode(['success' => false, 'message' => 'Vérification OTP requise']);
    exit;
}

$telephone = $_SESSION['reset_telephone'] ?? '';
error_log("Téléphone en session: " . $telephone);

$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($telephone) || empty($new_password) || empty($confirm_password)) {
    error_log("ERREUR: Champs vides");
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

if ($new_password !== $confirm_password) {
    error_log("ERREUR: Mots de passe différents");
    echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
    exit;
}

if (strlen($new_password) < 6 || strlen($new_password) > 16) {
    error_log("ERREUR: Longueur incorrecte");
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir entre 6 et 16 caractères']);
    exit;
}

global $db;

// Vérifier d'abord si le téléphone existe dans les tables
$check_client = $db->prepare("SELECT telephone FROM client WHERE telephone = ?");
$check_client->bind_param("s", $telephone);
$check_client->execute();
$client_exists = $check_client->get_result()->num_rows > 0;
error_log("Client existe: " . ($client_exists ? 'OUI' : 'NON'));

$check_proprietaire = $db->prepare("SELECT telephone FROM proprietaire WHERE telephone = ?");
$check_proprietaire->bind_param("s", $telephone);
$check_proprietaire->execute();
$proprietaire_exists = $check_proprietaire->get_result()->num_rows > 0;
error_log("Propriétaire existe: " . ($proprietaire_exists ? 'OUI' : 'NON'));

if (!$client_exists && !$proprietaire_exists) {
    error_log("ERREUR: Téléphone non trouvé dans aucune table");
    echo json_encode(['success' => false, 'message' => 'Numéro non trouvé']);
    exit;
}

// Hasher le nouveau mot de passe
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Commencer la transaction
$db->begin_transaction();

try {
    $updated = false;
    
    if ($client_exists) {
        $stmt = $db->prepare("UPDATE client SET mot_de_passe = ? WHERE telephone = ?");
        $stmt->bind_param("ss", $hashed_password, $telephone);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $updated = true;
            error_log("Client mis à jour: " . $stmt->affected_rows . " ligne(s)");
        } else {
            error_log("Client: Aucune ligne mise à jour");
        }
    }
    
    if ($proprietaire_exists && !$updated) {
        $stmt = $db->prepare("UPDATE proprietaire SET mot_de_passe = ? WHERE telephone = ?");
        $stmt->bind_param("ss", $hashed_password, $telephone);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $updated = true;
            error_log("Propriétaire mis à jour: " . $stmt->affected_rows . " ligne(s)");
        } else {
            error_log("Propriétaire: Aucune ligne mise à jour");
        }
    }
    
    if (!$updated) {
        throw new Exception("Aucune mise à jour effectuée");
    }
    
    $db->commit();
    
    // Nettoyer la session
    unset($_SESSION['reset_telephone']);
    unset($_SESSION['otp_verified']);
    
    error_log("SUCCÈS: Mot de passe modifié");
    echo json_encode(['success' => true, 'message' => 'Mot de passe modifié avec succès']);
    
} catch (Exception $e) {
    $db->rollback();
    error_log("ERREUR Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
}
?>