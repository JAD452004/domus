<?php
// Activation des erreurs pour voir ce qui se passe
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../PHP/data.php";

// Vérifier la session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nom_session = $_SESSION['nom'] ?? '';

// CORRECTION : Tables en minuscules pour correspondre à la base
if ($role === 'client') {
    $table = 'client';
    $id_field = 'id_cli';
    $name_field = 'nom_complet';
    $password_field = 'mot_de_passe';
    $photo_field = 'photo_profil';
    $accueil_page = 'accueilClient.php';
    $dashboard_page = 'client.php';
} elseif ($role === 'vendeur') {
    $table = 'proprietaire';
    $id_field = 'id_pro';
    $name_field = 'nom_complet';
    $password_field = 'mot_de_passe';
    $photo_field = 'photo_profil';
    $accueil_page = 'accueilPropriete.php';
    $dashboard_page = 'propriete.php';
} else {
    $table = 'admin';
    $id_field = 'id_admin';
    $name_field = 'nom';
    $password_field = 'mot_de_passe';
    $photo_field = 'photo_profil';
    $accueil_page = 'admin.php';
    $dashboard_page = 'admin.php';
}

// Vérifier si la table existe
$check_table = $db->query("SHOW TABLES LIKE '$table'");
if (!$check_table || $check_table->num_rows == 0) {
    die("Erreur: La table '$table' n'existe pas dans la base de données.");
}

// Récupérer l'utilisateur depuis la table appropriée
$select_sql = "SELECT * FROM $table WHERE $id_field = ? LIMIT 1";
$stmt = $db->prepare($select_sql);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $db->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$user = $result->fetch_assoc();
$nom_complet = $user[$name_field] ?? $nom_session;
$photo_profil = $user[$photo_field] ?? '';

// Vérifier si la colonne photo_profil existe
$check_photo_column = $db->query("SHOW COLUMNS FROM $table LIKE '$photo_field'");
$photo_column_exists = $check_photo_column && $check_photo_column->num_rows > 0;

// Vérifier si la colonne email existe
$check_email_column = $db->query("SHOW COLUMNS FROM $table LIKE 'email'");
$email_column_exists = $check_email_column && $check_email_column->num_rows > 0;

// Statistiques
$fav_count = $visite_count = 0;
$prop_count = $demande_count = 0;

if ($role === 'client') {
    // Vérifier si la table favoris existe
    $check_fav = $db->query("SHOW TABLES LIKE 'favoris'");
    if ($check_fav && $check_fav->num_rows > 0) {
        $fav_q = "SELECT COUNT(*) AS c FROM favoris WHERE id_cli = ?";
        $s = $db->prepare($fav_q); 
        if ($s) {
            $s->bind_param("i", $user_id); 
            $s->execute(); 
            $fav_count = $s->get_result()->fetch_assoc()['c'] ?? 0;
        }
    }
    
    // Vérifier si la table rendez_vous existe
    $check_rdv = $db->query("SHOW TABLES LIKE 'rendez_vous'");
    if ($check_rdv && $check_rdv->num_rows > 0) {
        $vis_q = "SELECT COUNT(*) AS c FROM rendez_vous WHERE id_client = ?";
        $s = $db->prepare($vis_q); 
        if ($s) {
            $s->bind_param("i", $user_id); 
            $s->execute(); 
            $visite_count = $s->get_result()->fetch_assoc()['c'] ?? 0;
        }
    }
} elseif ($role === 'vendeur') {
    // Vérifier si la table maison existe
    $check_maison = $db->query("SHOW TABLES LIKE 'maison'");
    if ($check_maison && $check_maison->num_rows > 0) {
        $p_q = "SELECT COUNT(*) AS c FROM maison WHERE id_pro = ?";
        $s = $db->prepare($p_q); 
        if ($s) {
            $s->bind_param("i", $user_id); 
            $s->execute(); 
            $row = $s->get_result()->fetch_assoc(); 
            $prop_count = $row['c'] ?? 0;
        }
    }
    
    // Vérifier si les tables rendez_vous et maison existent
    $check_rdv = $db->query("SHOW TABLES LIKE 'rendez_vous'");
    $check_maison = $db->query("SHOW TABLES LIKE 'maison'");
    if ($check_rdv && $check_rdv->num_rows > 0 && $check_maison && $check_maison->num_rows > 0) {
        $d_q = "SELECT COUNT(*) AS c FROM rendez_vous r JOIN maison m ON r.id_maison = m.id_maison WHERE m.id_pro = ?";
        $s = $db->prepare($d_q); 
        if ($s) {
            $s->bind_param("i", $user_id); 
            $s->execute(); 
            $demande_count = $s->get_result()->fetch_assoc()['c'] ?? 0;
        }
    }
}

$success_message = '';
$error_message = '';
$show_success_message = false;

// TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ===== UPLOAD DE PHOTO AVEC RECADRAGE =====
    if (isset($_POST['cropped_image']) && $photo_column_exists) {
        $cropped_image_data = $_POST['cropped_image'];
        
        // Extraire les données de l'image base64
        if (preg_match('/^data:image\/(\w+);base64,/', $cropped_image_data, $type)) {
            $image_data = substr($cropped_image_data, strpos($cropped_image_data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            // Vérifier le type
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $error_message = 'Type de fichier non autorisé.';
            } else {
                $image_data = base64_decode($image_data);
                
                if ($image_data !== false) {
                    // Vérifier la taille (max 5MB)
                    if (strlen($image_data) <= 5 * 1024 * 1024) {
                        // Générer un nom de fichier unique
                        $file_name = 'profile_' . $user_id . '_' . time() . '.' . $type;
                        $upload_dir = '../uploads/profiles/';
                        
                        // Créer le dossier s'il n'existe pas
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_path = $upload_dir . $file_name;
                        
                        // Sauvegarder l'image
                        if (file_put_contents($file_path, $image_data)) {
                            // Supprimer l'ancienne photo si elle existe
                            if (!empty($photo_profil) && file_exists('../' . $photo_profil)) {
                                @unlink('../' . $photo_profil);
                            }
                            
                            // Chemin relatif pour la base de données
                            $photo_path_db = 'uploads/profiles/' . $file_name;
                            
                            // Mettre à jour la base de données
                            $update_sql = "UPDATE $table SET $photo_field = ? WHERE $id_field = ?";
                            $stmt = $db->prepare($update_sql);
                            $stmt->bind_param('si', $photo_path_db, $user_id);
                            
                            if ($stmt->execute()) {
                                $_SESSION['photo_profil'] = $photo_path_db;
                                $photo_profil = $photo_path_db;
                                $success_message = 'Photo de profil mise à jour avec succès.';
                                $show_success_message = true;
                            } else {
                                $error_message = 'Erreur lors de la mise à jour de la photo.';
                                @unlink($file_path);
                            }
                        } else {
                            $error_message = 'Erreur lors de la sauvegarde du fichier.';
                        }
                    } else {
                        $error_message = 'Le fichier est trop volumineux. Taille maximum : 5MB.';
                    }
                } else {
                    $error_message = 'Erreur lors du décodage de l\'image.';
                }
            }
        }
    }
    
    // ===== SUPPRESSION DE LA PHOTO =====
    if (isset($_POST['delete_photo']) && $photo_column_exists && !empty($photo_profil)) {
        // Supprimer le fichier
        if (file_exists('../' . $photo_profil)) {
            @unlink('../' . $photo_profil);
        }
        
        // Mettre à jour la base de données
        $update_sql = "UPDATE $table SET $photo_field = NULL WHERE $id_field = ?";
        $stmt = $db->prepare($update_sql);
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['photo_profil'] = '';
            unset($_SESSION['photo_profil']);
            $photo_profil = '';
            $success_message = 'Photo de profil supprimée avec succès.';
            $show_success_message = true;
        } else {
            $error_message = 'Erreur lors de la suppression de la photo.';
        }
    }
    
    // ===== MISE À JOUR DES INFORMATIONS =====
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['nom'] ?? '');
        $new_tel = trim($_POST['telephone'] ?? '');
        $new_cin = trim($_POST['cin'] ?? '');
        $new_email = trim($_POST['email'] ?? '');

        if ($new_name === '') {
            $error_message = 'Le nom est obligatoire.';
        } else {
            // Validation email si la colonne existe
            if ($email_column_exists) {
                if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Veuillez fournir une adresse email valide.';
                } else {
                    // Vérifier unicité de l'email
                    $check_q = "SELECT $id_field FROM $table WHERE email = ? AND $id_field <> ? LIMIT 1";
                    $chk = $db->prepare($check_q);
                    $chk->bind_param('si', $new_email, $user_id);
                    $chk->execute();
                    $exists = $chk->get_result()->fetch_assoc();
                    if ($exists) {
                        $error_message = 'Cette adresse email est déjà utilisée.';
                    }
                }
            }

            if ($error_message === '') {
                // Construction de la requête de mise à jour
                if ($role === 'client') {
                    if ($email_column_exists) {
                        $upd = "UPDATE client SET nom_complet = ?, email = ?, telephone = ?, cin = ? WHERE id_cli = ?";
                        $s = $db->prepare($upd);
                        $s->bind_param('ssssi', $new_name, $new_email, $new_tel, $new_cin, $user_id);
                    } else {
                        $upd = "UPDATE client SET nom_complet = ?, telephone = ?, cin = ? WHERE id_cli = ?";
                        $s = $db->prepare($upd);
                        $s->bind_param('sssi', $new_name, $new_tel, $new_cin, $user_id);
                    }
                } elseif ($role === 'vendeur') {
                    if ($email_column_exists) {
                        $upd = "UPDATE proprietaire SET nom_complet = ?, email = ?, telephone = ?, cin = ? WHERE id_pro = ?";
                        $s = $db->prepare($upd);
                        $s->bind_param('ssssi', $new_name, $new_email, $new_tel, $new_cin, $user_id);
                    } else {
                        $upd = "UPDATE proprietaire SET nom_complet = ?, telephone = ?, cin = ? WHERE id_pro = ?";
                        $s = $db->prepare($upd);
                        $s->bind_param('sssi', $new_name, $new_tel, $new_cin, $user_id);
                    }
                } else {
                    if ($email_column_exists) {
                        $upd = "UPDATE admin SET nom = ?, email = ?, telephone = ? WHERE id_admin = ?";
                        $s = $db->prepare($upd);
                        $s->bind_param('sssi', $new_name, $new_email, $new_tel, $user_id);
                    } else {
                        $upd = "UPDATE admin SET nom = ?, telephone = ? WHERE id_admin = ?";
                        $s = $db->prepare($upd);
                        $s->bind_param('ssi', $new_name, $new_tel, $user_id);
                    }
                }
                
                if ($s && $s->execute()) {
                    $_SESSION['nom'] = $new_name;
                    if ($email_column_exists) {
                        $_SESSION['email'] = $new_email;
                    }
                    
                    $user[$name_field] = $new_name;
                    $user['telephone'] = $new_tel;
                    if (isset($user['cin'])) {
                        $user['cin'] = $new_cin;
                    }
                    if ($email_column_exists) {
                        $user['email'] = $new_email;
                    }
                    $nom_complet = $new_name;
                    $success_message = 'Profil mis à jour avec succès.';
                    $show_success_message = true;
                } else {
                    $error_message = 'Erreur lors de la mise à jour.';
                }
            }
        }
    }
    
    // ===== CHANGEMENT DE MOT DE PASSE =====
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $conf === '') {
            $error_message = 'Tous les champs du mot de passe sont obligatoires.';
        } elseif ($new !== $conf) {
            $error_message = 'Les nouveaux mots de passe ne correspondent pas.';
        } elseif (strlen($new) < 6) {
            $error_message = 'Le mot de passe doit contenir au moins 6 caractères.';
        } else {
            $stored_hash = $user[$password_field] ?? '';
            if ($stored_hash && password_verify($current, $stored_hash)) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $upd_pass = "UPDATE $table SET $password_field = ? WHERE $id_field = ?";
                $s = $db->prepare($upd_pass);
                $s->bind_param('si', $hash, $user_id);
                if ($s && $s->execute()) {
                    $success_message = 'Mot de passe changé avec succès.';
                    $show_success_message = true;
                } else {
                    $error_message = 'Erreur lors du changement de mot de passe.';
                }
            } else {
                $error_message = 'Le mot de passe actuel est incorrect.';
            }
        }
    }
}

// Forcer la recherche de la photo dans la session
if (isset($_SESSION['photo_profil']) && !empty($_SESSION['photo_profil'])) {
    $photo_profil = $_SESSION['photo_profil'];
}

// Rafraîchir la session depuis la base
$select_sql = "SELECT * FROM $table WHERE $id_field = ? LIMIT 1";
$stmt = $db->prepare($select_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $nom_complet = $user[$name_field] ?? $nom_session;
    $photo_profil = $user[$photo_field] ?? $_SESSION['photo_profil'] ?? '';
    $_SESSION['photo_profil'] = $photo_profil;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mon Profil - DOMUS</title>
    
    <!-- Importation des ressources externes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <!-- On garde le lien CSS principal, mais on override ici pour les choses critiques -->
    <link rel="stylesheet" href="../STYLE/accueilClient.css">
    
    <style>
  /* ===== VARIABLES ===== */
:root {
    --primary: #0f172a;
    --secondary: #2563eb;
    --bg-light: #f8fafc;
    --white: #ffffff;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --danger: #ef4444;
    --success: #10b981;
    --gray-300: #e2e8f0;
    --gray-200: #f1f5f9;
    --gray-100: #f8fafc;
    --primary-dark: #1d4ed8;
    
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    
    --radius-lg: 1rem;
    --radius-xl: 1.5rem;
    --radius-full: 9999px;
    
    --transition: all 0.3s ease;
    
    /* Tailles responsives */
    --nav-height-desktop: 80px;
    --nav-height-mobile: 60px;
    --container-padding-desktop: 1.5rem;
    --container-padding-mobile: 0.75rem;
}

/* ===== RESET & BASE ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: var(--bg-light);
    color: var(--text-main);
    min-height: 100vh;
    line-height: 1.5;
    padding-top: var(--nav-height-desktop);
    overflow-x: hidden;
}

/* ===== CONTAINER PRINCIPAL ===== */
.container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 var(--container-padding-desktop);
    width: 100%;
}

/* ===== NAVBAR ===== */
.navbar {
    background: var(--white);
    height: var(--nav-height-desktop);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 5%;
    box-shadow: var(--shadow-md);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
}

.logo {
    display: flex;
    align-items: center;
    gap: 8px;
}

.logo img {
    height: 40px;
    width: auto;
}

.logo-text {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--primary);
}

.logo-text span {
    color: var(--secondary);
}

.nav-links {
    display: flex;
    gap: 2rem;
}

.nav-links a {
    color: var(--text-main);
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.nav-links a:hover {
    color: var(--secondary);
}

.user-area {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logout-btn {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.mobile-toggle {
    display: none;
    font-size: 1.5rem;
    color: var(--text-main);
    cursor: pointer;
}

/* ===== ALERTES ===== */
.alert-container {
    margin-bottom: 1.5rem;
}

.alert {
    padding: 1rem 1.25rem;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideIn 0.3s ease;
    box-shadow: var(--shadow-lg);
    border-left: 4px solid transparent;
    font-size: 0.95rem;
}

.alert.success {
    background: #d1fae5;
    border-left-color: var(--success);
    color: #065f46;
}

.alert.error {
    background: #fee2e2;
    border-left-color: var(--danger);
    color: #991b1b;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== EN-TÊTE PROFIL ===== */
.profile-header {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-header h1 {
    font-size: 2.2rem;
    color: var(--primary);
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.profile-header p {
    color: var(--text-muted);
    font-size: 1rem;
}

/* ===== GRILLE PROFIL ===== */
.profile-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    align-items: start;
}

/* ===== CARTE PROFIL ===== */
.profile-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 2rem 1.5rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    position: sticky;
    top: calc(var(--nav-height-desktop) + 1rem);
}

.avatar-container {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto 1.5rem;
}

.avatar {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
    color: var(--white);
    box-shadow: var(--shadow-xl);
    border: 4px solid var(--white);
    position: relative;
    overflow: hidden;
}

.avatar.has-photo {
    background: none;
    background-size: cover;
    background-position: center;
}

.avatar-initials {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.avatar-upload-btn,
.avatar-delete-btn {
    position: absolute;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--white);
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-lg);
}

.avatar-upload-btn {
    bottom: 5px;
    right: 5px;
    background: var(--secondary);
    color: var(--white);
}

.avatar-delete-btn {
    top: 5px;
    right: 5px;
    background: var(--danger);
    color: var(--white);
}

.profile-name {
    text-align: center;
    margin-bottom: 1.5rem;
}

.profile-name h2 {
    font-size: 1.4rem;
    color: var(--text-main);
    margin-bottom: 0.5rem;
    word-break: break-word;
}

.profile-name .role {
    display: inline-block;
    padding: 0.25rem 1.25rem;
    background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
    color: var(--white);
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ===== STATISTIQUES ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin: 1.5rem 0;
    padding: 1.5rem 0;
    border-top: 2px solid var(--gray-200);
    border-bottom: 2px solid var(--gray-200);
}

.stat-item {
    text-align: center;
    padding: 0.75rem;
    background: var(--gray-100);
    border-radius: var(--radius-lg);
}

.stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--secondary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 500;
}

/* ===== CONTENU PRINCIPAL ===== */
.profile-content {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
}

/* ===== TABS ===== */
.tabs {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--gray-200);
    padding-bottom: 0.5rem;
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.tabs::-webkit-scrollbar {
    display: none;
}

.tab-btn {
    padding: 0.7rem 1.25rem;
    background: transparent;
    border: none;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    transition: var(--transition);
    border-radius: var(--radius-full);
    position: relative;
    font-size: 0.9rem;
}

.tab-btn.active {
    color: var(--secondary);
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -0.6rem;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--secondary), var(--primary-dark));
    border-radius: var(--radius-full);
}

.tab-content {
    display: none;
    animation: fadeIn 0.4s ease;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.section-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--text-main);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* ===== FORMULAIRES ===== */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 500;
    color: var(--text-main);
    font-size: 0.9rem;
}

.input-group {
    position: relative;
}

.form-control {
    width: 100%;
    padding: 0.8rem 1rem 0.8rem 2.8rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-lg);
    font-size: 0.9rem;
    transition: var(--transition);
    outline: none;
    background: var(--white);
}

.form-control:focus {
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-group i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 1rem;
}

.password-group .form-control {
    padding-right: 3rem;
}

.password-toggle {
    position: absolute;
    right: 0.3rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== BOUTONS ===== */
.btn {
    padding: 0.8rem 1.8rem;
    border: none;
    border-radius: var(--radius-lg);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
    color: var(--white);
    box-shadow: var(--shadow-lg);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-xl);
}

.btn-secondary {
    background: var(--gray-100);
    color: var(--text-muted);
    border: 1px solid var(--gray-300);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--gray-200);
}

/* ===== MODALS ===== */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 2rem;
    width: 100%;
    max-width: 500px;
    box-shadow: var(--shadow-xl);
    animation: slideUp 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-lg {
    max-width: 700px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-header h3 {
    font-size: 1.3rem;
    color: var(--text-main);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: var(--transition);
}

.modal-close:hover {
    background: var(--gray-100);
}

.upload-area {
    border: 2px dashed var(--gray-300);
    border-radius: var(--radius-lg);
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    margin-bottom: 1rem;
    transition: var(--transition);
}

.upload-area:hover {
    border-color: var(--secondary);
    background: var(--gray-100);
}

.upload-icon {
    font-size: 3rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.crop-container {
    width: 100%;
    max-height: 350px;
    background: #f1f1f1;
    margin-bottom: 1rem;
    border-radius: var(--radius-lg);
    overflow: hidden;
}

#cropImage {
    max-width: 100%;
    display: block;
}

.preview-container {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 1rem 0;
    padding: 1rem;
    background: var(--gray-100);
    border-radius: var(--radius-lg);
}

.preview-title {
    text-align: center;
    margin-bottom: 0.5rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.preview-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--secondary);
    box-shadow: var(--shadow-lg);
    margin: 0 auto;
}

.preview-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

/* ===== EMPTY STATES ===== */
.empty-state {
    text-align: center;
    padding: 2.5rem;
    background: var(--bg-light);
    border-radius: var(--radius-lg);
}

.empty-state i {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin: 0.75rem 0;
    color: var(--text-main);
    font-size: 1.2rem;
}

/* ===== FOOTER ===== */
.info {
    background: linear-gradient(135deg, var(--primary), #0c1424);
    color: #94a3b8;
    padding: 3rem 5% 2rem;
    margin-top: 4rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
}

.footer-section h2 {
    color: var(--white);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

/* =============================================
   RESPONSIVE - OPTIMISÉ POUR 340px
   ============================================= */

/* Écrans moyens (992px et moins) */
@media screen and (max-width: 992px) {
    .profile-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .profile-card {
        position: relative;
        top: 0;
        max-width: 100%;
    }
}

/* Tablettes et grands mobiles (768px et moins) */
@media screen and (max-width: 768px) {
    :root {
        --nav-height-desktop: var(--nav-height-mobile);
        --container-padding-desktop: var(--container-padding-mobile);
    }

    .navbar {
        height: var(--nav-height-mobile);
        padding: 0 4%;
    }

    .logo img {
        height: 32px;
    }

    .logo-text {
        font-size: 1.2rem;
    }

    .nav-links {
        position: fixed;
        top: var(--nav-height-mobile);
        right: -100%;
        width: 100%;
        height: calc(100vh - var(--nav-height-mobile));
        background: var(--white);
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: right 0.3s ease;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
        gap: 1.5rem;
        z-index: 999;
    }

    .nav-links.active {
        right: 0;
    }

    .nav-links a {
        font-size: 1rem;
        padding: 0.5rem 1rem;
        width: 200px;
        justify-content: center;
    }

    .mobile-toggle {
        display: block;
    }

    .user-info span {
        display: none;
    }

    .container {
        margin: 1rem auto;
    }

    .profile-header {
        margin-bottom: 1.5rem;
    }

    .profile-header h1 {
        font-size: 1.8rem;
    }

    .profile-card {
        padding: 1.5rem;
    }

    .avatar-container {
        width: 120px;
        height: 120px;
    }

    .avatar {
        font-size: 2.5rem;
    }

    .profile-name h2 {
        font-size: 1.3rem;
    }

    .profile-content {
        padding: 1.5rem;
    }

    .tabs {
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .tab-btn {
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }

    .section-title {
        font-size: 1.3rem;
        margin-bottom: 1.25rem;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-actions {
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
    }

    .btn {
        width: 100%;
        padding: 0.75rem 1rem;
    }

    .modal-content {
        padding: 1.5rem;
        margin: 0.5rem;
    }

    .modal-lg {
        max-width: 95%;
        padding: 1rem;
    }

    .crop-container {
        max-height: 250px;
    }

    .info {
        padding: 2rem 4% 1.5rem;
        gap: 1.5rem;
        margin-top: 3rem;
    }
}

/* Petits mobiles (480px et moins) */
@media screen and (max-width: 480px) {
    .profile-header h1 {
        font-size: 1.6rem;
    }

    .profile-header p {
        font-size: 0.9rem;
    }

    .profile-card {
        padding: 1.25rem;
    }

    .avatar-container {
        width: 100px;
        height: 100px;
    }

    .avatar {
        font-size: 2rem;
        border-width: 3px;
    }

    .avatar-upload-btn,
    .avatar-delete-btn {
        width: 32px;
        height: 32px;
        border-width: 2px;
    }

    .profile-name h2 {
        font-size: 1.2rem;
    }

    .profile-name .role {
        font-size: 0.7rem;
        padding: 0.2rem 1rem;
    }

    .stats-grid {
        gap: 0.5rem;
        padding: 1rem 0;
        margin: 1rem 0;
    }

    .stat-item {
        padding: 0.5rem;
    }

    .stat-value {
        font-size: 1.3rem;
    }

    .stat-label {
        font-size: 0.7rem;
    }

    .profile-content {
        padding: 1.25rem;
    }

    .tabs {
        gap: 0.3rem;
    }

    .tab-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
    }

    .tab-btn.active::after {
        bottom: -0.5rem;
        height: 2px;
    }

    .section-title {
        font-size: 1.2rem;
        gap: 0.5rem;
    }

    .form-label {
        font-size: 0.85rem;
    }

    .form-control {
        padding: 0.7rem 1rem 0.7rem 2.5rem;
        font-size: 0.85rem;
    }

    .input-group i {
        font-size: 0.9rem;
        left: 0.8rem;
    }

    .modal-content {
        padding: 1.25rem;
    }

    .modal-header h3 {
        font-size: 1.2rem;
    }

    .upload-area {
        padding: 1.5rem;
    }

    .upload-icon {
        font-size: 2.5rem;
    }

    .preview-circle {
        width: 80px;
        height: 80px;
    }

    .modal-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .empty-state {
        padding: 1.5rem;
    }

    .empty-state i {
        font-size: 2.5rem;
    }

    .empty-state h3 {
        font-size: 1.1rem;
    }

    .footer-section h2 {
        font-size: 1rem;
    }

    .footer-section p,
    .footer-section li {
        font-size: 0.85rem;
    }
}

/* Très petits écrans (340px et moins) - OPTIMISATION SPÉCIALE */
@media screen and (max-width: 340px) {
    :root {
        --nav-height-mobile: 55px;
        --container-padding-mobile: 0.5rem;
    }

    .navbar {
        padding: 0 3%;
    }

    .logo img {
        height: 28px;
    }

    .logo-text {
        font-size: 1rem;
    }

    .logout-btn {
        width: 35px;
        height: 35px;
    }

    .mobile-toggle {
        font-size: 1.3rem;
    }

    .container {
        margin: 0.75rem auto;
    }

    .profile-header {
        margin-bottom: 1rem;
    }

    .profile-header h1 {
        font-size: 1.4rem;
    }

    .profile-header p {
        font-size: 0.8rem;
    }

    .alert {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }

    .profile-card {
        padding: 1rem;
    }

    .avatar-container {
        width: 85px;
        height: 85px;
        margin-bottom: 1rem;
    }

    .avatar {
        font-size: 1.8rem;
        border-width: 2px;
    }

    .avatar-upload-btn,
    .avatar-delete-btn {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }

    .profile-name h2 {
        font-size: 1.1rem;
        margin-bottom: 0.3rem;
    }

    .profile-name .role {
        font-size: 0.65rem;
        padding: 0.15rem 0.8rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 0.4rem;
        padding: 0.75rem 0;
        margin: 0.75rem 0;
    }

    .stat-item {
        padding: 0.4rem;
    }

    .stat-value {
        font-size: 1.2rem;
    }

    .stat-label {
        font-size: 0.65rem;
    }

    .profile-content {
        padding: 1rem;
    }

    .tabs {
        gap: 0.2rem;
        margin-bottom: 1rem;
        padding-bottom: 0.3rem;
    }

    .tab-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.7rem;
    }

    .section-title {
        font-size: 1.1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        gap: 0.4rem;
    }

    .section-title i {
        font-size: 1rem;
    }

    .form-group {
        margin-bottom: 0.75rem;
    }

    .form-label {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
    }

    .form-control {
        padding: 0.6rem 0.8rem 0.6rem 2.2rem;
        font-size: 0.8rem;
        border-width: 1px;
    }

    .input-group i {
        font-size: 0.8rem;
        left: 0.7rem;
    }

    .password-toggle {
        width: 32px;
        height: 32px;
        right: 0.1rem;
    }

    .btn {
        padding: 0.65rem 0.8rem;
        font-size: 0.8rem;
        gap: 0.4rem;
    }

    .btn i {
        font-size: 0.8rem;
    }

    .form-actions {
        margin-top: 1.25rem;
        padding-top: 1rem;
    }

    .modal-content {
        padding: 1rem;
    }

    .modal-header {
        margin-bottom: 1rem;
    }

    .modal-header h3 {
        font-size: 1.1rem;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        font-size: 1.3rem;
    }

    .upload-area {
        padding: 1rem;
    }

    .upload-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .upload-area div {
        font-size: 0.85rem;
    }

    .upload-area small {
        font-size: 0.7rem;
    }

    .crop-container {
        max-height: 200px;
    }

    .preview-container {
        padding: 0.75rem;
        margin: 0.75rem 0;
    }

    .preview-title {
        font-size: 0.8rem;
    }

    .preview-circle {
        width: 70px;
        height: 70px;
        border-width: 2px;
    }

    .modal-actions {
        margin-top: 1rem;
        padding-top: 1rem;
    }

    .empty-state {
        padding: 1rem;
    }

    .empty-state i {
        font-size: 2rem;
    }

    .empty-state h3 {
        font-size: 1rem;
        margin: 0.5rem 0;
    }

    .empty-state .btn {
        margin-top: 0.75rem;
    }

    .info {
        padding: 1.5rem 3% 1rem;
        gap: 1rem;
        margin-top: 2rem;
    }

    .footer-section h2 {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }

    .footer-section p,
    .footer-section li {
        font-size: 0.75rem;
    }

    .footer-section li {
        margin-bottom: 0.4rem;
    }

    .footer-section i {
        font-size: 0.7rem;
    }
}

/* Gestion des très petits écrans en orientation paysage */
@media screen and (max-height: 500px) and (orientation: landscape) {
    .modal-content {
        max-height: 85vh;
    }

    .crop-container {
        max-height: 150px;
    }

    .nav-links {
        padding: 0.5rem 0;
    }

    .nav-links a {
        padding: 0.3rem 0.8rem;
    }
}

/* Utilitaires pour masquer/afficher selon la taille */
.hide-on-mobile {
    display: inline-block;
}

.show-on-mobile {
    display: none;
}

@media screen and (max-width: 768px) {
    .hide-on-mobile {
        display: none;
    }
    
    .show-on-mobile {
        display: inline-block;
    }
}

@media screen and (max-width: 340px) {
    .hide-on-small {
        display: none;
    }
}
    </style>
</head>
<body>

    <!-- ===== NAVIGATION ===== -->
    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS Logo">
            <div class="logo-text">DOM<span>US</span></div>
        </div>
        
        <ul class="nav-links" id="navLinks">
            <?php if ($role === 'client'): ?>
                <li><a href="accueilClient.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
                <li><a href="client.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <?php elseif ($role === 'vendeur'): ?>
                <li><a href="accueilPropriete.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
                <li><a href="propriete.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <?php else: ?>
                <li><a href="admin.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
                <li><a href="admin.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <?php endif; ?>
            <li><a href="contact.php"><i class="fa-solid fa-envelope"></i> <span>Contact</span></a></li>
        </ul>

        <div class="user-area">
            <div class="user-info">
                <?php include __DIR__ . '/_user_avatar.php'; ?>
            </div>
            
            <a href="../PHP/logout.php" class="logout-btn" title="Déconnexion">
                <i class="fa-solid fa-power-off"></i>
            </a>
            
            <div class="mobile-toggle" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- ===== MODAL DE SÉLECTION ===== -->
    <div class="modal" id="selectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Choisir une photo</h3>
                <button class="modal-close" id="selectModalClose"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="upload-area" id="selectUploadArea">
                    <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div>Cliquez pour sélectionner une image</div>
                    <small style="color: var(--text-muted);">JPG, PNG, GIF, WEBP (Max 5MB)</small>
                    <input type="file" id="selectFileInput" class="file-input" accept="image/*" style="display: none;">
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MODAL DE RECADRAGE ===== -->
    <div class="modal" id="cropModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>Recadrer la photo</h3>
                <button class="modal-close" id="cropModalClose"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="crop-container">
                    <img id="cropImage" src="" alt="Image à recadrer">
                </div>
                
                <div class="preview-container">
                    <div>
                        <div class="preview-title">Aperçu</div>
                        <div class="preview-circle">
                            <img id="preview" src="" alt="Aperçu">
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelCropBtn">
                        <i class="fa-solid fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" class="btn btn-primary" id="cropBtn">
                        <i class="fa-solid fa-crop"></i> Appliquer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire caché pour l'envoi -->
    <form id="cropForm" method="POST" style="display: none;">
        <input type="hidden" name="cropped_image" id="croppedImageInput">
    </form>

    <!-- Formulaire de suppression -->
    <form id="deletePhotoForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_photo" value="1">
    </form>

    <!-- ===== CONTAINER PRINCIPAL ===== -->
    <div class="container">
        <!-- Messages -->
        <?php if ($show_success_message && $success_message): ?>
            <div class="alert-container">
                <div class="alert success"><i class="fa-solid fa-check-circle"></i> <span><?php echo htmlspecialchars($success_message); ?></span></div>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert-container">
                <div class="alert error"><i class="fa-solid fa-exclamation-circle"></i> <span><?php echo htmlspecialchars($error_message); ?></span></div>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <h1>Mon Profil</h1>
            <p>Gérez vos informations personnelles</p>
        </div>

        <div class="profile-grid">
            <!-- Sidebar -->
            <aside class="profile-card">
                <div class="avatar-container">
                    <div class="avatar <?php echo !empty($photo_profil) ? 'has-photo' : ''; ?>" 
                         id="profileAvatar"
                         style="<?php echo !empty($photo_profil) ? "background-image: url('../" . htmlspecialchars($photo_profil) . "?t=" . time() . "')" : ''; ?>">
                        <?php if (empty($photo_profil)): ?>
                            <span class="avatar-initials"><?php echo strtoupper(substr($nom_complet, 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($photo_profil)): ?>
                        <div class="avatar-delete-btn" id="deletePhotoBtn" title="Supprimer"><i class="fa-solid fa-times"></i></div>
                    <?php endif; ?>
                    <div class="avatar-upload-btn" id="changePhotoBtn" title="<?php echo !empty($photo_profil) ? 'Modifier' : 'Ajouter'; ?>">
                        <i class="fa-solid <?php echo !empty($photo_profil) ? 'fa-pencil' : 'fa-camera'; ?>"></i>
                    </div>
                </div>

                <div class="profile-name">
                    <h2><?php echo htmlspecialchars($nom_complet); ?></h2>
                    <span class="role"><?php echo ucfirst($role); ?></span>
                </div>

                <!-- Stats -->
                <?php if ($role === 'client'): ?>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $fav_count; ?></div>
                        <div class="stat-label">Favoris</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $visite_count; ?></div>
                        <div class="stat-label">Rendez-vous</div>
                    </div>
                </div>
                <?php elseif ($role === 'vendeur'): ?>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $prop_count; ?></div>
                        <div class="stat-label">Propriétés</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $demande_count; ?></div>
                        <div class="stat-label">Demandes</div>
                    </div>
                </div>
                <?php endif; ?>

            
            </aside>

            <!-- Content -->
            <main class="profile-content">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="informations">Informations</button>
                    <button class="tab-btn" data-tab="securite">Sécurité</button>
                    <?php if ($role === 'client'): ?><button class="tab-btn" data-tab="favoris">Favoris</button><?php endif; ?>
                    <?php if ($role === 'vendeur'): ?><button class="tab-btn" data-tab="proprietes">Propriétés</button><?php endif; ?>
                </div>

                <!-- Tab: Infos -->
                <div id="informations" class="tab-content active">
                    <h2 class="section-title"><i class="fa-solid fa-user-circle"></i> Informations personnelles</h2>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nom complet</label>
                                <div class="input-group">
                                    <i class="fa-solid fa-user"></i>
                                    <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom_complet); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <i class="fa-solid fa-envelope"></i>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <div class="input-group">
                                    <i class="fa-solid fa-phone"></i>
                                    <input type="tel" class="form-control" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                                </div>
                            </div>
                           
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Security -->
                <div id="securite" class="tab-content">
                    <h2 class="section-title"><i class="fa-solid fa-lock"></i> Sécurité</h2>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Mot de passe actuel</label>
                                <div class="input-group">
                                    <i class="fa-solid fa-key"></i>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nouveau mot de passe</label>
                                <div class="input-group password-group">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" class="form-control" name="new_password" id="new_password" required minlength="6">
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)"><i class="fa-regular fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirmer</label>
                                <div class="input-group password-group">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" class="form-control" name="confirm_password" required minlength="6">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)"><i class="fa-regular fa-eye"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary"><i class="fa-solid fa-key"></i> Changer le mot de passe</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Favoris -->
                <?php if ($role === 'client'): ?>
                <div id="favoris" class="tab-content">
                    <h2 class="section-title"><i class="fa-solid fa-heart"></i> Mes favoris</h2>
                    <?php if ($fav_count > 0): ?>
                        <div style="text-align:center; padding: 2rem; color: var(--text-muted);">
                            <i class="fa-regular fa-heart" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Vous avez <?php echo $fav_count; ?> propriété(s) en favori.</p>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem; background: var(--bg-light); border-radius: 12px;">
                            <i class="fa-regular fa-heart" style="font-size: 3rem; color: var(--gray-300);"></i>
                            <h3 style="margin: 1rem 0;">Aucun favori</h3>
                            <a href="accueilClient.php" class="btn btn-primary" style="margin-top: 1rem;">Explorer</a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Tab: Propriétés -->
                <?php if ($role === 'vendeur'): ?>
                <div id="proprietes" class="tab-content">
                    <h2 class="section-title"><i class="fa-solid fa-house"></i> Mes propriétés</h2>
                    <?php if ($prop_count > 0): ?>
                        <div style="text-align:center; padding: 2rem; color: var(--text-muted);">
                            <i class="fa-regular fa-building" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Vous avez <?php echo $prop_count; ?> propriété(s) publiée(s).</p>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem; background: var(--bg-light); border-radius: 12px;">
                            <i class="fa-solid fa-house-chimney-medical" style="font-size: 3rem; color: var(--gray-300);"></i>
                            <h3 style="margin: 1rem 0;">Aucune propriété</h3>
                            <a href="../PHP/ajouter_propriete.php" class="btn btn-primary" style="margin-top: 1rem;">Publier</a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <footer class="info">
        <div class="footer-section">
            <h2>Pourquoi DOMUS ?</h2>
            <ul style="list-style: none;">
                <li style="margin-bottom: 10px;"><i class="fa-solid fa-check" style="color: var(--secondary);"></i> Large sélection certifiée</li>
                <li style="margin-bottom: 10px;"><i class="fa-solid fa-check" style="color: var(--secondary);"></i> Processus simplifié</li>
            </ul>
        </div>
        <div class="footer-section">
            <h2>Contactez-nous</h2>
            <p><i class="fa-solid fa-envelope" style="color: var(--secondary); margin-right: 10px;"></i> contact@domus.com</p>
        </div>
    </footer>

    <!-- Cropper.js Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tabs Logic
            const tabBtns = document.querySelectorAll('.tab-btn');
            
            function showTab(tabId) {
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                tabBtns.forEach(b => b.classList.remove('active'));
                
                const content = document.getElementById(tabId);
                const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
                
                if(content) content.classList.add('active');
                if(btn) btn.classList.add('active');
            }
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => showTab(btn.dataset.tab));
            });

            // Mobile Menu
            const mobileToggle = document.getElementById('mobileMenuBtn');
            const navLinks = document.getElementById('navLinks');
            
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navLinks.classList.toggle('active');
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                    
                    if(navLinks.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            }

            // Fermer le menu mobile si on clique ailleurs
            document.addEventListener('click', function(e) {
                if (navLinks && !navLinks.contains(e.target) && !mobileToggle.contains(e.target)) {
                    navLinks.classList.remove('active');
                    if(mobileToggle) {
                        const icon = mobileToggle.querySelector('i');
                        icon.classList.add('fa-bars');
                        icon.classList.remove('fa-times');
                    }
                    document.body.style.overflow = '';
                }
            });

            // Gestion des modals et du recadrage
            const selectModal = document.getElementById('selectModal');
            const cropModal = document.getElementById('cropModal');
            const changeBtn = document.getElementById('changePhotoBtn');
            const deleteBtn = document.getElementById('deletePhotoBtn');
            const selectModalClose = document.getElementById('selectModalClose');
            const cropModalClose = document.getElementById('cropModalClose');
            const cancelCropBtn = document.getElementById('cancelCropBtn');
            const selectUploadArea = document.getElementById('selectUploadArea');
            const selectFileInput = document.getElementById('selectFileInput');
            const cropImage = document.getElementById('cropImage');
            const preview = document.getElementById('preview');
            const cropBtn = document.getElementById('cropBtn');
            const croppedImageInput = document.getElementById('croppedImageInput');
            const cropForm = document.getElementById('cropForm');

            let cropper = null;
            let currentFile = null;

            // Ouvrir le modal de sélection
            if(changeBtn) {
                changeBtn.addEventListener('click', () => {
                    selectModal.classList.add('active');
                });
            }

            // Fermer les modals
            function closeSelectModal() {
                selectModal.classList.remove('active');
                selectFileInput.value = '';
            }

            function closeCropModal() {
                cropModal.classList.remove('active');
                if(cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropImage.src = '';
                preview.src = '';
            }

            if(selectModalClose) selectModalClose.addEventListener('click', closeSelectModal);
            if(cropModalClose) cropModalClose.addEventListener('click', closeCropModal);
            if(cancelCropBtn) cancelCropBtn.addEventListener('click', () => {
                closeCropModal();
                selectModal.classList.add('active');
            });

            // Fermer en cliquant à l'extérieur
            selectModal.addEventListener('click', (e) => {
                if(e.target === selectModal) closeSelectModal();
            });
            cropModal.addEventListener('click', (e) => {
                if(e.target === cropModal) closeCropModal();
            });

            // Upload area click
            if(selectUploadArea) {
                selectUploadArea.addEventListener('click', () => selectFileInput.click());
                
                // Drag & drop
                selectUploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    selectUploadArea.style.background = '#f1f5f9';
                });
                selectUploadArea.addEventListener('dragleave', () => {
                    selectUploadArea.style.background = 'transparent';
                });
                selectUploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    selectUploadArea.style.background = 'transparent';
                    if(e.dataTransfer.files.length) {
                        handleFileSelect(e.dataTransfer.files[0]);
                    }
                });
            }

            // File input change
            if(selectFileInput) {
                selectFileInput.addEventListener('change', () => {
                    if(selectFileInput.files.length) {
                        handleFileSelect(selectFileInput.files[0]);
                    }
                });
            }

            // Gestion de la sélection de fichier
            function handleFileSelect(file) {
                if(!file.type.startsWith('image/')) {
                    alert('Veuillez sélectionner une image valide.');
                    return;
                }
                if(file.size > 5 * 1024 * 1024) {
                    alert('L\'image ne doit pas dépasser 5 Mo.');
                    return;
                }

                currentFile = file;
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    cropImage.src = e.target.result;
                    preview.src = e.target.result;
                    
                    closeSelectModal();
                    cropModal.classList.add('active');
                    
                    setTimeout(() => {
                        if(cropper) cropper.destroy();
                        
                        cropper = new Cropper(cropImage, {
                            aspectRatio: 1,
                            viewMode: 1,
                            dragMode: 'move',
                            autoCropArea: 1,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: false,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false,
                            ready() {
                                // Ajuster la zone de recadrage pour être carrée et bien centrée
                                const cropBox = cropper.getCropBoxData();
                                const size = Math.min(cropBox.width, cropBox.height);
                                cropper.setCropBoxData({
                                    left: cropBox.left + (cropBox.width - size) / 2,
                                    top: cropBox.top + (cropBox.height - size) / 2,
                                    width: size,
                                    height: size
                                });
                            },
                            crop(event) {
                                // Mettre à jour l'aperçu en temps réel
                                const canvas = cropper.getCroppedCanvas({
                                    width: 300,
                                    height: 300
                                });
                                preview.src = canvas.toDataURL();
                            }
                        });
                    }, 100);
                };
                
                reader.readAsDataURL(file);
            }

            // Appliquer le recadrage
            if(cropBtn) {
                cropBtn.addEventListener('click', () => {
                    if(cropper) {
                        const canvas = cropper.getCroppedCanvas({
                            width: 500,
                            height: 500,
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high'
                        });
                        
                        // Convertir en base64
                        const croppedImageData = canvas.toDataURL(currentFile.type);
                        
                        // Mettre dans le champ caché
                        croppedImageInput.value = croppedImageData;
                        
                        // Soumettre le formulaire
                        cropForm.submit();
                    }
                });
            }

            // Suppression de la photo
            if(deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    if(confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')) {
                        document.getElementById('deletePhotoForm').submit();
                    }
                });
            }
        });

        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if(input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>