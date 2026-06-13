<?php
/**
 * Vista para Agregar Nueva Factura
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Fecha y hora actuales
$fechaActual = date('Y-m-d');
$horaActual = date('H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Registrar Factura - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .formulario-mesa { max-width: 700px; margin: 2rem auto; background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; }
        .formulario-mesa h2 { text-align: center; margin-bottom: 2rem; color: #4fc3f7; }
        .grid-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; }
        .grid-form > div { display: flex; flex-direction: column; }
        .grid-form label { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .grid-form input { padding: 0.8rem; border: 1px solid #333; border-radius: 8px; background: #1a1a2e; color: #fff; font-size: 1rem; }
        .grid-form input:focus { outline: none; border-color: #4fc3f7; }
        .acciones { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
        .cancelar { background: #555; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .cancelar:hover { background: #666; }
        .guardar { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .guardar:hover { background: #29b6f6; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        @media (max-width: 600px) { .grid-form { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<nav>
    <ul class="menu">
        <li class="logo">
            <img src="<?= base_url('images/logo2.png') ?>" alt="Logo SISPE">
        </li>
        <li><a href="<?= base_url('inicio') ?>">Inicio</a></li>
        <li><a href="<?= base_url('personas') ?>">Personas</a></li>
        <li><a href="<?= base_url('mesas') ?>">Mesas</a></li>
        <li><a href="<?= base_url('mesas/gestion') ?>">Gestion de Mesas</a></li>
        <li><a href="<?= base_url('menu') ?>">Menu</a></li>
        <li><a href="<?= base_url('productos') ?>">Productos</a></li>
        <li><a href="<?= base_url('facturas') ?>">Facturas</a></li>
        <li><a href="<?= base_url('pedidos') ?>">Pedidos</a></li>
        <?php if ($usuarioLogueado): ?>
            <li class="right"><a href="<?= base_url('logout') ?>">Cerrar Sesion</a></li>
        <?php else: ?>
            <li class="right"><a href="<?= base_url('/') ?>">Login</a></li>
            <li><a href="<?= base_url('registro') ?>">Registro</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section class="formulario-mesa">
    <h2>Registrar Nueva Factura</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
        <div class="alert alert-success"><?= esc($exito) ?></div>
    <?php endif; ?>
    
    <form action="<?= base_url('facturas/guardar') ?>" method="post" id="formFactura">
        <?= csrf_field() ?>
        
        <div class="grid-form">
            <div>
                <label for="id_factura">Id Factura</label>
                <input type="number" id="id_factura" name="id_factura" 
                       placeholder="Ingrese un numero de factura unico" required>
            </div>
            <div>
                <label for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" value="<?= $fechaActual ?>" required>
            </div>
            <div>
                <label for="numero_mesa">Numero de mesa</label>
                <input type="number" id="numero_mesa" name="numero_mesa" 
                       placeholder="Ingrese numero de mesa" required min="1">
            </div>
            <div>
                <label for="hora">Hora</label>
                <input type="time" id="hora" name="hora" value="<?= $horaActual ?>" required>
            </div>
            <div>
                <label for="cliente">Cliente</label>
                <input type="text" id="cliente" name="cliente" 
                       placeholder="Ingrese el nombre del cliente" required>
            </div>
            <div>
                <label for="documento_cliente">Documento</label>
                <input type="number" id="documento_cliente" name="documento_cliente" 
                       placeholder="Ingrese el numero de documento del cliente" required>
            </div>
            <div>
                <label for="mesero">Mesero</label>
                <input type="text" id="mesero" name="mesero" 
                       placeholder="Ingrese el nombre del mesero" required>
            </div>
            <div>
                <label for="valor_total">Valor Total</label>
                <input type="number" id="valor_total" name="valor_total" 
                       placeholder="Ingrese el valor total de la factura" required min="0">
            </div>
        </div>
        
        <div class="acciones">
            <a href="<?= base_url('facturas') ?>" class="cancelar">Cancelar</a>
            <button type="submit" class="guardar">Guardar</button>
        </div>
    </form>
</section>

<script>
document.getElementById('formFactura').addEventListener('submit', function(e) {
    let errores = [];
    
    const idFactura = document.getElementById('id_factura').value;
    const cliente = document.getElementById('cliente').value;
    const documento = document.getElementById('documento_cliente').value;
    const valorTotal = document.getElementById('valor_total').value;
    
    // Condicion: validar ID factura
    if (idFactura <= 0) {
        errores.push('El ID de factura debe ser mayor a 0');
    }
    
    // Condicion: validar cliente
    if (cliente.trim() === '') {
        errores.push('El nombre del cliente es requerido');
    }
    
    // Condicion: validar documento
    if (documento.length < 6) {
        errores.push('El documento debe tener al menos 6 digitos');
    }
    
    // Condicion: validar valor total
    if (valorTotal <= 0) {
        errores.push('El valor total debe ser mayor a 0');
    }
    
    // Condicion: mostrar errores
    if (errores.length > 0) {
        e.preventDefault();
        alert('Errores:\n- ' + errores.join('\n- '));
        return false;
    }
});
</script>
</body>
</html>
