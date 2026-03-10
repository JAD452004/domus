<?php
session_start();
require_once "domus/PHP/data.php";

// Nettoyage des entrées
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$ville = isset($_GET['ville']) ? trim($_GET['ville']) : '';

// Construction sécurisée de la requête
$sql = "SELECT * FROM maison WHERE 1=1";

if (!empty($search)) {
    $search_esc = $db->real_escape_string($search);
    $sql .= " AND (titre LIKE '%$search_esc%' OR description LIKE '%$search_esc%')";
}

if (!empty($type)) {
    $type_esc = $db->real_escape_string($type);
    $sql .= " AND type_bien = '$type_esc'";
}

if (!empty($ville)) {
    $ville_esc = $db->real_escape_string($ville);
    $sql .= " AND ville = '$ville_esc'";
}

$sql .= " ORDER BY id_maison DESC LIMIT 9";
$result = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>DOMUS - L'immobilier de luxe en Côte d'Ivoire</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="domus/DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="domus/STYLE/accueil.css">
    
    <!-- ===== AJOUT RESPONSIVE UNIQUEMENT ===== -->
    <style>
        /* CES STYLES NE S'APPLIQUENT QU'EN MOBILE/TABLETTE */
        
        /* Amélioration du menu mobile */
        @media screen and (max-width: 992px) {
            .mobile-toggle {
                display: block !important;
            }
            
            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 80%;
                max-width: 350px;
                height: calc(100vh - 80px);
                background: white;
                flex-direction: column;
                padding: 30px 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                transition: left 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
                display: flex !important;
            }
            
            .nav-links.active {
                left: 0;
            }
            
            .nav-links li {
                width: 100%;
                margin: 5px 0 !important;
            }
            
            .nav-links li a {
                width: 100%;
                padding: 12px 15px !important;
                display: flex !important;
                align-items: center;
                gap: 15px;
                border-radius: 8px;
            }
            
            .nav-links li a i {
                width: 25px;
                font-size: 1.2rem;
            }
            
            .nav-links li a span {
                display: inline-block !important;
            }
            
            .btn-primary span {
                display: none;
            }
            
            .btn-primary i {
                margin-right: 0;
            }
            
            .logo img {
                height: 50px;
            }
            
            .logo-text {
                font-size: 1.4rem;
            }
        }
        
        @media screen and (max-width: 480px) {
            .navbar {
                padding: 10px 15px !important;
            }
            
            .nav-links {
                top: 70px;
                width: 85%;
            }
            
            .logo img {
                height: 40px;
            }
            
            .logo-text {
                font-size: 1.2rem;
            }
        }
        
        /* Hero section responsive */
        @media screen and (max-width: 992px) {
            .hero {
                padding: 120px 20px 60px !important;
                background-attachment: scroll !important;
            }
            
            .hero h1 {
                font-size: 2.5rem !important;
            }
            
            .hero-subtitle {
                font-size: 1.1rem !important;
                padding: 0 15px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .hero h1 {
                font-size: 2rem !important;
            }
            
            .search-wrapper {
                padding: 20px !important;
            }
            
            .search-wrapper form {
                flex-direction: column !important;
                gap: 15px !important;
            }
            
            .form-group {
                width: 100% !important;
                min-width: 100% !important;
            }
            
            .search-input,
            .search-select {
                width: 100% !important;
                height: 50px !important;
                font-size: 16px !important;
            }
            
            .search-btn {
                width: 100% !important;
                height: 50px !important;
                font-size: 1rem !important;
            }
        }
        
        @media screen and (max-width: 480px) {
            .hero h1 {
                font-size: 1.5rem !important;
            }
            
            .hero-subtitle {
                font-size: 0.9rem !important;
            }
            
            .search-wrapper {
                padding: 15px !important;
            }
        }
        
        /* Grilles responsives */
        @media screen and (max-width: 992px) {
            .properties-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 20px;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 20px;
            }
            
            .cities-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 15px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .properties-grid {
                grid-template-columns: 1fr !important;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .features-grid {
                grid-template-columns: 1fr !important;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .cities-grid {
                grid-template-columns: 1fr !important;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .footer-content {
                grid-template-columns: 1fr !important;
                gap: 30px !important;
                margin-left: 0 !important;
                text-align: center;
            }
            
            .footer-col h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .footer-links {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .contact-item {
                justify-content: center;
            }
        }
        
        /* Cartes responsives */
        @media screen and (max-width: 768px) {
            .property-card {
                max-width: 100%;
            }
            
            .card-image {
                height: 200px !important;
            }
            
            .card-content {
                padding: 15px !important;
            }
            
            .card-price {
                font-size: 1.3rem !important;
            }
            
            .card-title {
                font-size: 1.1rem !important;
            }
            
            .card-features {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .feature {
                font-size: 0.85rem !important;
            }
        }
        
        @media screen and (max-width: 480px) {
            .card-image {
                height: 180px !important;
            }
            
            .card-features {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .feature {
                width: 100%;
            }
        }
        
        /* Villes responsives */
        @media screen and (max-width: 768px) {
            .city-card {
                height: 200px !important;
            }
            
            .city-name {
                font-size: 1.3rem !important;
            }
        }
        
        /* Témoignages responsifs */
        @media screen and (max-width: 768px) {
            .testimonial-card {
                padding: 25px !important;
                margin: 0 15px;
            }
            
            .testimonial-text {
                font-size: 1rem !important;
            }
            
            .testimonial-author {
                flex-direction: column !important;
                text-align: center;
            }
        }
        
        /* Sections responsives */
        @media screen and (max-width: 768px) {
            section {
                padding: 50px 0 !important;
            }
            
            .section-title {
                margin-bottom: 30px !important;
            }
            
            .section-title h2 {
                font-size: 1.8rem !important;
            }
            
            .section-title::after {
                width: 60px !important;
            }
        }
        
        @media screen and (max-width: 480px) {
            .section-title h2 {
                font-size: 1.5rem !important;
            }
        }
        
        /* Très petits écrans */
        @media screen and (max-width: 360px) {
            .hero h1 {
                font-size: 1.3rem !important;
            }
            
            .card-price {
                font-size: 1.1rem !important;
            }
        }
        
        /* Améliorations tactiles */
        @media (max-width: 992px) {
            .nav-links a,
            .btn,
            .search-btn,
            .property-card,
            .city-card,
            .feature-card {
                -webkit-tap-highlight-color: transparent;
                cursor: pointer;
            }
            
            .nav-links a:active,
            .btn:active,
            .search-btn:active {
                opacity: 0.7;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="domus/DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS Logo">
            <div class="logo-text">DOM<span>US</span></div>
        </div>
        
        <ul class="nav-links" id="navLinks">
            <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Accueil</span></a></li>
            <li><a href="domus/CONNECTION/connexionUser.php"><i class="fas fa-shopping-cart"></i> <span>Acheter</span></a></li>
            <li><a href="domus/CONNECTION/connexionUser.php"><i class="fas fa-key"></i> <span>Louer</span></a></li>
            <li><a href="domus/CONNECTION/connexionUser.php"><i class="fas fa-tag"></i> <span>Vendre</span></a></li>
            <li><a href="#contact"><i class="fas fa-phone-alt"></i> <span>Contact</span></a></li>
        </ul>

        <div class="user-area">
            <div class="nav-buttons">
                <a href="domus/CONNECTION/connexionUser.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </a>
            </div>
            
            <div class="mobile-toggle" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Trouvez la maison de vos rêves</h1>
            <p class="hero-subtitle">Achetez, louez ou vendez facilement partout en Côte d'Ivoire avec DOMUS.</p>
            
            <div class="search-wrapper">
                <form method="GET" action="">
                    <div class="form-group">
                        <i class="fa-solid fa-magnifying-glass input-icon"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="search-input" placeholder="Mots-clés, ville...">
                    </div>
                    
                    <div class="form-group">
                        <i class="fa-solid fa-house input-icon"></i>
                        <select name="type" class="search-select">
                            <option value="">Type de bien</option>
                            <option value="appartement" <?php if($type == 'appartement') echo 'selected'; ?>>Appartement</option>
                            <option value="maison" <?php if($type == 'maison') echo 'selected'; ?>>Maison</option>
                            <option value="villa" <?php if($type == 'villa') echo 'selected'; ?>>Villa</option>
                            <option value="terrain" <?php if($type == 'terrain') echo 'selected'; ?>>Terrain</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <i class="fa-solid fa-location-dot input-icon"></i>
                        <select name="ville" class="search-select">
                            <option value="">Toutes les villes</option>
                            <option value="Abidjan" <?php if($ville == 'Abidjan') echo 'selected'; ?>>Abidjan</option>
                            <option value="Yamoussoukro" <?php if($ville == 'Yamoussoukro') echo 'selected'; ?>>Yamoussoukro</option>
                            <option value="Bouaké" <?php if($ville == 'Bouaké') echo 'selected'; ?>>Bouaké</option>
                            <option value="San Pedro" <?php if($ville == 'San Pedro') echo 'selected'; ?>>San Pedro</option>
                            <option value="Daloa" <?php if($ville == 'Daloa') echo 'selected'; ?>>Daloa</option>
                            <option value="Korhogo" <?php if($ville == 'Korhogo') echo 'selected'; ?>>Korhogo</option>
                            <option value="Man" <?php if($ville == 'Man') echo 'selected'; ?>>Man</option>
                            <option value="Gagnoa" <?php if($ville == 'Gagnoa') echo 'selected'; ?>>Gagnoa</option>
                            <option value="Divo" <?php if($ville == 'Divo') echo 'selected'; ?>>Divo</option>
                            <option value="Anyama" <?php if($ville == 'Anyama') echo 'selected'; ?>>Anyama</option>
                            <option value="Abengourou" <?php if($ville == 'Abengourou') echo 'selected'; ?>>Abengourou</option>
                            <option value="Agboville" <?php if($ville == 'Agboville') echo 'selected'; ?>>Agboville</option>
                            <option value="Grand-Bassam" <?php if($ville == 'Grand-Bassam') echo 'selected'; ?>>Grand-Bassam</option>
                            <option value="Bingerville" <?php if($ville == 'Bingerville') echo 'selected'; ?>>Bingerville</option>
                            <option value="Dabou" <?php if($ville == 'Dabou') echo 'selected'; ?>>Dabou</option>
                            <option value="Assinie" <?php if($ville == 'Assinie') echo 'selected'; ?>>Assinie</option>
                            <option value="Jacqueville" <?php if($ville == 'Jacqueville') echo 'selected'; ?>>Jacqueville</option>
                            <option value="Soubré" <?php if($ville == 'Soubré') echo 'selected'; ?>>Soubré</option>
                            <option value="Odienné" <?php if($ville == 'Odienné') echo 'selected'; ?>>Odienné</option>
                            <option value="Bondoukou" <?php if($ville == 'Bondoukou') echo 'selected'; ?>>Bondoukou</option>
                            <option value="Séguéla" <?php if($ville == 'Séguéla') echo 'selected'; ?>>Séguéla</option>
                            <option value="Dimbokro" <?php if($ville == 'Dimbokro') echo 'selected'; ?>>Dimbokro</option>
                            <option value="Ferkessédougou" <?php if($ville == 'Ferkessédougou') echo 'selected'; ?>>Ferkessédougou</option>
                            <option value="Bouna" <?php if($ville == 'Bouna') echo 'selected'; ?>>Bouna</option>
                            <option value="Touba" <?php if($ville == 'Touba') echo 'selected'; ?>>Touba</option>
                            <option value="Boundiali" <?php if($ville == 'Boundiali') echo 'selected'; ?>>Boundiali</option>
                            <option value="Tengrela" <?php if($ville == 'Tengrela') echo 'selected'; ?>>Tengrela</option>
                            <option value="Tiassalé" <?php if($ville == 'Tiassalé') echo 'selected'; ?>>Tiassalé</option>
                            <option value="Oumé" <?php if($ville == 'Oumé') echo 'selected'; ?>>Oumé</option>
                            <option value="Lakota" <?php if($ville == 'Lakota') echo 'selected'; ?>>Lakota</option>
                            <option value="Guiglo" <?php if($ville == 'Guiglo') echo 'selected'; ?>>Guiglo</option>
                            <option value="Toulepleu" <?php if($ville == 'Toulepleu') echo 'selected'; ?>>Toulepleu</option>
                            <option value="Bloléquin" <?php if($ville == 'Bloléquin') echo 'selected'; ?>>Bloléquin</option>
                            <option value="Duékoué" <?php if($ville == 'Duékoué') echo 'selected'; ?>>Duékoué</option>
                            <option value="Bangolo" <?php if($ville == 'Bangolo') echo 'selected'; ?>>Bangolo</option>
                            <option value="Zouan-Hounien" <?php if($ville == 'Zouan-Hounien') echo 'selected'; ?>>Zouan-Hounien</option>
                            <option value="Danané" <?php if($ville == 'Danané') echo 'selected'; ?>>Danané</option>
                            <option value="Biankouma" <?php if($ville == 'Biankouma') echo 'selected'; ?>>Biankouma</option>
                            <option value="Sipilou" <?php if($ville == 'Sipilou') echo 'selected'; ?>>Sipilou</option>
                            <option value="Béoumi" <?php if($ville == 'Béoumi') echo 'selected'; ?>>Béoumi</option>
                            <option value="Bocanda" <?php if($ville == 'Bocanda') echo 'selected'; ?>>Bocanda</option>
                            <option value="Bongouanou" <?php if($ville == 'Bongouanou') echo 'selected'; ?>>Bongouanou</option>
                            <option value="Daoukro" <?php if($ville == 'Daoukro') echo 'selected'; ?>>Daoukro</option>
                            <option value="M'Bahiakro" <?php if($ville == 'M\'Bahiakro') echo 'selected'; ?>>M'Bahiakro</option>
                            <option value="Prikro" <?php if($ville == 'Prikro') echo 'selected'; ?>>Prikro</option>
                            <option value="Arrah" <?php if($ville == 'Arrah') echo 'selected'; ?>>Arrah</option>
                            <option value="Toumodi" <?php if($ville == 'Toumodi') echo 'selected'; ?>>Toumodi</option>
                            <option value="Sakassou" <?php if($ville == 'Sakassou') echo 'selected'; ?>>Sakassou</option>
                            <option value="Bouaflé" <?php if($ville == 'Bouaflé') echo 'selected'; ?>>Bouaflé</option>
                            <option value="Sinfra" <?php if($ville == 'Sinfra') echo 'selected'; ?>>Sinfra</option>
                            <option value="Zuénoula" <?php if($ville == 'Zuénoula') echo 'selected'; ?>>Zuénoula</option>
                            <option value="Koun-Fao" <?php if($ville == 'Koun-Fao') echo 'selected'; ?>>Koun-Fao</option>
                            <option value="Tanda" <?php if($ville == 'Tanda') echo 'selected'; ?>>Tanda</option>
                            <option value="Adzopé" <?php if($ville == 'Adzopé') echo 'selected'; ?>>Adzopé</option>
                            <option value="Affery" <?php if($ville == 'Affery') echo 'selected'; ?>>Affery</option>
                            <option value="Akoupé" <?php if($ville == 'Akoupé') echo 'selected'; ?>>Akoupé</option>
                            <option value="Alépé" <?php if($ville == 'Alépé') echo 'selected'; ?>>Alépé</option>
                            <option value="Yakassé-Attobrou" <?php if($ville == 'Yakassé-Attobrou') echo 'selected'; ?>>Yakassé-Attobrou</option>
                            <option value="Bonoua" <?php if($ville == 'Bonoua') echo 'selected'; ?>>Bonoua</option>
                            <option value="Kani" <?php if($ville == 'Kani') echo 'selected'; ?>>Kani</option>
                            <option value="Mankono" <?php if($ville == 'Mankono') echo 'selected'; ?>>Mankono</option>
                            <option value="Vavoua" <?php if($ville == 'Vavoua') echo 'selected'; ?>>Vavoua</option>
                            <option value="Issia" <?php if($ville == 'Issia') echo 'selected'; ?>>Issia</option>
                            <option value="Saïoua" <?php if($ville == 'Saïoua') echo 'selected'; ?>>Saïoua</option>
                            <option value="Buyo" <?php if($ville == 'Buyo') echo 'selected'; ?>>Buyo</option>
                            <option value="Méagui" <?php if($ville == 'Méagui') echo 'selected'; ?>>Méagui</option>
                            <option value="Tabou" <?php if($ville == 'Tabou') echo 'selected'; ?>>Tabou</option>
                            <option value="Grabo" <?php if($ville == 'Grabo') echo 'selected'; ?>>Grabo</option>
                            <option value="Bérébi" <?php if($ville == 'Bérébi') echo 'selected'; ?>>Bérébi</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="properties-section">
        <div class="container">
            <div class="section-title">
                <h2>Propriétés en Vedette</h2>
                <p>Découvrez nos meilleures propriétés sélectionnées pour vous</p>
            </div>
            
            <div class="properties-grid">
                <div class="property-card">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg" alt="Villa Moderne">
                        <span class="card-badge">Vente</span>
                    </div>
                    <div class="card-content">
                        <div class="card-price">250 000 000 XOF</div>
                        <h3 class="card-title">Villa Moderne Cocody</h3>
                        <div class="card-location">
                            <i class="fas fa-map-marker-alt"></i> Abidjan, Cocody
                        </div>
                        <p class="card-description">Magnifique villa moderne avec piscine et jardin, située dans un quartier résidentiel calme.</p>
                        <div class="card-features">
                            <div class="feature">
                                <i class="fas fa-bed"></i> 4 Chambres
                            </div>
                            <div class="feature">
                                <i class="fas fa-bath"></i> 3 Salles de bain
                            </div>
                            <div class="feature">
                                <i class="fas fa-expand-arrows-alt"></i> 350 m²
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="property-card">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg" alt="Villa Piscine">
                        <span class="card-badge">Luxe</span>
                    </div>
                    <div class="card-content">
                        <div class="card-price">180 000 000 XOF</div>
                        <h3 class="card-title">Villa Piscine Assinie</h3>
                        <div class="card-location">
                            <i class="fas fa-map-marker-alt"></i> Assinie
                        </div>
                        <p class="card-description">Villa de luxe avec accès direct à la plage, piscine privée et vue panoramique sur l'océan.</p>
                        <div class="card-features">
                            <div class="feature">
                                <i class="fas fa-bed"></i> 5 Chambres
                            </div>
                            <div class="feature">
                                <i class="fas fa-swimming-pool"></i> Piscine
                            </div>
                            <div class="feature">
                                <i class="fas fa-car"></i> 3 Places
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="property-card">
                    <div class="card-image">
                        <img src="https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg" alt="Appartement">
                        <span class="card-badge">Location</span>
                    </div>
                    <div class="card-content">
                        <div class="card-price">450 000 XOF/mois</div>
                        <h3 class="card-title">Appartement Cosy San Pedro</h3>
                        <div class="card-location">
                            <i class="fas fa-map-marker-alt"></i> San Pedro
                        </div>
                        <p class="card-description">Appartement moderne entièrement meublé, idéal pour les jeunes professionnels.</p>
                        <div class="card-features">
                            <div class="feature">
                                <i class="fas fa-bed"></i> 2 Chambres
                            </div>
                            <div class="feature">
                                <i class="fas fa-wifi"></i> Fibre
                            </div>
                            <div class="feature">
                                <i class="fas fa-expand-arrows-alt"></i> 85 m²
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($result && $result->num_rows > 0): ?>
    <section class="properties-section">
        <div class="container">
            <div class="section-title">
                <h2><?php echo ($search || $type || $ville) ? "Résultats de recherche" : "Nouvelles annonces"; ?></h2>
                <p><?php echo $result->num_rows; ?> propriété(s) trouvée(s)</p>
            </div>
            
            <div class="properties-grid">
                <?php while ($m = $result->fetch_assoc()): 
                    // Correction du chemin d'image
                    $imagePath = $m['image'];
                    
                    // Nettoyer le chemin pour l'affichage
                    if (empty($imagePath)) {
                        // Image par défaut
                        $imagePath = 'domus/DOMUS IMAGE/default-property.jpg';
                    } else {
                        // Nettoyer les chemins avec ..
                        $imagePath = str_replace(['../', './'], '', $imagePath);
                        
                        // S'assurer que le chemin commence par domus/
                        if (strpos($imagePath, 'domus/') !== 0) {
                            $imagePath = 'domus/' . $imagePath;
                        }
                    }
                ?>
                <div class="property-card" onclick="window.location.href='domus/CONNECTION/connexionUser.php'">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                             alt="<?php echo htmlspecialchars($m['titre']); ?>"
                             onerror="this.src='domus/DOMUS IMAGE/default-property.jpg';">
                        <span class="card-badge"><?php echo htmlspecialchars($m['type_bien']); ?></span>
                    </div>
                    <div class="card-content">
                        <div class="card-price"><?php echo number_format($m['prix'], 0, ',', ' '); ?> XOF</div>
                        <h3 class="card-title"><?php echo htmlspecialchars($m['titre']); ?></h3>
                        <div class="card-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($m['ville']); ?>
                        </div>
                        <div class="card-features">
                            <div class="feature"><i class="fas fa-bed"></i> <?php echo $m['chambres']; ?> Ch.</div>
                            <div class="feature"><i class="fas fa-bath"></i> <?php echo $m['salles_bain']; ?> Sdb.</div>
                            <div class="feature"><i class="fas fa-expand-arrows-alt"></i> <?php echo $m['surface']; ?> m²</div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php else: ?>
        <?php if ($search || $type || $ville): ?>
        <section class="properties-section">
            <div class="container text-center">
                <i class="fas fa-search" style="font-size: 4rem; color: var(--gray); margin-bottom: 20px;"></i>
                <h3 style="color: var(--dark); margin-bottom: 10px;">Aucune propriété ne correspond à votre recherche</h3>
                <p style="color: var(--gray);">Essayez de modifier vos critères de recherche</p>
            </div>
        </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="features-section">
        <div class="container">
            <div class="section-title">
                <h2>Pourquoi Choisir DOMUS ?</h2>
                <p>Nous rendons l'immobilier simple, rapide et sécurisé</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Sécurité Totale</h3>
                    <p>Toutes nos transactions sont sécurisées et vérifiées pour votre tranquillité d'esprit.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Recherche Intelligente</h3>
                    <p>Trouvez facilement la propriété parfaite avec nos filtres avancés et personnalisés.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Support 24/7</h3>
                    <p>Notre équipe est disponible pour vous accompagner à chaque étape de votre projet.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cities-section" id="cities">
        <div class="container">
            <div class="section-title">
                <h2>Nos Villes Principales</h2>
                <p>Découvrez nos meilleures offres par ville</p>
            </div>
            
            <div class="cities-grid">
                <div class="city-card">
                    <img src="https://media.gettyimages.com/id/113745932/fr/photo/aerial-view-of-abidjan-le-plateau.jpg?s=612x612&w=gi&k=20&c=NVL2h_Gl0LbKN5_XDV3at_HibAtHjYbUaBbuxtP8Drw=" alt="Abidjan">
                    <div class="city-overlay">
                        <div class="city-name">Abidjan</div>
                        <div class="city-count">150+ propriétés</div>
                    </div>
                </div>
                
                <div class="city-card">
                    <img src="https://media.routard.com/image/57/7/pt103928.1312577.w1000.jpg" alt="Yamoussoukro">
                    <div class="city-overlay">
                        <div class="city-name">Yamoussoukro</div>
                        <div class="city-count">80+ propriétés</div>
                    </div>
                </div>
                
                <div class="city-card">
                    <img src="https://s.france24.com/media/display/92b1e836-a0d8-11ea-9555-005056a98db9/w:1280/p:4x3/XXBT%20BIL%20BOUAKE%20PUSH%20PICTURE%20%280-00-00-00%29.jpg" alt="Bouaké">
                    <div class="city-overlay">
                        <div class="city-name">Bouaké</div>
                        <div class="city-count">45+ propriétés</div>
                    </div>
                </div>
                
                <div class="city-card">
                    <img src="https://media-files.abidjan.net/photo/san-pedro-une-ville-qui-se-transforme_lz8sgywhd6.jpg" alt="San Pedro">
                    <div class="city-overlay">
                        <div class="city-name">San Pedro</div>
                        <div class="city-count">60+ propriétés</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="container">
            <div class="section-title">
                <h2>Témoignages</h2>
                <p>Ce que nos clients disent de nous</p>
            </div>
            
            <div class="testimonials-slider">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "DOMUS m'a permis de trouver la maison parfaite en un temps record. Leur équipe est professionnelle et très réactive."
                    </div>
                    <div class="testimonial-author">
                        <img src="domus/DOMUS IMAGE/WIN_20251212_12_20_50_Pro.jpg" alt="Client" class="author-img">
                        <div class="author-info">
                            <h4>Kouassi Carry</h4>
                            <p>Entrepreneur à Abidjan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>DOMUS</h3>
                    <p style="margin-bottom: 20px; color: #cbd5e1;">
                        Votre partenaire de confiance pour l'immobilier en Côte d'Ivoire. Nous rendons l'achat, la vente et la location simples et transparents.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Accueil</a></li>
                        <li><a href="domus/CONNECTION/connexionUser.php"><i class="fas fa-chevron-right"></i> Propriétés à vendre</a></li>
                        <li><a href="domus/CONNECTION/connexionUser.php"><i class="fas fa-chevron-right"></i> Propriétés à louer</a></li>
                        <li><a href="domus/CONNECTION/connexionUser.php"><i class="fas fa-chevron-right"></i> Vendre un bien</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Contact</h3>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>contact@domus-ci.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+225 01 23 45 67 89</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Rue de IFSM Adjamé, Abidjan, Côte d'Ivoire</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 DOMUS Real Estate. Tous droits réservés.</p>
                <p style="margin-top: 10px;">Design & Développement par l'équipe DOMUS</p>
            </div>
        </div>
    </footer>

    <script src="domus/javascript/accueil.js"></script>
    
    <script>
    // Gestion du menu mobile améliorée
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');
        
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                navLinks.classList.toggle('active');
                
                // Empêcher le scroll du body quand le menu est ouvert
                if (navLinks.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
            
            // Fermer le menu quand on clique sur un lien
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
            
            // Fermer le menu quand on clique en dehors
            document.addEventListener('click', function(event) {
                if (!navLinks.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Fermer le menu quand on redimensionne l'écran au-dessus de 992px
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    navLinks.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }
    });
    </script>
</body>
</html>