<?php
session_start();
require_once "../PHP/data.php";

// Vérifier la connexion et le rôle
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur' || !isset($_SESSION['user_id'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_pro = $_SESSION['user_id'];
$nom_vendeur = $_SESSION['nom'];

// Récupérer les 10 dernières propriétés de ce vendeur
$query = "SELECT * FROM maison WHERE id_pro = ? ORDER BY id_maison DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id_pro);
$stmt->execute();
$result = $stmt->get_result();
$proprietes = [];

while ($row = $result->fetch_assoc()) {
    $proprietes[] = $row;
}

// Recherche
$search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
if ($search) {
    $search_query = "SELECT * FROM maison WHERE id_pro = ? AND (titre LIKE '%$search%' OR ville LIKE '%$search%') ORDER BY id_maison DESC";
    $search_stmt = $db->prepare($search_query);
    $search_stmt->bind_param("i", $id_pro);
    $search_stmt->execute();
    $result = $search_stmt->get_result();
    $proprietes = [];
    while ($row = $result->fetch_assoc()) {
        $proprietes[] = $row;
    }
}

// ============================
// STATISTIQUES POUR LE VENDEUR
// ============================

// Calculer le nombre total de vues uniques pour toutes les propriétés du vendeur
$query_vues = "SELECT SUM(vues) as total_vues FROM maison WHERE id_pro = ?";
$stmt_vues = $db->prepare($query_vues);
$stmt_vues->bind_param("i", $id_pro);
$stmt_vues->execute();
$result_vues = $stmt_vues->get_result();
$total_vues = $result_vues->fetch_assoc()['total_vues'] ?? 0;

// Compter les visiteurs uniques aujourd'hui
$query_visiteurs_jour = "SELECT COUNT(DISTINCT vm.ip_visiteur) as visiteurs_jour 
                         FROM vues_maison vm 
                         JOIN maison m ON vm.id_maison = m.id_maison 
                         WHERE m.id_pro = ? AND DATE(vm.date_vue) = CURDATE()";
$stmt_visiteurs = $db->prepare($query_visiteurs_jour);
$stmt_visiteurs->bind_param("i", $id_pro);
$stmt_visiteurs->execute();
$result_visiteurs = $stmt_visiteurs->get_result();
$visiteurs_jour = $result_visiteurs->fetch_assoc()['visiteurs_jour'] ?? 0;

// Compter les demandes de visite (rendez-vous)
$query_rdv = "SELECT COUNT(*) as total_rdv FROM rendez_vous r 
              JOIN maison m ON r.id_maison = m.id_maison 
              WHERE m.id_pro = ?";
$stmt_rdv = $db->prepare($query_rdv);
$stmt_rdv->bind_param("i", $id_pro);
$stmt_rdv->execute();
$result_rdv = $stmt_rdv->get_result();
$total_rdv = $result_rdv->fetch_assoc()['total_rdv'] ?? 0;

// Variable pour l'avatar
$nom_complet = $nom_vendeur;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Espace Vendeur - DOMUS</title>
    
    <!-- Importation des ressources externes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10 déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">
    
    <style>
        /* STYLES SPÉCIFIQUES À L'ESPACE VENDEUR */
        
        /* En-tête de l'espace vendeur */
        .vendeur-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(37, 99, 235, 0.7)),
                        url("https://images.pexels.com/photos/7031423/pexels-photo-7031423.jpeg") center/cover;
            min-height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 60px 20px;
            margin-bottom: 40px;
            position: relative;
        }
        
        /* Boutons d'action */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        
        .btn-dashboard {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        
        .btn-public {
            background: white;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn-add:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
        }
        
        .btn-dashboard:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        
        .btn-public:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        /* Styles pour le slider (copiés de accueilClient) */
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

        /* Carte de propriété (adaptée de accueilClient) */
        .maison-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            cursor: pointer;
            position: relative;
        }

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

        /* Badge de vues */
        .vues-badge {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 5;
        }

        .vues-badge i {
            font-size: 0.8rem;
            color: #fbbf24;
        }

        /* Aucune propriété */
        .empty-properties {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            color: #64748b;
        }
        
        .empty-properties i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
        
        /* Statistiques vendeur */
        .vendeur-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .stat-card-vendeur {
            background: white;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }
        
        .stat-card-vendeur:hover {
            transform: translateY(-3px);
            border-color: #bfdbfe;
        }
        
        .stat-icon-vendeur {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-content-vendeur h3 {
            font-size: 2rem;
            margin: 0;
            color: #0f172a;
            font-weight: 700;
        }
        
        .stat-content-vendeur p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        
        /* Petit badge pour les visiteurs du jour */
        .visiteurs-jour-badge {
            background: #f59e0b;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 10px;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .vendeur-stats {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 767px) {
            .vendeur-header {
                min-height: 30vh;
                padding: 40px 20px;
            }
            
            .slider-wrapper .maison-card {
                min-width: 250px;
            }
            
            .stat-card-vendeur {
                padding: 20px;
            }
            
            .stat-icon-vendeur {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
            
            .stat-content-vendeur h3 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .slider-wrapper .maison-card {
                min-width: 200px;
            }
            
            .stat-card-vendeur {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- ============================
         BARRE DE NAVIGATION (IDENTIQUE À accueilClient.php)
         ============================ -->
    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS Logo">
            <div class="logo-text">DOM<span>US</span></div>
        </div>
        
        <ul class="nav-links" id="navLinks">
            <li><a href="accueilPropriete.php" class="active"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="propriete.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <li><a href="contact.php"><i class="fa-solid fa-envelope"></i> <span>Contact</span></a></li>
        </ul>

        <!-- Zone utilisateur avec nom affiché -->
        <div class="user-area">
            <div class="user-info" onclick="window.location.href='profil.php'">
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
         HEADER VENDEUR
         ============================ -->
    <header class="vendeur-header">
        <div class="tete-content">
            <h2>Espace Vendeur</h2>
            <p>Bienvenue, <?php echo htmlspecialchars($nom_vendeur); ?>. Gérez vos propriétés en toute simplicité.</p>
            
            <!-- Boutons d'action principaux -->
            <div class="action-buttons">
                <a href="../PHP/ajouter_propriete.php" class="action-btn btn-add">
                    <i class="fa-solid fa-plus"></i> Ajouter une propriété
                </a>
              
                <a href="../index.php" class="action-btn btn-public" target="_blank">
                    <i class="fa-solid fa-eye"></i> Voir le site public
                </a>
            </div>
        </div>
    </header>

    <!-- ============================
         STATISTIQUES VENDEUR
         ============================ -->
    <div class="section-container" style="margin-bottom: 30px;">
        <div class="section-header">
            <h2>Vos Statistiques</h2>
        </div>
        
        <div class="vendeur-stats">
            <div class="stat-card-vendeur">
                <div class="stat-icon-vendeur" style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                    <i class="fa-solid fa-house"></i>
                </div>
                <div class="stat-content-vendeur">
                    <h3><?php echo count($proprietes); ?></h3>
                    <p>Propriétés publiées</p>
                </div>
            </div>
            
            <div class="stat-card-vendeur">
                <div class="stat-icon-vendeur" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <div class="stat-content-vendeur">
                    <h3><?php echo $total_vues; ?></h3>
                    <p>Visiteurs uniques (total)</p>
                    <small style="color: #f59e0b;"><?php echo $visiteurs_jour; ?> aujourd'hui</small>
                </div>
            </div>
            
            <div class="stat-card-vendeur">
                <div class="stat-icon-vendeur" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="stat-content-vendeur">
                    <h3><?php echo $total_rdv; ?></h3>
                    <p>Demandes de visite</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================
         MES PROPRIÉTÉS (AVEC SLIDER)
         ============================ -->
    <section class="section-container">
        <div class="section-header">
            <h2>Mes Dernières Propriétés</h2>
            <?php if (!empty($proprietes)): ?>
                <div class="type-count">
                    <span><?php echo count($proprietes); ?> propriété<?php echo count($proprietes) > 1 ? 's' : ''; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($proprietes)): ?>
            <div class="slider-wrapper" id="proprietesSlider">
                
                <button class="slider-btn prev"><i class="fas fa-chevron-left"></i></button>
                
                <div class="slider-container">
                    <?php foreach ($proprietes as $prop): ?>
                        <div class="maison-card" onclick="window.location.href='../PHP/details.php?id=<?php echo $prop['id_maison']; ?>'">
                            <div class="card-img-wrapper">
                                <img src="<?php echo htmlspecialchars($prop['image'] ?: '../DOMUS IMAGE/default.jpg'); ?>" 
                                     class="maison-image" 
                                     alt="<?php echo htmlspecialchars($prop['titre']); ?>">
                                <div class="type-badge"><?php echo $prop['type_bien']; ?></div>
                                
                                <!-- Badge de nombre de vues uniques -->
                                <div class="vues-badge" title="Visiteurs uniques">
                                    <i class="fa-solid fa-eye"></i>
                                    <span><?php echo $prop['vues'] ?? 0; ?></span>
                                </div>
                            </div>
                            <div class="description">
                                <h3><?php echo htmlspecialchars($prop['titre']); ?></h3>
                                <div class="property-details">
                                    <?php if ($prop['type_bien'] != 'Terrain'): ?>
                                    <p><i class="fa-solid fa-bed"></i> <?php echo $prop['chambres']; ?> Ch.</p>
                                    <p><i class="fa-solid fa-shower"></i> <?php echo $prop['salles_bain']; ?> Sdb.</p>
                                    <?php else: ?>
                                    <p><i class="fa-solid fa-ruler-combined"></i> <?php echo $prop['surface']; ?> m²</p>
                                    <?php endif; ?>
                                    <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($prop['ville']); ?></p>
                                </div>
                                <div class="prix">
                                    <?php echo number_format($prop['prix'], 0, ',', ' '); ?> XOF
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="slider-btn next"><i class="fas fa-chevron-right"></i></button>
                <div class="slider-dots"></div>
            </div>
        <?php else: ?>
            <!-- Aucune propriété -->
            <div class="empty-properties">
                <i class="fa-solid fa-house-chimney-medical"></i>
                <h3>Aucune propriété publiée</h3>
                <p>Commencez à vendre vos biens dès maintenant.</p>
                <a href="../PHP/ajouter_propriete.php" class="action-btn btn-add" style="margin-top: 20px; display: inline-block;">
                    <i class="fa-solid fa-plus"></i> Publier mon premier bien
                </a>
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

    <!-- ============================
         JAVASCRIPT
         ============================ -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Menu mobile
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
            
            // Ferme le menu quand on clique sur un lien
            const navItems = navLinks.querySelectorAll("a");
            navItems.forEach((item) => {
                item.addEventListener("click", function () {
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
            
            // Créer les dots
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
            
            // Fonction pour mettre à jour les dots
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
            
            // Gestion du scroll
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