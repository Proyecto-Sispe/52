<?php
/**
 * Vista de Gestion de Personas
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;
$rolUsuario = session('rol') ?? 'cliente';

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Datos de personas (pasados desde el controlador)
$personas = $personas ?? [];
$totalPersonas = count($personas);

// Funcion para formatear tipo de documento
$formatearTipoDoc = function(string $tipo): string {
    $tipos = [
        'CC' => 'C.C',
        'TI' => 'T.I',
        'CE' => 'C.E'
    ];
    return $tipos[$tipo] ?? $tipo;
};

// Funcion para calcular estadisticas
$calcularStats = function(array $lista): array {
    $stats = ['total' => 0, 'por_rol' => []];
    foreach ($lista as $item) {
        $stats['total']++;
        $rol = $item['rol'] ?? 'sin_rol';
        if (!isset($stats['por_rol'][$rol])) {
            $stats['por_rol'][$rol] = 0;
        }
        $stats['por_rol'][$rol]++;
    }
    return $stats;
};

$estadisticas = $calcularStats($personas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Gestion de Personas - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .hero { display: flex; justify-content: space-between; align-items: center; padding: 2rem 5%; flex-wrap: wrap; gap: 1rem; }
        .hero-texto h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .hero-texto p { color: #b0b0b0; }
        .hero-boton .btn { display: inline-block; background: #4fc3f7; color: #1a1a2e; padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .hero-boton .btn:hover { background: #29b6f6; }
        .panel { padding: 2rem 5%; }
        .filtros { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .filtros h3 { margin-bottom: 1rem; font-size: 1.1rem; }
        .fila-filtros { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .fila-filtros > div { display: flex; flex-direction: column; gap: 0.3rem; }
        .fila-filtros label { color: #b0b0b0; font-size: 0.85rem; }
        .fila-filtros input, .fila-filtros select { padding: 0.6rem; border: 1px solid #333; border-radius: 6px; background: #1a1a2e; color: #fff; min-width: 150px; }
        .btn-buscar { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-buscar:hover { background: #29b6f6; }
        .tabla { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; overflow-x: auto; }
        .tabla h3 { margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #333; }
        th { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; }
        tr:hover { background: rgba(255,255,255,0.03); }
        .badge { background: #4fc3f7; color: #1a1a2e; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        .edit, .delete { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.3rem; }
        .edit { background: #ffc107; color: #1a1a2e; }
        .delete { background: #f44336; color: #fff; }
        .alert { padding: 1rem; border-radius: 8px; margin: 0 5% 1rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        .stats { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .stat-card { background: rgba(79, 195, 247, 0.1); padding: 0.8rem 1.2rem; border-radius: 8px; }
        .stat-card strong { color: #4fc3f7; }
        .empty-state { text-align: center; padding: 3rem; color: #888; }
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

<section class="hero">
    <div class="hero-texto">
        <h1>Gestion de Personas</h1>
        <p>Administra la informacion de clientes, empleados y administradores</p>
    </div>
    <div class="hero-boton">
        <?php 
        // Condicion: solo admin puede agregar personas
        if ($rolUsuario === 'admin'): 
        ?>
            <a href="<?= base_url('personas/agregar') ?>" class="btn">+ Agregar persona</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($error): ?>
    <div class="alert alert-error"><?= esc($error) ?></div>
<?php endif; ?>

<?php if ($exito): ?>
    <div class="alert alert-success"><?= esc($exito) ?></div>
<?php endif; ?>

<section class="panel">
    <!-- Estadisticas -->
    <div class="stats">
        <div class="stat-card">
            <strong><?= $estadisticas['total'] ?></strong> Total Personas
        </div>
        <?php 
        // Bucle: mostrar estadisticas por rol
        foreach ($estadisticas['por_rol'] as $rol => $cantidad): 
        ?>
            <div class="stat-card">
                <strong><?= $cantidad ?></strong> <?= ucfirst(esc($rol)) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="filtros">
        <h3>Filtros de Busqueda</h3>
        <form action="<?= base_url('personas') ?>" method="get" class="fila-filtros">
            <div>
                <label>Tipo de Documento</label>
                <select name="tipo_doc">
                    <option value="">Todos</option>
                    <?php 
                    // Bucle: generar opciones de tipo documento
                    $tiposDoc = ['CC' => 'Cedula Ciudadania', 'TI' => 'Tarjeta Identidad', 'CE' => 'Cedula Extranjeria'];
                    foreach ($tiposDoc as $codigo => $nombre): 
                    ?>
                        <option value="<?= $codigo ?>"><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Rol</label>
                <select name="rol">
                    <option value="">Todos</option>
                    <?php 
                    // Bucle: generar opciones de roles
                    $roles = ['admin' => 'Administrador', 'mesero' => 'Mesero', 'cocinero' => 'Cocinero', 'cliente' => 'Cliente'];
                    foreach ($roles as $codigo => $nombre): 
                    ?>
                        <option value="<?= $codigo ?>"><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Buscar</label>
                <input type="text" name="buscar" placeholder="Nombre o Identificacion">
            </div>
            <button type="submit" class="btn-buscar">Buscar</button>
        </form>
    </div>

    <div class="tabla">
        <h3>Listado de Personas (<?= $totalPersonas ?>)</h3>
        <?php 
        // Condicion: verificar si hay personas para mostrar
        if ($totalPersonas > 0): 
        ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Usuario</th>
                        <th>Tipo Doc</th>
                        <th>Numero Documento</th>
                        <th>Primer Nombre</th>
                        <th>Segundo Nombre</th>
                        <th>Primer Apellido</th>
                        <th>Segundo Apellido</th>
                        <th>Telefono</th>
                        <th>Rol</th>
                        <?php if ($rolUsuario === 'admin'): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Bucle: mostrar cada persona
                    foreach ($personas as $persona): 
                    ?>
                        <tr>
                            <td><?= esc($persona['id'] ?? 'N/A') ?></td>
                            <td><span class="badge"><?= $formatearTipoDoc($persona['tipo_documento'] ?? 'CC') ?></span></td>
                            <td><?= esc($persona['numero_documento'] ?? '') ?></td>
                            <td><?= esc($persona['primer_nombre'] ?? '') ?></td>
                            <td><?= esc($persona['segundo_nombre'] ?? '') ?></td>
                            <td><?= esc($persona['primer_apellido'] ?? '') ?></td>
                            <td><?= esc($persona['segundo_apellido'] ?? '') ?></td>
                            <td><?= esc($persona['telefono'] ?? '') ?></td>
                            <td><?= ucfirst(esc($persona['rol'] ?? 'cliente')) ?></td>
                            <?php 
                            // Condicion: mostrar acciones solo para admin
                            if ($rolUsuario === 'admin'): 
                            ?>
                                <td>
                                    <a href="<?= base_url('personas/editar/' . ($persona['id'] ?? 0)) ?>">
                                        <button class="edit">Editar</button>
                                    </a>
                                    <button class="delete" onclick="confirmarEliminar(<?= $persona['id'] ?? 0 ?>)">Eliminar</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No hay personas registradas en el sistema.</p>
                <?php if ($rolUsuario === 'admin'): ?>
                    <a href="<?= base_url('personas/agregar') ?>" class="btn" style="display: inline-block; margin-top: 1rem;">Agregar primera persona</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Funcion para confirmar eliminacion
function confirmarEliminar(id) {
    // Condicion: confirmar antes de eliminar
    if (confirm('Esta seguro de eliminar esta persona? Esta accion no se puede deshacer.')) {
        window.location.href = '<?= base_url('personas/eliminar/') ?>' + id;
    }
}

// Funcion para filtrar en tiempo real
document.querySelector('input[name="buscar"]').addEventListener('input', function(e) {
    const busqueda = e.target.value.toLowerCase();
    const filas = document.querySelectorAll('tbody tr');
    let encontrados = 0;
    
    // Bucle: filtrar filas de la tabla
    filas.forEach(function(fila) {
        const texto = fila.textContent.toLowerCase();
        // Condicion: mostrar u ocultar fila
        if (texto.includes(busqueda)) {
            fila.style.display = '';
            encontrados++;
        } else {
            fila.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
