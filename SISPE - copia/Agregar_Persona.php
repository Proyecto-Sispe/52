<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Agregar Persona.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Agregar Persona</title>
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
<section class="formulario">
  <h2>Registrar Nueva Persona</h2>
  <form action="persona_crud.php?action=create" method="POST">
    <div class="grid-form">
      <div>
        <label for="tipo_doc">Tipo de Documento</label>
        <select id="tipo_doc" name="tipo_doc" required>
          <option value="1">Cedula de Ciudadania</option>
          <option value="2">Tarjeta de Identidad</option>
        </select>
      </div>
      <div>
        <label for="rol">Rol</label>
        <select id="rol" name="rol" required>
          <option value="1">Administrador</option>
          <option value="2">Cocinero</option>
          <option value="3">Mesero</option>
          <option value="4">Cliente</option>
        </select>
      </div>
      <div>
        <label for="id_usuario">Numero de Identificacion</label>
        <input type="number" id="id_usuario" name="id_usuario" required>
      </div>
      <div>
        <label for="nom1">Primer Nombre</label>
        <input type="text" id="nom1" name="nom1" required>
      </div>
      <div>
        <label for="nom2">Segundo Nombre</label>
        <input type="text" id="nom2" name="nom2">
      </div>
      <div>
        <label for="ape1">Primer Apellido</label>
        <input type="text" id="ape1" name="ape1" required>
      </div>
      <div>
        <label for="ape2">Segundo Apellido</label>
        <input type="text" id="ape2" name="ape2">
      </div>
      <div>
        <label for="telefono">Telefono</label>
        <input type="number" id="telefono" name="telefono" required>
      </div>
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div>
        <label for="password">Contrasena</label>
        <input type="password" id="password" name="password" required minlength="6">
      </div>
    </div>
    <div class="acciones">
      <a href="Persona.php"><button class="cancelar" type="button">Cancelar</button></a>
      <button class="guardar" type="submit">Guardar</button>
    </div>
  </form>
</section>
</body>
</html>
