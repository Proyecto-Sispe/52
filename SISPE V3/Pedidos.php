<?php
session_start();
include "pedido_crud.php";
$pedidos = readPedidos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Persona.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Gestion de Pedidos</title>
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
    <h1>Gestion de Pedidos</h1>
    <p>Administra los pedidos de las facturas</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Pedido.php" class="btn">+ Agregar pedido</a>
  </div>
</section>
<section class="panel">
  <div class="filtros">
    <h3>Filtros de Busqueda</h3>
    <form method="GET" action="Pedidos.php">
      <div class="fila-filtros">
        <div>
          <label>ID Factura</label>
          <input type="number" name="factura" placeholder="Numero de factura" value="<?php echo isset($_GET['factura']) ? htmlspecialchars($_GET['factura']) : ''; ?>">
        </div>
        <div>
          <label>Buscar</label>
          <input type="text" name="buscar" placeholder="Producto o ID Factura" value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
        </div>
        <button type="submit" class="btn-buscar">Buscar</button>
        <a href="Pedidos.php" class="btn-buscar" style="text-decoration:none; text-align:center;">Limpiar</a>
      </div>
    </form>
  </div>
  <div class="tabla">
    <h3>Listado de Pedidos <?php echo isset($_GET['factura']) ? '- Factura #' . htmlspecialchars($_GET['factura']) : ''; ?></h3>
    <table>
      <thead>
        <tr>
          <th>ID Factura</th>
          <th>Producto</th>
          <th>Precio Unit.</th>
          <th>Cantidad</th>
          <th>Observaciones</th>
          <th>Valor Venta</th>
          <th>Mesa</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($pedidos) > 0): ?>
          <?php foreach ($pedidos as $pedido): ?>
            <tr>
              <td><?php echo htmlspecialchars($pedido['pkfk_id_factura']); ?></td>
              <td><?php echo htmlspecialchars($pedido['Productos']); ?></td>
              <td>$<?php echo number_format($pedido['Precio'], 0, ',', '.'); ?></td>
              <td><?php echo htmlspecialchars($pedido['cantidad']); ?></td>
              <td><?php echo htmlspecialchars($pedido['observaciones'] ?? '-'); ?></td>
              <td>$<?php echo number_format($pedido['valor_venta'], 0, ',', '.'); ?></td>
              <td><?php echo htmlspecialchars($pedido['pkfk_id_Mesa'] ?? 'N/A'); ?></td>
              <td>
                <a href="Editar_Pedido.php?factura=<?php echo $pedido['pkfk_id_factura']; ?>&menu=<?php echo $pedido['pkfk_id_menu']; ?>">
                  <button class="edit">Editar</button>
                </a>
                <button class="delete" onclick="confirmarEliminar(<?php echo $pedido['pkfk_id_factura']; ?>, <?php echo $pedido['pkfk_id_menu']; ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align: center;">No se encontraron pedidos</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
function confirmarEliminar(factura, menu) {
    if (confirm('Esta seguro de que desea eliminar este pedido?')) {
        window.location.href = 'pedido_crud.php?action=delete&factura=' + factura + '&menu=' + menu;
    }
}
</script>
</body>
</html>
