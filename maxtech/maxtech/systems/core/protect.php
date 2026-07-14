<?php

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['id']) || !isset($_SESSION['nome'])) {
    header("Location: login.php?erro=autenticacao");
    exit;
}
?>