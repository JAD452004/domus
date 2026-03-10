<?php
session_start();
require_once "data.php";

// Détection automatique de l'ID utilisateur (compatible avec vos différentes pages)
$id_cli = $_SESSION['user_id'] ?? $_SESSION['id_cli'] ?? null;

if (!$id_cli || !isset($_POST['id_maison'])) {
    echo json_encode(['status' => 'error', 'message' => 'Veuillez vous connecter']);
    exit;
}

$id_maison = intval($_POST['id_maison']);

// Vérifier si le favori existe déjà
$check = $db->prepare("SELECT * FROM favoris WHERE id_cli = ? AND id_maison = ?");
$check->bind_param("ii", $id_cli, $id_maison);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Si il existe, on le retire
    $del = $db->prepare("DELETE FROM favoris WHERE id_cli = ? AND id_maison = ?");
    $del->bind_param("ii", $id_cli, $id_maison);
    if ($del->execute()) {
        echo json_encode(['status' => 'removed']);
    }
} else {
    // Sinon, on l'ajoute
    $ins = $db->prepare("INSERT INTO favoris (id_cli, id_maison) VALUES (?, ?)");
    $ins->bind_param("ii", $id_cli, $id_maison);
    if ($ins->execute()) {
        echo json_encode(['status' => 'added']);
    }
}
?>