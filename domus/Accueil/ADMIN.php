<?php
session_start();
require_once "../PHP/data.php";

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// --- RÉCUPÉRATION DES STATISTIQUES ---
$stats = [
    'biens' => $db->query("SELECT COUNT(*) as total FROM maison")->fetch_assoc()['total'],
    'visites' => $db->query("SELECT COUNT(*) as total FROM rendez_vous")->fetch_assoc()['total'],
    'clients' => $db->query("SELECT COUNT(*) as total FROM client")->fetch_assoc()['total'],
    'vendeurs' => $db->query("SELECT COUNT(*) as total FROM proprietaire")->fetch_assoc()['total'],
    'messages' => $db->query("SELECT COUNT(*) as total FROM contact")->fetch_assoc()['total']
];

// --- GESTION DES FILTRES ---
$filtres = [
    'type' => isset($_GET['type']) ? $_GET['type'] : '',
    'ville' => isset($_GET['ville']) ? $_GET['ville'] : '',
    'tri' => isset($_GET['tri']) ? $_GET['tri'] : 'date_desc'
];

// Construction de la requête avec filtres
$sql_where = [];
$params = [];
$types = '';

if (!empty($filtres['type'])) {
    $sql_where[] = "m.type_bien = ?";
    $params[] = $filtres['type'];
    $types .= 's';
}

if (!empty($filtres['ville'])) {
    $sql_where[] = "m.ville = ?";
    $params[] = $filtres['ville'];
    $types .= 's';
}

// Ordre de tri
$order_by = 'm.id_maison DESC';
switch ($filtres['tri']) {
    case 'prix_asc': $order_by = 'm.prix ASC'; break;
    case 'prix_desc': $order_by = 'm.prix DESC'; break;
    case 'date_asc': $order_by = 'm.id_maison ASC'; break;
    case 'date_desc': $order_by = 'm.id_maison DESC'; break;
    case 'titre_asc': $order_by = 'm.titre ASC'; break;
    case 'titre_desc': $order_by = 'm.titre DESC'; break;
}

// Requête principale
$sql = "SELECT m.*, p.nom_complet AS auteur 
        FROM maison m 
        LEFT JOIN proprietaire p ON m.id_pro = p.id_pro";

if (!empty($sql_where)) {
    $sql .= " WHERE " . implode(" AND ", $sql_where);
}

$sql .= " ORDER BY $order_by LIMIT 15";

// Exécution avec paramètres
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$maisons = $stmt->get_result();

// Récupérer les types distincts et villes pour les filtres
$types_maisons = $db->query("SELECT DISTINCT type_bien FROM maison ORDER BY type_bien");
$villes = $db->query("SELECT DISTINCT ville FROM maison ORDER BY ville");
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | DOMUS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10 déc._2025__21_34_36-removebg-preview.png" type="image/png">

    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --indigo: #6366f1;
            --pink: #ec4899;
            --dark: #1e293b;
            --light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: var(--light);
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }

       
        .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            border-right: 1px solid #e2e8f0;
            padding: 30px 20px;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo img {
            height: 100px;
            margin-bottom: 10px;
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
        }

        .logo-text span {
            color: var(--primary);
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-left: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            text-decoration: none;
            color: #64748b;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .badge {
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: auto;
            font-weight: 700;
        }

     
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 40px;
            max-width: calc(100% - 280px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .header-content h1 {
            font-size: 2.2rem;
            color: #1e293b;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .header-content p {
            color: #64748b;
            font-size: 1.1rem;
        }

       
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .stat-card:nth-child(1)::before { background: var(--primary); }
        .stat-card:nth-child(2)::before { background: var(--warning); }
        .stat-card:nth-child(3)::before { background: var(--success); }
        .stat-card:nth-child(4)::before { background: var(--indigo); }
        .stat-card:nth-child(5)::before { background: var(--pink); }

        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .stat-card:nth-child(1) .stat-icon { color: var(--primary); }
        .stat-card:nth-child(2) .stat-icon { color: var(--warning); }
        .stat-card:nth-child(3) .stat-icon { color: var(--success); }
        .stat-card:nth-child(4) .stat-icon { color: var(--indigo); }
        .stat-card:nth-child(5) .stat-icon { color: var(--pink); }

        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-top: 5px;
            line-height: 1;
        }

       
        .filters-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            margin-bottom: 30px;
        }

        .filters-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .filters-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-count {
            background: var(--primary);
            color: white;
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-input,
        .filter-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #334155;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            grid-column: 1 / -1;
            padding-top: 10px;
        }

        .btn-filter {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
        }

        .btn-apply:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-reset {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-reset:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

       
        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-subtitle {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        }

        th {
            text-align: left;
            padding: 18px 20px;
            color: var(--dark);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .property-img {
            width: 70px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .property-info {
            max-width: 300px;
        }

        .property-title {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
            font-size: 1.05rem;
        }

        .property-details {
            display: flex;
            gap: 15px;
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .property-details span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .property-type {
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
        }

        .property-author {
            font-size: 0.8rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .property-price {
            font-weight: 800;
            color: var(--success);
            font-size: 1.2rem;
            white-space: nowrap;
        }

        /* BOUTONS */
        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: var(--success);
        }

        .btn-view:hover {
            background: var(--success);
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        /* MESSAGE AUCUN RÉSULTAT */
        .no-results {
            text-align: center;
            padding: 50px 30px;
            color: #64748b;
        }

        .no-results i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            
            .main-content {
                margin-left: 250px;
                max-width: calc(100% - 250px);
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }
            
            .sidebar .logo-text,
            .sidebar .nav-title,
            .sidebar .nav-link span,
            .sidebar .badge {
                display: none;
            }
            
            .sidebar .logo img {
                height: 50px;
            }
            
            .main-content {
                margin-left: 70px;
                padding: 20px;
                max-width: calc(100% - 70px);
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .header-content h1 {
                font-size: 1.8rem;
            }
            
            .filters-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-card,
            .filters-container {
                padding: 20px;
            }
            
            .property-details {
                flex-direction: column;
                gap: 5px;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .btn-filter {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS">
        </div>

        <nav>
            <div class="nav-section">
                <div class="nav-title">Navigation</div>
                <a href="admin.php" class="nav-link active">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-title">Gestion</div>
                <a href="../PHP/liste_clients.php" class="nav-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Clients</span>
                    <span class="badge"><?php echo $stats['clients']; ?></span>
                </a>
                <a href="../PHP/liste_vendeurs.php" class="nav-link">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Vendeurs</span>
                    <span class="badge"><?php echo $stats['vendeurs']; ?></span>
                </a>
                <a href="../PHP/liste_messages.php" class="nav-link">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge"><?php echo $stats['messages']; ?></span>
                </a>
                <a href="../PHP/liste_rendezvous.php" class="nav-link">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Rendez-vous</span>
                    <span class="badge"><?php echo $stats['visites']; ?></span>
                </a>
            </div>

            <div class="nav-section">
                <a href="../CONNECTION/connexionUser.php" class="nav-link" style="color: var(--danger);">
                    <i class="fa-solid fa-power-off"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HEADER -->
        <div class="header">
            <div class="header-content">
                <h1>Administration <span class="logo-text">DOM<span>US</span></span></h1>
                <p>Tableau de bord de gestion de la plateforme</p>
            </div>
        </div>

        <!-- STATISTIQUES -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-house"></i>
                </div>
                <div class="stat-label">Annonces Actives</div>
                <div class="stat-value"><?php echo $stats['biens']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="stat-label">Rendez-vous</div>
                <div class="stat-value"><?php echo $stats['visites']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-label">Clients</div>
                <div class="stat-value"><?php echo $stats['clients']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="stat-label">Vendeurs</div>
                <div class="stat-value"><?php echo $stats['vendeurs']; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="stat-label">Messages</div>
                <div class="stat-value"><?php echo $stats['messages']; ?></div>
            </div>
        </div>

        <!-- BARRE DE FILTRES -->
        <div class="filters-container">
            <div class="filters-header">
                <h3 class="filters-title">
                    <i class="fa-solid fa-filter"></i>
                    Filtres de Recherche
                </h3>
                <span class="filter-count">
                    <?php echo $maisons->num_rows; ?> résultat<?php echo $maisons->num_rows > 1 ? 's' : ''; ?>
                </span>
            </div>

            <form method="GET" action="admin.php" class="filters-form">
                <!-- Filtre par type -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-home"></i>
                        Type de bien
                    </label>
                    <select name="type" class="filter-select">
                        <option value="">Tous les types</option>
                        <?php while($type = $types_maisons->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($type['type_bien']); ?>" 
                                <?php echo $filtres['type'] == $type['type_bien'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($type['type_bien'])); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Filtre par ville -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-location-dot"></i>
                        Ville
                    </label>
                    <select name="ville" class="filter-select">
                        <option value="">Toutes les villes</option>
                        <?php while($ville = $villes->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($ville['ville']); ?>" 
                                <?php echo $filtres['ville'] == $ville['ville'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ville['ville']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Tri des résultats -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-sort"></i>
                        Trier par
                    </label>
                    <select name="tri" class="filter-select">
                        <option value="date_desc" <?php echo $filtres['tri'] == 'date_desc' ? 'selected' : ''; ?>>Date (récentes d'abord)</option>
                        <option value="date_asc" <?php echo $filtres['tri'] == 'date_asc' ? 'selected' : ''; ?>>Date (anciennes d'abord)</option>
                        <option value="prix_desc" <?php echo $filtres['tri'] == 'prix_desc' ? 'selected' : ''; ?>>Prix (décroissant)</option>
                        <option value="prix_asc" <?php echo $filtres['tri'] == 'prix_asc' ? 'selected' : ''; ?>>Prix (croissant)</option>
                        <option value="titre_asc" <?php echo $filtres['tri'] == 'titre_asc' ? 'selected' : ''; ?>>Titre (A-Z)</option>
                        <option value="titre_desc" <?php echo $filtres['tri'] == 'titre_desc' ? 'selected' : ''; ?>>Titre (Z-A)</option>
                    </select>
                </div>

                <!-- Boutons d'action -->
                <div class="filter-actions">
                    <button type="reset" class="btn-filter btn-reset" onclick="resetFilters()">
                        <i class="fa-solid fa-rotate-left"></i>
                        Réinitialiser
                    </button>
                    <button type="submit" class="btn-filter btn-apply">
                        <i class="fa-solid fa-filter"></i>
                        Appliquer les filtres
                    </button>
                </div>
            </form>
        </div>

        <!-- LISTE DES BIENS -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <i class="fa-solid fa-list"></i>
                        Annonces Récentes
                    </h3>
                    <p class="card-subtitle">
                        <?php if(!empty($filtres['type']) || !empty($filtres['ville'])): ?>
                            Résultats filtrés (<?php echo $maisons->num_rows; ?>)
                        <?php else: ?>
                            Les 15 dernières propriétés publiées
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="table-container">
                <?php if ($maisons->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Propriété</th>
                                <th>Détails</th>
                                <th>Prix</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($m = $maisons->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($m['image']); ?>" 
                                         class="property-img" 
                                         alt="<?php echo htmlspecialchars($m['titre']); ?>"
                                         onerror="this.src='../DOMUS IMAGE/default.jpg'">
                                </td>
                                <td>
                                    <div class="property-info">
                                        <div class="property-title">
                                            <?php echo htmlspecialchars($m['titre']); ?>
                                            <span class="property-type"><?php echo ucfirst($m['type_bien']); ?></span>
                                        </div>
                                        <div class="property-details">
                                            <span>
                                                <i class="fa-solid fa-bed"></i>
                                                <?php echo $m['chambres']; ?> Ch.
                                            </span>
                                            <span>
                                                <i class="fa-solid fa-shower"></i>
                                                <?php echo $m['salles_bain']; ?> Sdb.
                                            </span>
                                            <span>
                                                <i class="fa-solid fa-ruler-combined"></i>
                                                <?php echo $m['surface']; ?> m²
                                            </span>
                                        </div>
                                        <div class="property-author">
                                            <i class="fa-solid fa-user-pen"></i>
                                            <?php echo $m['auteur'] ? htmlspecialchars($m['auteur']) : "Administrateur"; ?>
                                            •
                                            <i class="fa-solid fa-location-dot"></i>
                                            <?php echo htmlspecialchars($m['ville']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="property-price">
                                    <?php echo number_format($m['prix'], 0, '.', ' '); ?> XOF
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../PHP/detailAdmin.php?id=<?php echo $m['id_maison']; ?>" 
                                           class="btn-action btn-view" 
                                           title="Voir détails">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <button onclick="confirmerSuppression(<?php echo $m['id_maison']; ?>)" 
                                                class="btn-action btn-delete" 
                                                title="Supprimer">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fa-solid fa-house-chimney-medical"></i>
                        <h3>Aucune annonce trouvée</h3>
                        <p>Essayez de modifier vos critères de recherche</p>
                        <button onclick="resetFilters()" class="btn-filter btn-reset" style="margin-top: 15px;">
                            <i class="fa-solid fa-rotate-left"></i>
                            Réinitialiser les filtres
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function confirmerSuppression(id) {
            if (confirm('Voulez-vous vraiment supprimer cette annonce ? Cette action est irréversible.')) {
                window.location.href = "../PHP/supprimer_maison.php?id=" + id;
            }
        }

        function resetFilters() {
            window.location.href = "admin.php";
        }

        // Auto-soumission quand on change le tri
        document.querySelector('select[name="tri"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>