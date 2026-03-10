<?php
session_start();
require_once "../PHP/data.php";

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// --- RÉCUPÉRATION DES STATISTIQUES POUR LA SIDEBAR ---
$stats = [
    'clients' => $db->query("SELECT COUNT(*) as total FROM client")->fetch_assoc()['total'],
    'vendeurs' => $db->query("SELECT COUNT(*) as total FROM proprietaire")->fetch_assoc()['total'],
    'messages' => $db->query("SELECT COUNT(*) as total FROM contact")->fetch_assoc()['total'],
    'visites' => $db->query("SELECT COUNT(*) as total FROM rendez_vous")->fetch_assoc()['total'],
    'biens' => $db->query("SELECT COUNT(*) as total FROM maison")->fetch_assoc()['total']
];

// --- GESTION DES FILTRES ---
$filtres = [
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
    'statut' => isset($_GET['statut']) ? $_GET['statut'] : '',
    'tri' => isset($_GET['tri']) ? $_GET['tri'] : 'date_desc'
];

// Construction de la requête avec filtres
$sql_where = [];
$params = [];
$types = '';

if (!empty($filtres['search'])) {
    $sql_where[] = "(c.nom_complet LIKE ? OR m.titre LIKE ? OR p.nom_complet LIKE ?)";
    $search_term = "%{$filtres['search']}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($filtres['statut'])) {
    $sql_where[] = "r.statut = ?";
    $params[] = $filtres['statut'];
    $types .= 's';
}

// Ordre de tri
$order_by = 'r.date_rdv DESC, r.heure_rdv DESC';
switch ($filtres['tri']) {
    case 'date_asc': $order_by = 'r.date_rdv ASC, r.heure_rdv ASC'; break;
    case 'client_asc': $order_by = 'c.nom_complet ASC'; break;
    case 'client_desc': $order_by = 'c.nom_complet DESC'; break;
    case 'vendeur_asc': $order_by = 'p.nom_complet ASC'; break;
    case 'vendeur_desc': $order_by = 'p.nom_complet DESC'; break;
}

// Requête principale avec JOIN pour récupérer toutes les infos
$sql = "SELECT r.*, 
               c.nom_complet as nom_client, 
               c.email as email_client,
               c.telephone as tel_client,
               m.titre as titre_bien,
               m.ville as ville_bien,
               m.prix as prix_bien,
               m.image as image_bien,
               p.nom_complet as nom_vendeur,
               p.telephone as tel_vendeur,
               p.email as email_vendeur
        FROM rendez_vous r
        JOIN client c ON r.id_client = c.id_cli
        JOIN maison m ON r.id_maison = m.id_maison
        JOIN proprietaire p ON m.id_pro = p.id_pro";

if (!empty($sql_where)) {
    $sql .= " WHERE " . implode(" AND ", $sql_where);
}

$sql .= " ORDER BY $order_by LIMIT 20";

// Exécution avec paramètres
if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rdv_list = $stmt->get_result();
} else {
    $rdv_list = $db->query($sql);
}

// Récupérer les statuts distincts pour les filtres
$statuts = $db->query("SELECT DISTINCT statut FROM rendez_vous ORDER BY statut");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Rendez-vous | DOMUS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        /* SIDEBAR */
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

        /* MAIN CONTENT */
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

        /* BARRE DE FILTRES */
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

        /* CARD STYLES */
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

        /* TABLEAU COMPACT */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        thead {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        }

        th {
            text-align: left;
            padding: 14px 16px;
            color: var(--dark);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        /* STYLES COMPACTS DES RDV */
        .rdv-client {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .client-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .client-contact {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 0.8rem;
            color: #64748b;
        }

        .rdv-vendeur {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .vendeur-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .vendeur-contact {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 0.8rem;
            color: #64748b;
        }

        .rdv-bien {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .bien-title {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bien-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 0.8rem;
            color: #64748b;
        }

        .bien-price {
            font-weight: 800;
            color: var(--success);
            font-size: 0.9rem;
        }

        .rdv-date {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .date-label {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .date-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-day {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
            width: fit-content;
        }

        .date-time {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
            width: fit-content;
        }

        .rdv-status {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .status-label {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: fit-content;
        }

        .status-confirmed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .status-completed {
            background: rgba(99, 102, 241, 0.1);
            color: var(--indigo);
        }

        /* ICÔNES PLUS PETITES */
        .fa-user, .fa-user-tie, .fa-house, .fa-calendar-days, .fa-circle {
            font-size: 0.9rem;
        }

        .fa-envelope, .fa-phone, .fa-location-dot, .fa-tag, .fa-clock {
            font-size: 0.8rem;
        }

        /* MESSAGE AUCUN RÉSULTAT */
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .no-results i {
            font-size: 2.5rem;
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
            
            table {
                min-width: 800px;
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
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .header-content h1 {
                font-size: 1.8rem;
            }
            
            table {
                min-width: 700px;
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 12px 14px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-card,
            .filters-container {
                padding: 20px;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .btn-filter {
                width: 100%;
                justify-content: center;
                padding: 10px 20px;
            }
            
            .header-content h1 {
                font-size: 1.6rem;
            }
            
            .header-content p {
                font-size: 1rem;
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
                <a href="../Accueil/Admin.php" class="nav-link">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-title">Gestion</div>
                <a href="liste_clients.php" class="nav-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Clients</span>
                    <span class="badge"><?php echo $stats['clients']; ?></span>
                </a>
                <a href="liste_vendeurs.php" class="nav-link">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Vendeurs</span>
                    <span class="badge"><?php echo $stats['vendeurs']; ?></span>
                </a>
                <a href="liste_messages.php" class="nav-link">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge"><?php echo $stats['messages']; ?></span>
                </a>
                 <a href="liste_rendezvous.php" class="nav-link active">
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
                <h1>Gestion des Rendez-vous <span class="logo-text">DOM<span>US</span></span></h1>
                <p>Planning des visites entre clients et vendeurs</p>
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
                    <?php echo $rdv_list->num_rows; ?> RDV<?php echo $rdv_list->num_rows > 1 ? 's' : ''; ?>
                </span>
            </div>

            <form method="GET" action="liste_rendezvous.php" class="filters-form">
                <!-- Recherche par mot-clé -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Recherche
                    </label>
                    <input type="text" 
                           name="search" 
                           class="filter-input" 
                           placeholder="Client, vendeur ou bien..."
                           value="<?php echo htmlspecialchars($filtres['search']); ?>">
                </div>

                <!-- Filtre par statut -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-circle"></i>
                        Statut
                    </label>
                    <select name="statut" class="filter-select">
                        <option value="">Tous les statuts</option>
                        <?php while($statut = $statuts->fetch_assoc()): ?>
                            <option value="<?php echo $statut['statut']; ?>" 
                                <?php echo $filtres['statut'] == $statut['statut'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($statut['statut']); ?>
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
                        <option value="client_asc" <?php echo $filtres['tri'] == 'client_asc' ? 'selected' : ''; ?>>Client (A-Z)</option>
                        <option value="client_desc" <?php echo $filtres['tri'] == 'client_desc' ? 'selected' : ''; ?>>Client (Z-A)</option>
                        <option value="vendeur_asc" <?php echo $filtres['tri'] == 'vendeur_asc' ? 'selected' : ''; ?>>Vendeur (A-Z)</option>
                        <option value="vendeur_desc" <?php echo $filtres['tri'] == 'vendeur_desc' ? 'selected' : ''; ?>>Vendeur (Z-A)</option>
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

        <!-- LISTE DES RENDEZ-VOUS -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <i class="fa-solid fa-calendar-check"></i>
                        Rendez-vous Programmés
                    </h3>
                    <p class="card-subtitle">
                        <?php if(!empty($filtres['search']) || !empty($filtres['statut'])): ?>
                            Résultats filtrés (<?php echo $rdv_list->num_rows; ?> RDV)
                        <?php else: ?>
                            Les 20 derniers rendez-vous programmés
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="table-container">
                <?php if ($rdv_list->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 18%;">Client</th>
                                <th style="width: 18%;">Vendeur</th>
                                <th style="width: 22%;">Bien</th>
                                <th style="width: 20%;">Date & Heure</th>
                                <th style="width: 12%;">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = $rdv_list->fetch_assoc()): ?>
                            <tr>
                                <!-- COLONNE CLIENT -->
                                <td>
                                    <div class="rdv-client">
                                        <div class="client-name">
                                            <i class="fa-solid fa-user" style="color: var(--primary);"></i>
                                            <?php echo htmlspecialchars($r['nom_client']); ?>
                                        </div>
                                        <div class="client-contact">
                                            <?php if(!empty($r['email_client'])): ?>
                                            <div>
                                                <i class="fa-solid fa-envelope"></i>
                                                <?php echo htmlspecialchars($r['email_client']); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($r['tel_client'])): ?>
                                            <div>
                                                <i class="fa-solid fa-phone"></i>
                                                <?php echo htmlspecialchars($r['tel_client']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE VENDEUR -->
                                <td>
                                    <div class="rdv-vendeur">
                                        <div class="vendeur-name">
                                            <i class="fa-solid fa-user-tie" style="color: var(--indigo);"></i>
                                            <?php echo htmlspecialchars($r['nom_vendeur']); ?>
                                        </div>
                                        <div class="vendeur-contact">
                                            <?php if(!empty($r['email_vendeur'])): ?>
                                            <div>
                                                <i class="fa-solid fa-envelope"></i>
                                                <?php echo htmlspecialchars($r['email_vendeur']); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($r['tel_vendeur'])): ?>
                                            <div>
                                                <i class="fa-solid fa-phone"></i>
                                                <?php echo htmlspecialchars($r['tel_vendeur']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE BIEN -->
                                <td>
                                    <div class="rdv-bien">
                                        <div class="bien-title">
                                            <i class="fa-solid fa-house" style="color: var(--success);"></i>
                                            <?php echo htmlspecialchars($r['titre_bien']); ?>
                                        </div>
                                        <div class="bien-details">
                                            <div>
                                                <i class="fa-solid fa-location-dot"></i>
                                                <?php echo htmlspecialchars($r['ville_bien']); ?>
                                            </div>
                                            <div class="bien-price">
                                                <i class="fa-solid fa-tag"></i>
                                                <?php echo number_format($r['prix_bien'], 0, '.', ' '); ?> XOF
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE DATE & HEURE -->
                                <td>
                                    <div class="rdv-date">
                                        <div class="date-details">
                                            <div class="date-day">
                                                <?php echo date('d/m/Y', strtotime($r['date_rdv'])); ?>
                                            </div>
                                            <div class="date-time">
                                                <i class="fa-solid fa-clock"></i>
                                                <?php echo $r['heure_rdv']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE STATUT -->
                                <td>
                                    <div class="rdv-status">
                                        <?php 
                                        $status_class = 'status-pending';
                                        $status_text = 'En attente';
                                        
                                        switch(strtolower($r['statut'])) {
                                            case 'confirmé':
                                            case 'confirmé':
                                                $status_class = 'status-confirmed';
                                                $status_text = 'Confirmé';
                                                break;
                                            case 'annulé':
                                            case 'annulé':
                                                $status_class = 'status-cancelled';
                                                $status_text = 'Annulé';
                                                break;
                                            case 'terminé':
                                            case 'terminé':
                                                $status_class = 'status-completed';
                                                $status_text = 'Terminé';
                                                break;
                                            default:
                                                $status_class = 'status-pending';
                                                $status_text = 'En attente';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <h3>Aucun rendez-vous trouvé</h3>
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
        function resetFilters() {
            window.location.href = "liste_rendezvous.php";
        }

        // Auto-soumission quand on change le tri
        document.querySelector('select[name="tri"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>