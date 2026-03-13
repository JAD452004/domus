<?php
session_start();
require_once "../PHP/data.php"; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur') {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_pro = $_SESSION['user_id'];
$nom_vendeur = $_SESSION['nom'];

// Statistiques
$query_stats = "SELECT  
    (SELECT COUNT(*) FROM maison WHERE id_pro = ?) as total_biens,
    (SELECT COUNT(*) FROM rendez_vous r JOIN maison m ON r.id_maison = m.id_maison WHERE m.id_pro = ?) as total_rdv,
    (SELECT COUNT(*) FROM rendez_vous r JOIN maison m ON r.id_maison = m.id_maison WHERE m.id_pro = ? AND r.statut = 'en_attente') as rdv_en_attente,
    (SELECT COUNT(*) FROM rendez_vous r JOIN maison m ON r.id_maison = m.id_maison WHERE m.id_pro = ? AND r.statut = 'confirme') as rdv_confirme,
    (SELECT SUM(vues) FROM maison WHERE id_pro = ?) as total_vues";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bind_param("iiiii", $id_pro, $id_pro, $id_pro, $id_pro, $id_pro);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Liste des biens avec le nombre de vues
$query_list = "SELECT *, vues FROM maison WHERE id_pro = ? ORDER BY id_maison DESC";
$stmt_list = $db->prepare($query_list);
$stmt_list->bind_param("i", $id_pro);
$stmt_list->execute();
$mes_biens = $stmt_list->get_result();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tableau de Bord Vendeur - DOMUS</title>
    
    <!-- Importation des ressources externes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10 déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">
    
    <style>
        /* STYLES SPÉCIFIQUES AU TABLEAU DE BORD VENDEUR */
        
        .vendeur-dashboard-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(37, 99, 235, 0.7)),
                        url("https://images.pexels.com/photos/7031427/pexels-photo-7031427.jpeg") center/cover;
            min-height: 30vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 60px 20px;
            margin-bottom: 40px;
            position: relative;
        }
        
        .vendeur-container {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        
        .stats-grid-vendeur {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .stat-card-vendeur {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card-vendeur:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #bfdbfe;
        }
        
        .stat-card-vendeur::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }
        
        .stat-card-1::before { background: linear-gradient(180deg, #2563eb, #1d4ed8); }
        .stat-card-2::before { background: linear-gradient(180deg, #10b981, #059669); }
        .stat-card-3::before { background: linear-gradient(180deg, #f59e0b, #d97706); }
        .stat-card-4::before { background: linear-gradient(180deg, #8b5cf6, #7c3aed); }
        .stat-card-5::before { background: linear-gradient(180deg, #ef4444, #dc2626); }
        
        .stat-header-vendeur {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stat-icon-vendeur {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }
        
        .icon-1 { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .icon-2 { background: linear-gradient(135deg, #10b981, #059669); }
        .icon-3 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .icon-4 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .icon-5 { background: linear-gradient(135deg, #ef4444, #dc2626); }
        
        .stat-title-vendeur {
            font-size: 1rem;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }
        
        .stat-value-vendeur {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1;
        }
        
        .stat-subtitle-vendeur {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .properties-list-vendeur {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }
        
        .property-item-vendeur {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }
        
        .property-item-vendeur:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #bfdbfe;
        }
        
        .property-image-vendeur {
            width: 100px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .property-image-vendeur img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .property-content-vendeur h4 {
            font-size: 1.1rem;
            color: #0f172a;
            margin: 0 0 8px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .property-details-vendeur {
            display: flex;
            gap: 15px;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .property-price-vendeur {
            color: #10b981;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: baseline;
            gap: 5px;
        }
        
        .property-actions-vendeur {
            display: flex;
            gap: 10px;
        }
        
        .action-btn-vendeur {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
        }
        
        .btn-edit {
            color: #2563eb;
            border-color: #bfdbfe;
        }
        
        .btn-edit:hover {
            background: #2563eb;
            color: white;
            transform: scale(1.1);
        }
        
        .btn-delete {
            color: #ef4444;
            border-color: #fecaca;
        }
        
        .btn-delete:hover {
            background: #ef4444;
            color: white;
            transform: scale(1.1);
        }
        
        .btn-view {
            color: #10b981;
            border-color: #a7f3d0;
        }
        
        .btn-view:hover {
            background: #10b981;
            color: white;
            transform: scale(1.1);
        }
        
        .vues-badge-property {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #475569;
        }
        
        .vues-badge-property i {
            color: #f59e0b;
        }
        
        .notification-badge {
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            position: absolute;
            top: -5px;
            right: -5px;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .add-property-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .add-property-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, #059669, #047857);
        }
        
        .empty-properties-vendeur {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            color: #64748b;
        }
        
        .empty-properties-vendeur i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
        
        .transaction-badge-mini {
            margin-left: 10px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            color: white;
            display: inline-block;
        }
        
        @media (max-width: 991px) {
            .stats-grid-vendeur {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .property-item-vendeur {
                grid-template-columns: 80px 1fr auto;
                padding: 15px;
                gap: 15px;
            }
        }
        
        @media (max-width: 767px) {
            .vendeur-dashboard-header {
                min-height: 25vh;
                padding: 40px 20px;
            }
            
            .stat-card-vendeur {
                padding: 20px;
            }
            
            .stat-value-vendeur {
                font-size: 2rem;
            }
            
            .property-item-vendeur {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .property-image-vendeur {
                margin: 0 auto;
            }
            
            .property-actions-vendeur {
                justify-content: center;
            }
            
            .property-details-vendeur {
                justify-content: center;
            }
            
            .property-content-vendeur h4 {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid-vendeur {
                grid-template-columns: 1fr;
            }
            
            .add-property-btn {
                width: 100%;
                justify-content: center;
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
            <li><a href="accueilPropriete.php"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="propriete.php" class="active"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <li><a href="contact.php"><i class="fa-solid fa-envelope"></i> <span>Contact</span></a></li>
        </ul>

        <div class="user-area">
            <div class="user-info">
                <?php include __DIR__ . '/_user_avatar.php'; ?>
            </div>
            
            <a href="../PHP/liste_rdv.php" style="position: relative; margin-right: 15px; color: #64748b;">
                <i class="fa-solid fa-bell" style="font-size: 1.3rem;"></i>
                <?php if ($stats['rdv_en_attente'] > 0): ?>
                    <span class="notification-badge"><?php echo $stats['rdv_en_attente']; ?></span>
                <?php endif; ?>
            </a>
            
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
    <header class="vendeur-dashboard-header">
        <div class="tete-content">
            <h2>Tableau de Bord Vendeur</h2>
            <p>Bienvenue, <?php echo htmlspecialchars($nom_vendeur); ?>. Gérez vos propriétés et rendez-vous.</p>
            
            <a href="../PHP/ajouter_propriete.php" class="add-property-btn">
                <i class="fa-solid fa-plus"></i> Ajouter une propriété
            </a>
        </div>
    </header>

    <!-- ============================
         CONTENU PRINCIPAL
         ============================ -->
    <div class="vendeur-container">
        <!-- Statistiques (5 cartes) -->
        <div class="stats-grid-vendeur">
            <div class="stat-card-vendeur stat-card-1">
                <div class="stat-header-vendeur">
                    <div class="stat-icon-vendeur icon-1">
                        <i class="fa-solid fa-house"></i>
                    </div>
                    <h3 class="stat-title-vendeur">Propriétés en ligne</h3>
                </div>
                <p class="stat-value-vendeur"><?php echo $stats['total_biens']; ?></p>
                <p class="stat-subtitle-vendeur">Total de vos annonces</p>
            </div>
            
            <div class="stat-card-vendeur stat-card-2">
                <div class="stat-header-vendeur">
                    <div class="stat-icon-vendeur icon-2">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h3 class="stat-title-vendeur">Rendez-vous total</h3>
                </div>
                <p class="stat-value-vendeur"><?php echo $stats['total_rdv']; ?></p>
                <p class="stat-subtitle-vendeur">Demandes reçues</p>
            </div>
            
            <div class="stat-card-vendeur stat-card-3">
                <div class="stat-header-vendeur">
                    <div class="stat-icon-vendeur icon-3">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <h3 class="stat-title-vendeur">En attente</h3>
                </div>
                <p class="stat-value-vendeur"><?php echo $stats['rdv_en_attente']; ?></p>
                <p class="stat-subtitle-vendeur">RDV à confirmer</p>
            </div>
            
            <div class="stat-card-vendeur stat-card-4">
                <div class="stat-header-vendeur">
                    <div class="stat-icon-vendeur icon-4">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <h3 class="stat-title-vendeur">Confirmés</h3>
                </div>
                <p class="stat-value-vendeur"><?php echo $stats['rdv_confirme']; ?></p>
                <p class="stat-subtitle-vendeur">RDV acceptés</p>
            </div>
            
            <div class="stat-card-vendeur stat-card-5">
                <div class="stat-header-vendeur">
                    <div class="stat-icon-vendeur icon-5">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <h3 class="stat-title-vendeur">Vues totales</h3>
                </div>
                <p class="stat-value-vendeur"><?php echo $stats['total_vues'] ?? 0; ?></p>
                <p class="stat-subtitle-vendeur">Sur toutes vos annonces</p>
            </div>
        </div>

        <!-- Liste des propriétés -->
        <section class="section-container">
            <div class="section-header">
                <h2>Mes Propriétés</h2>
                <div class="type-count">
                    <span><?php echo $stats['total_biens']; ?> propriété<?php echo $stats['total_biens'] > 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <?php if ($mes_biens->num_rows > 0): ?>
                <div class="properties-list-vendeur">
                    <?php while ($bien = $mes_biens->fetch_assoc()): 
                        $prop_transaction = $bien['transaction_type'] ?? 'vente';
                    ?>
                        <div class="property-item-vendeur">
                            <!-- Image -->
                            <div class="property-image-vendeur">
                                <img src="<?php echo htmlspecialchars($bien['image']); ?>" alt="<?php echo htmlspecialchars($bien['titre']); ?>">
                            </div>
                            
                            <!-- Contenu -->
                            <div class="property-content-vendeur">
                                <h4>
                                    <?php echo htmlspecialchars($bien['titre']); ?>
                                    <span class="transaction-badge-mini" style="background: <?php echo $prop_transaction === 'vente' ? '#10b981' : '#f59e0b'; ?>;">
                                        <?php echo $prop_transaction === 'vente' ? 'Vente' : 'Location'; ?>
                                    </span>
                                </h4>
                                <div class="property-details-vendeur">
                                    <span><i class="fa-solid fa-bed"></i> <?php echo $bien['chambres']; ?> Ch.</span>
                                    <span><i class="fa-solid fa-shower"></i> <?php echo $bien['salles_bain']; ?> Sdb.</span>
                                    <span><i class="fa-solid fa-ruler-combined"></i> <?php echo $bien['surface']; ?> m²</span>
                                    <span class="vues-badge-property">
                                        <i class="fa-solid fa-eye"></i> <?php echo $bien['vues'] ?? 0; ?> vue<?php echo ($bien['vues'] ?? 0) > 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                                <div class="property-price-vendeur">
                                    <?php echo number_format($bien['prix'], 0, ',', ' '); ?> XOF
                                    <?php if ($prop_transaction === 'location'): ?>
                                        <span style="font-size: 0.8rem; color: #64748b;">/mois</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="property-actions-vendeur">
                                <a href="../PHP/modifier_propriete.php?id=<?php echo $bien['id_maison']; ?>" 
                                   class="action-btn-vendeur btn-edit" 
                                   title="Modifier">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <button onclick="confirmerSuppression(<?php echo $bien['id_maison']; ?>)" 
                                        class="action-btn-vendeur btn-delete" 
                                        title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <a href="../PHP/details.php?id=<?php echo $bien['id_maison']; ?>" 
                                   target="_blank" 
                                   class="action-btn-vendeur btn-view" 
                                   title="Voir sur le site">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <!-- Aucune propriété -->
                <div class="empty-properties-vendeur">
                    <i class="fa-solid fa-house-chimney-medical"></i>
                    <h3>Aucune propriété publiée</h3>
                    <p>Commencez à ajouter vos biens immobiliers.</p>
                    <a href="../PHP/ajouter_propriete.php" class="add-property-btn" style="margin-top: 20px; display: inline-block;">
                        <i class="fa-solid fa-plus"></i> Ajouter ma première propriété
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>

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
    });
    
    function confirmerSuppression(id) {
        if (confirm("Voulez-vous vraiment supprimer cette propriété ? Cette action est irréversible.")) {
            window.location.href = "../PHP/supprimer_propriete.php?id=" + id;
        }
    }
    
    function updateNotifications() {
        fetch('../PHP/get_rdv_count.php')
            .then(response => response.text())
            .then(count => {
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.innerText = count;
                }
            })
            .catch(error => console.error('Erreur de mise à jour:', error));
    }
    
    setInterval(updateNotifications, 10000);
    </script>
</body>
</html>