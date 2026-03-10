<?php
session_start();
require_once "data.php"; 

// Vérifie si l'utilisateur est connecté en tant que client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['user_id'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// 1. Historique des vues
if (isset($_SESSION['user_id']) && isset($_GET['id'])) {
    $id_user = $_SESSION['user_id'];
    $id_maison = (int)$_GET['id'];
    $sql_vue = "INSERT INTO vues_recentes (id_user, id_maison) VALUES (?, ?)";
    $stmt_vue = $db->prepare($sql_vue);
    $stmt_vue->bind_param("ii", $id_user, $id_maison);
    $stmt_vue->execute();
}

// 2. Infos de la maison
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = "SELECT * FROM maison WHERE id_maison = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$bien = $stmt->get_result()->fetch_assoc();

if (!$bien) { header("Location: accueilClient.php"); exit(); }

// 3. Galerie d'images
$query_galerie = "SELECT chemin_image FROM images_maison WHERE id_maison = ?";
$stmt_gal = $db->prepare($query_galerie);
$stmt_gal->bind_param("i", $id);
$stmt_gal->execute();
$res_gal = $stmt_gal->get_result();

$galerie = [];
$galerie[] = $bien['image']; 
while ($row = $res_gal->fetch_assoc()) {
    $galerie[] = $row['chemin_image'];
}

$nom_user = isset($_SESSION['nom']) ? $_SESSION['nom'] : "Utilisateur";
$id_client = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($bien['titre']); ?> - DOMUS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    
    <!-- Google Fonts: Poppins (même que accueilClient) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">

    <style>
        /* Styles supplémentaires spécifiques à details.php */
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --accent: #e11d48;
            --accent-dark: #be123c;
            --bg-body: #f3f4f6;
            --bg-card: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.5;
            padding-bottom: 40px;
        }

        img { max-width: 100%; height: auto; display: block; }
        a { text-decoration: none; color: inherit; }

        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 16px;
        }

        /* Alert message */
        .alert {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid #a7f3d0;
        }

        /* Grid layout */
        .layout-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 900px) {
            .layout-grid {
                grid-template-columns: 1.4fr 1fr;
                gap: 40px;
            }
            .container { margin-top: 40px; }
        }

        /* Gallery */
        .gallery-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .main-image-container {
            width: 100%;
            aspect-ratio: 16/10;
            background: #e5e7eb;
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.3s ease;
        }

        .thumbs-wrapper {
            padding: 12px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            background: #fff;
            border-top: 1px solid var(--border);
        }

        .thumb {
            aspect-ratio: 4/3;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .thumb:hover { opacity: 1; }
        .thumb.active {
            opacity: 1;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37,99,235,0.2);
        }

        /* Info card */
        .info-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 16px;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--primary); }

        .title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .location {
            color: var(--text-muted);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
        }

        .price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 4px;
        }
        .price small {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .specs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .spec-item {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #f3f4f6;
        }
        .spec-item i { color: var(--primary); font-size: 1.1rem; }
        .spec-text { font-weight: 600; font-size: 0.95rem; }

        .desc-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }
        .desc-text {
            color: #4b5563;
            font-size: 0.95rem;
            margin-bottom: 30px;
            white-space: pre-line;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-align: center;
        }
        .btn:active { transform: scale(0.98); }

        .btn-primary {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 6px rgba(225, 29, 72, 0.25);
        }
        .btn-primary:hover { background: var(--accent-dark); }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        .btn-secondary:hover { background: #eff6ff; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }

        .modal {
            background: white;
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-title { font-size: 1.25rem; font-weight: 700; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #9ca3af; }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
        }
        .form-input:focus { outline: 2px solid var(--primary); border-color: transparent; }

        @media (max-width: 600px) {
            .thumbs-wrapper {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
            .title { font-size: 1.3rem; }
            .price { font-size: 1.75rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR (exactement comme accueilClient.php avec l'utilisateur connecté) -->
    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS Logo">
            <div class="logo-text">DOM<span>US</span></div>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="../Accueil/accueilClient.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="../Accueil/client.php"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <li><a href="../Accueil/contact.php"><i class="fa-solid fa-envelope"></i> <span>Contact</span></a></li>
        </ul>
        <div class="user-area">
            <div class="user-info">
                <?php include __DIR__ . '/../Accueil/_user_avatar.php'; ?>
            </div>
            <a href="../PHP/logout.php" class="logout-btn" title="Déconnexion">
                <i class="fa-solid fa-power-off"></i>
            </a>
            <div class="mobile-toggle" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert">
                <i class="fa-solid fa-check-circle"></i> Demande envoyée avec succès !
            </div>
        <?php endif; ?>

        <div class="layout-grid">
            
            <!-- GAUCHE : GALERIE -->
            <div class="gallery-card">
                <div class="main-image-container">
                    <img id="main-view" src="<?php echo htmlspecialchars($bien['image']); ?>" class="main-image" alt="Photo principale">
                </div>
                <div class="thumbs-wrapper">
                    <?php foreach ($galerie as $index => $img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" 
                             class="thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                             onclick="changeImage('<?php echo htmlspecialchars($img); ?>', this)"
                             alt="Miniature">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- DROITE : INFOS -->
            <div class="info-card">
                <a href="../Accueil/accueilClient.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </a>

                <h1 class="title"><?php echo htmlspecialchars($bien['titre']); ?></h1>
                <div class="location">
                    <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($bien['ville']); ?>
                </div>
                
                <div class="price">
                    <?php echo number_format($bien['prix'], 0, ',', ' '); ?> <small>XOF</small>
                </div>

                <div class="specs">
                    <div class="spec-item">
                        <i class="fa-solid fa-bed"></i>
                        <span class="spec-text"><?php echo $bien['chambres']; ?> Chambres</span>
                    </div>
                    <div class="spec-item">
                        <i class="fa-solid fa-shower"></i>
                        <span class="spec-text"><?php echo $bien['salles_bain']; ?> SDB</span>
                    </div>
                    <div class="spec-item">
                        <i class="fa-solid fa-ruler-combined"></i>
                        <span class="spec-text"><?php echo $bien['surface']; ?> m²</span>
                    </div>
                    <div class="spec-item">
                        <i class="fa-solid fa-house"></i>
                        <span class="spec-text"><?php echo htmlspecialchars($bien['type_bien']); ?></span>
                    </div>
                </div>

                <div class="desc-title">Description</div>
                <div class="desc-text"><?php echo nl2br(htmlspecialchars($bien['description'])); ?></div>

                <div class="btn-group">
                    <button onclick="document.getElementById('modal').classList.add('active')" class="btn btn-primary">
                        <i class="fa-solid fa-calendar-check"></i> JE LA VEUX !
                    </button>
                    <a href="../Accueil/contact.php?bien=<?php echo urlencode($bien['titre']); ?>" class="btn btn-secondary">
                         CONTACTER L'AGENT
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL RDV -->
    <div id="modal" class="modal-overlay" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Planifier une visite</div>
                <button class="close-btn" onclick="document.getElementById('modal').classList.remove('active')">&times;</button>
            </div>
            <form action="traiter_rdv.php" method="POST">
                <input type="hidden" name="id_maison" value="<?php echo $id; ?>">
                
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="date_rdv" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Heure</label>
                    <input type="time" name="heure_rdv" required class="form-input">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Confirmer</button>
            </form>
        </div>
    </div>

    <script>
        function changeImage(url, el) {
            document.getElementById('main-view').style.opacity = '0.7';
            setTimeout(() => {
                document.getElementById('main-view').src = url;
                document.getElementById('main-view').style.opacity = '1';
            }, 150);

            document.querySelectorAll('.thumb').forEach(img => img.classList.remove('active'));
            el.classList.add('active');
        }

        // Menu mobile (copié depuis accueilClient.php)
        document.addEventListener("DOMContentLoaded", function() {
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