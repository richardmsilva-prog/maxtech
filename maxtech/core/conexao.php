<?php

$host    = "127.0.0.1";
$usuario = "root"; 
$senha   = "";
$banco   = "maxtech_db";
$porta   = 3306;

$mysqli = new mysqli($host, $usuario, $senha, $banco, $porta);

$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("Falha ao conectar ao MySQL:  (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}
?>