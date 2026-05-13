<?php
session_start();
include "factura_crud.php";
$facturas = readFacturas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Persona.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Gestion de Facturas</title>
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
    <h1>Gestion de Facturas</h1>
    <p>Administra la informacion de las facturas generadas</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Factura.php" class="btn">+ Agregar factura</a>
  </div>
</section>
<section class="panel">
  <div class="filtros">
    <h3>Filtros de Busqueda</h3>
    <form method="GET" action="Factura.php">
      <div class="fila-filtros">
        <div>
          <label>Buscar</label>
          <input type="text" name="buscar" placeholder="ID Factura o Documento Cliente" value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
        </div>
        <div>
          <label>Fecha</label>
          <input type="date" name="fecha" value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>">
        </div>
        <button type="submit" class="btn-buscar">Buscar</button>
        <a href="Factura.php" class="btn-buscar" style="text-decoration:none; text-align:center;">Limpiar</a>
      </div>
    </form>
  </div>
  <div class="tabla">
    <h3>Listado de Facturas</h3>
    <table>
      <thead>
        <tr>
          <th>ID Factura</th>
          <th>Fecha</th>
          <th>Hora</th>
          <th>Mesa</th>
          <th>Mesero</th>
          <th>Doc. Cliente</th>
          <th>Cliente</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($facturas) > 0): ?>
          <?php foreach ($facturas as $factura): ?>
            <?php 
              $fecha_hora = new DateTime($factura['Fecha_hora']);
            ?>
            <tr>
              <td><?php echo htmlspecialchars($factura['id_factura']); ?></td>
              <td><?php echo $fecha_hora->format('d/m/Y'); ?></td>
              <td><?php echo $fecha_hora->format('H:i:s'); ?></td>
              <td><?php echo htmlspecialchars($factura['pkfk_id_Mesa']); ?></td>
              <td><?php echo htmlspecialchars($factura['mesero_nombre'] ?? 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($factura['cliente_documento'] ?? 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($factura['cliente_nombre'] ?? 'N/A'); ?></td>
              <td>$<?php echo number_format($factura['Total'], 0, ',', '.'); ?></td>
              <td>
                <a href="Editar_Factura.php?id=<?php echo $factura['id_factura']; ?>">
                  <button class="edit">Editar</button>
                </a>
                <a href="Pedidos.php?factura=<?php echo $factura['id_factura']; ?>">
                  <button class="edit">Pedidos</button>
                </a>
                <button class="delete" onclick="confirmarEliminar(<?php echo $factura['id_factura']; ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" style="text-align: center;">No se encontraron facturas</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
function confirmarEliminar(id) {
    if (confirm('Esta seguro de que desea eliminar esta factura? Se eliminaran tambien todos los pedidos asociados.')) {
        window.location.href = 'factura_crud.php?action=delete&id=' + id;
    }
}
</script>
</body>
</html>
