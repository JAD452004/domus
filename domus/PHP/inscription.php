<?php
session_start();
require_once "data.php";

if (isset($_POST['inscrit'])) {
    
    if(!empty($_POST['nom']) && !empty($_POST['email']) && !empty($_POST['telephone']) && !empty($_POST['role']) && !empty($_POST['password'])) {
        
        $nom = $_POST['nom'];
        $email = $_POST['email'];
        $tel = $_POST['telephone'];
        $role = $_POST['role'];
        
        if ($_POST['password'] !== $_POST['confirm_password']) {
            header("Location: ../CONNECTION/connexionUser.php?error=Les mots de passe ne correspondent pas");
            exit();
        }
        
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Gestion upload CNI
        $cin_nom_fichier = "";
        if (isset($_FILES['cin']) && $_FILES['cin']['error'] === 0) {
            $dossier_upload = "../UPLOADS/CNI/";
            if (!is_dir($dossier_upload)) {
                mkdir($dossier_upload, 0777, true);
            }
            $extension = pathinfo($_FILES['cin']['name'], PATHINFO_EXTENSION);
            $cin_nom_fichier = "CNI_" . time() . "_" . uniqid() . "." . $extension;
            move_uploaded_file($_FILES['cin']['tmp_name'], $dossier_upload . $cin_nom_fichier);
        }

        if($role === 'vendeur') {
            // Vérification existence
            $check = $db->prepare("SELECT email FROM proprietaire WHERE email = ? OR telephone = ?");
            $check->bind_param("ss", $email, $tel);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                header("Location: ../CONNECTION/connexionUser.php?error=Email ou téléphone déjà utilisé");
                exit();
            } else {
                $stmt = $db->prepare("INSERT INTO proprietaire (nom_complet, email, telephone, cin, mot_de_passe) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nom, $email, $tel, $cin_nom_fichier, $password);
                if ($stmt->execute()) {
                    header("Location: ../CONNECTION/connexionUser.php?success=Inscription vendeur réussie !");
                    exit();
                }
            }
        } else {
            // Client
            $check = $db->prepare("SELECT email FROM client WHERE email = ? OR telephone = ?");
            $check->bind_param("ss", $email, $tel);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                header("Location: ../CONNECTION/connexionUser.php?error=Email ou téléphone déjà utilisé");
                exit();
            } else {
                $stmt = $db->prepare("INSERT INTO client (nom_complet, email, telephone, cin, mot_de_passe) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nom, $email, $tel, $cin_nom_fichier, $password);
                if ($stmt->execute()) {
                    header("Location: ../CONNECTION/connexionUser.php?success=Inscription client réussie !");
                    exit();
                }
            }
        }
    } else {
        header("Location: ../CONNECTION/connexionUser.php?error=Tous les champs sont obligatoires");
        exit();
    }
} else {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}
?>