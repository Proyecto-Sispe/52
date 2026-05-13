<?php
session_start();
include "Inicio de Sesion Conexion.php";

if (!isset($_GET['id'])) {
    header("Location: Factura.php");
    exit();
}

$id_factura = intval($_GET['id']);

// Obtener datos de la factura
$stmt = $connection->prepare("SELECT f.*, CONCAT(mes.Nom1_usu, ' ', mes.Ape1_usu) as mesero_nombre, CONCAT(c.Nom1_usu, ' ', c.Ape1_usu) as cliente_nombre
                              FROM Factura f
                              LEFT JOIN Persona mes ON f.pkfk_mesero_id_usuario = mes.id_usuario AND f.pkfk_Tipo_doc = mes.pkfk_Tipo_doc
                              LEFT JOIN Persona c ON f.Cliente_Persona_id_usuario = c.id_usuario AND f.pkfk_cliente_tipo_doc = c.pkfk_Tipo_doc
                              WHERE f.id_factura = ?");
$stmt->bind_param("i", $id_factura);
$stmt->execute();
$result = $stmt->get_result();
$factura = $result->fetch_assoc();
$stmt->close();

if (!$factura) {
    header("Location: Factura.php");
    exit();
}

// Obtener mesas
$mesas = $connection->query("SELECT * FROM Mesa ORDER BY id_Mesa");
$fecha_hora = new DateTime($factura['Fecha_hora']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Agregar Mesa.Css">
    <title>Editar Factura</title>
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
  <h2>Editar Factura #<?php echo $factura['id_factura']; ?></h2>
  <form action="factura_crud.php?action=update" method="POST" class="grid-form">
    <input type="hidden" name="id_factura_original" value="<?php echo $factura['id_factura']; ?>">
    
    <div>
      <label>ID Factura</label>
      <input type="number" value="<?php echo $factura['id_factura']; ?>" disabled>
    </div>
    <div>
      <label>Fecha</label>
      <input type="date" name="fecha" value="<?php echo $fecha_hora->format('Y-m-d'); ?>" required>
    </div>
    <div>
      <label>Hora</label>
      <input type="time" name="hora" value="<?php echo $fecha_hora->format('H:i'); ?>" required>
    </div>
    <div>
      <label>Mesa</label>
      <select name="id_mesa" required>
        <?php while ($mesa = $mesas->fetch_assoc()): ?>
          <option value="<?php echo $mesa['id_Mesa']; ?>" <?php echo $mesa['id_Mesa'] == $factura['pkfk_id_Mesa'] ? 'selected' : ''; ?>>
            Mesa <?php echo $mesa['id_Mesa']; ?> - Capacidad: <?php echo $mesa['Capacidad']; ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div>
      <label>Mesero</label>
      <input type="text" value="<?php echo htmlspecialchars($factura['mesero_nombre'] ?? 'N/A'); ?>" disabled>
    </div>
    <div>
      <label>Cliente</label>
      <input type="text" value="<?php echo htmlspecialchars($factura['cliente_nombre'] ?? 'N/A'); ?>" disabled>
    </div>
    <div>
      <label>Total</label>
      <input type="number" name="total" value="<?php echo $factura['Total']; ?>" min="0" step="100" required>
    </div>
    <div>
      <a href="Pedidos.php?factura=<?php echo $factura['id_factura']; ?>" class="btn" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;">
        Ver/Agregar Pedidos
      </a>
    </div>
  </form>
  <div class="acciones">
    <a href="Factura.php"><button class="cancelar">Cancelar</button></a>
    <button class="guardar" onclick="document.querySelector('form').submit()">Actualizar</button>
  </div>
</section>
</body>
</html>
