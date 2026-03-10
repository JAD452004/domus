<?php
session_start();
require_once "data.php";

// 1. VÉRIFICATION DE CONNEXION ET RÔLE
if (!isset($_SESSION['user_id'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur') {
    header("Location: ../Accueil/accueilPropriete.php");
    exit();
}

// 2. TRAITEMENT DU FORMULAIRE
if (isset($_POST['ajouter_propriete'])) {
    $id_pro = $_SESSION['user_id'];
    $titre = $db->real_escape_string($_POST['titre']);
    
    // Nettoyage du prix (enlève les espaces de formatage)
    $prix_raw = str_replace(' ', '', $_POST['prix']);
    $prix = floatval($prix_raw);
    
    $ville = $db->real_escape_string($_POST['ville']);
    $type = $_POST['type_bien'];
    $surface = (int)$_POST['surface'];
    $description = $db->real_escape_string($_POST['description']);

    $is_terrain = ($type === 'terrain');
    $chambres = $is_terrain ? 0 : (int)$_POST['chambres'];
    $sdb = $is_terrain ? 0 : (int)$_POST['salles_bain'];

    // Dossiers de destination
    $upload_dir = "../UPLOADS/";
    $papier_dir = $upload_dir . "papiers/";
    
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    if (!file_exists($papier_dir)) mkdir($papier_dir, 0777, true);

    // Fonction utilitaire d'upload
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

    // A. Upload de l'image principale (OBLIGATOIRE)
    $target_main = uploadFile('image', $upload_dir);

    if ($target_main) {
        // B. Upload des documents juridiques (IMAGES)
        $titre_foncier = uploadFile('papier_titre_foncier', $papier_dir);
        $attestation   = uploadFile('papier_attestation', $papier_dir);
        $permis        = uploadFile('papier_permis_construire', $papier_dir);
        $urbanisme     = uploadFile('papier_certificat_urbanisme', $papier_dir);
        $cadastral     = uploadFile('papier_cadastre', $papier_dir);
        $contrat       = uploadFile('papier_contrat', $papier_dir);
        $autres_file   = uploadFile('autres_papiers_file', $papier_dir);
        $autres_titre  = !empty($_POST['autres_papiers_text']) ? $db->real_escape_string($_POST['autres_papiers_text']) : null;

        // C. Insertion dans la table 'maison'
        // Note: Assurez-vous que votre table possède ces colonnes via ALTER TABLE
        $sql = "INSERT INTO maison (
                    id_pro, titre, prix, ville, type_bien, chambres, salles_bain, surface, 
                    description, image, titre_foncier, attestation_propriete, 
                    permis_construire, certificat_urbanisme, plan_cadastral, 
                    contrat_vente, autres_documents, autres_documents_titre
                ) VALUES (
                    '$id_pro', '$titre', '$prix', '$ville', '$type', '$chambres', '$sdb', '$surface', 
                    '$description', '$target_main', 
                    " . ($titre_foncier ? "'$titre_foncier'" : "NULL") . ", 
                    " . ($attestation ? "'$attestation'" : "NULL") . ", 
                    " . ($permis ? "'$permis'" : "NULL") . ", 
                    " . ($urbanisme ? "'$urbanisme'" : "NULL") . ", 
                    " . ($cadastral ? "'$cadastral'" : "NULL") . ", 
                    " . ($contrat ? "'$contrat'" : "NULL") . ", 
                    " . ($autres_file ? "'$autres_file'" : "NULL") . ", 
                    " . ($autres_titre ? "'$autres_titre'" : "NULL") . "
                )";

        if ($db->query($sql)) {
            $id_maison = $db->insert_id;

            // D. Gestion de la Galerie
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