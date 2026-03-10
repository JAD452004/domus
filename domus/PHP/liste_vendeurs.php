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

// --- GESTION DU FILTRE PAR NOM ---
$search_nom = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- RÉCUPÉRATION DE LA LISTE DES VENDEURS ---
if (!empty($search_nom)) {
    $sql = "SELECT * FROM proprietaire WHERE nom_complet LIKE ? ORDER BY nom_complet ASC";
    $stmt = $db->prepare($sql);
    $search_term = "%" . $search_nom . "%";
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $vendeurs = $stmt->get_result();
} else {
    $vendeurs = $db->query("SELECT * FROM proprietaire ORDER BY nom_complet ASC");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Vendeurs | DOMUS</title>
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
            grid-template-columns: 2fr 1fr;
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

        .filter-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #334155;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            align-items: center;
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

        /* TABLEAU */
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--indigo), #8b5cf6);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .user-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.05rem;
        }

        .user-id {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .user-contact {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .user-phone {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .user-email {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .user-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-verified {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .date-cell {
            font-size: 0.9rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .btn-login {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: var(--indigo);
        }

        .btn-login:hover {
            background: var(--indigo);
            color: white;
            transform: translateY(-2px);
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
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .header-content h1 {
                font-size: 1.8rem;
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
            }
            
            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
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
                <a href="liste_vendeurs.php" class="nav-link active">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Vendeurs</span>
                    <span class="badge"><?php echo $stats['vendeurs']; ?></span>
                </a>
                <a href="liste_messages.php" class="nav-link">
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
                <h1>Gestion des Vendeurs</h1>
                <p>Administration des propriétaires et partenaires DOMUS</p>
            </div>
        </div>

        <!-- BARRE DE FILTRES -->
        <div class="filters-container">
            <div class="filters-header">
                <h3 class="filters-title">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Recherche par nom
                </h3>
                <span class="filter-count">
                    <?php echo $vendeurs->num_rows; ?> vendeur<?php echo $vendeurs->num_rows > 1 ? 's' : ''; ?>
                </span>
            </div>

            <form method="GET" action="" class="filters-form">
                <!-- Recherche par nom -->
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fa-solid fa-user-tie"></i>
                        Nom du vendeur
                    </label>
                    <input type="text" 
                           name="search" 
                           class="filter-input" 
                           placeholder="Rechercher un vendeur par nom..."
                           value="<?php echo htmlspecialchars($search_nom); ?>">
                </div>

                <!-- Boutons d'action -->
                <div class="filter-actions">
                    <?php if (!empty($search_nom)): ?>
                    <a href="liste_vendeurs.php" class="btn-filter btn-reset">
                        <i class="fa-solid fa-rotate-left"></i>
                        Réinitialiser
                    </a>
                    <?php endif; ?>
                    <button type="submit" class="btn-filter btn-apply">
                        <i class="fa-solid fa-search"></i>
                        Rechercher
                    </button>
                </div>
            </form>
            
            <?php if (!empty($search_nom)): ?>
            <div style="margin-top: 15px; font-size: 0.9rem; color: #64748b;">
                <i class="fa-solid fa-filter" style="margin-right: 8px;"></i>
                Filtre actif : <strong>"<?php echo htmlspecialchars($search_nom); ?>"</strong>
            </div>
            <?php endif; ?>
        </div>

        <!-- LISTE DES VENDEURS -->
        <div class="dashboard-card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <i class="fa-solid fa-user-tie"></i>
                        <?php echo !empty($search_nom) ? 'Résultats de la recherche' : 'Tous les vendeurs'; ?>
                    </h3>
                    <p class="card-subtitle">
                        <?php if(!empty($search_nom)): ?>
                            <?php echo $vendeurs->num_rows; ?> résultat<?php echo $vendeurs->num_rows > 1 ? 's' : ''; ?> trouvé<?php echo $vendeurs->num_rows > 1 ? 's' : ''; ?>
                        <?php else: ?>
                            Liste complète des propriétaires et partenaires DOMUS
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="table-container">
                <?php if ($vendeurs->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Vendeur</th>
                                <th>Contact</th>
                                <th>Biens publiés</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            while($v = $vendeurs->fetch_assoc()): 
                                // Compter les biens du vendeur
                                $count_biens_res = $db->query("SELECT COUNT(*) as total FROM maison WHERE id_pro = " . $v['id_pro']);
                                $count_biens = $count_biens_res ? $count_biens_res->fetch_assoc()['total'] : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($v['nom_complet'] ?? 'V', 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($v['nom_complet']); ?></div>
                                            <div class="user-id">ID: #<?php echo $v['id_pro']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-contact">
                                        <?php if(!empty($v['telephone'])): ?>
                                        <div class="user-phone">
                                            <i class="fa-solid fa-phone"></i>
                                            <?php echo htmlspecialchars($v['telephone']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if(!empty($v['email'])): ?>
                                        <div class="user-email">
                                            <i class="fa-solid fa-envelope"></i>
                                            <?php echo htmlspecialchars($v['email']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="background: linear-gradient(135deg, var(--primary), #1d4ed8); color: white; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                                            <?php echo $count_biens; ?> annonce<?php echo $count_biens > 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-status status-verified">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Vérifié
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="connexion_forcee.php?id=<?php echo $v['id_pro']; ?>&role=vendeur" 
                                           class="btn-action btn-login" 
                                           title="Se connecter en tant que ce vendeur">
                                            <i class="fa-solid fa-user-shield"></i>
                                        </a>
                                        <a href="detail_vendeur.php?id=<?php echo $v['id_pro']; ?>" 
                                           class="btn-action btn-view" 
                                           title="Voir détails">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="supprimer_vendeur.php?id=<?php echo $v['id_pro']; ?>" 
                                           class="btn-action btn-delete" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce vendeur ? Cette action est irréversible.')"
                                           title="Supprimer">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fa-solid fa-user-tie"></i>
                        <h3>Aucun vendeur trouvé</h3>
                        <p>
                            <?php if (!empty($search_nom)): ?>
                                Aucun résultat pour "<?php echo htmlspecialchars($search_nom); ?>"
                            <?php else: ?>
                                Aucun vendeur n'est inscrit pour le moment.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search_nom)): ?>
                        <a href="liste_vendeurs.php" class="btn-filter btn-reset" style="margin-top: 15px;">
                            <i class="fa-solid fa-rotate-left"></i>
                            Réinitialiser la recherche
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Focus sur le champ de recherche au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>