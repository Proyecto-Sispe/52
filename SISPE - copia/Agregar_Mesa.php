<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Agregar Mesa.Css">
    <title>Agregar Mesa</title>
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
  <h2>Registrar Nueva Mesa</h2>
  <form action="mesa_crud.php?action=create" method="POST" class="grid-form">
    <div>
      <label>Numero de Mesa</label>
      <input type="number" name="id_mesa" placeholder="Ingresar numero de mesa" required min="1">
    </div>
    <div>
      <label>Estado</label>
      <select name="estado" required>
        <option value="0">Libre</option>
        <option value="1">Ocupada</option>
        <option value="2">Reservada</option>
      </select>
    </div>
    <div>
      <label>Capacidad</label>
      <input type="number" name="capacidad" placeholder="Cantidad de personas" required min="1">
    </div>
    <div>
      <label>Ubicacion</label>
      <select name="ubicacion" required>
        <option value="1">Piso 1</option>
        <option value="2">Piso 2</option>
        <option value="3">Terraza</option>
      </select>
    </div>
    <div class="acciones" style="grid-column: 1 / -1;">
      <a href="Mesas2.php"><button type="button" class="cancelar">Cancelar</button></a>
      <button type="submit" class="guardar">Guardar</button>
    </div>
  </form>
</section>
</body>
</html>
