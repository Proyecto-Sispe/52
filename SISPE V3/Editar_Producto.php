<?php
session_start();
include "Inicio de Sesion Conexion.php";

if (!isset($_GET['id'])) {
    header("Location: Productos.php");
    exit();
}

$id_menu = intval($_GET['id']);

// Obtener datos del producto
$stmt = $connection->prepare("SELECT m.*, c.nom_categoria FROM Menu m LEFT JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria WHERE m.id_menu = ?");
$stmt->bind_param("i", $id_menu);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    header("Location: Productos.php");
    exit();
}

// Obtener categorias
$categorias = $connection->query("SELECT * FROM Categoria ORDER BY nom_categoria");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Formulario Menu.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Editar Producto</title>
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
<section class="formulario-producto">
  <h2>Editar Producto #<?php echo $producto['id_menu']; ?></h2>
  <form action="producto_crud.php?action=update" method="POST" class="grid-form">
    <input type="hidden" name="id_menu_original" value="<?php echo $producto['id_menu']; ?>">
    
    <div>
      <label>Categoria</label>
      <select name="categoria" required>
        <?php while ($cat = $categorias->fetch_assoc()): ?>
          <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $cat['id_categoria'] == $producto['pkfk_id_categoria'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($cat['nom_categoria']); ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div>
      <label>ID</label>
      <input type="number" value="<?php echo $producto['id_menu']; ?>" disabled>
    </div>
    <div>
      <label>Producto</label>
      <input type="text" name="producto" value="<?php echo htmlspecialchars($producto['Productos']); ?>" required>
    </div>
    <div>
      <label>Precio</label>
      <input type="number" name="precio" value="<?php echo $producto['Precio']; ?>" required min="0" step="100">
    </div>
    <div class="full">
      <label>Descripcion</label>
      <input type="text" name="descripcion" value="<?php echo htmlspecialchars($producto['descripcion']); ?>" required>
    </div>
  </form>
  <div class="acciones">
    <a href="Productos.php"><button class="cancelar">Cancelar</button></a>
    <button class="guardar" onclick="document.querySelector('form').submit()">Actualizar</button>
  </div>
</section>
</body>
</html>
