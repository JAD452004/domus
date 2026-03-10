<?php
session_start();
require_once "data.php";

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// Vérifier si l'ID est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: liste_messages.php?error=id_missing");
    exit();
}

$id_message = intval($_GET['id']);

// Vérifier d'abord si le message existe
$check_sql = "SELECT * FROM contact WHERE id_contact = ?";
$check_stmt = $db->prepare($check_sql);
$check_stmt->bind_param("i", $id_message);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: liste_messages.php?error=not_found");
    exit();
}

// Préparer et exécuter la requête de suppression
$sql = "DELETE FROM contact WHERE id_contact = ?";
$stmt = $db->prepare($sql);

if (!$stmt) {
    header("Location: liste_messages.php?error=prepare_failed");
    exit();
}

$stmt->bind_param("i", $id_message);

if ($stmt->execute()) {
    // Vérifier si une ligne a été affectée
    if ($stmt->affected_rows > 0) {
        header("Location: liste_messages.php?success=deleted");
    } else {
        header("Location: liste_messages.php?error=no_rows_affected");
    }
} else {
    header("Location: liste_messages.php?error=delete_failed");
}

$stmt->close();
$check_stmt->close();
exit();
?>