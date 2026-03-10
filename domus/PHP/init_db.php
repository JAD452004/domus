<?php
require_once "data.php";

// Créer la table proprietes si elle n'existe pas
$sql = "CREATE TABLE IF NOT EXISTS proprietes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietaire_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    prix BIGINT NOT NULL,
    ville VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    chambres INT NOT NULL,
    salles_bain INT NOT NULL,
    surface INT NOT NULL,
    image VARCHAR(255),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietaire_id) REFERENCES proprietaire(id) ON DELETE CASCADE
)";

if ($db->query($sql) === TRUE) {
    echo "Table 'proprietes' créée avec succès!";
} else {
    echo "Erreur lors de la création de la table: " . $db->error;
}
?>
