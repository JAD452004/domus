<?php
session_start();
require_once "../PHP/data.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_client = $_SESSION['user_id']; 
$nom_client = $_SESSION['nom'];
$id_client_clean = $db->real_escape_string($id_client);

// Variable pour l'avatar
$nom_complet = $nom_client;

// ===== Vérifier si les tables existent =====
$tables_existantes = [];
$check_tables = $db->query("SHOW TABLES");
while ($table = $check_tables->fetch_array()) {
    $tables_existantes[] = $table[0];
}

// Initialiser les variables
$total_favoris = 0;
$total_vues = 0;
$total_rdv = 0;
$result_fav = null;
$result_vues = null;

// ===== Récupérer les favoris =====
if (in_array('favoris', $tables_existantes)) {
    $sql_fav = "SELECT m.* FROM maison m 
                JOIN favoris f ON m.id_maison = f.id_maison 
                WHERE f.id_cli = '$id_client_clean' 
                ORDER BY f.id_favoris DESC";
    $result_fav = $db->query($sql_fav);
    if ($result_fav) {
        $total_favoris = $result_fav->num_rows;
    }
}

// ===== Récupérer les vues récentes =====
if (in_array('vues_recentes', $tables_existantes)) {
    $sql_vues = "SELECT m.* FROM maison m 
                 JOIN vues_recentes v ON m.id_maison = v.id_maison 
                 WHERE v.id_user = '$id_client_clean' 
                 GROUP BY m.id_maison 
                 ORDER BY MAX(v.id_vue) DESC LIMIT 4";
    $result_vues = $db->query($sql_vues);
    if ($result_vues) {
        $total_vues = $result_vues->num_rows;
    }
}

// ===== Compter les rendez-vous =====
if (in_array('rendez_vous', $tables_existantes)) {
    $sql_rdv_count = "SELECT COUNT(*) as total FROM rendez_vous WHERE id_client = '$id_client_clean'";
    $result_rdv = $db->query($sql_rdv_count);
    if ($result_rdv) {
        $row_rdv = $result_rdv->fetch_assoc();
        $total_rdv = $row_rdv['total'] ?? 0;
    }
}

// ===== Fonction pour sécuriser les images =====
function getImagePath($image) {
    return !empty($image) ? htmlspecialchars($image) : 'https://via.placeholder.com/300x200?text=DOMUS';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tableau de Bord - DOMUS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">
    
    <style>
        /* En-tête du tableau de bord */
        .dashboard-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(37, 99, 235, 0.7)),
                        url("https://images.pexels.com/photos/7031406/pexels-photo-7031406.jpeg") center/cover;
            min-height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 60px 20px;
            margin-bottom: 40px;
            position: relative;
        }
        
        /* Lien rendez-vous */
        .rdv-container {
            max-width: 900px;
            margin: 30px auto 0;
        }
        
        .rdv-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            text-decoration: none;
            color: #1e293b;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .rdv-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            border-color: #2563eb;
        }
        
        .rdv-icon {
            font-size: 2rem;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .rdv-content {
            flex: 1;
            margin: 0 25px;
        }
        
        .rdv-title {
            display: block;
            font-weight: 600;
            font-size: 1.1rem;
            color: #0f172a;
        }
        
        .rdv-subtitle {
            display: block;
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .rdv-badge {
            background: #2563eb;
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Grille des statistiques */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #bfdbfe;
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
            line-height: 1;
        }
        
        .stat-label {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.95rem;
        }
        
        /* Styles pour le slider */
        .slider-wrapper {
            position: relative;
            padding: 10px 0 30px 0;
            margin: 20px 0;
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
        }

        .slider-wrapper .maison-card:hover {
            transform: translateY(-5px);
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

        /* Cartes de favoris avec bouton de suppression */
        .favori-btn-dashboard {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .favori-btn-dashboard i {
            color: #ef4444;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .favori-btn-dashboard:hover {
            background: #ef4444;
            transform: scale(1.1);
        }
        
        .favori-btn-dashboard:hover i {
            color: white;
        }
        
        /* Animation de suppression */
        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: scale(0.9);
            }
        }
        
        .fade-out {
            animation: fadeOut 0.4s ease forwards;
        }
        
        /* Historique */
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .clear-history {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .clear-history:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        /* Card image wrapper */
        .card-img-wrapper {
            position: relative;
            width: 100%;
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
            backdrop-filter: blur(5px);
        }

        .description {
            padding: 20px;
        }

        .description h3 {
            font-size: 1.1rem;
            color: #0f172a;
            margin: 0 0 10px 0;
            font-weight: 600;
            line-height: 1.3;
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
        
        /* Styles pour l'avatar */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }
        
        .user-name-link {
            text-decoration: none;
            color: inherit;
        }
        
        .user-name {
            font-weight: 500;
            margin-left: 8px;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .rdv-card {
                padding: 18px;
            }
            
            .rdv-content {
                margin: 0 15px;
            }
        }
        
        @media (max-width: 767px) {
            .dashboard-header {
                min-height: 40vh;
                padding: 40px 20px;
            }
            
            .rdv-card {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .rdv-content {
                margin: 0;
            }
            
            .stat-card {
                padding: 25px;
            }
            
            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .stat-card {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- ============================
         BARRE DE NAVIGATION
         ============================ -->
    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS">
            <div class="logo-text">DOM<span>US</span></div>
        </div>
        
        <ul class="nav-links" id="navLinks">
            <li><a href="accueilClient.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="client.php" class="active"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
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

    <!-- ============================
         HEADER DU DASHBOARD
         ============================ -->
    <header class="dashboard-header">
        <div class="tete-content">
            <h2>Mon Tableau de Bord</h2>
            <p>Bienvenue, <?php echo htmlspecialchars($nom_client); ?></p>
            
            <div class="rdv-container">
                <a href="../PHP/mes_rendez_vous.php" class="rdv-card">
                    <div class="rdv-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="rdv-content">
                        <span class="rdv-title">Suivre mes demandes de rendez-vous</span>
                        <span class="rdv-subtitle">Consultez si vos visites ont été acceptées ou refusées</span>
                    </div>
                    <div class="rdv-badge">
                        <span><?php echo $total_rdv; ?> RDV</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_favoris; ?></h3>
                        <p class="stat-label">Propriétés favorites</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_vues; ?></h3>
                        <p class="stat-label">Consultations récentes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo $total_rdv; ?></h3>
                        <p class="stat-label">Rendez-vous demandés</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ============================
         MES FAVORIS (AVEC SLIDER)
         ============================ -->
    <section class="section-container">
        <div class="section-header">
            <h2><i class="fa-solid fa-heart" style="color: #ef4444;"></i> Mes Favoris</h2>
            <?php if ($total_favoris > 0): ?>
                <div class="type-count">
                    <span><?php echo $total_favoris; ?> propriété<?php echo $total_favoris > 1 ? 's' : ''; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_favoris > 0 && $result_fav): ?>
            <div class="slider-wrapper" id="favorisSlider">
                
                <button class="slider-btn prev"><i class="fas fa-chevron-left"></i></button>
                
                <div class="slider-container">
                    <?php while ($m = $result_fav->fetch_assoc()): ?>
                        <div class="maison-card" id="card-<?php echo $m['id_maison']; ?>">
                            <button onclick="event.stopPropagation(); retirerFavori(<?php echo $m['id_maison']; ?>)" 
                                    class="favori-btn-dashboard" title="Retirer des favoris">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                            
                            <div onclick="window.location.href='../PHP/details.php?id=<?php echo $m['id_maison']; ?>'">
                                <div class="card-img-wrapper">
                                    <img src="<?php echo getImagePath($m['image']); ?>" 
                                         class="maison-image" 
                                         alt="<?php echo htmlspecialchars($m['titre'] ?? 'Image'); ?>">
                                    <div class="type-badge"><?php echo $m['type_bien'] ?? 'Propriété'; ?></div>
                                </div>
                                
                                <div class="description">
                                    <h3><?php echo htmlspecialchars($m['titre'] ?? 'Sans titre'); ?></h3>
                                    <div class="property-details">
                                        <p><i class="fa-solid fa-bed"></i> <?php echo $m['chambres'] ?? 0; ?> Ch.</p>
                                        <p><i class="fa-solid fa-shower"></i> <?php echo $m['salles_bain'] ?? 0; ?> Sdb.</p>
                                        <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($m['ville'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="prix">
                                        <?php echo number_format($m['prix'] ?? 0, 0, ',', ' '); ?> XOF
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <button class="slider-btn next"><i class="fas fa-chevron-right"></i></button>
                <div class="slider-dots"></div>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fa-regular fa-heart"></i>
                <p>Vous n'avez pas encore de favoris</p>
                <a href="accueilClient.php" class="search-btn" style="margin-top: 15px; display: inline-block;">
                    <i class="fa-solid fa-magnifying-glass"></i> Explorer les propriétés
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================
         HISTORIQUE (AVEC SLIDER)
         ============================ -->
    <section class="section-container">
        <div class="section-header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Récemment Consultés</h2>
            <?php if ($total_vues > 0): ?>
                <div class="history-header">
                    <div class="type-count">
                        <span><?php echo $total_vues; ?> propriété<?php echo $total_vues > 1 ? 's' : ''; ?></span>
                    </div>
                    <a href="../PHP/effacer_historique.php" 
                       onclick="return confirm('Êtes-vous sûr de vouloir effacer tout votre historique ?')" 
                       class="clear-history">
                        <i class="fa-solid fa-trash-can"></i> Effacer l'historique
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_vues > 0 && $result_vues): ?>
            <div class="slider-wrapper" id="historiqueSlider">
                
                <button class="slider-btn prev"><i class="fas fa-chevron-left"></i></button>
                
                <div class="slider-container">
                    <?php while ($v = $result_vues->fetch_assoc()): ?>
                        <div class="maison-card" 
                             onclick="window.location.href='../PHP/details.php?id=<?php echo $v['id_maison']; ?>'">
                            <div class="card-img-wrapper">
                                <img src="<?php echo getImagePath($v['image']); ?>" 
                                     class="maison-image" 
                                     alt="<?php echo htmlspecialchars($v['titre'] ?? 'Image'); ?>">
                                <div class="type-badge"><?php echo $v['type_bien'] ?? 'Propriété'; ?></div>
                            </div>
                            
                            <div class="description">
                                <h3><?php echo htmlspecialchars($v['titre'] ?? 'Sans titre'); ?></h3>
                                <div class="property-details">
                                    <p><i class="fa-solid fa-bed"></i> <?php echo $v['chambres'] ?? 0; ?> Ch.</p>
                                    <p><i class="fa-solid fa-shower"></i> <?php echo $v['salles_bain'] ?? 0; ?> Sdb.</p>
                                    <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($v['ville'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="prix">
                                    <?php echo number_format($v['prix'] ?? 0, 0, ',', ' '); ?> XOF
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <button class="slider-btn next"><i class="fas fa-chevron-right"></i></button>
                <div class="slider-dots"></div>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fa-regular fa-clock"></i>
                <p>Aucune propriété consultée récemment</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============================
         FOOTER
         ============================ -->
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

    <script>
    function retirerFavori(idMaison) {
        if (confirm("Retirer cette propriété de vos favoris ?")) {
            fetch("../PHP/toggle_favoris.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id_maison=" + idMaison,
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === "removed") {
                    const card = document.getElementById("card-" + idMaison);
                    card.classList.add("fade-out");
                    
                    setTimeout(() => {
                        card.remove();
                        
                        const favCountElement = document.querySelector(".stat-card:first-child .stat-value");
                        if (favCountElement) {
                            let currentCount = parseInt(favCountElement.innerText);
                            favCountElement.innerText = currentCount - 1;
                            
                            const headerCount = document.querySelector(".section-header .type-count span");
                            if (headerCount) {
                                headerCount.innerText = (currentCount - 1) + " propriété" + (currentCount - 1 > 1 ? 's' : '');
                            }
                            
                            if (currentCount - 1 === 0) {
                                location.reload();
                            }
                        }
                    }, 400);
                }
            })
            .catch(error => {
                console.error("Erreur:", error);
                alert("Une erreur est survenue. Veuillez réessayer.");
            });
        }
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        const mobileToggle = document.getElementById("mobileMenuBtn");
        const navLinks = document.getElementById("navLinks");
        
        if (mobileToggle && navLinks) {
            mobileToggle.addEventListener("click", function (e) {
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
            
            document.addEventListener("click", function (event) {
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
                item.addEventListener("click", function () {
                    if (window.innerWidth <= 768) {
                        navLinks.classList.remove("active");
                        const icon = mobileToggle.querySelector("i");
                        icon.classList.remove("fa-times");
                        icon.classList.add("fa-bars");
                        document.body.style.overflow = "";
                    }
                });
            });
        }
        
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