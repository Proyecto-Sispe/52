<?php
session_start();
include "pedido_crud.php";
$menus = getMenus();
$facturas = getFacturasActivas();

// Si viene de la pagina de mesas
$mesa_preseleccionada = isset($_GET['mesa']) ? intval($_GET['mesa']) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Agregar Mesa.Css">
    <title>Agregar Pedido</title>
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
  <h2>Registrar Nuevo Pedido</h2>
  <form action="pedido_crud.php?action=create" method="POST" class="grid-form">
    <div>
      <label>Factura</label>
      <select name="id_factura" required>
        <option value="">Seleccione una factura</option>
        <?php foreach ($facturas as $factura): ?>
          <option value="<?php echo $factura['id_factura']; ?>" <?php echo ($mesa_preseleccionada && $factura['pkfk_id_Mesa'] == $mesa_preseleccionada) ? 'selected' : ''; ?>>
            Factura #<?php echo $factura['id_factura']; ?> - Mesa <?php echo $factura['pkfk_id_Mesa']; ?>
          </option>
        <?php endforeach; ?>
        <?php if (empty($facturas)): ?>
          <option value="" disabled>No hay facturas activas. Cree una primero.</option>
        <?php endif; ?>
      </select>
    </div>
    <div>
      <label>Producto</label>
      <select name="id_menu" id="producto-select" required onchange="actualizarPrecio()">
        <option value="">Seleccione un producto</option>
        <?php 
        $categoria_actual = '';
        foreach ($menus as $menu): 
          if ($menu['nom_categoria'] != $categoria_actual):
            if ($categoria_actual != '') echo '</optgroup>';
            $categoria_actual = $menu['nom_categoria'];
            echo '<optgroup label="' . htmlspecialchars($categoria_actual) . '">';
          endif;
        ?>
          <option value="<?php echo $menu['id_menu']; ?>" data-precio="<?php echo $menu['Precio']; ?>">
            <?php echo htmlspecialchars($menu['Productos']); ?> - $<?php echo number_format($menu['Precio'], 0, ',', '.'); ?>
          </option>
        <?php endforeach; ?>
        <?php if ($categoria_actual != '') echo '</optgroup>'; ?>
      </select>
    </div>
    <div>
      <label>Cantidad</label>
      <input type="number" name="cantidad" id="cantidad" value="1" min="1" required onchange="actualizarTotal()">
    </div>
    <div>
      <label>Precio Unitario</label>
      <input type="text" id="precio-unitario" value="$0" disabled>
    </div>
    <div>
      <label>Total Estimado</label>
      <input type="text" id="total-estimado" value="$0" disabled>
    </div>
    <div class="full">
      <label>Observaciones</label>
      <textarea name="observaciones" placeholder="Ej: Sin cebolla, extra queso..." rows="2" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
    </div>
  </form>
  <div class="acciones">
    <a href="Pedidos.php"><button class="cancelar">Cancelar</button></a>
    <button class="guardar" onclick="document.querySelector('form').submit()">Guardar</button>
  </div>
</section>

<script>
function actualizarPrecio() {
    var select = document.getElementById('producto-select');
    var selected = select.options[select.selectedIndex];
    var precio = selected.dataset.precio || 0;
    document.getElementById('precio-unitario').value = '$' + Number(precio).toLocaleString('es-CO');
    actualizarTotal();
}

function actualizarTotal() {
    var select = document.getElementById('producto-select');
    var selected = select.options[select.selectedIndex];
    var precio = parseFloat(selected.dataset.precio) || 0;
    var cantidad = parseInt(document.getElementById('cantidad').value) || 0;
    var total = precio * cantidad;
    document.getElementById('total-estimado').value = '$' + total.toLocaleString('es-CO');
}
</script>
</body>
</html>
