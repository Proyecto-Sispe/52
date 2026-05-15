<?php
/**
 * Vista para Agregar/Editar Mesa
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Datos de mesa para edicion
$mesa = $mesa ?? null;
$esEdicion = $mesa !== null;
$titulo = $esEdicion ? 'Editar Mesa' : 'Registrar Nueva Mesa';

// Funcion para obtener valor
$obtenerValor = function(string $campo, $mesa) {
    if ($mesa !== null && isset($mesa[$campo])) {
        return $mesa[$campo];
    }
    return old($campo) ?? '';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title><?= $titulo ?> - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .formulario-mesa { max-width: 600px; margin: 2rem auto; background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; }
        .formulario-mesa h2 { text-align: center; margin-bottom: 2rem; color: #4fc3f7; }
        .grid-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; }
        .grid-form > div { display: flex; flex-direction: column; }
        .grid-form label { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .grid-form input, .grid-form select { padding: 0.8rem; border: 1px solid #333; border-radius: 8px; background: #1a1a2e; color: #fff; font-size: 1rem; }
        .grid-form input:focus, .grid-form select:focus { outline: none; border-color: #4fc3f7; }
        .acciones { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
        .cancelar { background: #555; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .cancelar:hover { background: #666; }
        .guardar { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .guardar:hover { background: #29b6f6; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        @media (max-width: 500px) { .grid-form { grid-template-columns: 1fr; } }
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
    <h2><?= $titulo ?></h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
        <div class="alert alert-success"><?= esc($exito) ?></div>
    <?php endif; ?>
    
    <?php 
    $actionUrl = $esEdicion 
        ? base_url('mesas/actualizar/' . ($mesa['id'] ?? 0))
        : base_url('mesas/guardar');
    ?>
    <form action="<?= $actionUrl ?>" method="post" id="formMesa">
        <?= csrf_field() ?>
        
        <div class="grid-form">
            <div>
                <label for="numero">Numero de Mesa</label>
                <input type="number" id="numero" name="numero" 
                       value="<?= esc($obtenerValor('numero', $mesa)) ?>" 
                       placeholder="Ingresar numero de mesa" required min="1">
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado" required>
                    <?php 
                    // Bucle: generar opciones de estado
                    $estados = ['libre' => 'Libre', 'ocupada' => 'Ocupada', 'reservada' => 'Reservada'];
                    $estadoActual = $obtenerValor('estado', $mesa);
                    foreach ($estados as $codigo => $nombre): 
                        $selected = ($estadoActual === $codigo) ? 'selected' : '';
                    ?>
                        <option value="<?= $codigo ?>" <?= $selected ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="capacidad">Capacidad</label>
                <input type="number" id="capacidad" name="capacidad" 
                       value="<?= esc($obtenerValor('capacidad', $mesa)) ?>" 
                       placeholder="Ingresar personas" required min="1" max="20">
            </div>
            <div>
                <label for="ubicacion">Ubicacion</label>
                <input type="text" id="ubicacion" name="ubicacion" 
                       value="<?= esc($obtenerValor('ubicacion', $mesa)) ?>" 
                       placeholder="Ingresar ubicacion">
            </div>
        </div>
        
        <div class="acciones">
            <a href="<?= base_url('mesas/gestion') ?>" class="cancelar">Cancelar</a>
            <button type="submit" class="guardar"><?= $esEdicion ? 'Actualizar' : 'Guardar' ?></button>
        </div>
    </form>
</section>

<script>
document.getElementById('formMesa').addEventListener('submit', function(e) {
    let errores = [];
    
    const numero = document.getElementById('numero').value;
    const capacidad = document.getElementById('capacidad').value;
    
    // Condicion: validar numero de mesa
    if (numero <= 0) {
        errores.push('El numero de mesa debe ser mayor a 0');
    }
    
    // Condicion: validar capacidad
    if (capacidad <= 0 || capacidad > 20) {
        errores.push('La capacidad debe estar entre 1 y 20 personas');
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
