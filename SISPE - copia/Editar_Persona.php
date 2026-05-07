<?php
session_start();
include "Inicio de Sesion Conexion.php";

if (!isset($_GET['id_usuario']) || !isset($_GET['tipo_doc'])) {
    header("Location: Persona.php");
    exit();
}

$id_usuario = intval($_GET['id_usuario']);
$tipo_doc = intval($_GET['tipo_doc']);

// Obtener datos de la persona
$stmt = $connection->prepare("SELECT p.*, td.tipo_doc as tipo_doc_nombre, r.Nom_rol, r.idRol, u.Correo_usu, u.Password
                              FROM Persona p
                              LEFT JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
                              LEFT JOIN Persona_has_Rol pr ON p.id_usuario = pr.pkfk_id_usuario AND p.pkfk_Tipo_doc = pr.pkfk_Tipo_doc
                              LEFT JOIN Rol r ON pr.pkfk_idRol = r.idRol
                              LEFT JOIN Usuario u ON p.id_usuario = u.pkfk_id_usuario AND p.pkfk_Tipo_doc = u.pkfk_Tipo_doc
                              WHERE p.id_usuario = ? AND p.pkfk_Tipo_doc = ?");
$stmt->bind_param("ii", $id_usuario, $tipo_doc);
$stmt->execute();
$result = $stmt->get_result();
$persona = $result->fetch_assoc();
$stmt->close();

if (!$persona) {
    header("Location: Persona.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Agregar Persona.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Editar Persona</title>
</head>
<body>
<nav>
  <ul class="menu">
    <li class="logo">
      <img src="Logo 2.png" alt="Logo">
    </li>
    <li><a href="Inicio.html">Inicio</a></li>
    <li><a href="Persona.php">Personas</a></li>
    <li><a href="Mesas.php">Mesas</a></li>
    <li><a href="Mesas2.php">Gestion de Mesas</a></li>
    <li><a href="Menu.php">Menu</a></li>
    <li><a href="Productos.php">Productos</a></li>
    <li><a href="Factura.php">Facturas</a></li>
    <li><a href="Pedidos.php">Pedidos</a></li>
    <li class="right"><a href="Inicio de Sesion.Html">Login</a></li>
    <li><a href="Registro.Html">Registro</a></li>
  </ul>
</nav>
<section class="formulario">
  <h2>Editar Persona</h2>
  <form action="persona_crud.php?action=update" method="POST">
    <input type="hidden" name="tipo_doc_original" value="<?php echo $tipo_doc; ?>">
    <input type="hidden" name="id_usuario_original" value="<?php echo $id_usuario; ?>">
    
    <div class="grid-form">
      <div>
        <label for="tipo_doc">Tipo de Documento</label>
        <input type="text" id="tipo_doc" value="<?php echo htmlspecialchars($persona['tipo_doc_nombre']); ?>" disabled>
      </div>
      <div>
        <label for="id_usuario">Numero de Identificacion</label>
        <input type="text" id="id_usuario" value="<?php echo htmlspecialchars($persona['id_usuario']); ?>" disabled>
      </div>
      <div>
        <label for="nom1">Primer Nombre</label>
        <input type="text" id="nom1" name="nom1" value="<?php echo htmlspecialchars($persona['Nom1_usu']); ?>" required>
      </div>
      <div>
        <label for="nom2">Segundo Nombre</label>
        <input type="text" id="nom2" name="nom2" value="<?php echo htmlspecialchars($persona['Nom2_usu'] ?? ''); ?>">
      </div>
      <div>
        <label for="ape1">Primer Apellido</label>
        <input type="text" id="ape1" name="ape1" value="<?php echo htmlspecialchars($persona['Ape1_usu']); ?>" required>
      </div>
      <div>
        <label for="ape2">Segundo Apellido</label>
        <input type="text" id="ape2" name="ape2" value="<?php echo htmlspecialchars($persona['Ape2_usu'] ?? ''); ?>">
      </div>
      <div>
        <label for="telefono">Telefono</label>
        <input type="number" id="telefono" name="telefono" value="<?php echo htmlspecialchars($persona['Telefono']); ?>" required>
      </div>
      <div>
        <label for="rol">Rol Actual</label>
        <input type="text" id="rol" value="<?php echo htmlspecialchars($persona['Nom_rol'] ?? 'Sin rol'); ?>" disabled>
      </div>
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($persona['Correo_usu'] ?? ''); ?>">
      </div>
      <div>
        <label for="password">Nueva Contrasena (dejar vacio para mantener)</label>
        <input type="password" id="password" name="password" minlength="6" placeholder="Nueva contrasena...">
      </div>
    </div>
    <div class="acciones">
      <a href="Persona.php"><button class="cancelar" type="button">Cancelar</button></a>
      <button class="guardar" type="submit">Actualizar</button>
    </div>
  </form>
</section>
</body>
</html>
