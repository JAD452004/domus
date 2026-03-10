<?php
require_once "data.php";

// Récupérer toutes les propriétés avec les infos du propriétaire

$query = "SELECT m.*, pr.NOMPRO, pr.PRENPRO, pr.EMAILPRO 
          FROM Maison m 
          JOIN Proprietaire pr ON m.CNI_PRO = pr.CNI_PRO 
          ORDER BY m.id_lot DESC";

$result = $db->query($query);
$proprietes = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $proprietes[] = $row;
    }
}
?>
