<?php
// Include réutilisable pour afficher l'avatar de l'utilisateur connecté

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détermine le lien vers la page de profil selon le dossier courant
$profile_link = $profile_link ?? ((strpos($_SERVER['SCRIPT_NAME'], '/PHP/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/Accueil/') !== false) ? 'voirProfil.php' : 'Accueil/voirProfil.php');

// Déterminer le nom à afficher
$display_name = $display_name ?? ($nom_complet ?? $nom_client ?? $nom_vendeur ?? $nom_user ?? ($_SESSION['nom'] ?? ''));

// Connexion à la base de données
require_once dirname(__DIR__) . '/PHP/data.php';

// Récupérer la photo depuis la base de données
$photo = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // CORRECTION : Tables en minuscules pour correspondre à la base
    if ($role === 'client') {
        $query = "SELECT photo_profil FROM client WHERE id_cli = ?";  // ✅ "client" en minuscules
    } elseif ($role === 'vendeur') {
        $query = "SELECT photo_profil FROM proprietaire WHERE id_pro = ?";  // ✅ "proprietaire" en minuscules
    } else {
        $query = "SELECT photo_profil FROM admin WHERE id_admin = ?";  // ✅ "admin" (déjà bon)
    }
    
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $photo = $row['photo_profil'];
            $_SESSION['photo_profil'] = $photo;
        }
        $stmt->close();
    }
}

// Si pas trouvé en base, prendre la session
if (empty($photo)) {
    $photo = $_SESSION['photo_profil'] ?? ($photo_profil ?? '');
}

// AFFICHAGE
if (!empty($photo)) {
    $photo = ltrim($photo, '/');
    $img_url = '../' . $photo;
    echo '<a href="' . htmlspecialchars($profile_link) . '"><div class="user-avatar" style="background-image: url(' . htmlspecialchars($img_url . '?t=' . time()) . '); background-size: cover; background-position: center;"></div></a>';
} else {
    $initial = strtoupper(substr($display_name, 0, 1));
    echo '<a href="' . htmlspecialchars($profile_link) . '"><div class="user-avatar">' . htmlspecialchars($initial) . '</div></a>';
}

echo '<a href="' . htmlspecialchars($profile_link) . '" class="user-name-link"><span class="user-name">' . htmlspecialchars($display_name) . '</span></a>';
?>