<?php
session_start();
$logueado = isset($_SESSION['logueado']) && $_SESSION['logueado'] === true;
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Inicio.Css">
    <title>Inicio SISPE</title>
    <style>
        .user-badge {
            background: linear-gradient(135deg, #f39c12, #e74c3c);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-badge i {
            font-size: 1rem;
        }
        .btn-dashboard {
            background: #27ae60;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 0.5rem;
        }
        .btn-dashboard:hover {
            background: #1e8449;
        }
        .btn-logout {
            background: #e74c3c;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-logout:hover {
            background: #c0392b;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav>
  <ul class="menu">
    <li class="logo">
      <img src="Logo 2.png" alt="Logo">
    </li>
    <li><a href="Inicio.php">Inicio</a></li>
    <li><a href="Persona.php">Personas</a></li>
    <li><a href="Vista_Mesas.php">Mesas</a></li>
    <li><a href="Menu.php">Menu</a></li>
    <li><a href="Productos.php">Productos</a></li>
    <li><a href="Factura.php">Facturas</a></li>
    <li><a href="Pedidos.php">Pedidos</a></li>
    <li class="dropdown">
      <a href="#">Roles</a>
      <ul class="submenu">
        <li><a href="Vista_Cocinero.php">Cocinero</a></li>
        <li><a href="Vista_Mesero.php">Mesero</a></li>
        <li><a href="Vista_Cliente.php">Cliente</a></li>
      </ul>
    </li>
    <?php if ($logueado): ?>
    <li class="right">
        <span class="user-badge">
            <i class="fas fa-user"></i>
            <?php echo htmlspecialchars($nombre_usuario); ?> (<?php echo htmlspecialchars($rol_usuario); ?>)
        </span>
    </li>
    <li><a href="Dashboard.php" class="btn-dashboard"><i class="fas fa-chart-line"></i> Dashboard</a></li>
    <li><a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
    <?php else: ?>
    <li class="right"><a href="Inicio de Sesion.Html">Login</a></li>
    <li><a href="Registro.Html">Registro</a></li>
    <?php endif; ?>
  </ul>
</nav>
<section class="hero">
  <div class="hero-contenido">
    <h1>Sistema de Gestion para Restaurantes</h1>
    <p>
      Administra tu restaurante de manera eficiente con SISPE.
      Control de mesas, pedidos, menu y facturacion en un solo lugar.
    </p>
    <div class="botones">
      <?php if ($logueado): ?>
      <a href="Dashboard.php" class="btn btn-principal">Ir al Dashboard</a>
      <?php else: ?>
      <a href="Inicio de Sesion.Html" class="btn btn-principal">Ingresar al Sistema</a>
      <?php endif; ?>
      <a href="#modulos" class="btn btn-secundario">Conocer mas</a>
    </div>
  </div>
  <div class="hero-icono">
    <img src="Logo.png" alt="Icono">
  </div>
</section>
<section class="modulos" id="modulos">
  <h2>Modulos del Sistema</h2>
  <div class="contenedor-modulos">
    <div class="card">
      <h3>Gestion de Personas</h3>
      <p>Administra clientes, meseros, cocineros y administradores.</p>
      <a href="Persona.php" class="btn-card">Ver mas</a>
    </div>
    <div class="card">
      <h3>Control de Mesas</h3>
      <p>Gestiona disponibilidad, capacidad y ubicacion.</p>
      <a href="Vista_Mesas.php" class="btn-card">Ver mas</a>
    </div>
    <div class="card">
      <h3>Menu Digital</h3>
      <p>Administra productos, categorias y precios.</p>
      <a href="Menu.php" class="btn-card">Ver mas</a>
    </div>
    <div class="card">
      <h3>Pedidos</h3>
      <p>Controla pedidos en tiempo real.</p>
      <a href="Pedidos.php" class="btn-card">Ver mas</a>
    </div>
    <div class="card">
      <h3>Facturacion</h3>
      <p>Genera facturas y gestiona pagos.</p>
      <a href="Factura.php" class="btn-card">Ver mas</a>
    </div>
    <div class="card">
      <h3>Productos</h3>
      <p>Gestion y modificacion de productos.</p>
      <a href="Productos.php" class="btn-card">Ver mas</a>
    </div>
  </div>
</section>
<section class="modulos">
  <h2>Acceso por Roles</h2>
  <div class="contenedor-modulos">
    <div class="card" style="border-top: 4px solid #e74c3c;">
      <h3>Panel Cocinero</h3>
      <p>Gestiona pedidos, cambia estados y controla tiempos de preparacion.</p>
      <a href="Vista_Cocinero.php" class="btn-card">Acceder</a>
    </div>
    <div class="card" style="border-top: 4px solid #27ae60;">
      <h3>Panel Mesero</h3>
      <p>Ve pedidos listos, recibe notificaciones y entrega a las mesas.</p>
      <a href="Vista_Mesero.php" class="btn-card">Acceder</a>
    </div>
    <div class="card" style="border-top: 4px solid #9b59b6;">
      <h3>Menu Cliente</h3>
      <p>Acceso digital al menu, realiza pedidos y ve el estado.</p>
      <a href="Vista_Cliente.php" class="btn-card">Acceder</a>
    </div>
  </div>
</section>
<footer class="footer">
  <div class="footer-contenido">
    <div class="footer-izq">
      <h3>SISPE</h3>
      <p>Sistema de Gestion para Restaurantes</p>
    </div>
    <div class="footer-der">
      <p>2024 SISPE. Todos los derechos reservados.</p>
    </div>
  </div>
</footer>
</body>
</html>
