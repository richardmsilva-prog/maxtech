<?php

if (!isset($_SESSION)) {
    session_start();
}

$_SESSION = array();

if (init_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

if(!empty($_SERVER["HTTP_REFERER"])) {
    $pagina_destino = $_SERVER["HTTP_REFERER"];
} else {
    $pagina_destino = "index.php"
}

header("Location: ". $pagina_destino);
exit;
?>