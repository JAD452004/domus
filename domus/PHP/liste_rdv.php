<?php
session_start();
require_once "../PHP/data.php"; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_pro = $_SESSION['user_id'];

// Requête préparée pour éviter les injections SQL
$query_rdv = "SELECT r.*, m.titre, m.image, c.nom_complet, c.email as email_client, c.telephone as tel_client 
              FROM rendez_vous r
              INNER JOIN maison m ON r.id_maison = m.id_maison
              INNER JOIN client c ON r.id_client = c.id_cli
              WHERE m.id_pro = ? 
              ORDER BY 
                CASE 
                    WHEN r.statut = 'en_attente' THEN 1
                    WHEN r.statut = 'confirme' THEN 2
                    ELSE 3
                END,
                r.date_rdv ASC";

$stmt = $db->prepare($query_rdv);
if (!$stmt) {
    die("Erreur de préparation de la requête: " . $db->error);
}
$stmt->bind_param("i", $id_pro);
$stmt->execute();
$liste_rdv = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Rendez-vous - DOMUS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
        }

        :root { 
            --primary: #0f172a;
            --secondary: #2563eb;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --nav-height: 80px;
            
            --gray-300: #e2e8f0;
            --gray-200: #f1f5f9;
            --gray-100: #f8fafc;
            --primary-dark: #1d4ed8;
            --primary-light: #60a5fa;
        }

        body {
            background: var(--bg-light);
            color: var(--text-main);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar avec dégradé bleu */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--secondary), #1d4ed8);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: var(--shadow);
        }

        .logo {
            padding: 0 20px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo img {
            width: 140px;
            height: auto;
            filter: brightness(0) invert(1);
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-top: 10px;
        }

        .logo-text span {
            color: rgba(255,255,255,0.9);
        }

        .nav-menu {
            list-style: none;
            padding: 0 15px;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: var(--shadow);
            border-left: 3px solid white;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main content */
        .main-content { 
            flex: 1;
            margin-left: 280px;
            padding: 30px; 
            background: var(--bg-light); 
            min-height: 100vh; 
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid var(--gray-200);
        }

        .page-header h1 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .page-header p {
            color: var(--text-muted);
        }

        /* Messages d'alerte */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .rdv-card { 
            background: white; 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 15px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 5px solid var(--secondary);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid var(--gray-200);
        }

        .rdv-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            border-color: var(--secondary);
        }

        .img-box { 
            width: 110px; 
            height: 80px; 
            overflow: hidden; 
            border-radius: 8px; 
            flex-shrink: 0; 
        }

        .img-box img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .info { 
            flex: 1; 
            margin-left: 20px; 
        }

        .status { 
            font-size: 0.75rem; 
            font-weight: bold; 
            text-transform: uppercase; 
            padding: 4px 10px; 
            border-radius: 20px; 
            display: inline-block; 
            margin-bottom: 5px; 
        }

        .status-confirme, .status-confirmé, .status-confirmee, .status-confirmée { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }

        .status-annule, .status-annulé, .status-refuse, .status-refusé { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }

        .status-en_attente { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba; 
        }

        .client-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin: 5px 0 3px;
        }

        .property-title {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .btn-actions { 
            display: flex; 
            gap: 8px; 
            margin-top: 10px; 
            align-items: center; 
            flex-wrap: wrap;
        }

        .btn { 
            border: none; 
            padding: 8px 16px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 0.85rem; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 5px; 
            text-decoration: none; 
            transition: all 0.2s;
        }

        .btn-accept { 
            background: linear-gradient(135deg, var(--secondary), #1d4ed8); 
            color: white; 
        }

        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-refuse { 
            background: #6c757d; 
            color: white; 
        }

        .btn-refuse:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-delete { 
            background: var(--danger); 
            color: white; 
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-chat { 
            background: var(--gray-100); 
            color: var(--secondary); 
            border: 1px solid var(--secondary); 
        }

        .btn-chat:hover {
            background: linear-gradient(135deg, var(--secondary), #1d4ed8);
            color: white;
            transform: translateY(-2px);
        }

        .date-info { 
            text-align: right; 
            border-left: 1px solid var(--gray-200); 
            padding-left: 20px; 
            min-width: 120px;
        }

        .date-info strong {
            display: block;
            font-size: 1.1rem;
            color: var(--primary);
        }

        .date-info span {
            color: var(--secondary);
            font-weight: 600;
        }

        .empty-state {
            text-align: center; 
            background: white; 
            padding: 60px 30px; 
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
            .rdv-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .date-info {
                border-left: none;
                border-top: 1px solid var(--gray-200);
                padding-left: 0;
                padding-top: 15px;
                margin-top: 15px;
                width: 100%;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar avec dégradé bleu -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS">
                <div class="logo-text">DOM<span>US</span></div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="../Accueil/accueilPropriete.php" class="nav-link"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
                <li class="nav-item"><a href="../Accueil/propriete.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
                <li class="nav-item"><a href="liste_rdv.php" class="nav-link active"><i class="fa-solid fa-calendar-check"></i> <span>Rendez-vous</span></a></li>
                <li class="nav-item"><a href="../PHP/ajouter_propriete.php" class="nav-link"><i class="fa-solid fa-plus-circle"></i> <span>Ajouter</span></a></li>
                <li class="nav-item"><a href="../CONNECTION/deconnexion.php" class="nav-link" style="color:#ef4444;"><i class="fa-solid fa-power-off"></i> <span>Quitter</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Gestion des Visites</h1>
                <p>Gérez vos demandes et discutez avec vos futurs acheteurs.</p>
            </div>

            <!-- Messages d'alerte -->
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_GET['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($_GET['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($liste_rdv && $liste_rdv->num_rows > 0): ?>
                <?php while($rdv = $liste_rdv->fetch_assoc()): 
                    $statut = strtolower(trim($rdv['statut'] ?? 'en_attente'));
                    
                    // Gestion de l'image
                    $image_path = '../DOMUS IMAGE/default-property.jpg';
                    if (!empty($rdv['image'])) {
                        if (strpos($rdv['image'], 'http') === 0) {
                            $image_path = $rdv['image'];
                        } else {
                            $clean_path = str_replace(['../', './'], '', $rdv['image']);
                            $image_path = '../' . $clean_path;
                        }
                    }
                    
                    // Déterminer la classe du statut
                    $statut_class = 'en_attente';
                    $statut_text = 'En attente';
                    
                    if (in_array($statut, ['confirme', 'confirmé', 'confirmee', 'confirmée'])) {
                        $statut_class = 'confirme';
                        $statut_text = 'Confirmé';
                    } elseif (in_array($statut, ['annule', 'annulé', 'refuse', 'refusé'])) {
                        $statut_class = 'annule';
                        $statut_text = 'Annulé';
                    }
                ?>
                    <div class="rdv-card">
                        <div style="display: flex; align-items: center; flex: 1;">
                            <div class="img-box">
                                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                     alt="Propriété"
                                     onerror="this.src='../DOMUS IMAGE/default-property.jpg';">
                            </div>
                            <div class="info">
                                <span class="status status-<?php echo $statut_class; ?>">
                                    <?php echo $statut_text; ?>
                                </span>
                                <h3 class="client-name">
                                    <i class="fa-solid fa-user"></i> 
                                    <?php echo htmlspecialchars($rdv['nom_complet'] ?? 'Client'); ?>
                                </h3>
                                <p class="property-title">
                                    <i class="fa-solid fa-house"></i> 
                                    <?php echo htmlspecialchars($rdv['titre'] ?? 'Propriété sans titre'); ?>
                                </p>
                                
                                <div class="btn-actions">
                                    <?php if($statut_class === 'en_attente'): ?>
                                        <button onclick="gererRDV(<?php echo $rdv['id_rdv']; ?>, 'accepte')" class="btn btn-accept">
                                            <i class="fa-solid fa-check"></i> Accepter
                                        </button>
                                        <button onclick="gererRDV(<?php echo $rdv['id_rdv']; ?>, 'refuse')" class="btn btn-refuse">
                                            <i class="fa-solid fa-xmark"></i> Refuser
                                        </button>
                                    <?php endif; ?>

                                    <?php if($statut_class === 'confirme'): ?>
                                        <!-- LIEN VERS DISCUSSION.PHP -->
                                        <a href="discussion.php?rdv=<?php echo $rdv['id_rdv']; ?>" class="btn btn-chat">
                                            <i class="fa-solid fa-comments"></i> Discuter
                                        </a>
                                    <?php endif; ?>

                                    <button onclick="supprimerRDV(<?php echo $rdv['id_rdv']; ?>)" class="btn btn-delete">
                                        <i class="fa-solid fa-trash"></i> Supprimer
                                    </button>
                                </div>

                                <!-- Contact info (optionnel) -->
                                <?php if (!empty($rdv['email_client']) || !empty($rdv['tel_client'])): ?>
                                <div style="margin-top: 10px; font-size: 0.8rem; color: var(--text-muted);">
                                    <?php if (!empty($rdv['email_client'])): ?>
                                        <span><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($rdv['email_client']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($rdv['tel_client'])): ?>
                                        <span style="margin-left: 10px;"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($rdv['tel_client']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="date-info">
                            <strong><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></strong>
                            <span>à <?php echo htmlspecialchars($rdv['heure_rdv']); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <h3>Aucune demande de visite</h3>
                    <p>Vous n'avez pas encore de rendez-vous programmés.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function gererRDV(id, statut) {
        let message = statut === 'accepte' 
            ? 'Accepter cette demande de visite ?' 
            : 'Refuser cette demande de visite ?';
            
        if(confirm(message)) {
            window.location.href = "action_rdv.php?action=status&id=" + id + "&statut=" + statut;
        }
    }
    
    function supprimerRDV(id) {
        if(confirm("Supprimer définitivement cette demande ?")) {
            window.location.href = "action_rdv.php?action=delete&id=" + id;
        }
    }
    </script>
</body>
</html>