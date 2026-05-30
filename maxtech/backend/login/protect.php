<?php
// Protect.php - Verifica se o usuário está autenticado

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['id']) || !isset($_SESSION['nome'])) {
    header("Location: index.php");
    exit;
}
?>