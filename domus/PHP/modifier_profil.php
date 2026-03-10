<?php
session_start();
require_once __DIR__ . '/data.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

// Déterminer la table et champs
if ($role === 'client') {
    $table = 'Client';
    $id_field = 'id_cli';
    $name_field = 'nom_complet';
    $password_field = 'mot_de_passe';
} elseif ($role === 'vendeur') {
    $table = 'Proprietaire';
    $id_field = 'id_pro';
    $name_field = 'nom_complet';
    $password_field = 'mot_de_passe';
} else {
    $table = 'admin';
    $id_field = 'id_admin';
    $name_field = 'nom';
    $password_field = 'mot_de_passe';
}

$action = $_POST['action'] ?? '';

// Utility: check if column exists
function column_exists($db, $table, $column) {
    $q = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $q && $q->num_rows > 0;
}

$email_exists = column_exists($db, $table, 'email');

if ($action === 'update_profile') {
    $new_name = trim($_POST['nom'] ?? '');
    $new_tel = trim($_POST['telephone'] ?? '');
    $new_cin = trim($_POST['cin'] ?? '');
    $new_email = trim($_POST['email'] ?? '');

    if ($new_name === '') {
        echo json_encode(['success' => false, 'message' => 'Le nom est obligatoire.']);
        exit;
    }

    if ($email_exists) {
        if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Adresse email invalide.']);
            exit;
        }
        // unicité
        $check_q = $db->prepare("SELECT $id_field FROM $table WHERE email = ? AND $id_field <> ? LIMIT 1");
        $check_q->bind_param('si', $new_email, $user_id);
        $check_q->execute();
        if ($check_q->get_result()->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Cette adresse email est déjà utilisée.']);
            exit;
        }
    }

    if ($role === 'client') {
        if ($email_exists) {
            $upd = "UPDATE Client SET nom_complet = ?, email = ?, telephone = ?, cin = ? WHERE id_cli = ?";
            $s = $db->prepare($upd);
            $s->bind_param('ssssi', $new_name, $new_email, $new_tel, $new_cin, $user_id);
        } else {
            $upd = "UPDATE Client SET nom_complet = ?, telephone = ?, cin = ? WHERE id_cli = ?";
            $s = $db->prepare($upd);
            $s->bind_param('sssi', $new_name, $new_tel, $new_cin, $user_id);
        }
    } elseif ($role === 'vendeur') {
        if ($email_exists) {
            $upd = "UPDATE Proprietaire SET nom_complet = ?, email = ?, telephone = ?, cin = ? WHERE id_pro = ?";
            $s = $db->prepare($upd);
            $s->bind_param('ssssi', $new_name, $new_email, $new_tel, $new_cin, $user_id);
        } else {
            $upd = "UPDATE Proprietaire SET nom_complet = ?, telephone = ?, cin = ? WHERE id_pro = ?";
            $s = $db->prepare($upd);
            $s->bind_param('sssi', $new_name, $new_tel, $new_cin, $user_id);
        }
    } else {
        if ($email_exists) {
            $upd = "UPDATE admin SET nom = ?, email = ?, telephone = ? WHERE id_admin = ?";
            $s = $db->prepare($upd);
            $s->bind_param('sssi', $new_name, $new_email, $new_tel, $user_id);
        } else {
            $upd = "UPDATE admin SET nom = ?, telephone = ? WHERE id_admin = ?";
            $s = $db->prepare($upd);
            $s->bind_param('ssi', $new_name, $new_tel, $user_id);
        }
    }

    if ($s->execute()) {
        $_SESSION['nom'] = $new_name;
        if ($email_exists) $_SESSION['email'] = $new_email;
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
    exit;
}

if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $conf === '') {
        echo json_encode(['success' => false, 'message' => 'Tous les champs du mot de passe sont obligatoires.']);
        exit;
    }
    if ($new !== $conf) {
        echo json_encode(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas.']);
        exit;
    }
    if (strlen($new) < 6) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.']);
        exit;
    }

    $sel = $db->prepare("SELECT $password_field FROM $table WHERE $id_field = ? LIMIT 1");
    $sel->bind_param('i', $user_id);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    $stored_hash = $row[$password_field] ?? '';

    if ($stored_hash && password_verify($current, $stored_hash)) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd_pass = $db->prepare("UPDATE $table SET $password_field = ? WHERE $id_field = ?");
        $upd_pass->bind_param('si', $hash, $user_id);
        if ($upd_pass->execute()) {
            echo json_encode(['success' => true, 'message' => 'Mot de passe changé avec succès.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Erreur lors du changement de mot de passe.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Le mot de passe actuel est incorrect.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
exit;

?>
