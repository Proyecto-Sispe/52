<?php
session_start();
include "Inicio de Sesion Conexion.php";

if (!isset($_GET['factura']) || !isset($_GET['menu'])) {
    header("Location: Pedidos.php");
    exit();
}

$id_factura = intval($_GET['factura']);
$id_menu = intval($_GET['menu']);

// Obtener datos del pedido
$stmt = $connection->prepare("SELECT p.*, m.Productos, m.Precio
                              FROM Pedido p
                              LEFT JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                              WHERE p.pkfk_id_factura = ? AND p.pkfk_id_menu = ?");
$stmt->bind_param("ii", $id_factura, $id_menu);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header("Location: Pedidos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Agregar Mesa.Css">
    <title>Editar Pedido</title>
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
  <h2>Editar Pedido</h2>
  <form action="pedido_crud.php?action=update" method="POST" class="grid-form">
    <input type="hidden" name="id_factura_original" value="<?php echo $id_factura; ?>">
    <input type="hidden" name="id_menu_original" value="<?php echo $id_menu; ?>">
    
    <div>
      <label>Factura</label>
      <input type="text" value="Factura #<?php echo $id_factura; ?>" disabled>
    </div>
    <div>
      <label>Producto</label>
      <input type="text" value="<?php echo htmlspecialchars($pedido['Productos']); ?>" disabled>
    </div>
    <div>
      <label>Precio Unitario</label>
      <input type="text" value="$<?php echo number_format($pedido['Precio'], 0, ',', '.'); ?>" disabled>
      <input type="hidden" id="precio" value="<?php echo $pedido['Precio']; ?>">
    </div>
    <div>
      <label>Cantidad</label>
      <input type="number" name="cantidad" id="cantidad" value="<?php echo $pedido['cantidad']; ?>" min="1" required onchange="actualizarTotal()">
    </div>
    <div>
      <label>Total Estimado</label>
      <input type="text" id="total-estimado" value="$<?php echo number_format($pedido['valor_venta'], 0, ',', '.'); ?>" disabled>
    </div>
    <div class="full">
      <label>Observaciones</label>
      <textarea name="observaciones" placeholder="Ej: Sin cebolla, extra queso..." rows="2" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo htmlspecialchars($pedido['observaciones'] ?? ''); ?></textarea>
    </div>
  </form>
  <div class="acciones">
    <a href="Pedidos.php?factura=<?php echo $id_factura; ?>"><button class="cancelar">Cancelar</button></a>
    <button class="guardar" onclick="document.querySelector('form').submit()">Actualizar</button>
  </div>
</section>

<script>
function actualizarTotal() {
    var precio = parseFloat(document.getElementById('precio').value) || 0;
    var cantidad = parseInt(document.getElementById('cantidad').value) || 0;
    var total = precio * cantidad;
    document.getElementById('total-estimado').value = '$' + total.toLocaleString('es-CO');
}
</script>
</body>
</html>
