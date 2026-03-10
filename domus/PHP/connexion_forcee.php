<?php
session_start();
require_once "../PHP/data.php";

// 1. Sécurité : Vérifier si l'ID et le rôle sont présents
if (!isset($_GET['id']) || !isset($_GET['role'])) {
    die("Paramètres manquants.");
}

$id = intval($_GET['id']);
$role = $_GET['role'];

if ($role === 'client') {
    // --- INCARNER UN CLIENT ---
    $stmt = $db->prepare("SELECT id_cli, nom_complet FROM client WHERE id_cli = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        // On remplace les variables de session
        $_SESSION['user_id'] = $user['id_cli'];
        $_SESSION['nom']     = $user['nom_complet'];
        $_SESSION['role']    = 'client';

        // Redirection vers l'accueil client
        header("Location: ../Accueil/accueilClient.php");
        exit();
    }
} 
elseif ($role === 'vendeur') {
    // --- INCARNER UN VENDEUR ---
    $stmt = $db->prepare("SELECT id_pro, nom_complet FROM proprietaire WHERE id_pro = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        // On remplace les variables de session
        $_SESSION['user_id'] = $user['id_pro'];
        $_SESSION['nom']     = $user['nom_complet'];
        $_SESSION['role']    = 'vendeur';

        // Redirection vers l'espace vendeur
        header("Location: ../Accueil/accueilPropriete.php");
        exit();
    }
}

// Si on arrive ici, c'est qu'il y a eu un problème
echo "<script>alert('Utilisateur introuvable.'); window.history.back();</script>";
?>