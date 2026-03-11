<?php
// connexion_bd.php - Version avec VOS identifiants InfinityFree
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "domus_db";

$db = new mysqli($servername, $username, $password, $dbname);

if ($db->connect_error) {
    die("Erreur de connexion : " . $db->connect_error);
}

$db->set_charset("utf8mb4");

// Fonction pour obtenir la connexion (pour la compatibilité avec PDO)
function getConnection() {
    global $servername, $username, $password, $dbname;
    
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Erreur de connexion PDO: " . $e->getMessage());
    }
}
?>