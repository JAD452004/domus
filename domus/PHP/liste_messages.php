<?php
session_start();
require_once "data.php";

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

// Créer un token CSRF pour la sécurité
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    'type' => isset($_GET['type']) ? $_GET['type'] : '',
    'tri' => isset($_GET['tri']) ? $_GET['tri'] : 'date_desc'
];

// Construction de la requête avec filtres
$sql_where = [];
$params = [];
$types = '';

if (!empty($filtres['search'])) {
    $sql_where[] = "(nom LIKE ? OR email LIKE ? OR sujet LIKE ? OR message LIKE ?)";
    $search_term = "%{$filtres['search']}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ssss';
}

if (!empty($filtres['type'])) {
    $sql_where[] = "user_type = ?";
    $params[] = $filtres['type'];
    $types .= 's';
}

// Ordre de tri
$order_by = 'date_envoi DESC';
switch ($filtres['tri']) {
    case 'date_asc': $order_by = 'date_envoi ASC'; break;
    case 'nom_asc': $order_by = 'nom ASC'; break;
    case 'nom_desc': $order_by = 'nom DESC'; break;
    case 'type_asc': $order_by = 'user_type ASC'; break;
    case 'type_desc': $order_by = 'user_type DESC'; break;
}

// Requête principale
$sql = "SELECT * FROM contact";

if (!empty($sql_where)) {
    $sql .= " WHERE " . implode(" AND ", $sql_where);
}

$sql .= " ORDER BY $order_by LIMIT 20";

// Exécution avec paramètres
if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $messages = $stmt->get_result();
} else {
    $messages = $db->query($sql);
}

// Récupérer les types distincts pour les filtres
$types_users = $db->query("SELECT DISTINCT user_type FROM contact ORDER BY user_type");

// Vérifier si un message a été supprimé
$success_message = '';
$error_message = '';

if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $success_message = 'Message supprimé avec succès.';
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'delete_failed':
            $error_message = 'Erreur lors de la suppression du message.';
            break;
        case 'not_found':
            $error_message = 'Message non trouvé.';
            break;
        case 'id_missing':
            $error_message = 'ID du message manquant.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Messages | DOMUS</title>
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

        /* MESSAGES D'ALERTE */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
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

        /* STYLES COMPACTS DES MESSAGES */
        .message-expediteur {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .expediteur-nom {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .expediteur-email {
            color: #64748b;
            font-size: 0.8rem;
            word-break: break-all;
        }

        .message-date {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-jour {
            background: linear-gradient(135deg, var(--primary), #1d4ed8);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
            width: fit-content;
        }

        .date-heure {
            color: #64748b;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .message-type {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: fit-content;
        }

        .type-client {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .type-vendeur {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .type-visiteur {
            background: rgba(99, 102, 241, 0.1);
            color: var(--indigo);
        }

        .type-other {
            background: rgba(148, 163, 184, 0.1);
            color: #64748b;
        }

        .message-contenu {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .message-sujet {
            font-weight: 700;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .message-texte {
            color: #475569;
            font-size: 0.85rem;
            line-height: 1.4;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-box-orient: vertical;
        }

        /* BOUTONS D'ACTION */
        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-view {
            background: linear-gradient(135deg, #dbeafe, #93c5fd);
            color: var(--primary);
        }

        .btn-view:hover {
            background: var(--primary);
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

        .btn-reply {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: var(--success);
        }

        .btn-reply:hover {
            background: var(--success);
            color: white;
            transform: translateY(-2px);
        }

        .btn-group {
            display: flex;
            gap: 8px;
            justify-content: center;
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

        /* MODAL DE SUPPRESSION */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-text {
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            min-width: 100px;
        }

        .btn-modal-cancel {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-modal-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-modal-confirm {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .btn-modal-confirm:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
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
            
            .modal-content {
                padding: 20px;
                width: 95%;
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
            
            .modal-actions {
                flex-direction: column;
            }
            
            .btn-modal {
                width: 100%;
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
                <a href="liste_messages.php" class="nav-link active">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                    <span class="badge"><?php echo $stats['messages']; ?></span>
                </a>
                 <a href="liste_rendezvous.php" class="nav-link">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Rendez-vous</span>
                    <span class="badge"><?php echo $stats['visites']; ?></span>
                </a>
            </div>

            <div class="nav-section">
                <a href="../CONNECTION/deconnexion.php" class="nav-link" style="color: var(--danger);">
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
                <h1>Gestion des Messages <span class="logo-text">DOM<span>US</span></h1>
                <p>Messages reçus via le formulaire de contact</p>
            </div>
        </div>

        <!-- MESSAGES D'ALERTE -->
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- BARRE DE FILTRES -->
        <div class="filters-container">
            <div class="filters-header">
                <h3 class="filters-title">
                    <i class="fa-solid fa-filter"></i>
                    Filtres de Recherche
                </h3>
                <span class="filter-count">
                    <?php echo $messages->num_rows; ?> message<?php echo $messages->num_rows > 1 ? 's' : ''; ?>
                </span>
            </div>

            <form method="GET" action="liste_messages.php" class="filters-form">
                <!-- Recherche par mot-clé -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Recherche
                    </label>
                    <input type="text" 
                           name="search" 
                           class="filter-input" 
                           placeholder="Nom, email, sujet ou message..."
                           value="<?php echo htmlspecialchars($filtres['search']); ?>">
                </div>

                <!-- Filtre par type -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-user"></i>
                        Type d'utilisateur
                    </label>
                    <select name="type" class="filter-select">
                        <option value="">Tous les types</option>
                        <?php while($type = $types_users->fetch_assoc()): ?>
                            <option value="<?php echo $type['user_type']; ?>" 
                                <?php echo $filtres['type'] == $type['user_type'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($type['user_type']); ?>
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
                        <option value="nom_asc" <?php echo $filtres['tri'] == 'nom_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                        <option value="nom_desc" <?php echo $filtres['tri'] == 'nom_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                        <option value="type_asc" <?php echo $filtres['tri'] == 'type_asc' ? 'selected' : ''; ?>>Type (A-Z)</option>
                        <option value="type_desc" <?php echo $filtres['tri'] == 'type_desc' ? 'selected' : ''; ?>>Type (Z-A)</option>
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

        <!-- LISTE DES MESSAGES -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <i class="fa-solid fa-inbox"></i>
                        Boîte de Réception
                    </h3>
                    <p class="card-subtitle">
                        <?php if(!empty($filtres['search']) || !empty($filtres['type'])): ?>
                            Résultats filtrés (<?php echo $messages->num_rows; ?> messages)
                        <?php else: ?>
                            Les 20 derniers messages reçus
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="table-container">
                <?php if ($messages->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%;">Expéditeur</th>
                                <th style="width: 12%;">Date</th>
                                <th style="width: 10%;">Type</th>
                                <th style="width: 43%;">Message</th>
                                <th style="width: 10%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($msg = $messages->fetch_assoc()): ?>
                            <tr>
                                <!-- COLONNE EXPÉDITEUR -->
                                <td>
                                    <div class="message-expediteur">
                                        <div class="expediteur-nom">
                                            <?php echo htmlspecialchars($msg['nom']); ?>
                                        </div>
                                        <div class="expediteur-email">
                                            <?php echo htmlspecialchars($msg['email']); ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE DATE -->
                                <td>
                                    <div class="message-date">
                                        <div class="date-jour">
                                            <?php echo date('d/m/Y', strtotime($msg['date_envoi'])); ?>
                                        </div>
                                        <div class="date-heure">
                                            <?php echo date('H:i', strtotime($msg['date_envoi'])); ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE TYPE -->
                                <td>
                                    <div class="message-type">
                                        <?php 
                                        $type_class = 'type-other';
                                        switch(strtolower($msg['user_type'])) {
                                            case 'client':
                                                $type_class = 'type-client';
                                                break;
                                            case 'vendeur':
                                                $type_class = 'type-vendeur';
                                                break;
                                            case 'visiteur':
                                                $type_class = 'type-visiteur';
                                                break;
                                        }
                                        ?>
                                        <span class="type-badge <?php echo $type_class; ?>">
                                            <?php echo ucfirst($msg['user_type']); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- COLONNE MESSAGE -->
                                <td>
                                    <div class="message-contenu">
                                        <div class="message-sujet">
                                            <?php echo htmlspecialchars($msg['sujet']); ?>
                                        </div>
                                        <div class="message-texte" title="<?php echo htmlspecialchars($msg['message']); ?>">
                                            <?php echo nl2br(htmlspecialchars(substr($msg['message'], 0, 150))); ?>
                                            <?php if(strlen($msg['message']) > 150): ?>...<?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- COLONNE ACTIONS -->
                                <td>
                                    <div class="btn-group">
                                        <button onclick="voirMessage(<?php echo $msg['id_contact']; ?>)" 
                                                class="btn-action btn-view" 
                                                title="Voir le message complet">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button onclick="repondreMessage('<?php echo htmlspecialchars(addslashes($msg['email'])); ?>', '<?php echo htmlspecialchars(addslashes($msg['sujet'])); ?>')" 
                                                class="btn-action btn-reply" 
                                                title="Répondre">
                                            <i class="fa-solid fa-reply"></i>
                                        </button>
                                        <button onclick="openDeleteModal(<?php echo $msg['id_contact']; ?>)" 
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
                        <i class="fa-solid fa-envelope-open"></i>
                        <h3>Aucun message trouvé</h3>
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

    <!-- MODAL DE SUPPRESSION -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">
                <i class="fa-solid fa-trash-can" style="color: var(--danger);"></i>
                Confirmer la suppression
            </h3>
            <p class="modal-text">
                Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.
            </p>
            <div class="modal-actions">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="closeDeleteModal()">
                    Annuler
                </button>
                <button type="button" class="btn-modal btn-modal-confirm" onclick="confirmDelete()">
                    Supprimer
                </button>
            </div>
        </div>
    </div>

    <script>
        let messageToDelete = null;

        function voirMessage(id) {
            window.open('detail_message.php?id=' + id, '_blank');
        }

        function repondreMessage(email, sujet) {
            window.location.href = 'mailto:' + email + '?subject=RE: ' + encodeURIComponent(sujet);
        }

        function openDeleteModal(id) {
            messageToDelete = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            messageToDelete = null;
            document.getElementById('deleteModal').style.display = 'none';
        }

        function confirmDelete() {
            if (messageToDelete) {
                window.location.href = 'supprimer_messageAdmin.php?id=' + messageToDelete;
            }
        }

        function resetFilters() {
            window.location.href = "liste_messages.php";
        }

        // Auto-soumission quand on change le tri
        document.querySelector('select[name="tri"]').addEventListener('change', function() {
            this.form.submit();
        });

        // Fermer le modal en cliquant en dehors
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Fermer le modal avec la touche Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>