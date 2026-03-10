<?php
session_start();
require_once "data.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_client = $_SESSION['user_id'];
$nom_client = $_SESSION['nom'];

// Initialiser les variables
$result = null;
$prochaines_visites = null;

// Vérifier d'abord si la table rendez_vous existe
$check_table = $db->query("SHOW TABLES LIKE 'rendez_vous'");
if ($check_table && $check_table->num_rows > 0) {
    
    // Vérifier si la colonne id_client existe dans la table rendez_vous
    $check_column = $db->query("SHOW COLUMNS FROM rendez_vous LIKE 'id_client'");
    if ($check_column && $check_column->num_rows > 0) {
        
        // Requête pour tous les rendez-vous
        $sql = "SELECT r.*, 
                       m.titre, 
                       m.image, 
                       m.ville,
                       m.id_maison,
                       m.prix,
                       m.type_bien,
                       m.chambres,
                       m.salles_bain,
                       p.nom_complet AS nom_vendeur, 
                       p.telephone AS tel_vendeur
                FROM rendez_vous r 
                LEFT JOIN maison m ON r.id_maison = m.id_maison 
                LEFT JOIN proprietaire p ON m.id_pro = p.id_pro 
                WHERE r.id_client = ? 
                ORDER BY r.date_rdv DESC, r.heure_rdv DESC";
        
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $id_client);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        // Requête pour les prochaines visites (confirmées à venir)
        $sql_prochaines = "SELECT r.*, 
                           m.titre, 
                           m.image, 
                           m.ville,
                           m.id_maison,
                           m.prix,
                           m.type_bien,
                           m.chambres,
                           m.salles_bain,
                           p.nom_complet AS nom_vendeur
                        FROM rendez_vous r 
                        LEFT JOIN maison m ON r.id_maison = m.id_maison 
                        LEFT JOIN proprietaire p ON m.id_pro = p.id_pro 
                        WHERE r.id_client = ? 
                        AND LOWER(r.statut) IN ('confirme', 'confirmé', 'confirmee', 'confirmée')
                        AND (r.date_rdv > CURDATE() OR (r.date_rdv = CURDATE() AND r.heure_rdv > CURTIME()))
                        ORDER BY r.date_rdv ASC, r.heure_rdv ASC 
                        LIMIT 5";
        
        $stmt_prochaines = $db->prepare($sql_prochaines);
        if ($stmt_prochaines) {
            $stmt_prochaines->bind_param("i", $id_client);
            $stmt_prochaines->execute();
            $prochaines_visites = $stmt_prochaines->get_result();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mes Rendez-vous - DOMUS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">
    
    <style>
        /* En-tête de la page */
        .rdv-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(37, 99, 235, 0.7)),
                        url("https://images.pexels.com/photos/7031426/pexels-photo-7031426.jpeg") center/cover;
            min-height: 30vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 60px 20px;
            margin-bottom: 40px;
        }
        
        /* Styles pour le slider */
        .slider-wrapper {
            position: relative;
            padding: 10px 0 30px 0;
            margin: 20px 0 40px;
        }

        .slider-container {
            display: flex;
            gap: 25px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 10px 5px;
            scroll-snap-type: x mandatory;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .slider-container::-webkit-scrollbar {
            display: none;
        }

        .slider-wrapper .maison-card {
            min-width: 300px; 
            max-width: 300px;
            flex: 0 0 auto;
            scroll-snap-align: start;
            transition: transform 0.3s ease;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .slider-wrapper .maison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: 1px solid #e2e8f0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10;
            transition: all 0.3s ease;
            color: #2563eb;
        }

        .slider-btn:hover {
            background: #2563eb;
            color: white;
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
        }

        .slider-btn.prev { left: -15px; }
        .slider-btn.next { right: -15px; }

        @media (max-width: 768px) {
            .slider-btn { display: none; }
            .slider-wrapper .maison-card {
                min-width: 85vw;
                max-width: 85vw;
            }
        }

        .slider-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
        }

        .dot {
            width: 10px;
            height: 10px;
            background: #cbd5e1;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
        }

        .dot.active {
            background: #2563eb;
            width: 25px;
            border-radius: 10px;
        }
        
        /* Badge de statut pour les cartes du slider */
        .rdv-badge-card {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 10;
            background: #10b981;
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .date-badge {
            position: absolute;
            bottom: 15px;
            left: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 10;
        }
        
        .date-badge i {
            color: #fbbf24;
        }
        
        .card-img-wrapper {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .maison-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .maison-card:hover .maison-image {
            transform: scale(1.1);
        }
        
        .type-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(37, 99, 235, 0.9);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 5;
        }
        
        .description {
            padding: 20px;
        }
        
        .description h3 {
            font-size: 1.1rem;
            color: #0f172a;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        
        .property-details {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #64748b;
            font-size: 0.9rem;
            flex-wrap: wrap;
        }
        
        .property-details p {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0;
        }
        
        .property-details i {
            color: #2563eb;
        }
        
        .prix {
            color: #10b981;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        /* Conteneur principal */
        .rdv-container {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        
        /* Bouton retour */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 30px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .back-btn:hover {
            background: #f1f5f9;
            transform: translateX(-5px);
        }
        
        /* En-tête de section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 20px;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            color: #0f172a;
            font-weight: 600;
        }
        
        .section-header h2 i {
            color: #2563eb;
            margin-right: 10px;
        }
        
        /* Liste des rendez-vous */
        .rdv-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* Carte de rendez-vous */
        .rdv-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            transition: all 0.3s ease;
            display: grid;
            grid-template-columns: 200px 1fr auto;
        }
        
        .rdv-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #bfdbfe;
        }
        
        /* Image de la propriété */
        .rdv-img-container {
            height: 100%;
            min-height: 180px;
            overflow: hidden;
            background: #f1f5f9;
        }
        
        .rdv-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Contenu de la carte */
        .rdv-content {
            padding: 20px;
        }
        
        /* Badge de statut */
        .rdv-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        
        .badge-confirme, 
        .badge-confirmé, 
        .badge-confirmee, 
        .badge-confirmée { 
            background: #d1fae5; 
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .badge-en_attente { 
            background: #fef3c7; 
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .badge-annule, 
        .badge-annulé, 
        .badge-refuse, 
        .badge-refusé { 
            background: #fee2e2; 
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Titre et informations */
        .rdv-title {
            font-size: 1.2rem;
            color: #0f172a;
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        
        .rdv-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .rdv-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .rdv-detail i {
            color: #2563eb;
            width: 16px;
        }
        
        /* Informations vendeur */
        .vendeur-info {
            background: #f8fafc;
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 3px solid #2563eb;
            margin-top: 15px;
        }
        
        .vendeur-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vendeur-phone {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Actions */
        .rdv-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 20px;
            background: #f8fafc;
            border-left: 1px solid #e2e8f0;
            min-width: 150px;
        }
        
        .rdv-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-align: center;
            border: 1px solid transparent;
        }
        
        .btn-chat {
            background: #2563eb;
            color: white;
        }
        
        .btn-chat:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .btn-clear {
            background: white;
            color: #f59e0b;
            border-color: #f59e0b;
        }
        
        .btn-clear:hover {
            background: #f59e0b;
            color: white;
        }
        
        .btn-cancel {
            background: white;
            color: #ef4444;
            border-color: #ef4444;
        }
        
        .btn-cancel:hover {
            background: #ef4444;
            color: white;
        }
        
        .btn-view {
            background: white;
            color: #10b981;
            border-color: #10b981;
        }
        
        .btn-view:hover {
            background: #10b981;
            color: white;
        }
        
        /* Aucun résultat */
        .no-rdv {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            color: #64748b;
        }
        
        .no-rdv i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
        
        .no-rdv h3 {
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .search-btn {
            background: #2563eb;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
            margin-top: 20px;
        }
        
        .search-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        /* Styles pour l'avatar et nom */
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .user-avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid white;
            transition: all 0.3s ease;
        }

        .user-avatar-img:hover {
            transform: scale(1.05);
            border-color: #2563eb;
        }

        .user-name {
            font-weight: 500;
            color: #1e293b;
            font-size: 0.95rem;
            white-space: nowrap;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-info:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }

        @media (max-width: 768px) {
            .user-name {
                display: none;
            }
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .rdv-card {
                grid-template-columns: 1fr;
            }
            
            .rdv-img-container {
                height: 200px;
            }
            
            .rdv-actions {
                flex-direction: row;
                border-left: none;
                border-top: 1px solid #e2e8f0;
            }
            
            .rdv-btn {
                flex: 1;
            }
        }
        
        @media (max-width: 600px) {
            .rdv-actions {
                flex-direction: column;
            }
            
            .rdv-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS">
            <div class="logo-text">DOM<span>US</span></div>
        </div>

        <ul class="nav-links" id="navLinks">
            <li><a href="../Accueil/accueilClient.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="../Accueil/client.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <li><a href="../Accueil/contact.php"><i class="fa-solid fa-envelope"></i> <span>Contact</span></a></li>
        </ul>

        <div class="user-area">
            <div class="user-info" onclick="window.location.href='../Accueil/profil.php'">
                <?php
                // Vérifier si une photo de profil existe
                $sql_photo = "SELECT photo_profil FROM client WHERE id_cli = ?";
                $stmt_photo = $db->prepare($sql_photo);
                $photo_trouvee = false;
                
                if ($stmt_photo) {
                    $stmt_photo->bind_param("i", $id_client);
                    $stmt_photo->execute();
                    $res_photo = $stmt_photo->get_result();
                    if ($row_photo = $res_photo->fetch_assoc()) {
                        if (!empty($row_photo['photo_profil'])) {
                            echo '<img src="' . htmlspecialchars($row_photo['photo_profil']) . '" alt="Photo" class="user-avatar-img" title="Voir mon profil">';
                            $photo_trouvee = true;
                        }
                    }
                    $stmt_photo->close();
                }
                
                if (!$photo_trouvee) {
                    $initial = strtoupper(substr($nom_client, 0, 1));
                    echo '<div class="user-avatar" title="Voir mon profil">' . $initial . '</div>';
                }
                ?>
                <span class="user-name"><?php echo htmlspecialchars($nom_client); ?></span>
            </div>
            
            <a href="logout.php" class="logout-btn" title="Déconnexion">
                <i class="fa-solid fa-power-off"></i>
            </a>
            
            <div class="mobile-toggle" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- En-tête -->
    <header class="rdv-header">
        <div class="tete-content">
            <h2>Mes Rendez-vous</h2>
            <p><?php echo htmlspecialchars($nom_client); ?>, suivez l'état de vos demandes de visite</p>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="rdv-container">
        <a href="../Accueil/client.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Retour au tableau de bord
        </a>

        <!-- SLIDER DES PROCHAINES VISITES -->
        <?php if ($prochaines_visites && $prochaines_visites->num_rows > 0): ?>
        <div class="section-header">
            <h2><i class="fa-solid fa-calendar-check"></i> Vos prochaines visites</h2>
        </div>
        
        <div class="slider-wrapper" id="prochainesVisitesSlider">
            <button class="slider-btn prev"><i class="fas fa-chevron-left"></i></button>
            
            <div class="slider-container">
                <?php while ($pv = $prochaines_visites->fetch_assoc()): 
                    $image = !empty($pv['image']) ? $pv['image'] : 'https://via.placeholder.com/300x200?text=DOMUS';
                    $date_formatee = date('d/m/Y', strtotime($pv['date_rdv']));
                ?>
                    <div class="maison-card" onclick="window.location.href='details.php?id=<?php echo $pv['id_maison']; ?>'">
                        <div class="card-img-wrapper">
                            <img src="<?php echo htmlspecialchars($image); ?>" class="maison-image" alt="<?php echo htmlspecialchars($pv['titre']); ?>">
                            <div class="type-badge"><?php echo $pv['type_bien'] ?? 'Visite'; ?></div>
                            
                            <div class="rdv-badge-card">
                                <i class="fa-solid fa-check"></i> Confirmé
                            </div>
                            
                            <div class="date-badge">
                                <i class="fa-solid fa-calendar-alt"></i>
                                <?php echo $date_formatee; ?> à <?php echo htmlspecialchars($pv['heure_rdv']); ?>
                            </div>
                        </div>
                        <div class="description">
                            <h3><?php echo htmlspecialchars($pv['titre'] ?? 'Propriété'); ?></h3>
                            <div class="property-details">
                                <?php if ($pv['type_bien'] != 'Terrain'): ?>
                                <p><i class="fa-solid fa-bed"></i> <?php echo $pv['chambres'] ?? 0; ?> Ch.</p>
                                <p><i class="fa-solid fa-shower"></i> <?php echo $pv['salles_bain'] ?? 0; ?> Sdb.</p>
                                <?php endif; ?>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pv['ville']); ?></p>
                            </div>
                            <div class="prix">
                                <?php echo number_format($pv['prix'] ?? 0, 0, ',', ' '); ?> XOF
                            </div>
                            <div style="margin-top: 10px; font-size: 0.8rem; color: #2563eb;">
                                <i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($pv['nom_vendeur'] ?? 'Vendeur'); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <button class="slider-btn next"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots"></div>
        </div>
        <?php endif; ?>

        <!-- LISTE DE TOUS LES RENDEZ-VOUS -->
        <div class="section-header">
            <h2><i class="fa-solid fa-list"></i> Historique des demandes</h2>
        </div>

        <div class="rdv-list">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($r = $result->fetch_assoc()): 
                    // Déterminer le statut
                    $statut = isset($r['statut']) ? strtolower(trim($r['statut'])) : 'en_attente';
                    $statut_class = 'en_attente';
                    $statut_text = 'En attente';
                    
                    if (in_array($statut, ['confirme', 'confirmé', 'confirmee', 'confirmée'])) {
                        $statut_class = 'confirme';
                        $statut_text = 'Confirmé';
                    } elseif (in_array($statut, ['annule', 'annulé', 'refuse', 'refusé'])) {
                        $statut_class = 'annule';
                        $statut_text = 'Refusé';
                    }
                    
                    // Gestion de l'image
                    $image_path = '../DOMUS IMAGE/default-property.jpg';
                    if (!empty($r['image'])) {
                        if (strpos($r['image'], 'http') === 0) {
                            $image_path = $r['image'];
                        } else {
                            $clean_path = str_replace(['../', './'], '', $r['image']);
                            $image_path = '../' . $clean_path;
                        }
                    }
                ?>
                    <div class="rdv-card">
                        <!-- Image -->
                        <div class="rdv-img-container">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                 class="rdv-img" 
                                 alt="<?php echo htmlspecialchars($r['titre'] ?? 'Propriété'); ?>"
                                 onerror="this.src='../DOMUS IMAGE/default-property.jpg';">
                        </div>
                        
                        <!-- Informations -->
                        <div class="rdv-content">
                            <span class="rdv-badge badge-<?php echo $statut_class; ?>">
                                <?php echo $statut_text; ?>
                            </span>
                            
                            <h3 class="rdv-title">
                                <?php echo htmlspecialchars($r['titre'] ?? 'Propriété sans titre'); ?>
                            </h3>
                            
                            <div class="rdv-details">
                                <?php if (!empty($r['date_rdv'])): ?>
                                <div class="rdv-detail">
                                    <i class="fa-solid fa-calendar-alt"></i>
                                    <span>Date: <?php echo date('d/m/Y', strtotime($r['date_rdv'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($r['heure_rdv'])): ?>
                                <div class="rdv-detail">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>Heure: <?php echo htmlspecialchars($r['heure_rdv']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($r['ville'])): ?>
                                <div class="rdv-detail">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span><?php echo htmlspecialchars($r['ville']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($r['nom_vendeur'])): ?>
                            <div class="vendeur-info">
                                <div class="vendeur-name">
                                    <i class="fa-solid fa-user-tie"></i>
                                    <?php echo htmlspecialchars($r['nom_vendeur']); ?>
                                </div>
                                <?php if (!empty($r['tel_vendeur'])): ?>
                                <div class="vendeur-phone">
                                    <i class="fa-solid fa-phone"></i>
                                    <?php echo htmlspecialchars($r['tel_vendeur']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="rdv-actions">
                            <?php if ($statut_class == 'confirme'): ?>
                                <a href="discussion.php?rdv=<?php echo $r['id_rdv']; ?>" class="rdv-btn btn-chat">
    <i class="fa-solid fa-comment-dots"></i> Discuter
</a>
                            <?php elseif ($statut_class == 'annule'): ?>
                                <a href="action_visite.php?action=delete&id=<?php echo $r['id_rdv']; ?>" 
                                   class="rdv-btn btn-clear" 
                                   onclick="return confirm('Voulez-vous supprimer cette demande ?')">
                                    <i class="fa-solid fa-trash-can"></i>
                                    Effacer
                                </a>
                            <?php else: ?>
                                <a href="action_visite.php?action=cancel&id=<?php echo $r['id_rdv']; ?>" 
                                   class="rdv-btn btn-cancel" 
                                   onclick="return confirm('Voulez-vous annuler cette demande ?')">
                                    <i class="fa-solid fa-xmark"></i>
                                    Annuler
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($r['id_maison'])): ?>
                            <a href="details.php?id=<?php echo $r['id_maison']; ?>" class="rdv-btn btn-view">
                                <i class="fa-solid fa-eye"></i>
                                Détails
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Aucun rendez-vous -->
                <div class="no-rdv">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <h3>Aucun rendez-vous</h3>
                    <p>Vous n'avez pas encore de rendez-vous programmés.</p>
                    <a href="../Accueil/accueilClient.php" class="search-btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Explorer les propriétés
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="info">
        <div class="footer-section">
            <h2>Pourquoi DOMUS ?</h2>
            <ul class="features-list">
                <li><i class="fa-solid fa-check"></i> Large sélection certifiée</li>
                <li><i class="fa-solid fa-check"></i> Processus simplifié</li>
                <li><i class="fa-solid fa-check"></i> Support client dédié</li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h2>Contactez-nous</h2>
            <p><i class="fa-solid fa-envelope"></i> contact@domus.com</p>
            <p><i class="fa-solid fa-phone"></i> +225 07 00 00 00 00</p>
        </div>
        
        <div class="footer-section">
            <h2>Suivez-nous</h2>
            <div class="social-icons">
                <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
                <a href="#"><i class="fa-brands fa-whatsapp"></i></a>
            </div>
        </div>
    </footer>

    <!-- Script pour le slider et menu mobile -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Menu mobile
        const mobileToggle = document.getElementById("mobileMenuBtn");
        const navLinks = document.getElementById("navLinks");
        
        if (mobileToggle && navLinks) {
            mobileToggle.addEventListener("click", function(e) {
                e.stopPropagation();
                navLinks.classList.toggle("active");
                
                const icon = this.querySelector("i");
                if (navLinks.classList.contains("active")) {
                    icon.classList.remove("fa-bars");
                    icon.classList.add("fa-times");
                    document.body.style.overflow = "hidden";
                } else {
                    icon.classList.remove("fa-times");
                    icon.classList.add("fa-bars");
                    document.body.style.overflow = "";
                }
            });
            
            document.addEventListener("click", function(event) {
                if (!mobileToggle.contains(event.target) && !navLinks.contains(event.target)) {
                    navLinks.classList.remove("active");
                    const icon = mobileToggle.querySelector("i");
                    if (icon) {
                        icon.classList.remove("fa-times");
                        icon.classList.add("fa-bars");
                    }
                    document.body.style.overflow = "";
                }
            });
            
            const navItems = navLinks.querySelectorAll("a");
            navItems.forEach((item) => {
                item.addEventListener("click", function() {
                    if (window.innerWidth <= 768) {
                        navLinks.classList.remove("active");
                        const icon = mobileToggle.querySelector("i");
                        if (icon) {
                            icon.classList.remove("fa-times");
                            icon.classList.add("fa-bars");
                        }
                        document.body.style.overflow = "";
                    }
                });
            });
        }
        
        // Gestion des sliders
        const sliders = document.querySelectorAll('.slider-wrapper');

        sliders.forEach(slider => {
            const container = slider.querySelector('.slider-container');
            const prevBtn = slider.querySelector('.prev');
            const nextBtn = slider.querySelector('.next');
            const dotsContainer = slider.querySelector('.slider-dots');
            const cards = container.querySelectorAll('.maison-card');

            if (cards.length === 0) return;

            dotsContainer.innerHTML = '';

            cards.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.classList.add('dot');
                if (index === 0) dot.classList.add('active');
                dot.addEventListener('click', () => {
                    container.scrollTo({
                        left: cards[index].offsetLeft - container.offsetLeft,
                        behavior: 'smooth'
                    });
                });
                dotsContainer.appendChild(dot);
            });

            const updateDots = () => {
                const scrollPosition = container.scrollLeft;
                const cardWidth = cards[0].offsetWidth + 25;
                const activeIndex = Math.round(scrollPosition / cardWidth);
                
                const dots = dotsContainer.querySelectorAll('.dot');
                dots.forEach((dot, index) => {
                    if (index === activeIndex) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            };

            const scrollAmount = 325;

            prevBtn.addEventListener('click', () => {
                container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', () => {
                container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });

            container.addEventListener('scroll', () => {
                requestAnimationFrame(updateDots);
            });
        });
    });
    </script>
</body>
</html>