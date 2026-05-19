<?php
session_start();

include "Inicio de Sesion Conexion.php";

// Validar si los campos están vacíos
if (!isset($_POST["documento"]) || !isset($_POST["email"]) || !isset($_POST["password"])) {
    echo "<script>alert('Por favor, complete todos los campos.'); history.back();</script>";
    exit();
}

$tipo_doc = trim($_POST["documento"]);
$email = trim($_POST["email"]);
$password = trim($_POST["password"]);

// Validar que los campos no estén vacíos
if (empty($tipo_doc) || empty($email) || empty($password)) {
    echo "<script>alert('Por favor, complete todos los campos.'); history.back();</script>";
    exit();
}

// Convertir tipo de documento a ID
$tipo_doc_map = array(
    "Cédula de Ciudadanía" => 1,
    "Tarjeta de Identidad" => 2,
    "Cédula de Extranjería" => 3
);
$tipo_doc_id = isset($tipo_doc_map[$tipo_doc]) ? $tipo_doc_map[$tipo_doc] : null;

if ($tipo_doc_id === null) {
    echo "<script>alert('Tipo de documento no válido.'); history.back();</script>";
    exit();
}

// Usar prepared statement para mayor seguridad
$sql = "SELECT u.pkfk_id_usuario, u.Correo_usu, u.pkfk_Tipo_doc, pr.pkfk_idRol, r.Nom_rol, 
               p.Nom1_usu, p.Ape1_usu
        FROM Usuario u
        INNER JOIN Persona p ON u.pkfk_id_usuario = p.id_usuario AND u.pkfk_Tipo_doc = p.pkfk_Tipo_doc
        LEFT JOIN Persona_has_Rol pr ON u.pkfk_id_usuario = pr.pkfk_id_usuario AND u.pkfk_Tipo_doc = pr.pkfk_Tipo_doc
        LEFT JOIN Rol r ON pr.pkfk_idRol = r.idRol
        WHERE u.Correo_usu=? AND u.Password=? AND u.pkfk_Tipo_doc=?";

$stmt = $connection->prepare($sql);
if ($stmt === false) {
    echo "<script>alert('Error en la consulta. Intente más tarde.'); history.back();</script>";
    exit();
}

$stmt->bind_param("ssi", $email, $password, $tipo_doc_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Usuario encontrado
    $row = $result->fetch_assoc();
    $_SESSION["email"] = $email;
    $_SESSION["tipo_doc"] = $tipo_doc;
    $_SESSION["tipo_doc_id"] = $tipo_doc_id;
    $_SESSION["user_id"] = $row["pkfk_id_usuario"];
    $_SESSION["rol"] = $row["Nom_rol"];
    $_SESSION["rol_id"] = $row["pkfk_idRol"];
    $_SESSION["nombre"] = $row["Nom1_usu"] . " " . $row["Ape1_usu"];
    $_SESSION["logueado"] = true;
    
    // Redirección según el rol
    $rol = $row["Nom_rol"];
    $nombre_usuario = htmlspecialchars($row["Nom1_usu"] . " " . $row["Ape1_usu"]);
    
    switch ($rol) {
        case "Administrador":
            echo "<script>alert('Bienvenido Administrador: " . $nombre_usuario . "'); window.location.href='Dashboard.php';</script>";
            break;
        case "Cocinero":
            echo "<script>alert('Bienvenido Cocinero: " . $nombre_usuario . "'); window.location.href='Vista_Cocinero.php';</script>";
            break;
        case "Mesero":
            echo "<script>alert('Bienvenido Mesero: " . $nombre_usuario . "'); window.location.href='Vista_Mesero.php';</script>";
            break;
        case "Mesa":
        case "Cliente":
            echo "<script>alert('Bienvenido Cliente: " . $nombre_usuario . "'); window.location.href='Vista_Cliente.php';</script>";
            break;
        default:
            echo "<script>alert('Bienvenido: " . $nombre_usuario . "'); window.location.href='Inicio.php';</script>";
            break;
    }
    exit();
} else {
    // Usuario no encontrado o credenciales incorrectas
    echo "<script>alert('El usuario no existe o las credenciales son incorrectas.'); history.back();</script>";
}

$stmt->close();
$connection->close();
?>
