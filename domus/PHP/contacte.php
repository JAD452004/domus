<?php
session_start();
require_once "data.php"; // Vérifie que data.php est bien là

if(isset($_POST['envoyer'])){
    // Protection contre les injections SQL
    $nom     = $db->real_escape_string($_POST['nom']);
    $email   = $db->real_escape_string($_POST['email']);
    $sujet   = $db->real_escape_string($_POST['sujet']);
    $message = $db->real_escape_string($_POST['message']);
    
    // Récupération des infos de session
    $id_user = $_SESSION['user_id'] ?? 0;
    $role    = $_SESSION['role'] ?? 'visiteur';

    if(!empty($nom) && !empty($email) && !empty($sujet) && !empty($message)){
        
        $sql = "INSERT INTO contact (id_user, user_type, nom, email, sujet, message) 
                VALUES ('$id_user', '$role', '$nom', '$email', '$sujet', '$message')";

        if($db->query($sql)){
            // Redirection vers ton formulaire avec succès
            header("Location: ../Accueil/contact.php?success=1");
            exit();
        } else {
            echo "Erreur de base de données : " . $db->error;
        }
    } else {
        header("Location: ../Accueil/contact.php?error=empty");
        exit();
    }
} else {
    header("Location: ../Accueil/contact.php");
    exit();
}
?>