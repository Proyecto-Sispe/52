<?php
session_start();
include "producto_crud.php";
$categorias = getCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Formulario Menu.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Agregar Producto</title>
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
  <h2>Agregar Producto</h2>
  <form action="producto_crud.php?action=create" method="POST" class="grid-form">
    <div>
      <label>Categoria</label>
      <select name="categoria" required>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?php echo $cat['id_categoria']; ?>"><?php echo htmlspecialchars($cat['nom_categoria']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>ID</label>
      <input type="number" name="id_menu" placeholder="Ej: 1" required min="1">
    </div>
    <div>
      <label>Producto</label>
      <input type="text" name="producto" placeholder="Ej: Hamburguesa" required>
    </div>
    <div>
      <label>Precio</label>
      <input type="number" name="precio" placeholder="Ej: 15000" required min="0" step="100">
    </div>
    <div class="full">
      <label>Descripcion</label>
      <input type="text" name="descripcion" placeholder="Descripcion del producto" required>
    </div>
  </form>
  <div class="acciones">
    <a href="Productos.php"><button class="cancelar">Cancelar</button></a>
    <button class="guardar" onclick="document.querySelector('form').submit()">Guardar</button>
  </div>
</section>
</body>
</html>
