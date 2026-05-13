<?php
/**
 * Vista de Gestion de Mesas (Vista de tarjetas)
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;
$rolUsuario = session('rol') ?? 'cliente';

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Datos de mesas (pasados desde controlador o datos de ejemplo)
$mesas = $mesas ?? [
    ['id' => 1, 'numero' => 1, 'estado' => 'disponible', 'capacidad' => 4, 'ubicacion' => '1 Piso'],
    ['id' => 2, 'numero' => 2, 'estado' => 'ocupada', 'capacidad' => 2, 'ubicacion' => '1 Piso'],
    ['id' => 3, 'numero' => 3, 'estado' => 'disponible', 'capacidad' => 6, 'ubicacion' => '1 Piso'],
    ['id' => 4, 'numero' => 4, 'estado' => 'reservada', 'capacidad' => 4, 'ubicacion' => '1 Piso'],
    ['id' => 5, 'numero' => 5, 'estado' => 'disponible', 'capacidad' => 8, 'ubicacion' => '1 Piso'],
    ['id' => 6, 'numero' => 0, 'estado' => 'espera', 'capacidad' => 0, 'ubicacion' => '1 Piso', 'tiempo' => '10 min']
];

// Funcion para obtener color segun estado
$obtenerColorEstado = function(string $estado): string {
    $colores = [
        'disponible' => '#27ae60',
        'ocupada' => '#e74c3c',
        'reservada' => '#f39c12',
        'espera' => '#9b59b6'
    ];
    return $colores[$estado] ?? '#95a5a6';
};

// Funcion para contar mesas por estado
$contarPorEstado = function(array $mesas): array {
    $conteo = ['disponible' => 0, 'ocupada' => 0, 'reservada' => 0, 'espera' => 0];
    foreach ($mesas as $mesa) {
        $estado = $mesa['estado'] ?? 'disponible';
        if (isset($conteo[$estado])) {
            $conteo[$estado]++;
        }
    }
    return $conteo;
};

$conteoEstados = $contarPorEstado($mesas);
$totalMesas = count($mesas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Gestion de Mesas - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .hero-mesas { display: flex; justify-content: space-between; align-items: center; padding: 2rem 5%; flex-wrap: wrap; gap: 1rem; background: rgba(0,0,0,0.2); }
        .hero-texto h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .hero-texto p { color: #b0b0b0; }
        .hero-boton .btn { display: inline-block; background: #4fc3f7; color: #1a1a2e; padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .hero-boton .btn:hover { background: #29b6f6; }
        .hero { padding: 2rem 5%; }
        .stats-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .stat-item { padding: 0.8rem 1.2rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem; }
        .stat-item span { font-size: 1.5rem; font-weight: bold; }
        .contenedor { max-width: 1400px; margin: 0 auto; }
        .hero-pedidos { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; transition: transform 0.3s, box-shadow 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .card h3 { margin-bottom: 1rem; color: #4fc3f7; font-size: 1.3rem; }
        .card p { margin-bottom: 0.5rem; color: #b0b0b0; }
        .card p strong { color: #fff; }
        .acciones-card { display: flex; gap: 0.8rem; margin-top: 1.2rem; flex-wrap: wrap; }
        .btn-card { display: inline-block; background: #4fc3f7; color: #1a1a2e; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .btn-card:hover { background: #29b6f6; }
        .btn-card-2 { display: inline-block; background: transparent; color: #4fc3f7; border: 1px solid #4fc3f7; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; }
        .btn-card-2:hover { background: rgba(79, 195, 247, 0.1); }
        .estado-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .alert { padding: 1rem; border-radius: 8px; margin: 0 5% 1rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
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

<section class="hero-mesas">
    <div class="hero-texto">
        <h1>Gestion de Mesas</h1>
        <p>Administra estado, capacidad y control de mesas</p>
    </div>
    <div class="hero-boton">
        <?php 
        // Condicion: solo admin puede agregar mesas
        if ($rolUsuario === 'admin'): 
        ?>
            <a href="<?= base_url('mesas/agregar') ?>" class="btn">+ Agregar Mesa</a>
        <?php endif; ?>
    </div>
</section>

<?php if ($error): ?>
    <div class="alert alert-error"><?= esc($error) ?></div>
<?php endif; ?>

<?php if ($exito): ?>
    <div class="alert alert-success"><?= esc($exito) ?></div>
<?php endif; ?>

<section class="hero">
    <!-- Estadisticas de mesas -->
    <div class="stats-bar">
        <div class="stat-item" style="background: rgba(39, 174, 96, 0.2); border: 1px solid #27ae60;">
            <span><?= $conteoEstados['disponible'] ?></span> Disponibles
        </div>
        <div class="stat-item" style="background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c;">
            <span><?= $conteoEstados['ocupada'] ?></span> Ocupadas
        </div>
        <div class="stat-item" style="background: rgba(243, 156, 18, 0.2); border: 1px solid #f39c12;">
            <span><?= $conteoEstados['reservada'] ?></span> Reservadas
        </div>
        <div class="stat-item" style="background: rgba(155, 89, 182, 0.2); border: 1px solid #9b59b6;">
            <span><?= $conteoEstados['espera'] ?></span> En Espera
        </div>
    </div>

    <div class="contenedor">
        <div class="hero-pedidos">
            <?php 
            // Bucle: mostrar cada mesa como tarjeta
            foreach ($mesas as $mesa): 
                $estado = $mesa['estado'] ?? 'disponible';
                $colorEstado = $obtenerColorEstado($estado);
                $numeroMesa = $mesa['numero'] ?? 0;
                $nombreMesa = $numeroMesa > 0 ? "Mesa {$numeroMesa}" : "Mesa Anonima";
            ?>
                <div class="card" style="border-top: 4px solid <?= $colorEstado ?>;">
                    <h3><?= esc($nombreMesa) ?></h3>
                    <p>
                        <strong>Estado:</strong> 
                        <span class="estado-badge" style="background: <?= $colorEstado ?>; color: #fff;">
                            <?= ucfirst(esc($estado)) ?>
                        </span>
                    </p>
                    <?php 
                    // Condicion: mostrar capacidad o tiempo segun estado
                    if ($estado === 'espera' && isset($mesa['tiempo'])): 
                    ?>
                        <p><strong>Tiempo:</strong> <?= esc($mesa['tiempo']) ?></p>
                    <?php else: ?>
                        <p><strong>Capacidad:</strong> <?= esc($mesa['capacidad'] ?? 0) ?> personas</p>
                    <?php endif; ?>
                    <p><strong>Ubicacion:</strong> <?= esc($mesa['ubicacion'] ?? 'No especificada') ?></p>
                    
                    <div class="acciones-card">
                        <?php 
                        // Condicion: mostrar boton segun estado de mesa
                        if ($estado === 'disponible'): 
                        ?>
                            <a href="<?= base_url('pedidos/agregar?mesa=' . ($mesa['id'] ?? 0)) ?>" class="btn-card">Reservar</a>
                        <?php elseif ($estado === 'ocupada'): ?>
                            <a href="<?= base_url('pedidos?mesa=' . ($mesa['id'] ?? 0)) ?>" class="btn-card">Ver Pedido</a>
                        <?php else: ?>
                            <a href="<?= base_url('pedidos/agregar?mesa=' . ($mesa['id'] ?? 0)) ?>" class="btn-card">Reservar</a>
                        <?php endif; ?>
                        <a href="<?= base_url('menu') ?>" class="btn-card-2">Ver menu</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
// Actualizar estado de mesas cada 30 segundos
<?php 
// Condicion: activar auto-refresh solo para roles de staff
if (in_array($rolUsuario, ['admin', 'mesero', 'cocinero'])): 
?>
setInterval(function() {
    // Recargar pagina para actualizar estados
    // En produccion, usar AJAX para mejor UX
    // location.reload();
}, 30000);
<?php endif; ?>

// Funcion para cambiar estado de mesa rapidamente
function cambiarEstado(mesaId, nuevoEstado) {
    if (confirm('Cambiar estado de la mesa a: ' + nuevoEstado + '?')) {
        window.location.href = '<?= base_url('mesas/estado/') ?>' + mesaId + '/' + nuevoEstado;
    }
}
</script>
</body>
</html>
