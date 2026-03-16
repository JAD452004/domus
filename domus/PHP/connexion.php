<?php
session_start();
require_once "data.php";

if (isset($_POST['connecter'])) {
    $identifiant = trim($_POST['identifiant']);
    $pass = trim($_POST['code']);
    
    $user = null;
    $role = '';

    // Détecter si c'est un email (contient @)
    $estEmail = strpos($identifiant, '@') !== false;
    
    if ($estEmail) {
        // 🔴 C'EST UN EMAIL - Recherche uniquement par email
        
        // 1. RECHERCHE CLIENT par EMAIL
        $sqlCli = "SELECT id_cli AS id, nom_complet, mot_de_passe FROM client WHERE email = ?";
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

        // 2. RECHERCHE PROPRIETAIRE par EMAIL (si pas trouvé dans client)
        if (!$user) {
            $sqlPro = "SELECT id_pro AS id, nom_complet, mot_de_passe FROM proprietaire WHERE email = ?";
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
        
    } else {
        // 🔵 C'EST UN TÉLÉPHONE - Recherche par téléphone dans toutes les tables
        
        // 1. RECHERCHE ADMIN
        $sqlAdmin = "SELECT id_admin AS id, nom AS nom_complet, mot_de_passe FROM admin WHERE telephone = ?";
        $stmt = $db->prepare($sqlAdmin);
        if ($stmt) {
            $stmt->bind_param("s", $identifiant);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // Vérification spéciale pour l'admin avec mot de passe 123456
                if ($pass === '123456' && $row['mot_de_passe'] === '123456') {
                    $user = $row;
                    $role = 'admin';
                }
            }
            $stmt->close();
        }

        // 2. RECHERCHE CLIENT (si pas admin)
        if (!$user) {
            $sqlCli = "SELECT id_cli AS id, nom_complet, mot_de_passe FROM client WHERE telephone = ?";
            $stmt = $db->prepare($sqlCli);
            if ($stmt) {
                $stmt->bind_param("s", $identifiant);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    // Pour client, on garde password_verify
                    if (password_verify($pass, $row['mot_de_passe'])) {
                        $user = $row;
                        $role = 'client';
                    }
                }
                $stmt->close();
            }
        }

        // 3. RECHERCHE PROPRIETAIRE (si pas client)
        if (!$user) {
            $sqlPro = "SELECT id_pro AS id, nom_complet, mot_de_passe FROM proprietaire WHERE telephone = ?";
            $stmt = $db->prepare($sqlPro);
            if ($stmt) {
                $stmt->bind_param("s", $identifiant);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    // Pour proprietaire, on garde password_verify
                    if (password_verify($pass, $row['mot_de_passe'])) {
                        $user = $row;
                        $role = 'vendeur';
                    }
                }
                $stmt->close();
            }
        }
    }

    // VÉRIFICATION DU MOT DE PASSE pour les non-admin
    if ($user && $role !== 'admin' && password_verify($pass, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom'] = $user['nom_complet'];
        $_SESSION['role'] = $role;

        // Redirection selon le rôle
        if ($role === 'client') {
            header("Location: ../Accueil/accueilClient.php");
        } else {
            header("Location: ../Accueil/accueilPropriete.php");
        }
        exit();
    }
    
    // VÉRIFICATION pour ADMIN
    if ($user && $role === 'admin') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom'] = $user['nom_complet'];
        $_SESSION['role'] = $role;
        
        header("Location: ../Accueil/ADMIN.php");
        exit();
    }
    
    // Si pas trouvé ou mot de passe incorrect
    header("Location: ../CONNECTION/connexionUser.php?error=Identifiant ou mot de passe incorrect");
    exit();
    
} else {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}
?>