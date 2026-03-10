<?php
session_start();
require_once "data.php";

if (isset($_SESSION['user_id'])) {
    $id_pro = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) as total FROM rendez_vous r 
              JOIN maison m ON r.id_maison = m.id_maison 
              WHERE m.id_pro = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id_pro);
    $stmt->execute();
    echo $stmt->get_result()->fetch_assoc()['total'];
} else {
    echo "0";
}
?>