<?php
session_start();
include "Inicio de Sesion Conexion.php";

if (!isset($_GET['id'])) {
    header("Location: Mesas2.php");
    exit();
}

$id_mesa = intval($_GET['id']);

// Obtener datos de la mesa
$stmt = $connection->prepare("SELECT * FROM Mesa WHERE id_Mesa = ?");
$stmt->bind_param("i", $id_mesa);
$stmt->execute();
$result = $stmt->get_result();
$mesa = $result->fetch_assoc();
$stmt->close();

if (!$mesa) {
    header("Location: Mesas2.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Agregar Mesa.Css">
    <title>Editar Mesa</title>
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
<section class="formulario-mesa">
  <h2>Editar Mesa #<?php echo $mesa['id_Mesa']; ?></h2>
  <form action="mesa_crud.php?action=update" method="POST" class="grid-form">
    <input type="hidden" name="id_mesa_original" value="<?php echo $mesa['id_Mesa']; ?>">
    
    <div>
      <label>Numero de Mesa</label>
      <input type="number" value="<?php echo $mesa['id_Mesa']; ?>" disabled>
    </div>
    <div>
      <label>Estado</label>
      <select name="estado" required>
        <option value="0" <?php echo $mesa['Estado'] == 0 ? 'selected' : ''; ?>>Libre</option>
        <option value="1" <?php echo $mesa['Estado'] == 1 ? 'selected' : ''; ?>>Ocupada</option>
        <option value="2" <?php echo $mesa['Estado'] == 2 ? 'selected' : ''; ?>>Reservada</option>
      </select>
    </div>
    <div>
      <label>Capacidad</label>
      <input type="number" name="capacidad" value="<?php echo $mesa['Capacidad']; ?>" required min="1">
    </div>
    <div>
      <label>Ubicacion</label>
      <select name="ubicacion" required>
        <option value="1" <?php echo $mesa['Ubicacion'] == 1 ? 'selected' : ''; ?>>Piso 1</option>
        <option value="2" <?php echo $mesa['Ubicacion'] == 2 ? 'selected' : ''; ?>>Piso 2</option>
        <option value="3" <?php echo $mesa['Ubicacion'] == 3 ? 'selected' : ''; ?>>Terraza</option>
      </select>
    </div>
    <div class="acciones" style="grid-column: 1 / -1;">
      <a href="Mesas2.php"><button type="button" class="cancelar">Cancelar</button></a>
      <button type="submit" class="guardar">Actualizar</button>
    </div>
  </form>
</section>
</body>
</html>
