<?php
/**
 * Vista de Inicio de Sesion
 * Sistema SISPE - CodeIgniter 4
 */

// Funcion para obtener mensaje flash
$obtenerMensaje = function(string $tipo): ?string {
    return session()->getFlashdata($tipo);
};

// Obtener mensajes de error y exito
$error = $obtenerMensaje('error');
$exito = $obtenerMensaje('exito');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= base_url('css/login.css') ?>">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Inicio de Sesion - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; display: flex; flex-direction: column; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        h1 { color: #fff; text-align: center; margin: 3rem 0 2rem; font-size: 2rem; }
        form { max-width: 400px; margin: 0 auto; background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; backdrop-filter: blur(10px); }
        .grid-inputs { display: flex; flex-direction: column; gap: 1.2rem; }
        .grid-inputs label { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.3rem; display: block; }
        .grid-inputs input, .grid-inputs select { width: 100%; padding: 0.8rem; border: 1px solid #333; border-radius: 8px; background: #1a1a2e; color: #fff; font-size: 1rem; }
        .grid-inputs input:focus, .grid-inputs select:focus { outline: none; border-color: #4fc3f7; }
        .botones { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .botones a { color: #4fc3f7; text-decoration: none; font-size: 0.9rem; }
        .botones button { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.8rem 2rem; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; transition: background 0.3s; }
        .botones button:hover { background: #29b6f6; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; max-width: 400px; margin-left: auto; margin-right: auto; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
    </style>
</head>
<body>
<nav>
    <ul class="menu">
        <li class="logo">
            <img src="<?= base_url('../images/logo2.png') ?>" alt="Logo SISPE">
        </li>
        <li><a href="<?= base_url('/') ?>">Inicio</a></li>
        <?php 
        // Condicion: mostrar enlaces segun estado de sesion
        if (session('logueado') === true): 
        ?>
            <li><a href="<?= base_url('personas') ?>">Personas</a></li>
            <li><a href="<?= base_url('mesas') ?>">Mesas</a></li>
            <li><a href="<?= base_url('mesas/gestion') ?>">Gestion de Mesas</a></li>
            <li><a href="<?= base_url('menu') ?>">Menu</a></li>
            <li><a href="<?= base_url('productos') ?>">Productos</a></li>
            <li><a href="<?= base_url('facturas') ?>">Facturas</a></li>
            <li><a href="<?= base_url('pedidos') ?>">Pedidos</a></li>
            <li class="right"><a href="<?= base_url('logout') ?>">Cerrar Sesion</a></li>
        <?php else: ?>
            <li class="right"><a href="<?= base_url('registro') ?>">Registro</a></li>
        <?php endif; ?>
    </ul>
</nav>

<h1>Inicio de Sesion</h1>

<?php 
// Condicion: mostrar mensaje de error si existe
if ($error !== null): 
?>
    <div class="alert alert-error">
        <?= esc($error) ?>
    </div>
<?php endif; ?>

<?php 
// Condicion: mostrar mensaje de exito si existe
if ($exito !== null): 
?>
    <div class="alert alert-success">
        <?= esc($exito) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('login') ?>" method="post">
    <?= csrf_field() ?>
    <div class="grid-inputs">
        <div>
            <label for="documento">Tipo de documento:</label>
            <select id="documento" name="documento" required>
                <option value="">Selecciona un documento</option>
                <?php 
                // Bucle: generar opciones de tipo de documento
                $tiposDocumento = [
                    'CC' => 'Cedula de Ciudadania',
                    'TI' => 'Tarjeta de Identidad',
                    'CE' => 'Cedula de Extranjeria'
                ];
                foreach ($tiposDocumento as $codigo => $nombre): 
                ?>
                    <option value="<?= $codigo ?>"><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="correo">Email:</label>
            <input type="email" id="correo" name="correo" required placeholder="correo@ejemplo.com">
        </div>
        <div>
            <label for="password">Contrasena:</label>
            <input type="password" id="password" name="password" required placeholder="Tu contrasena">
        </div>
    </div>
    <div class="botones">
        <a href="<?= base_url('registro') ?>">No tienes cuenta? Crea una</a>
        <button type="submit">Ingresar</button>
    </div>
</form>

<script>
// Validacion del lado del cliente
document.querySelector('form').addEventListener('submit', function(e) {
    const correo = document.getElementById('correo').value;
    const password = document.getElementById('password').value;
    
    // Condicion: validar campos vacios
    if (correo.trim() === '' || password.trim() === '') {
        e.preventDefault();
        alert('Por favor, complete todos los campos.');
        return false;
    }
    
    // Condicion: validar formato de correo
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(correo)) {
        e.preventDefault();
        alert('Por favor, ingrese un correo valido.');
        return false;
    }
});
</script>
</body>
</html>
