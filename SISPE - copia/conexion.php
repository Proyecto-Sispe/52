<?php
// Archivo de conexion a la base de datos
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "divina_comida";

$conexion = mysqli_connect($servidor, $usuario, $password, $base_datos);

if (!$conexion) {
    die("Error de conexion: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8");

// Funcion para limpiar datos de entrada
function limpiar($conexion, $dato) {
    return mysqli_real_escape_string($conexion, trim($dato));
}

// Funcion para generar codigo de acceso de mesa
function generarCodigoMesa() {
    return strtoupper(substr(md5(uniqid()), 0, 6));
}
?>
