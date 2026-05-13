<?php
session_start();
include "producto_crud.php";
$productos = readProductos();
$categorias = getCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Productos Menu.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Gestion Productos</title>
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
    <h1>Gestion de Productos</h1>
    <p>Administra la informacion, descripcion y precio de los productos</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Producto.php" class="btn">+Agregar productos</a>
  </div>
</section>
<section class="panel">
  <div class="filtros">
    <h3>Filtros de Busqueda</h3>
    <form method="GET" action="Productos.php">
      <div class="fila-filtros">
        <div>
          <label>Categorias</label>
          <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $cat): ?>
              <option value="<?php echo $cat['id_categoria']; ?>" <?php echo (isset($_GET['categoria']) && $_GET['categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['nom_categoria']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Buscar</label>
          <input type="text" name="buscar" placeholder="Nombre o ID..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
        </div>
        <button type="submit" class="btn-buscar">Buscar</button>
        <a href="Productos.php" class="btn-buscar" style="text-decoration:none; text-align:center;">Limpiar</a>
      </div>
    </form>
  </div>
  <div class="tabla">
    <h3>Listado de Productos</h3>
    <table>
      <thead>
        <tr>
          <th>ID Producto</th>
          <th>Producto</th>
          <th>Precio</th>
          <th>Descripcion</th>
          <th>Categoria</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($productos) > 0): ?>
          <?php foreach ($productos as $producto): ?>
            <tr>
              <td><?php echo htmlspecialchars($producto['id_menu']); ?></td>
              <td><?php echo htmlspecialchars($producto['Productos']); ?></td>
              <td>$<?php echo number_format($producto['Precio'], 0, ',', '.'); ?></td>
              <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
              <td><?php echo htmlspecialchars($producto['nom_categoria'] ?? 'Sin categoria'); ?></td>
              <td>
                <a href="Editar_Producto.php?id=<?php echo $producto['id_menu']; ?>">
                  <button class="edit">Editar</button>
                </a>
                <button class="delete" onclick="confirmarEliminar(<?php echo $producto['id_menu']; ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center;">No se encontraron productos</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
function confirmarEliminar(id) {
    if (confirm('Esta seguro de que desea eliminar este producto?')) {
        window.location.href = 'producto_crud.php?action=delete&id=' + id;
    }
}
</script>
</body>
</html>
