<?php
session_start();
include "mesa_crud.php";
$mesas = readMesas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Mesas 2.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Gestion de Mesas</title>
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
    <h1>Gestion de Mesas</h1>
    <p>Administra la informacion de las Mesas</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Mesa.php" class="btn">+ Agregar Mesa</a>
  </div>
</section>
<section class="panel">
  <div class="filtros">
    <h3>Filtros de Busqueda</h3>
    <form method="GET" action="Mesas2.php">
      <div class="fila-filtros">
        <div>
          <label>Buscar</label>
          <input type="number" name="buscar" placeholder="Numero de Mesa" value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
        </div>
        <div>
          <label>Estado</label>
          <select name="estado">
            <option value="">Todos</option>
            <option value="0" <?php echo (isset($_GET['estado']) && $_GET['estado'] === '0') ? 'selected' : ''; ?>>Libre</option>
            <option value="1" <?php echo (isset($_GET['estado']) && $_GET['estado'] === '1') ? 'selected' : ''; ?>>Ocupada</option>
            <option value="2" <?php echo (isset($_GET['estado']) && $_GET['estado'] === '2') ? 'selected' : ''; ?>>Reservada</option>
          </select>
        </div>
        <button type="submit" class="btn-buscar">Buscar</button>
        <a href="Mesas2.php" class="btn-buscar" style="text-decoration:none; text-align:center;">Limpiar</a>
      </div>
    </form>
  </div>
  <div class="tabla">
    <h3>Listado de Mesas</h3>
    <table>
      <thead>
        <tr>
          <th>ID Mesa</th>
          <th>Estado</th>
          <th>Capacidad</th>
          <th>Ubicacion</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($mesas) > 0): ?>
          <?php foreach ($mesas as $mesa): ?>
            <tr>
              <td><?php echo htmlspecialchars($mesa['id_Mesa']); ?></td>
              <td>
                <span class="badge <?php echo $mesa['Estado'] == 0 ? 'libre' : ($mesa['Estado'] == 1 ? 'ocupada' : 'reservada'); ?>">
                  <?php echo getEstadoTexto($mesa['Estado']); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($mesa['Capacidad']); ?> personas</td>
              <td><?php echo getUbicacionTexto($mesa['Ubicacion']); ?></td>
              <td>
                <a href="Editar_Mesa.php?id=<?php echo $mesa['id_Mesa']; ?>">
                  <button class="edit">Editar</button>
                </a>
                <button class="delete" onclick="confirmarEliminar(<?php echo $mesa['id_Mesa']; ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align: center;">No se encontraron mesas</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
function confirmarEliminar(id) {
    if (confirm('Esta seguro de que desea eliminar esta mesa?')) {
        window.location.href = 'mesa_crud.php?action=delete&id=' + id;
    }
}
</script>
</body>
</html>
