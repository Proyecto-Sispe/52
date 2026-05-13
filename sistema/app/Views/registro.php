<?php
/**
 * Vista de Registro de Usuario
 * Sistema SISPE - CodeIgniter 4
 */

// Funcion para obtener valor antiguo del formulario
$valorAntiguo = function(string $campo, string $default = ''): string {
    return old($campo) ?? $default;
};

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Roles disponibles (pasados desde el controlador)
$rolesDisponibles = $roles ?? ['cliente', 'mesero', 'cocinero'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Registro Usuario - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .formulario { max-width: 700px; margin: 2rem auto; background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; backdrop-filter: blur(10px); }
        .formulario h2 { color: #fff; text-align: center; margin-bottom: 2rem; font-size: 1.8rem; }
        .grid-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.2rem; }
        .grid-form > div { display: flex; flex-direction: column; }
        .grid-form label { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .grid-form input, .grid-form select { padding: 0.8rem; border: 1px solid #333; border-radius: 8px; background: #1a1a2e; color: #fff; font-size: 1rem; }
        .grid-form input:focus, .grid-form select:focus { outline: none; border-color: #4fc3f7; }
        .acciones { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
        .cancelar { background: #555; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        .cancelar:hover { background: #666; }
        .guardar { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; }
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
        <li><a href="<?= base_url('/') ?>">Inicio</a></li>
        <li><a href="<?= base_url('personas') ?>">Personas</a></li>
        <li><a href="<?= base_url('mesas') ?>">Mesas</a></li>
        <li><a href="<?= base_url('mesas/gestion') ?>">Gestion de Mesas</a></li>
        <li><a href="<?= base_url('menu') ?>">Menu</a></li>
        <li><a href="<?= base_url('productos') ?>">Productos</a></li>
        <li><a href="<?= base_url('facturas') ?>">Facturas</a></li>
        <li><a href="<?= base_url('pedidos') ?>">Pedidos</a></li>
        <li class="right"><a href="<?= base_url('/') ?>">Login</a></li>
    </ul>
</nav>

<section class="formulario">
    <h2>Registrar Nueva Persona</h2>
    
    <?php 
    // Condicion: mostrar mensaje de error
    if ($error !== null): 
    ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    
    <?php 
    // Condicion: mostrar mensaje de exito
    if ($exito !== null): 
    ?>
        <div class="alert alert-success"><?= esc($exito) ?></div>
    <?php endif; ?>
    
    <form action="<?= base_url('guardar') ?>" method="post" id="formRegistro">
        <?= csrf_field() ?>
        <div class="grid-form">
            <div>
                <label for="tipo_documento">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" required>
                    <?php 
                    // Bucle: generar opciones de tipo de documento
                    $tiposDocumento = [
                        'CC' => 'Cedula de Ciudadania',
                        'TI' => 'Tarjeta de Identidad',
                        'CE' => 'Cedula de Extranjeria'
                    ];
                    foreach ($tiposDocumento as $codigo => $nombre): 
                        $selected = ($valorAntiguo('tipo_documento') === $codigo) ? 'selected' : '';
                    ?>
                        <option value="<?= $codigo ?>" <?= $selected ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="rol">Rol</label>
                <select id="rol" name="rol" required>
                    <?php 
                    // Bucle: generar opciones de roles
                    foreach ($rolesDisponibles as $rol): 
                        $selected = ($valorAntiguo('rol') === $rol) ? 'selected' : '';
                        $rolFormateado = ucfirst($rol);
                    ?>
                        <option value="<?= esc($rol) ?>" <?= $selected ?>><?= $rolFormateado ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="numero_documento">Numero de Identificacion</label>
                <input type="text" id="numero_documento" name="numero_documento" 
                       value="<?= esc($valorAntiguo('numero_documento')) ?>" required>
            </div>
            <div>
                <label for="primer_nombre">Primer Nombre</label>
                <input type="text" id="primer_nombre" name="primer_nombre" 
                       value="<?= esc($valorAntiguo('primer_nombre')) ?>" required>
            </div>
            <div>
                <label for="segundo_nombre">Segundo Nombre</label>
                <input type="text" id="segundo_nombre" name="segundo_nombre" 
                       value="<?= esc($valorAntiguo('segundo_nombre')) ?>">
            </div>
            <div>
                <label for="primer_apellido">Primer Apellido</label>
                <input type="text" id="primer_apellido" name="primer_apellido" 
                       value="<?= esc($valorAntiguo('primer_apellido')) ?>" required>
            </div>
            <div>
                <label for="segundo_apellido">Segundo Apellido</label>
                <input type="text" id="segundo_apellido" name="segundo_apellido" 
                       value="<?= esc($valorAntiguo('segundo_apellido')) ?>">
            </div>
            <div>
                <label for="correo">Email</label>
                <input type="email" id="correo" name="correo" 
                       value="<?= esc($valorAntiguo('correo')) ?>" required>
            </div>
            <div>
                <label for="telefono">Telefono</label>
                <input type="tel" id="telefono" name="telefono" 
                       value="<?= esc($valorAntiguo('telefono')) ?>">
            </div>
            <div>
                <label for="password">Contrasena</label>
                <input type="password" id="password" name="password" required>
            </div>
        </div>
        <div class="acciones">
            <button class="cancelar" type="button" onclick="cancelarRegistro()">Cancelar</button>
            <button class="guardar" type="submit">Guardar</button>
        </div>
    </form>
</section>

<script>
<?php
// Generar validacion JavaScript dinamica
$camposRequeridos = ['numero_documento', 'primer_nombre', 'primer_apellido', 'correo', 'password'];
?>

// Funcion para cancelar registro
function cancelarRegistro() {
    if (confirm('Esta seguro de cancelar el registro?')) {
        window.location.href = '<?= base_url('/') ?>';
    }
}

// Validacion del formulario
document.getElementById('formRegistro').addEventListener('submit', function(e) {
    const camposRequeridos = <?= json_encode($camposRequeridos) ?>;
    let errores = [];
    
    // Bucle: validar cada campo requerido
    for (let i = 0; i < camposRequeridos.length; i++) {
        const campo = document.getElementById(camposRequeridos[i]);
        if (campo && campo.value.trim() === '') {
            errores.push('El campo ' + camposRequeridos[i].replace('_', ' ') + ' es requerido');
        }
    }
    
    // Condicion: validar formato de correo
    const correo = document.getElementById('correo').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (correo && !emailRegex.test(correo)) {
        errores.push('El formato del correo no es valido');
    }
    
    // Condicion: validar longitud de contrasena
    const password = document.getElementById('password').value;
    if (password.length < 6) {
        errores.push('La contrasena debe tener al menos 6 caracteres');
    }
    
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
