<?php
session_start();
require_once "data.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['id_rdv'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$id_rdv = intval($_POST['id_rdv']);
$exp_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Vérifier l'accès
$check_sql = "SELECT r.id_rdv FROM rendez_vous r 
              WHERE r.id_rdv = ? 
              AND (r.id_client = ? OR r.id_maison IN (SELECT id_maison FROM maison WHERE id_pro = ?))";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("iii", $id_rdv, $exp_id, $exp_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si un fichier est uploadé
if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] != 0) {
    echo json_encode(['success' => false, 'error' => 'Aucun fichier ou erreur d\'upload']);
    exit();
}

$upload_dir = "../UPLOADS/chat/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_tmp = $_FILES['fichier']['tmp_name'];
$file_name = $_FILES['fichier']['name'];
$file_size = $_FILES['fichier']['size'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Types de fichiers autorisés
$allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowed_docs = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'ppt', 'pptx'];
$allowed_all = array_merge($allowed_images, $allowed_docs);

if (!in_array($file_ext, $allowed_all)) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']);
    exit();
}

// Limite de taille (10MB)
if ($file_size > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10MB)']);
    exit();
}

// Déterminer le type
if (in_array($file_ext, $allowed_images)) {
    $type_fichier = 'image';
} elseif ($file_ext === 'pdf') {
    $type_fichier = 'pdf';
} elseif (in_array($file_ext, ['doc', 'docx'])) {
    $type_fichier = 'word';
} else {
    $type_fichier = 'document';
}

// Nom de fichier unique
$new_name = time() . "_" . bin2hex(random_bytes(8)) . "." . $file_ext;
$target_path = $upload_dir . $new_name;

if (move_uploaded_file($file_tmp, $target_path)) {
    // Insérer dans la base de données avec statut 'envoye'
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    $stmt = $db->prepare("INSERT INTO chat_messages (id_rdv, id_expediteur, role_exp, message, type_fichier, chemin_fichier, nom_fichier_original, statut) VALUES (?, ?, ?, ?, ?, ?, ?, 'envoye')");
    $stmt->bind_param("iisssss", $id_rdv, $exp_id, $role, $message, $type_fichier, $target_path, $file_name);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'type' => $type_fichier,
            'chemin' => $target_path,
            'nom' => $file_name
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'upload']);
}
?>