<?php
session_start();
include "producto_crud.php";
$productos = readProductos();
$categorias = getCategorias();

// Agrupar productos por categoria
$productosPorCategoria = [];
foreach ($productos as $producto) {
    $catId = $producto['pkfk_id_categoria'];
    $catNombre = $producto['nom_categoria'] ?? 'Sin categoria';
    if (!isset($productosPorCategoria[$catNombre])) {
        $productosPorCategoria[$catNombre] = [];
    }
    $productosPorCategoria[$catNombre][] = $producto;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Menu Estilo.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Menu</title>
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
<section class="hero">
  <div class="hero-texto">
    <h1>Menu de Comida Rapida</h1>
    <p>Descubre nuestros deliciosos productos</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Producto.php" class="btn">+Agregar Producto</a>
  </div>
</section>
<section class="menu-comida">
  <?php foreach ($productosPorCategoria as $categoria => $items): ?>
    <div class="categoria">
      <h3><?php echo htmlspecialchars($categoria); ?></h3>
      <div class="items">
        <?php foreach ($items as $item): ?>
          <div class="item">
            <h4><?php echo htmlspecialchars($item['Productos']); ?></h4>
            <p class="desc"><?php echo htmlspecialchars($item['descripcion']); ?></p>
            <p class="precio">$<?php echo number_format($item['Precio'], 0, ',', '.'); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
  
  <?php if (empty($productosPorCategoria)): ?>
    <div class="categoria">
      <h3>Sin Productos</h3>
      <div class="items">
        <div class="item">
          <h4>No hay productos</h4>
          <p class="desc">Agrega productos desde la seccion de gestion.</p>
          <a href="Agregar_Producto.php" class="precio">Agregar</a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>
</body>
</html>
