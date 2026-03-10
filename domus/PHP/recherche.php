<?php
session_start();

require_once "data.php"; 

if (!isset($_SESSION['id_cli'])) {
    header("Location: ../CONNECTION/connexionUser.php");
    exit();
}

$id_client = $_SESSION['id_cli'];
$nom_client = $_SESSION['nom_complet'];

// Récupération des filtres
$search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
$type = isset($_GET['type']) ? $db->real_escape_string($_GET['type']) : '';
$ville = isset($_GET['ville']) ? $db->real_escape_string($_GET['ville']) : '';

$sql = "SELECT * FROM maison WHERE 1=1";
if ($search != '') $sql .= " AND (titre LIKE '%$search%' OR description LIKE '%$search%')";
if ($type != '')   $sql .= " AND type_bien = '$type'";
if ($ville != '')  $sql .= " AND ville = '$ville'";
$sql .= " ORDER BY id_maison DESC";

$result = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de recherche - DOMUS</title>
    <link rel="stylesheet" href="../STYLE/accueilCient.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" type="image/png">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../DOMUS IMAGE/ChatGPT_Image_10_déc._2025__21_34_36-removebg-preview.png" alt="DOMUS"> 
        </div>
        <div class="nav-links">
            <a href="../Accueil/accueilClient.php">Accueil</a>
        </div>
        <div class="nav-buttons">
             <span style="font-weight: bold;"><?php echo htmlspecialchars($nom_client); ?></span>
        </div>
    </nav>

    <div style="padding: 30px 5%;">
        <h2><?php echo $result->num_rows; ?> résultat(s) trouvé(s)</h2>
        <div class="maison-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <?php while ($m = $result->fetch_assoc()): ?>
                <div class="maison-card" onclick="window.location.href='../Accueil/details.php?id=<?php echo $m['id_maison']; ?>'" style="cursor:pointer; background:white; padding:15px; border-radius:15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <img src="<?php echo htmlspecialchars($m['image']); ?>" style="width:100%; height:200px; object-fit:cover; border-radius:10px;">
                    <h3><?php echo htmlspecialchars($m['titre']); ?></h3>
                    <p style="color: #2563eb; font-weight: bold;"><?php echo number_format($m['prix'], 0, ',', ' '); ?> XOF</p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>