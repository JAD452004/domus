<?php
session_start();
require_once "../PHP/data.php";

// Sécurité : Rediriger si le client n'est pas connecté
if (!isset($_SESSION['id_cli'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_client = $_SESSION['id_cli'];

// Requête SQL pour récupérer uniquement les maisons en favoris
$sql = "SELECT m.* FROM maison m 
        JOIN favoris f ON m.id_maison = f.id_maison 
        WHERE f.id_cli = '$id_client' 
        ORDER BY f.id_favoris DESC";

$result = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Favoris - DOMUS</title>
    <link rel="stylesheet" href="../STYLE/accueil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS">
        </div>
        <div class="nav-links">
            <a href="accueilClient.php">Accueil</a>
            <a href="#" class="active">Mes Favoris</a>
        </div>
    </nav>

    <section class="section-container" style="min-height: 70vh;">
        <div class="section-header">
            <h2>Mes Coups de Cœur <i class="fa-solid fa-heart" style="color: #dc3545;"></i></h2>
        </div>

        <div class="maison-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($m = $result->fetch_assoc()): ?>
                    <div class="maison-card" id="card-<?php echo $m['id_maison']; ?>">
                        <button class="fav-btn" onclick="retirerFavori(<?php echo $m['id_maison']; ?>, this)" 
                                style="position: absolute; top: 15px; right: 15px; background: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fa-solid fa-heart" style="color: #dc3545; font-size: 1.2rem;"></i>
                        </button>
                        
                        <div onclick="window.location.href='details.php?id=<?php echo $m['id_maison']; ?>'" style="cursor: pointer;">
                            <img src="<?php echo htmlspecialchars($m['image']); ?>" class="maison-image">
                            <div class="description">
                                <h3><?php echo htmlspecialchars($m['titre']); ?></h3>
                                <p><?php echo $m['chambres']; ?> ch • <?php echo $m['surface']; ?> m²</p>
                                <p style="color: #2563eb; font-weight: bold;"><?php echo number_format($m['prix'], 0, ',', ' '); ?> XOF</p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; width: 100%; padding: 50px;">
                    <p>Vous n'avez pas encore de favoris.</p>
                    <a href="accueilClient.php" class="btn" style="background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;">Parcourir les maisons</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="info">
        </footer>

    <script>
    function retirerFavori(idMaison, element) {
        if(confirm("Retirer cette maison de vos favoris ?")) {
            fetch('../PHP/toggle_favoris.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_maison=' + idMaison
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'removed') {
                    // On fait disparaître la carte en douceur
                    const card = document.getElementById('card-' + idMaison);
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 400);
                }
            });
        }
    }
    </script>
</body>
</html>