<?php
session_start();

// Destruir todas las variables de sesion
$_SESSION = array();

// Destruir la sesion
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir al login
header("Location: Inicio de Sesion.Html");
exit();
?>
