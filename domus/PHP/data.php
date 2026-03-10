<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "domus_db";

$db = new mysqli($servername, $username, $password, $dbname);

if ($db->connect_error) {
    die("Erreur de connexion : " . $db->connect_error);
}

$db->set_charset("utf8mb4");
?>