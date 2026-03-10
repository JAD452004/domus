<?php
session_start();
require_once "data.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $id_client = $_SESSION['user_id'];
    $id_maison = intval($_POST['id_maison']);
    $date = $_POST['date_rdv'];
    $heure = $_POST['heure_rdv'];

    $sql = "INSERT INTO rendez_vous (id_client, id_maison, date_rdv, heure_rdv) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiss", $id_client, $id_maison, $date, $heure);

    if ($stmt->execute()) {
        // REDIRECTION DIRECTE DANS LE MÊME DOSSIER
        header("Location: details.php?id=$id_maison&success=1");
        exit();
    } else {
        echo "Erreur SQL : " . $db->error;
    }
} else {
    echo "Veuillez vous connecter pour prendre RDV.";
}
?>