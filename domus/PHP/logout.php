<?php
session_start();
session_destroy();
header("Location: ../CONNECTION/connexionUser.php");
exit();
?>
