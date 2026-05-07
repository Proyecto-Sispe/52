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
    <link rel="stylesheet" href="mesas.css">
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
<section class="hero-mesas">
  <div class="hero-texto">
    <h1>Gestion de Mesas</h1>
    <p>Administra estado, capacidad y control de mesas</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Mesa.php" class="btn">+ Agregar Mesa</a>
  </div>
</section>
<section class="hero">
  <div class="contenedor">
    <div class="hero-pedidos">
      <?php if (count($mesas) > 0): ?>
        <?php foreach ($mesas as $mesa): ?>
          <div class="card">
            <h3>Mesa <?php echo htmlspecialchars($mesa['id_Mesa']); ?></h3>
            <p><strong>Estado:</strong> 
              <span class="<?php echo $mesa['Estado'] == 0 ? 'estado-libre' : ($mesa['Estado'] == 1 ? 'estado-ocupada' : 'estado-reservada'); ?>">
                <?php echo getEstadoTexto($mesa['Estado']); ?>
              </span>
            </p>
            <p><strong>Capacidad:</strong> <?php echo htmlspecialchars($mesa['Capacidad']); ?> personas</p>
            <p><strong>Ubicacion:</strong> <?php echo getUbicacionTexto($mesa['Ubicacion']); ?></p>
            <div class="acciones-card">
              <?php if ($mesa['Estado'] == 0): ?>
                <a href="Agregar_Pedido.php?mesa=<?php echo $mesa['id_Mesa']; ?>" class="btn-card">Reservar</a>
              <?php else: ?>
                <span class="btn-card disabled">Ocupada</span>
              <?php endif; ?>
              <a href="Menu.php" class="btn-card-2">Ver menu</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card">
          <h3>Sin Mesas</h3>
          <p>No hay mesas registradas en el sistema.</p>
          <div class="acciones-card">
            <a href="Agregar_Mesa.php" class="btn-card">Agregar Mesa</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
</body>
</html>
