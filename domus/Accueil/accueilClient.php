<?php
session_start();
require_once "../PHP/data.php";

// Vérifie si l'utilisateur est connecté en tant que client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['user_id'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// ============================
// RÉCUPÉRATION DES INFORMATIONS CLIENT
$id_client = $_SESSION['user_id'];
$nom_client = $_SESSION['nom'];

// ============================
// Récupérer les favoris existants de l'utilisateur
$favoris_existants = [];
$sql_fav = "SELECT id_maison FROM favoris WHERE id_cli = '$id_client'";
$result_fav = $db->query($sql_fav);
if ($result_fav) {
    while ($row = $result_fav->fetch_assoc()) {
        $favoris_existants[] = $row['id_maison'];
    }
}

// ============================
// GESTION DE LA RECHERCHE
$search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
$type = isset($_GET['type']) ? $db->real_escape_string($_GET['type']) : '';
$ville = isset($_GET['ville']) ? $db->real_escape_string($_GET['ville']) : '';

// ============================
// TYPES À AFFICHER DANS L'ORDRE
$types_a_afficher = ['Maison', 'Villa', 'Appartement', 'Terrain'];
$has_results = false;

// Variable pour l'avatar
$nom_complet = $nom_client;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DOMUS - Mon Espace Client</title>
    
    <!-- Importation des ressources externes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">

    <style>
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

        /* Styles pour les notifications */
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #10b981;
            color: white;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        }

        .toast-message.error {
            background: #ef4444;
        }

        .toast-message.info {
            background: #3b82f6;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Style amélioré pour le bouton favori */
        .favori-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            z-index: 10;
            transition: all 0.3s ease;
        }

        .favori-btn i {
            font-size: 1.2rem;
            color: #cbd5e1;
            transition: all 0.3s ease;
        }

        .favori-btn:hover {
            transform: scale(1.1);
            background: #fee2e2;
        }

        .favori-btn.active i {
            color: #dc3545 !important;
        }

        .favori-btn:hover i {
            color: #dc3545;
        }

        /* Badge pour le nombre de vues uniques */
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

        /* Tooltip pour les vues */
        .vues-badge:hover::after {
            content: "Visiteurs uniques";
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            margin-bottom: 5px;
        }

        /* Styles pour l'avatar (définis dans _user_avatar.php) */
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
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS Logo">
            <div class="logo-text">DOM<span>US</span></div>
        </div>
        
        <ul class="nav-links" id="navLinks">
            <li><a href="accueilClient.php" class="active"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="client.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
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

    <header class="tete">
        <div class="tete-content">
            <h2>Bienvenue, <?php echo htmlspecialchars($nom_client); ?></h2>
            <p>Explorez nos exclusivités immobilières.</p>
            <div class="search-container">
                <form method="GET" action="">
                    <div class="input-group">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="search-input" placeholder="Mots-clés...">
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-house"></i>
                        <select name="type" class="search-select">
                            <option value="">Tous les types</option>
                            <option value="Maison" <?php echo ($type == 'Maison') ? 'selected' : ''; ?>>Maison</option>
                            <option value="Villa" <?php echo ($type == 'Villa') ? 'selected' : ''; ?>>Villa</option>
                            <option value="Appartement" <?php echo ($type == 'Appartement') ? 'selected' : ''; ?>>Appartement</option>
                            <option value="Terrain" <?php echo ($type == 'Terrain') ? 'selected' : ''; ?>>Terrain</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <i class="fa-solid fa-location-dot"></i>
                        <select name="ville" class="search-select">
                            <option value="">Toutes les villes</option>
                            <option value="Abidjan" <?php echo ($ville == 'Abidjan') ? 'selected' : ''; ?>>Abidjan</option>
                            <option value="Yamoussoukro" <?php echo ($ville == 'Yamoussoukro') ? 'selected' : ''; ?>>Yamoussoukro</option>
                            <option value="Bouaké" <?php echo ($ville == 'Bouaké') ? 'selected' : ''; ?>>Bouaké</option>
                            <option value="San Pedro" <?php echo ($ville == 'San Pedro') ? 'selected' : ''; ?>>San Pedro</option>
                            <option value="Daloa" <?php echo ($ville == 'Daloa') ? 'selected' : ''; ?>>Daloa</option>
                            <option value="Autre" <?php echo ($ville == 'Autre') ? 'selected' : ''; ?>>Autres villes</option>
                        </select>
                    </div>
                    <button type="submit" class="search-btn">
                        <i class="fa-solid fa-magnifying-glass"></i> Chercher
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- ============================
         SECTIONS PAR TYPE DE BIEN (AVEC SLIDER)
         ============================ -->
    <?php
    foreach ($types_a_afficher as $type_bien) {
        $slider_id = "slider-" . str_replace(' ', '-', $type_bien);

        $sql = "SELECT * FROM maison WHERE type_bien = '$type_bien'";
        if ($search != '') $sql .= " AND (titre LIKE '%$search%' OR description LIKE '%$search%')";
        if ($type != '' && $type != $type_bien) continue; 
        if ($ville != '') $sql .= " AND ville = '$ville'";
        $sql .= " ORDER BY id_maison DESC";
        
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0):
            $has_results = true;
    ?>
    <section class="section-container">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2><?php echo $type_bien; ?>s disponibles</h2>
            <div class="type-count">
                <span><?php echo $result->num_rows; ?> propriété<?php echo $result->num_rows > 1 ? 's' : ''; ?></span>
            </div>
        </div>

        <div class="slider-wrapper" id="<?php echo $slider_id; ?>">
            
            <button class="slider-btn prev"><i class="fas fa-chevron-left"></i></button>
            
            <div class="slider-container">
                <?php while ($prop = $result->fetch_assoc()): 
                    $est_favori = in_array($prop['id_maison'], $favoris_existants);
                    $image = !empty($prop['image']) ? $prop['image'] : 'https://via.placeholder.com/300x200?text=DOMUS';
                ?>
                    <div class="maison-card" onclick="window.location.href='../PHP/incrementer_vue_unique.php?id=<?php echo $prop['id_maison']; ?>'">
                        <div class="card-img-wrapper">
                            <img src="<?php echo htmlspecialchars($image); ?>" class="maison-image" alt="<?php echo htmlspecialchars($prop['titre']); ?>">
                            <div class="type-badge"><?php echo $prop['type_bien']; ?></div>
                            
                            <div class="vues-badge" title="Visiteurs uniques">
                                <i class="fa-solid fa-eye"></i>
                                <span><?php echo $prop['vues'] ?? 0; ?></span>
                            </div>
                            
                            <button onclick="event.stopPropagation(); toggleFavori(<?php echo $prop['id_maison']; ?>, this)" 
                                class="favori-btn <?php echo $est_favori ? 'active' : ''; ?>"
                                data-property-id="<?php echo $prop['id_maison']; ?>">
                                <i class="fa-solid fa-heart" style="color: <?php echo $est_favori ? '#dc3545' : '#cbd5e1'; ?>;"></i>
                            </button>
                        </div>
                        <div class="description">
                            <h3><?php echo htmlspecialchars($prop['titre']); ?></h3>
                            <div class="property-details">
                                <?php if ($type_bien != 'Terrain'): ?>
                                <p><i class="fa-solid fa-bed"></i> <?php echo $prop['chambres'] ?? 0; ?> Ch.</p>
                                <p><i class="fa-solid fa-shower"></i> <?php echo $prop['salles_bain'] ?? 0; ?> Sdb.</p>
                                <?php else: ?>
                                <p><i class="fa-solid fa-ruler-combined"></i> <?php echo $prop['surface'] ?? 'N/A'; ?> m²</p>
                                <?php endif; ?>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($prop['ville']); ?></p>
                            </div>
                            <div class="prix">
                                <?php echo number_format($prop['prix'] ?? 0, 0, ',', ' '); ?> XOF
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <button class="slider-btn next"><i class="fas fa-chevron-right"></i></button>
            <div class="slider-dots"></div>
        </div>

    </section>
    <?php 
        endif;
    }
    
    if (!$has_results):
    ?>
    <section class="section-container">
        <div class="no-results">
            <i class="fa-regular fa-folder-open" style="font-size: 60px; color: #cbd5e1; margin-bottom: 20px;"></i>
            <h3 style="color: #1e293b; margin-bottom: 10px;">Aucune annonce trouvée</h3>
            <p style="color: #64748b; margin-bottom: 20px;">Aucune propriété ne correspond à votre recherche.</p>
            <?php if ($type != '' || $ville != '' || $search != ''): ?>
            <a href="accueilClient.php" style="background: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;">
                <i class="fa-solid fa-times"></i> Effacer tous les filtres
            </a>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

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

    <!-- JavaScript -->
    <script>
        function toggleFavori(idMaison, btn) {
            event.stopPropagation();
            
            const icon = btn.querySelector('i');
            
            icon.style.transform = 'scale(1.2)';
            setTimeout(() => icon.style.transform = 'scale(1)', 200);
            
            fetch('../PHP/toggle_favoris.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_maison=' + idMaison
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'added') {
                    icon.style.color = '#dc3545';
                    btn.classList.add('active');
                    showMessage('Ajouté aux favoris', 'success');
                } 
                else if (data.status === 'removed') {
                    icon.style.color = '#cbd5e1';
                    btn.classList.remove('active');
                    showMessage('Retiré des favoris', 'info');
                }
                else {
                    showMessage('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur de connexion', 'error');
            });
        }

        function showMessage(text, type) {
            const toast = document.createElement('div');
            toast.className = 'toast-message ' + (type === 'error' ? 'error' : type === 'info' ? 'info' : '');
            toast.textContent = text;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }

        document.addEventListener("DOMContentLoaded", function() {
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
            
            const mobileToggle = document.getElementById('mobileMenuBtn');
            const navLinks = document.getElementById('navLinks');
            
            if (mobileToggle && navLinks) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navLinks.classList.toggle('active');
                    
                    const icon = this.querySelector('i');
                    if (navLinks.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                        document.body.style.overflow = 'hidden';
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        document.body.style.overflow = '';
                    }
                });
                
                document.addEventListener('click', function(event) {
                    if (!mobileToggle.contains(event.target) && !navLinks.contains(event.target)) {
                        navLinks.classList.remove('active');
                        const icon = mobileToggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>
</body>
</html>