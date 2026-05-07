<?php
session_start();
include "factura_crud.php";
$meseros = getMeseros();
$clientes = getClientes();
$mesas = getMesasDisponibles();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <link rel="stylesheet" href="Agregar Mesa.Css">
    <title>Agregar Factura</title>
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
  <h2>Registrar Nueva Factura</h2>
  <form action="factura_crud.php?action=create" method="POST" class="grid-form">
    <div>
      <label>ID Factura</label>
      <input type="number" name="id_factura" placeholder="Ingrese un numero de factura unico" required min="1">
    </div>
    <div>
      <label>Fecha</label>
      <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div>
      <label>Hora</label>
      <input type="time" name="hora" value="<?php echo date('H:i'); ?>" required>
    </div>
    <div>
      <label>Mesa</label>
      <select name="id_mesa" required>
        <option value="">Seleccione una mesa</option>
        <?php foreach ($mesas as $mesa): ?>
          <option value="<?php echo $mesa['id_Mesa']; ?>">
            Mesa <?php echo $mesa['id_Mesa']; ?> - Capacidad: <?php echo $mesa['Capacidad']; ?>
          </option>
        <?php endforeach; ?>
        <?php if (empty($mesas)): ?>
          <option value="" disabled>No hay mesas disponibles</option>
        <?php endif; ?>
      </select>
    </div>
    <div>
      <label>Mesero</label>
      <select name="id_mesero" required>
        <option value="">Seleccione un mesero</option>
        <?php foreach ($meseros as $mesero): ?>
          <option value="<?php echo $mesero['id_usuario']; ?>" data-tipo="<?php echo $mesero['pkfk_Tipo_doc']; ?>">
            <?php echo htmlspecialchars($mesero['nombre']); ?> (<?php echo $mesero['id_usuario']; ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="tipo_doc_mesero" id="tipo_doc_mesero" value="1">
    </div>
    <div>
      <label>Cliente</label>
      <select name="id_cliente" required>
        <option value="">Seleccione un cliente</option>
        <?php foreach ($clientes as $cliente): ?>
          <option value="<?php echo $cliente['id_usuario']; ?>" data-tipo="<?php echo $cliente['pkfk_Tipo_doc']; ?>">
            <?php echo htmlspecialchars($cliente['nombre']); ?> (<?php echo $cliente['id_usuario']; ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="tipo_doc_cliente" id="tipo_doc_cliente" value="1">
    </div>
    <div>
      <label>Total Inicial</label>
      <input type="number" name="total" value="0" min="0" step="100">
      <small>Se calculara automaticamente al agregar pedidos</small>
    </div>
  </form>
  <div class="acciones">
    <a href="Factura.php"><button class="cancelar">Cancelar</button></a>
    <button class="guardar" onclick="submitForm()">Guardar</button>
  </div>
</section>

<script>
// Actualizar tipo de documento al seleccionar mesero
document.querySelector('select[name="id_mesero"]').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('tipo_doc_mesero').value = selected.dataset.tipo || 1;
});

// Actualizar tipo de documento al seleccionar cliente
document.querySelector('select[name="id_cliente"]').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('tipo_doc_cliente').value = selected.dataset.tipo || 1;
});

function submitForm() {
    document.querySelector('form').submit();
}
</script>
</body>
</html>
