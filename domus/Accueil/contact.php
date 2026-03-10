<?php
session_start();
require_once "../PHP/data.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_user = $_SESSION['user_id'];
$nom_user = $_SESSION['nom'] ?? 'Utilisateur';
$role = $_SESSION['role'] ?? 'client';

$nom_complet = $nom_user;

$lien_accueil = ($role === 'vendeur') ? 'accueilPropriete.php' : 'accueilClient.php';
$lien_dashboard = ($role === 'vendeur') ? 'propriete.php' : 'client.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Contact - DOMUS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
   <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="../STYLE/accueilClient.css">
    
    <style>
        .contact-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(37, 99, 235, 0.7)),
                        url("https://images.pexels.com/photos/8293778/pexels-photo-8293778.jpeg") center/cover;
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
        
        .contact-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        
        .contact-coordinates {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
        }
        
        .contact-coordinates h2 {
            color: #0f172a;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-coordinates h2 i {
            color: #2563eb;
        }
        
        .contact-coordinates hr {
            border: none;
            height: 2px;
            background: linear-gradient(to right, #2563eb, transparent);
            margin: 0 0 25px 0;
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .info-item i {
            color: #2563eb;
            font-size: 1.2rem;
            margin-top: 3px;
            flex-shrink: 0;
        }
        
        .info-content strong {
            color: #0f172a;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-content span {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .contact-form {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
        }
        
        .contact-form h2 {
            color: #0f172a;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-form h2 i {
            color: #10b981;
        }
        
        .contact-form hr {
            border: none;
            height: 2px;
            background: linear-gradient(to right, #10b981, transparent);
            margin: 0 0 25px 0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #0f172a;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            color: #1e293b;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        
        .map-container {
            max-width: 1200px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }
        
        .map-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
        }
        
        .map-card h2 {
            color: #0f172a;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .map-card h2 i {
            color: #f59e0b;
        }
        
        .map-placeholder {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 12px;
            height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            margin-top: 20px;
        }
        
        .map-placeholder i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #94a3b8;
        }
        
        /* Styles pour l'avatar */
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
        
        @media (max-width: 768px) {
            .contact-section {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .contact-header {
                min-height: 30vh;
                padding: 40px 20px;
            }
            
            .contact-coordinates,
            .contact-form,
            .map-card {
                padding: 25px;
            }
        }
        
        @media (max-width: 480px) {
            .contact-coordinates,
            .contact-form,
            .map-card {
                padding: 20px;
            }
            
            .info-item {
                flex-direction: column;
                gap: 5px;
            }
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
            <li><a href="<?php echo $lien_accueil; ?>"><i class="fa-solid fa-house"></i> <span>Accueil</span></a></li>
            <li><a href="<?php echo $lien_dashboard; ?>"><i class="fa-solid fa-chart-line"></i> <span>Tableau de Bord</span></a></li>
            <li><a href="contact.php" class="active"><i class="fa-solid fa-envelope"></i> <span>Contact</span></a></li>
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

    <header class="contact-header">
        <div class="tete-content">
            <h2>Contactez-nous</h2>
            <p>Nous sommes là pour répondre à toutes vos questions</p>
        </div>
    </header>

    <div class="contact-section">
        <div class="contact-coordinates">
            <h2><i class="fa-solid fa-location-dot"></i> Nos Coordonnées</h2>
            <hr>
            
            <div class="contact-info">
                <div class="info-item">
                    <i class="fa-solid fa-map-marker-alt"></i>
                    <div class="info-content">
                        <strong>Adresse</strong>
                        <span>123 Rue de IFSM Adjamé, Abidjan, Côte d'Ivoire</span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-phone"></i>
                    <div class="info-content">
                        <strong>Téléphone</strong>
                        <span>+225 01 23 45 67 89</span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div class="info-content">
                        <strong>Email</strong>
                        <span>contact@domus.com</span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-clock"></i>
                    <div class="info-content">
                        <strong>Horaires d'ouverture</strong>
                        <span>Lundi - Vendredi: 9h00 - 18h00<br>Samedi: 9h00 - 13h00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-form">
            <h2><i class="fa-solid fa-comment-dots"></i> Envoyez-nous un message</h2>
            <hr>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Votre message a été envoyé avec succès !</span>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>Une erreur est survenue. Veuillez réessayer.</span>
                </div>
            <?php endif; ?>

            <form action="../PHP/contacte.php" method="POST">
                <div class="form-group">
                    <label for="nom">Nom complet</label>
                    <input type="text" id="nom" name="nom" 
                           value="<?php echo htmlspecialchars($nom_user); ?>" 
                           class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" 
                           class="form-control" required
                           placeholder="votre@email.com">
                </div>
                
                <div class="form-group">
                    <label for="sujet">Sujet du message</label>
                    <input type="text" id="sujet" name="sujet" 
                           class="form-control" required
                           placeholder="À propos de...">
                </div>
                
                <div class="form-group">
                    <label for="message">Votre message</label>
                    <textarea id="message" name="message" 
                              class="form-control" required
                              placeholder="Décrivez-nous votre demande..."></textarea>
                </div>
                
                <button type="submit" name="envoyer" class="submit-btn">
                    <i class="fa-solid fa-paper-plane"></i>
                    Envoyer le message
                </button>
            </form>
        </div>
    </div>

    <div class="map-container">
        <div class="map-card">
            <h2><i class="fa-solid fa-map"></i> Notre Localisation</h2>
            <div class="map-placeholder">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3972.443014207816!2d-4.022612929934472!3d5.349153223851949!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfc1eb724ef36051%3A0x80a140d3946088e5!2sIFSM%20ADJAME!5e0!3m2!1sfr!2sga!4v1770744497559!5m2!1sfr!2sga" 
                    width="100%" 
                    height="100%" 
                    style="border:0; border-radius: 12px;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>

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
                        if (icon) {
                            icon.classList.remove("fa-times");
                            icon.classList.add("fa-bars");
                        }
                        document.body.style.overflow = "";
                    }
                });
            });
        }
    });
    </script>
</body>
</html>