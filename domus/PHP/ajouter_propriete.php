<?php
session_start();
require_once "data.php";

// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// Vérifier si c'est un vendeur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur') {
    header("Location: ../Accueil/accueilPropriete.php");
    exit();
}

$id_pro = $_SESSION['user_id'];
$nom_vendeur = $_SESSION['nom'];
$error_msg = "";

if (isset($_POST['ajouter_propriete'])) {
    $id_pro = $_SESSION['user_id'];
    $titre = $db->real_escape_string($_POST['titre']);
    
    // ===== CORRECTION 1 : Nettoyage RADICAL du prix =====
    $prix_raw = $_POST['prix'];
    // On ne garde QUE les chiffres (0-9), on supprime TOUT le reste
    $prix_nettoye = preg_replace('/[^0-9]/', '', $prix_raw);
    $prix = floatval($prix_nettoye);
    
    // Sécurité : si le résultat est vide, on met 0
    if (empty($prix_nettoye)) {
        $prix = 0;
        $error_msg = "Le prix doit être un nombre valide.";
    }
    // ===== FIN CORRECTION 1 =====
    
    // Récupérer le type de transaction
    $transaction_type = $_POST['transaction_type'];
    
    $ville = $db->real_escape_string($_POST['ville']);
    $type = $_POST['type_bien'];
    $surface = (int)$_POST['surface'];
    $description = $db->real_escape_string($_POST['description']);

    $is_terrain = ($type === 'terrain');
    $chambres = $is_terrain ? 0 : (int)$_POST['chambres'];
    $sdb = $is_terrain ? 0 : (int)$_POST['salles_bain'];

    // Dossier de destination
    $upload_dir = "../UPLOADS/";
    $papier_dir = $upload_dir . "papiers/";
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    if (!file_exists($papier_dir)) mkdir($papier_dir, 0777, true);

    function uploadFile($input_name, $destination) {
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES[$input_name]["name"], PATHINFO_EXTENSION));
            $new_name = time() . "_" . $input_name . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $target = $destination . $new_name;
            if (move_uploaded_file($_FILES[$input_name]["tmp_name"], $target)) {
                return $target;
            }
        }
        return null;
    }

    $target_main = uploadFile('image', $upload_dir);

    if ($target_main) {
        $titre_foncier = null;
        $attestation = null;
        $permis_construire = null;
        $certificat_urbanisme = null;
        $plan_cadastral = null;
        $contrat_vente = null;
        $autres_documents = null;
        $autres_documents_titre = null;
        
        if ($transaction_type === 'vente') {
            $titre_foncier = uploadFile('papier_titre_foncier', $papier_dir);
            $attestation = uploadFile('papier_attestation', $papier_dir);
            $permis_construire = uploadFile('papier_permis_construire', $papier_dir);
            $certificat_urbanisme = uploadFile('papier_certificat_urbanisme', $papier_dir);
            $plan_cadastral = uploadFile('papier_cadastre', $papier_dir);
            $contrat_vente = uploadFile('papier_contrat', $papier_dir);
            $autres_documents = uploadFile('autres_papiers_file', $papier_dir);
            $autres_documents_titre = !empty($_POST['autres_papiers_text']) ? $db->real_escape_string($_POST['autres_papiers_text']) : null;
        }

        $sql = "INSERT INTO maison (
                    id_pro, titre, prix, transaction_type, ville, type_bien, chambres, salles_bain, surface, 
                    description, image, titre_foncier, attestation_propriete, 
                    permis_construire, certificat_urbanisme, plan_cadastral, 
                    contrat_vente, autres_documents, autres_documents_titre
                ) VALUES (
                    '$id_pro', '$titre', '$prix', '$transaction_type', '$ville', '$type', '$chambres', '$sdb', '$surface', 
                    '$description', '$target_main', 
                    " . ($titre_foncier ? "'$titre_foncier'" : "NULL") . ", 
                    " . ($attestation ? "'$attestation'" : "NULL") . ", 
                    " . ($permis_construire ? "'$permis_construire'" : "NULL") . ", 
                    " . ($certificat_urbanisme ? "'$certificat_urbanisme'" : "NULL") . ", 
                    " . ($plan_cadastral ? "'$plan_cadastral'" : "NULL") . ", 
                    " . ($contrat_vente ? "'$contrat_vente'" : "NULL") . ", 
                    " . ($autres_documents ? "'$autres_documents'" : "NULL") . ", 
                    " . ($autres_documents_titre ? "'$autres_documents_titre'" : "NULL") . "
                )";

        if ($db->query($sql)) {
            $id_maison = $db->insert_id;

            if (!empty($_FILES['galerie']['name'][0])) {
                foreach ($_FILES['galerie']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['galerie']['error'][$key] == 0) {
                        $nom_img = time() . "_gal_" . bin2hex(random_bytes(4)) . "." . strtolower(pathinfo($_FILES['galerie']['name'][$key], PATHINFO_EXTENSION));
                        $target_gal = $upload_dir . $nom_img;
                        if (move_uploaded_file($tmp_name, $target_gal)) {
                            $db->query("INSERT INTO images_maison (id_maison, chemin_image) VALUES ('$id_maison', '$target_gal')");
                        }
                    }
                }
            }

            header("Location: ../Accueil/propriete.php?success=1");
            exit();
        } else {
            $error_msg = "Erreur SQL : " . $db->error;
        }
    } else {
        $error_msg = "L'image principale est obligatoire.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOMUS - Publier un bien</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-muted: #64748b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Poppins", sans-serif;
            background-color: var(--bg-light);
            color: #1e293b;
            line-height: 1.6;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo img {
            height: 40px;
            width: auto;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .logo-text span {
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-links a:hover {
            background: var(--secondary);
            color: white;
        }
        
        .nav-links a.active {
            background: var(--secondary);
            color: white;
        }
        
        .user-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--secondary), #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--primary);
        }
        
        .logout-btn {
            background: var(--secondary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .logout-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        
        .mobile-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary);
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f8fafc;
        }
        
        .form-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-header h1 {
            color: var(--primary);
            font-size: 2.2rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .form-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .form-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .form-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), #3b82f6);
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 20px;
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: rgba(37, 99, 235, 0.15);
            transform: translateX(-5px);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #fca5a5;
            color: #b91c1c;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border: 1px solid #34d399;
            color: #065f46;
        }
        
        .grid-2, .grid-3 {
            display: grid;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--primary);
            font-size: 0.95rem;
        }
        
        .required:after {
            content: ' *';
            color: #ef4444;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .transaction-section {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        
        .transaction-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: 600;
        }
        
        .transaction-title i {
            color: var(--secondary);
            font-size: 1.2rem;
        }
        
        .transaction-options {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .transaction-option {
            flex: 1;
            min-width: 150px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .transaction-option:hover {
            border-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        
        .transaction-option.selected {
            border-color: var(--secondary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .transaction-option i {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 10px;
            display: block;
        }
        
        .transaction-option span {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .transaction-option small {
            display: block;
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .type-selection {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .type-option {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            color: var(--primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .type-option:hover {
            border-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .type-option.selected {
            border-color: var(--secondary);
            background: rgba(37, 99, 235, 0.05);
            color: var(--secondary);
        }
        
        .type-option i {
            font-size: 1.5rem;
            color: var(--secondary);
        }
        
        .characteristics-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }
        
        .papiers-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .papiers-section.hidden {
            display: none;
        }
        
        .papiers-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .papier-item {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .papier-item.selected {
            border-color: var(--secondary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .papier-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            cursor: pointer;
        }
        
        .papier-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--secondary);
            cursor: pointer;
        }
        
        .papier-title {
            font-weight: 500;
            color: var(--primary);
            flex: 1;
        }
        
        .papier-header i {
            font-size: 1.2rem;
        }
        
        .papier-upload {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #e2e8f0;
            display: none;
        }
        
        .papier-item.selected .papier-upload {
            display: block;
        }
        
        .upload-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .file-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .upload-help {
            display: block;
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .upload-section {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .autres-papiers {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--secondary), #1d4ed8);
            color: white;
            border: none;
            padding: 18px 40px;
            width: 100%;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 40px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }
        
        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 8px;
            display: block;
        }
        
        textarea.form-control {
            min-height: 140px;
            resize: vertical;
            line-height: 1.5;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 10px;
            background: rgba(37, 99, 235, 0.1);
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .file-info i {
            color: var(--secondary);
        }
        
        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            
            .papiers-grid {
                grid-template-columns: 1fr;
            }
            
            .type-selection {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .transaction-options {
                flex-direction: column;
                gap: 10px;
            }
            
            .transaction-option {
                min-width: auto;
            }
            
            .form-card {
                padding: 25px;
            }
            
            .form-header h1 {
                font-size: 1.8rem;
            }
            
            .nav-links {
                display: none;
                flex-direction: column;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: white;
                padding: 80px 20px 20px;
                z-index: 999;
                overflow-y: auto;
                gap: 0;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .nav-links li {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .nav-links a {
                width: 100%;
                justify-content: flex-start;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 5px;
                font-size: 1.1rem;
            }
            
            .mobile-toggle {
                display: flex;
                z-index: 1000;
            }
            
            .navbar {
                padding: 15px 20px;
            }
            
            .user-name {
                display: none;
            }
            
            .form-container {
                padding: 0 15px;
            }
            
            .characteristics-box,
            .papiers-section,
            .upload-section {
                padding: 20px;
            }
            
            .btn-submit {
                padding: 16px 30px;
                font-size: 1rem;
            }
            
            .upload-section {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }
        
        @media (max-width: 480px) {
            .type-selection {
                grid-template-columns: 1fr;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .characteristics-box,
            .papiers-section,
            .upload-section {
                padding: 15px;
            }
            
            .form-container {
                padding: 0 10px;
            }
        }
        
        @media (min-width: 769px) {
            .grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .papiers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-card {
                padding: 40px;
            }
            
            .characteristics-box,
            .papiers-section,
            .upload-section {
                padding: 30px;
            }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <a href="../Accueil/propriete.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord
        </a>
        
        <div class="form-header">
            <h1><i class="fa-solid fa-plus-circle"></i> Publier un nouveau bien</h1>
            <p>Remplissez les informations détaillées de votre propriété</p>
        </div>
        
        <div class="form-card">
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> 
                    <div><strong>Erreur :</strong> <?php echo htmlspecialchars($error_msg); ?></div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> 
                    <div><strong>Succès !</strong> Votre bien a été publié avec succès.</div>
                </div>
            <?php endif; ?>

            <form action="ajouter_propriete.php" method="POST" enctype="multipart/form-data" id="propertyForm">
                
                <!-- Titre -->
                <div class="form-group">
                    <label for="titre" class="required">Titre de l'annonce</label>
                    <input type="text" id="titre" name="titre" class="form-control" required 
                           placeholder="Ex: Magnifique villa avec piscine à Cocody">
                    <span class="form-help">Un bon titre attire plus d'acheteurs</span>
                </div>

                <!-- Type de transaction -->
                <div class="transaction-section">
                    <div class="transaction-title">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        <span>Type de transaction</span>
                    </div>
                    
                    <div class="transaction-options">
                        <div class="transaction-option selected" data-value="vente" onclick="selectTransaction(this)">
                            <i class="fa-solid fa-tag"></i>
                            <span>Vente</span>
                            <small>Prix de vente définitif</small>
                        </div>
                        
                        <div class="transaction-option" data-value="location" onclick="selectTransaction(this)">
                            <i class="fa-solid fa-calendar-alt"></i>
                            <span>Location</span>
                            <small>Loyer mensuel</small>
                        </div>
                    </div>
                    
                    <input type="hidden" name="transaction_type" id="transaction_type" value="vente" required>
                </div>

                <!-- ===== CORRECTION 2 : Champ PRIX en type "number" ===== -->
                <div class="grid-2">
                    <div class="form-group">
                        <label for="prix" class="required" id="prix-label">Prix de vente (FCFA)</label>
                        <input type="number" id="prix" name="prix" class="form-control" required 
                               placeholder="1500000" step="1" min="0">
                        <span class="form-help" id="prix-help">Prix total de vente en FCFA</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="ville" class="required">Ville</label>
                        <input type="text" id="ville" name="ville" class="form-control" required 
                               placeholder="Ex: Abidjan">
                        <span class="form-help">Commune ou quartier</span>
                    </div>
                </div>
                <!-- ===== FIN CORRECTION 2 ===== -->

                <!-- Type de bien -->
                <div class="form-group">
                    <label class="required">Type de bien</label>
                    <div class="type-selection">
                        <div class="type-option" data-value="maison" onclick="selectType(this)">
                            <i class="fas fa-house"></i>
                            <span>Maison</span>
                        </div>
                        <div class="type-option" data-value="villa" onclick="selectType(this)">
                            <i class="fas fa-home"></i>
                            <span>Villa</span>
                        </div>
                        <div class="type-option" data-value="appartement" onclick="selectType(this)">
                            <i class="fas fa-building"></i>
                            <span>Appartement</span>
                        </div>
                        <div class="type-option" data-value="terrain" onclick="selectType(this)">
                            <i class="fas fa-mountain"></i>
                            <span>Terrain</span>
                        </div>
                    </div>
                    <input type="hidden" name="type_bien" id="type_bien" value="maison" required>
                </div>

                <!-- Caractéristiques -->
                <div class="characteristics-box">
                    <div class="grid-3">
                        <div class="form-group" id="field-chambres">
                            <label for="chambres">Nombre de chambres</label>
                            <input type="number" id="chambres" name="chambres" class="form-control" 
                                   min="0" placeholder="0">
                        </div>
                        
                        <div class="form-group" id="field-sdb">
                            <label for="salles_bain">Salles de bain</label>
                            <input type="number" id="salles_bain" name="salles_bain" class="form-control" 
                                   min="0" placeholder="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="surface" class="required">Surface (m²)</label>
                            <input type="number" id="surface" name="surface" class="form-control" required 
                                   placeholder="350" min="0" step="0.5">
                        </div>
                    </div>
                </div>

                <!-- Section Papiers -->
                <div id="papiersSection" class="papiers-section">
                    <h3 style="margin-bottom: 20px; color: var(--primary); display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-file-contract"></i> Papiers de la propriété
                    </h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.95rem;">
                        Sélectionnez les papiers disponibles et téléchargez une photo/scan de chaque document
                    </p>
                    
                    <div class="papiers-grid">
                        <!-- Titre foncier -->
                        <div class="papier-item" id="papier-titre_foncier">
                            <div class="papier-header" onclick="togglePapier('titre_foncier')">
                                <input type="checkbox" id="check_titre_foncier" class="papier-checkbox">
                                <span class="papier-title">Titre foncier</span>
                                <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i>
                            </div>
                            <div class="papier-upload">
                                <label class="upload-label">Photo/Scan du titre foncier</label>
                                <input type="file" id="file_titre_foncier" name="papier_titre_foncier" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                                <span class="upload-help">Formats acceptés : PDF, JPG, PNG, DOC</span>
                            </div>
                        </div>
                        
                        <!-- Attestation de propriété -->
                        <div class="papier-item" id="papier-attestation">
                            <div class="papier-header" onclick="togglePapier('attestation')">
                                <input type="checkbox" id="check_attestation" class="papier-checkbox">
                                <span class="papier-title">Attestation de propriété</span>
                                <i class="fa-solid fa-file-signature" style="color: #3b82f6;"></i>
                            </div>
                            <div class="papier-upload">
                                <label class="upload-label">Photo/Scan de l'attestation</label>
                                <input type="file" id="file_attestation" name="papier_attestation" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                                <span class="upload-help">Formats acceptés : PDF, JPG, PNG, DOC</span>
                            </div>
                        </div>
                        
                        <!-- Permis de construire -->
                        <div class="papier-item" id="papier-permis_construire">
                            <div class="papier-header" onclick="togglePapier('permis_construire')">
                                <input type="checkbox" id="check_permis_construire" class="papier-checkbox">
                                <span class="papier-title">Permis de construire</span>
                                <i class="fa-solid fa-file-certificate" style="color: #10b981;"></i>
                            </div>
                            <div class="papier-upload">
                                <label class="upload-label">Photo/Scan du permis</label>
                                <input type="file" id="file_permis_construire" name="papier_permis_construire" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                                <span class="upload-help">Formats acceptés : PDF, JPG, PNG, DOC</span>
                            </div>
                        </div>
                        
                        <!-- Certificat d'urbanisme -->
                        <div class="papier-item" id="papier-certificat_urbanisme">
                            <div class="papier-header" onclick="togglePapier('certificat_urbanisme')">
                                <input type="checkbox" id="check_certificat_urbanisme" class="papier-checkbox">
                                <span class="papier-title">Certificat d'urbanisme</span>
                                <i class="fa-solid fa-file-alt" style="color: #f59e0b;"></i>
                            </div>
                            <div class="papier-upload">
                                <label class="upload-label">Photo/Scan du certificat</label>
                                <input type="file" id="file_certificat_urbanisme" name="papier_certificat_urbanisme" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                                <span class="upload-help">Formats acceptés : PDF, JPG, PNG, DOC</span>
                            </div>
                        </div>
                        
                        <!-- Plan cadastral -->
                        <div class="papier-item" id="papier-cadastre">
                            <div class="papier-header" onclick="togglePapier('cadastre')">
                                <input type="checkbox" id="check_cadastre" class="papier-checkbox">
                                <span class="papier-title">Plan cadastral</span>
                                <i class="fa-solid fa-map" style="color: #8b5cf6;"></i>
                            </div>
                            <div class="papier-upload">
                                <label class="upload-label">Photo/Scan du plan</label>
                                <input type="file" id="file_cadastre" name="papier_cadastre" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                                <span class="upload-help">Formats acceptés : PDF, JPG, PNG, DOC</span>
                            </div>
                        </div>
                        
                        <!-- Contrat de vente -->
                        <div class="papier-item" id="papier-contrat">
                            <div class="papier-header" onclick="togglePapier('contrat')">
                                <input type="checkbox" id="check_contrat" class="papier-checkbox">
                                <span class="papier-title">Contrat de vente</span>
                                <i class="fa-solid fa-handshake" style="color: #6366f1;"></i>
                            </div>
                            <div class="papier-upload">
                                <label class="upload-label">Photo/Scan du contrat</label>
                                <input type="file" id="file_contrat" name="papier_contrat" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                                <span class="upload-help">Formats acceptés : PDF, JPG, PNG, DOC</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Autres papiers -->
                    <div class="autres-papiers">
                        <h4 style="margin-bottom: 15px; color: var(--primary);">
                            <i class="fa-solid fa-file-import"></i> Autres documents
                        </h4>
                        <div class="form-group">
                            <label for="autres_papiers_text">Description des autres documents</label>
                            <input type="text" id="autres_papiers_text" name="autres_papiers_text" class="form-control" 
                                   placeholder="Ex: Acte notarié, Certificat de non-gage, Factures...">
                        </div>
                        <div class="form-group">
                            <label class="upload-label">Télécharger le document</label>
                            <input type="file" id="file_autres_papiers" name="autres_papiers_file" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input">
                            <span class="upload-help">Ajoutez une photo/scan de vos autres documents</span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Description détaillée</label>
                    <textarea id="description" name="description" class="form-control" 
                              placeholder="Décrivez votre bien en détail (emplacement, caractéristiques, équipements, avantages...)"></textarea>
                    <span class="form-help">Plus votre description est complète, plus vous aurez de visites</span>
                </div>

                <!-- Section Upload -->
                <div class="upload-section">
                    <div class="form-group">
                        <label class="upload-label required">
                            <i class="fa-solid fa-camera"></i> Photo principale
                        </label>
                        <input type="file" name="image" accept="image/*" required class="file-input">
                        <span class="upload-help">Cette photo apparaîtra en couverture de votre annonce</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="upload-label">
                            <i class="fa-solid fa-images"></i> Galerie photos (optionnelle)
                        </label>
                        <input type="file" name="galerie[]" accept="image/*" multiple class="file-input">
                        <span class="upload-help">Ajoutez plusieurs photos pour montrer votre bien sous tous les angles</span>
                    </div>
                </div>

                <!-- Bouton de soumission -->
                <button type="submit" name="ajouter_propriete" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> PUBLIER L'ANNONCE
                </button>
            </form>
        </div>
    </div>

    <script>
        function selectTransaction(element) {
            document.querySelectorAll('.transaction-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            element.classList.add('selected');
            const transactionType = element.dataset.value;
            document.getElementById('transaction_type').value = transactionType;
            
            const prixLabel = document.getElementById('prix-label');
            const prixHelp = document.getElementById('prix-help');
            const papiersSection = document.getElementById('papiersSection');
            
            if (transactionType === 'vente') {
                prixLabel.innerHTML = 'Prix de vente (FCFA) <span style="color:#ef4444;">*</span>';
                prixHelp.innerHTML = 'Prix total de vente en FCFA';
                papiersSection.style.display = 'block';
            } else {
                prixLabel.innerHTML = 'Loyer mensuel (FCFA) <span style="color:#ef4444;">*</span>';
                prixHelp.innerHTML = 'Montant du loyer mensuel en FCFA';
                papiersSection.style.display = 'none';
            }
        }
        
        function selectType(element) {
            document.querySelectorAll('.type-option').forEach(option => {
                option.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('type_bien').value = element.dataset.value;
            toggleTerrainFields();
        }
        
        function toggleTerrainFields() {
            const type = document.getElementById('type_bien').value;
            const champChambres = document.getElementById('field-chambres');
            const champSdb = document.getElementById('field-sdb');
            
            if (type === 'terrain') {
                champChambres.style.display = 'none';
                champSdb.style.display = 'none';
                document.getElementById('chambres').value = '0';
                document.getElementById('salles_bain').value = '0';
            } else {
                champChambres.style.display = 'block';
                champSdb.style.display = 'block';
            }
        }
        
        function togglePapier(type) {
            const item = document.getElementById('papier-' + type);
            const checkbox = document.getElementById('check_' + type);
            const fileInput = document.getElementById('file_' + type);
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
                fileInput.value = '';
                const fileInfo = item.querySelector('.file-info');
                if (fileInfo) fileInfo.remove();
            }
        }
        
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelector('.type-option[data-value="maison"]').classList.add('selected');
            toggleTerrainFields();
            
            document.getElementById('propertyForm').addEventListener('submit', function(e) {
                const type = document.getElementById('type_bien').value;
                const transactionType = document.getElementById('transaction_type').value;
                const surface = document.getElementById('surface').value;
                const prix = document.getElementById('prix').value;
                const titre = document.getElementById('titre').value;
                const ville = document.getElementById('ville').value;
                
                let errors = [];
                
                if (!titre.trim()) errors.push('Veuillez saisir un titre pour votre annonce.');
                if (!ville.trim()) errors.push('Veuillez saisir la ville de votre bien.');
                if (!surface || surface <= 0) errors.push('Veuillez saisir une surface valide (supérieure à 0).');
                
                // CORRECTION : validation du prix (le navigateur nous envoie déjà un nombre)
                if (!prix || isNaN(parseFloat(prix)) || parseFloat(prix) <= 0) {
                    errors.push('Veuillez saisir un prix valide (supérieur à 0).');
                }
                
                if (type !== 'terrain') {
                    const chambres = document.getElementById('chambres').value;
                    const sdb = document.getElementById('salles_bain').value;
                    if (chambres < 0 || sdb < 0) {
                        errors.push('Le nombre de chambres et salles de bain doit être positif ou nul.');
                    }
                }
                
                const mainImage = document.querySelector('input[name="image"]');
                if (!mainImage.files.length) {
                    errors.push('Veuillez sélectionner une photo principale pour votre annonce.');
                }
                
                if (transactionType === 'vente') {
                    const papiersTypes = ['titre_foncier', 'attestation', 'permis_construire', 'certificat_urbanisme', 'cadastre', 'contrat'];
                    papiersTypes.forEach(papierType => {
                        const checkbox = document.getElementById('check_' + papierType);
                        const fileInput = document.getElementById('file_' + papierType);
                        
                        if (checkbox.checked && (!fileInput.files || fileInput.files.length === 0)) {
                            const nomPapier = document.querySelector('#papier-' + papierType + ' .papier-title').textContent;
                            errors.push('Veuillez télécharger un fichier pour : ' + nomPapier);
                        }
                    });
                    
                    const autresText = document.getElementById('autres_papiers_text').value;
                    const autresFile = document.getElementById('file_autres_papiers');
                    if (autresText.trim() && (!autresFile.files || autresFile.files.length === 0)) {
                        errors.push('Si vous indiquez d\'autres documents, veuillez télécharger un fichier.');
                    }
                }
                
                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Veuillez corriger les erreurs suivantes :\n\n' + errors.join('\n'));
                    return false;
                }
                
                return true;
            });
            
            // ===== CORRECTION 3 : SUPPRESSION des écouteurs qui causaient le problème =====
            // Ces lignes ont été commentées car elles interfèrent avec le type="number"
            /*
            const prixInput = document.getElementById('prix');
            prixInput.addEventListener('blur', function() {
                let value = this.value.replace(/\D/g, '');
                if (value) {
                    this.value = parseInt(value, 10).toLocaleString('fr-FR');
                }
            });
            
            prixInput.addEventListener('focus', function() {
                this.value = this.value.replace(/\s/g, '');
            });
            */
            // ===== FIN CORRECTION 3 =====
            
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const parent = this.closest('.papier-item') || this.closest('.autres-papiers') || this.closest('.upload-section .form-group');
                    if (parent && this.files.length > 0) {
                        const fileName = this.files[0].name;
                        const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                        
                        const existingInfo = parent.querySelector('.file-info');
                        if (existingInfo) existingInfo.remove();
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'file-info';
                        fileInfo.innerHTML = `
                            <i class="fa-solid fa-file"></i>
                            <span>${fileName} (${fileSize} MB)</span>
                        `;
                        
                        this.parentNode.appendChild(fileInfo);
                    } else if (parent) {
                        const existingInfo = parent.querySelector('.file-info');
                        if (existingInfo) existingInfo.remove();
                    }
                });
            });
        });
    </script>
</body>
</html>