<?php
// Logout.php - Encerra a sessão do usuário

if (!isset($_SESSION)) {
    session_start();
}

session_unset();
session_destroy();

header("Location: index.php");
exit;
?>