<?php
/**
 * Vista del Dashboard Principal
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$nombreUsuario = session('nombre') ?? 'Usuario';
$rolUsuario = session('rol') ?? 'cliente';
$correoUsuario = session('correo') ?? '';

// Datos de estadisticas (pasados desde controlador o datos de ejemplo)
$estadisticas = $estadisticas ?? [
    'total_usuarios' => 15,
    'mesas_ocupadas' => 8,
    'mesas_disponibles' => 12,
    'pedidos_pendientes' => 5,
    'ventas_hoy' => 450000
];

// Funcion para formatear precio
$formatearPrecio = function(int $precio): string {
    return '$' . number_format($precio, 0, ',', '.');
};

// Funcion para obtener saludo segun hora
$obtenerSaludo = function(): string {
    $hora = (int) date('H');
    if ($hora >= 5 && $hora < 12) {
        return 'Buenos dias';
    } elseif ($hora >= 12 && $hora < 18) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
};

$saludo = $obtenerSaludo();

// Accesos rapidos segun rol
$accesosRapidos = [];

// Condicion: definir accesos segun rol
if ($rolUsuario === 'admin') {
    $accesosRapidos = [
        ['titulo' => 'Usuarios', 'enlace' => 'personas', 'icono' => 'users', 'color' => '#3498db'],
        ['titulo' => 'Mesas', 'enlace' => 'mesas', 'icono' => 'table', 'color' => '#27ae60'],
        ['titulo' => 'Pedidos', 'enlace' => 'pedidos', 'icono' => 'clipboard', 'color' => '#e74c3c'],
        ['titulo' => 'Facturas', 'enlace' => 'facturas', 'icono' => 'receipt', 'color' => '#9b59b6'],
        ['titulo' => 'Menu', 'enlace' => 'menu', 'icono' => 'book', 'color' => '#f39c12'],
        ['titulo' => 'Productos', 'enlace' => 'productos', 'icono' => 'box', 'color' => '#1abc9c']
    ];
} elseif ($rolUsuario === 'mesero') {
    $accesosRapidos = [
        ['titulo' => 'Mesas', 'enlace' => 'mesas', 'icono' => 'table', 'color' => '#27ae60'],
        ['titulo' => 'Pedidos', 'enlace' => 'pedidos', 'icono' => 'clipboard', 'color' => '#e74c3c'],
        ['titulo' => 'Menu', 'enlace' => 'menu', 'icono' => 'book', 'color' => '#f39c12'],
        ['titulo' => 'Facturas', 'enlace' => 'facturas', 'icono' => 'receipt', 'color' => '#9b59b6']
    ];
} elseif ($rolUsuario === 'cocinero') {
    $accesosRapidos = [
        ['titulo' => 'Pedidos', 'enlace' => 'pedidos', 'icono' => 'clipboard', 'color' => '#e74c3c'],
        ['titulo' => 'Menu', 'enlace' => 'menu', 'icono' => 'book', 'color' => '#f39c12']
    ];
} else {
    $accesosRapidos = [
        ['titulo' => 'Menu', 'enlace' => 'menu', 'icono' => 'book', 'color' => '#f39c12'],
        ['titulo' => 'Mis Pedidos', 'enlace' => 'cliente/pedidos', 'icono' => 'clipboard', 'color' => '#e74c3c']
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Dashboard - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .dashboard { padding: 2rem 5%; }
        .bienvenida { margin-bottom: 2rem; }
        .bienvenida h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .bienvenida p { color: #b0b0b0; }
        .bienvenida .rol-badge { display: inline-block; background: #4fc3f7; color: #1a1a2e; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-left: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; border-left: 4px solid #4fc3f7; }
        .stat-card h3 { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card .valor { font-size: 2rem; font-weight: 700; color: #4fc3f7; }
        .accesos-rapidos h2 { margin-bottom: 1.5rem; font-size: 1.5rem; }
        .accesos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; }
        .acceso-card { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; text-align: center; transition: transform 0.3s, box-shadow 0.3s; text-decoration: none; color: #fff; }
        .acceso-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .acceso-card .icono { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; }
        .acceso-card h3 { font-size: 1rem; }
        .actividad { margin-top: 2rem; background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; }
        .actividad h2 { margin-bottom: 1rem; font-size: 1.3rem; color: #4fc3f7; }
        .actividad-item { padding: 0.8rem 0; border-bottom: 1px solid #333; display: flex; justify-content: space-between; }
        .actividad-item:last-child { border-bottom: none; }
        .actividad-item .hora { color: #888; font-size: 0.85rem; }
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
        <li><a href="<?= base_url('menu') ?>">Menu</a></li>
        <li><a href="<?= base_url('productos') ?>">Productos</a></li>
        <li><a href="<?= base_url('facturas') ?>">Facturas</a></li>
        <li><a href="<?= base_url('pedidos') ?>">Pedidos</a></li>
        <li class="right"><a href="<?= base_url('logout') ?>">Cerrar Sesion</a></li>
    </ul>
</nav>

<section class="dashboard">
    <div class="bienvenida">
        <h1><?= esc($saludo) ?>, <?= esc($nombreUsuario) ?>!</h1>
        <p>
            Bienvenido al panel de control de SISPE
            <span class="rol-badge"><?= ucfirst(esc($rolUsuario)) ?></span>
        </p>
    </div>

    <?php 
    // Condicion: mostrar estadisticas solo para admin y mesero
    if (in_array($rolUsuario, ['admin', 'mesero'])): 
    ?>
    <div class="stats-grid">
        <?php if ($rolUsuario === 'admin'): ?>
            <div class="stat-card">
                <h3>Total Usuarios</h3>
                <div class="valor"><?= $estadisticas['total_usuarios'] ?></div>
            </div>
        <?php endif; ?>
        <div class="stat-card">
            <h3>Mesas Ocupadas</h3>
            <div class="valor"><?= $estadisticas['mesas_ocupadas'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Mesas Disponibles</h3>
            <div class="valor"><?= $estadisticas['mesas_disponibles'] ?></div>
        </div>
        <div class="stat-card">
            <h3>Pedidos Pendientes</h3>
            <div class="valor"><?= $estadisticas['pedidos_pendientes'] ?></div>
        </div>
        <?php if ($rolUsuario === 'admin'): ?>
            <div class="stat-card">
                <h3>Ventas de Hoy</h3>
                <div class="valor"><?= $formatearPrecio($estadisticas['ventas_hoy']) ?></div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="accesos-rapidos">
        <h2>Accesos Rapidos</h2>
        <div class="accesos-grid">
            <?php 
            // Bucle: mostrar accesos rapidos segun rol
            foreach ($accesosRapidos as $acceso): 
            ?>
                <a href="<?= base_url($acceso['enlace']) ?>" class="acceso-card">
                    <div class="icono" style="background: <?= $acceso['color'] ?>20; color: <?= $acceso['color'] ?>;">
                        <?= substr($acceso['titulo'], 0, 1) ?>
                    </div>
                    <h3><?= esc($acceso['titulo']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php 
    // Condicion: mostrar actividad reciente solo para admin
    if ($rolUsuario === 'admin'): 
    ?>
    <div class="actividad">
        <h2>Actividad Reciente</h2>
        <?php 
        // Datos de ejemplo de actividad
        $actividades = [
            ['texto' => 'Nuevo pedido en Mesa 3', 'hora' => '10:45'],
            ['texto' => 'Factura #1023 generada', 'hora' => '10:30'],
            ['texto' => 'Mesa 5 liberada', 'hora' => '10:15'],
            ['texto' => 'Nuevo usuario registrado', 'hora' => '09:50']
        ];
        
        // Bucle: mostrar actividades
        foreach ($actividades as $actividad): 
        ?>
            <div class="actividad-item">
                <span><?= esc($actividad['texto']) ?></span>
                <span class="hora"><?= esc($actividad['hora']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<script>
// Actualizar hora cada minuto
setInterval(function() {
    // En produccion, actualizar datos via AJAX
}, 60000);
</script>
</body>
</html>
