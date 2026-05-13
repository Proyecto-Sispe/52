<?php
session_start();
include "persona_crud.php";
$personas = readPersonas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Persona.Css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <title>Gestion de Personas</title>
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
    <h1>Gestion de Personas</h1>
    <p>Administra la informacion de clientes, empleados y administradores</p>
  </div>
  <div class="hero-boton">
    <a href="Agregar_Persona.php" class="btn">+ Agregar persona</a>
  </div>
</section>
<section class="panel">
  <div class="filtros">
    <h3>Filtros de Busqueda</h3>
    <form method="GET" action="Persona.php">
      <div class="fila-filtros">
        <div>
          <label>Tipo de Documento</label>
          <select name="tipo_doc">
            <option value="">Todos</option>
            <option value="1" <?php echo (isset($_GET['tipo_doc']) && $_GET['tipo_doc'] == '1') ? 'selected' : ''; ?>>C.C</option>
            <option value="2" <?php echo (isset($_GET['tipo_doc']) && $_GET['tipo_doc'] == '2') ? 'selected' : ''; ?>>T.I</option>
          </select>
        </div>
        <div>
          <label>Rol</label>
          <select name="rol">
            <option value="">Todos</option>
            <option value="1" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '1') ? 'selected' : ''; ?>>Administrador</option>
            <option value="2" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '2') ? 'selected' : ''; ?>>Cocinero</option>
            <option value="3" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '3') ? 'selected' : ''; ?>>Mesero</option>
            <option value="4" <?php echo (isset($_GET['rol']) && $_GET['rol'] == '4') ? 'selected' : ''; ?>>Cliente</option>
          </select>
        </div>
        <div>
          <label>Buscar</label>
          <input type="text" name="buscar" placeholder="Nombre o Identificacion" value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
        </div>
        <button type="submit" class="btn-buscar">Buscar</button>
        <a href="Persona.php" class="btn-buscar" style="text-decoration:none; text-align:center;">Limpiar</a>
      </div>
    </form>
  </div>
  <div class="tabla">
    <h3>Listado de Personas</h3>
    <table>
      <thead>
        <tr>
          <th>ID Usuario</th>
          <th>Tipo Documento</th>
          <th>Primer Nombre</th>
          <th>Segundo Nombre</th>
          <th>Primer Apellido</th>
          <th>Segundo Apellido</th>
          <th>Telefono</th>
          <th>Rol</th>
          <th>Email</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($personas) > 0): ?>
          <?php foreach ($personas as $persona): ?>
            <tr>
              <td><?php echo htmlspecialchars($persona['id_usuario']); ?></td>
              <td><span class="badge"><?php echo htmlspecialchars($persona['tipo_doc'] ?? 'N/A'); ?></span></td>
              <td><?php echo htmlspecialchars($persona['Nom1_usu']); ?></td>
              <td><?php echo htmlspecialchars($persona['Nom2_usu'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($persona['Ape1_usu']); ?></td>
              <td><?php echo htmlspecialchars($persona['Ape2_usu'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($persona['Telefono']); ?></td>
              <td><?php echo htmlspecialchars($persona['Nom_rol'] ?? 'Sin rol'); ?></td>
              <td><?php echo htmlspecialchars($persona['Correo_usu'] ?? '-'); ?></td>
              <td>
                <a href="Editar_Persona.php?id_usuario=<?php echo $persona['id_usuario']; ?>&tipo_doc=<?php echo $persona['pkfk_Tipo_doc']; ?>">
                  <button class="edit">Editar</button>
                </a>
                <button class="delete" onclick="confirmarEliminar(<?php echo $persona['id_usuario']; ?>, <?php echo $persona['pkfk_Tipo_doc']; ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="10" style="text-align: center;">No se encontraron personas</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<script>
function confirmarEliminar(id_usuario, tipo_doc) {
    if (confirm('Esta seguro de que desea eliminar esta persona?')) {
        window.location.href = 'persona_crud.php?action=delete&id_usuario=' + id_usuario + '&tipo_doc=' + tipo_doc;
    }
}
</script>
</body>
</html>
