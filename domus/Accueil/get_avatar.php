<?php
session_start();
header('Content-Type: application/json');

require_once "../PHP/data.php";

$response = ['photo' => null, 'initial' => null];

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // CORRECTION : Utiliser les bons noms de colonnes
    if ($role === 'client') {
        $query = "SELECT photo_profil, nom_complet FROM client WHERE id_cli = ?";
    } elseif ($role === 'vendeur') {
        $query = "SELECT photo_profil, nom_complet FROM proprietaire WHERE id_pro = ?";
    } else {
        $query = "SELECT photo_profil, nom FROM admin WHERE id_admin = ?";
    }
    
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $response['photo'] = $row['photo_profil'];
            // Utiliser le bon nom de champ
            $nom = $row['nom_complet'] ?? $row['nom'] ?? '';
            $response['initial'] = strtoupper(substr($nom, 0, 1));
            
            // Mettre à jour la session
            $_SESSION['photo_profil'] = $row['photo_profil'];
        }
        $stmt->close();
    }
}

echo json_encode($response);
?>