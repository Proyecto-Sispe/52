<?php
/**
 * Vista para Agregar/Editar Persona
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Datos de persona para edicion (si existe)
$persona = $persona ?? null;
$esEdicion = $persona !== null;
$titulo = $esEdicion ? 'Editar Persona' : 'Registrar Nueva Persona';

// Funcion para obtener valor del campo
$obtenerValor = function(string $campo, $persona) {
    if ($persona !== null && isset($persona[$campo])) {
        return $persona[$campo];
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
        .formulario { max-width: 800px; margin: 2rem auto; background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; }
        .formulario h2 { text-align: center; margin-bottom: 2rem; color: #4fc3f7; }
        .grid-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; }
        .grid-form > div { display: flex; flex-direction: column; }
        .grid-form label { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .grid-form input, .grid-form select { padding: 0.8rem; border: 1px solid #333; border-radius: 8px; background: #1a1a2e; color: #fff; font-size: 1rem; }
        .grid-form input:focus, .grid-form select:focus { outline: none; border-color: #4fc3f7; }
        .acciones { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
        .cancelar { background: #555; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
        .cancelar:hover { background: #666; }
        .guardar { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .guardar:hover { background: #29b6f6; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        .required::after { content: ' *'; color: #f44336; }
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

<section class="formulario">
    <h2><?= $titulo ?></h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    
    <?php if ($exito): ?>
        <div class="alert alert-success"><?= esc($exito) ?></div>
    <?php endif; ?>
    
    <?php 
    // Condicion: determinar URL de accion segun si es edicion o creacion
    $actionUrl = $esEdicion 
        ? base_url('personas/actualizar/' . ($persona['id'] ?? 0))
        : base_url('personas/guardar');
    ?>
    <form action="<?= $actionUrl ?>" method="post" id="formPersona">
        <?= csrf_field() ?>
        
        <div class="grid-form">
            <div>
                <label for="tipo_documento" class="required">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" required>
                    <?php 
                    // Bucle: generar opciones de tipo documento
                    $tiposDoc = [
                        'CC' => 'Cedula de Ciudadania',
                        'TI' => 'Tarjeta de Identidad',
                        'CE' => 'Cedula de Extranjeria'
                    ];
                    $valorActual = $obtenerValor('tipo_documento', $persona);
                    foreach ($tiposDoc as $codigo => $nombre): 
                        $selected = ($valorActual === $codigo) ? 'selected' : '';
                    ?>
                        <option value="<?= $codigo ?>" <?= $selected ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="rol" class="required">Rol</label>
                <select id="rol" name="rol" required>
                    <?php 
                    // Bucle: generar opciones de roles
                    $roles = ['admin' => 'Administrador', 'mesero' => 'Mesero', 'cocinero' => 'Cocinero', 'cliente' => 'Cliente'];
                    $rolActual = $obtenerValor('rol', $persona);
                    foreach ($roles as $codigo => $nombre): 
                        $selected = ($rolActual === $codigo) ? 'selected' : '';
                    ?>
                        <option value="<?= $codigo ?>" <?= $selected ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="numero_documento" class="required">Numero de Identificacion</label>
                <input type="text" id="numero_documento" name="numero_documento" 
                       value="<?= esc($obtenerValor('numero_documento', $persona)) ?>" required>
            </div>
            <div>
                <label for="primer_nombre" class="required">Primer Nombre</label>
                <input type="text" id="primer_nombre" name="primer_nombre" 
                       value="<?= esc($obtenerValor('primer_nombre', $persona)) ?>" required>
            </div>
            <div>
                <label for="segundo_nombre">Segundo Nombre</label>
                <input type="text" id="segundo_nombre" name="segundo_nombre" 
                       value="<?= esc($obtenerValor('segundo_nombre', $persona)) ?>">
            </div>
            <div>
                <label for="primer_apellido" class="required">Primer Apellido</label>
                <input type="text" id="primer_apellido" name="primer_apellido" 
                       value="<?= esc($obtenerValor('primer_apellido', $persona)) ?>" required>
            </div>
            <div>
                <label for="segundo_apellido">Segundo Apellido</label>
                <input type="text" id="segundo_apellido" name="segundo_apellido" 
                       value="<?= esc($obtenerValor('segundo_apellido', $persona)) ?>">
            </div>
            <div>
                <label for="correo" class="required">Email</label>
                <input type="email" id="correo" name="correo" 
                       value="<?= esc($obtenerValor('correo', $persona)) ?>" required>
            </div>
            <?php 
            // Condicion: mostrar campo de password solo en creacion
            if (!$esEdicion): 
            ?>
                <div>
                    <label for="password" class="required">Contrasena</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="confirm_password" class="required">Confirmar Contrasena</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="acciones">
            <a href="<?= base_url('personas') ?>" class="cancelar">Cancelar</a>
            <button type="submit" class="guardar"><?= $esEdicion ? 'Actualizar' : 'Guardar' ?></button>
        </div>
    </form>
</section>

<script>
document.getElementById('formPersona').addEventListener('submit', function(e) {
    let errores = [];
    
    // Validar campos requeridos
    const camposRequeridos = ['numero_documento', 'primer_nombre', 'primer_apellido', 'correo'];
    
    // Bucle: verificar campos requeridos
    for (let i = 0; i < camposRequeridos.length; i++) {
        const campo = document.getElementById(camposRequeridos[i]);
        if (campo && campo.value.trim() === '') {
            errores.push('El campo ' + camposRequeridos[i].replace('_', ' ') + ' es requerido');
        }
    }
    
    // Validar correo
    const correo = document.getElementById('correo').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (correo && !emailRegex.test(correo)) {
        errores.push('El formato del correo no es valido');
    }
    
    <?php if (!$esEdicion): ?>
    // Validar contrasenas
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Condicion: validar longitud de contrasena
    if (password.length < 6) {
        errores.push('La contrasena debe tener al menos 6 caracteres');
    }
    
    // Condicion: validar que las contrasenas coincidan
    if (password !== confirmPassword) {
        errores.push('Las contrasenas no coinciden');
    }
    <?php endif; ?>
    
    // Condicion: mostrar errores si existen
    if (errores.length > 0) {
        e.preventDefault();
        alert('Errores encontrados:\n- ' + errores.join('\n- '));
        return false;
    }
});
</script>
</body>
</html>
