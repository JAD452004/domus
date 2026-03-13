<?php
session_start();
require_once "data.php";

if (isset($_POST['connecter'])) {
    // CORRECTION : 'identifiant' au lieu de 'numero'
    $identifiant = trim($_POST['identifiant']);
    $pass = trim($_POST['code']);
    
    $user = null;
    $role = '';

    // RECHERCHE ADMIN
    $sqlAdmin = "SELECT id_admin AS id, nom AS nom_complet, mot_de_passe FROM admin WHERE telephone = ?";
    $stmt = $db->prepare($sqlAdmin);
    if ($stmt) {
        $stmt->bind_param("s", $identifiant);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $user = $row;
            $role = 'admin';
        }
        $stmt->close();
    }

    // RECHERCHE PROPRIETAIRE
    if (!$user) {
        $sqlPro = "SELECT id_pro AS id, nom_complet, mot_de_passe FROM proprietaire WHERE telephone = ?";
        $stmt = $db->prepare($sqlPro);
        if ($stmt) {
            $stmt->bind_param("s", $identifiant);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $user = $row;
                $role = 'vendeur';
            }
            $stmt->close();
        }
    }

    // RECHERCHE CLIENT
    if (!$user) {
        $sqlCli = "SELECT id_cli AS id, nom_complet, mot_de_passe FROM client WHERE telephone = ?";
        $stmt = $db->prepare($sqlCli);
        if ($stmt) {
            $stmt->bind_param("s", $identifiant);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $user = $row;
                $role = 'client';
            }
            $stmt->close();
        }
    }

    // VÉRIFICATION
    if ($user && password_verify($pass, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom'] = $user['nom_complet'];
        $_SESSION['role'] = $role;

        if ($role === 'admin') {
            header("Location: ../Accueil/ADMIN.php");
        } elseif ($role === 'client') {
            header("Location: ../Accueil/accueilClient.php");
        } else {
            header("Location: ../Accueil/accueilPropriete.php");
        }
        exit();
    }
    
    header("Location: ../CONNECTION/connexionUser.php?error=Numéro ou mot de passe incorrect");
    exit();
    
} else {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}
?>