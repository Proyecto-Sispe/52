<?php
/**
 * Vista de Gestion de Facturas
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;
$rolUsuario = session('rol') ?? 'cliente';

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Datos de facturas
$facturas = $facturas ?? [];
$totalFacturas = count($facturas);

// Funcion para formatear precio
$formatearPrecio = function(int $precio): string {
    return '$' . number_format($precio, 0, ',', '.');
};

// Funcion para calcular total de ventas
$calcularTotalVentas = function(array $facturas): int {
    $total = 0;
    foreach ($facturas as $factura) {
        $total += $factura['total'] ?? 0;
    }
    return $total;
};

$totalVentas = $calcularTotalVentas($facturas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Gestion de Facturas - SISPE</title>
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
        .panel { padding: 2rem 5%; }
        .filtros { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; }
        .filtros h3 { margin-bottom: 1rem; }
        .fila-filtros { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .fila-filtros > div { display: flex; flex-direction: column; gap: 0.3rem; }
        .fila-filtros label { color: #b0b0b0; font-size: 0.85rem; }
        .fila-filtros input { padding: 0.6rem; border: 1px solid #333; border-radius: 6px; background: #1a1a2e; color: #fff; min-width: 150px; }
        .btn-buscar { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .tabla { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; overflow-x: auto; }
        .tabla h3 { margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #333; }
        th { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; }
        tr:hover { background: rgba(255,255,255,0.03); }
        .edit, .delete { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.3rem; }
        .edit { background: #ffc107; color: #1a1a2e; }
        .delete { background: #f44336; color: #fff; }
        .alert { padding: 1rem; border-radius: 8px; margin: 0 5% 1rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        .empty-state { text-align: center; padding: 3rem; color: #888; }
        .stats { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .stat-card { background: rgba(79, 195, 247, 0.1); padding: 0.8rem 1.2rem; border-radius: 8px; }
        .stat-card strong { color: #4fc3f7; }
        .total { color: #4fc3f7; font-weight: 700; }
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
        <h1>Gestion de Facturas</h1>
        <p>Administra la informacion de las facturas generadas</p>
    </div>
    <div class="hero-boton">
        <?php if (in_array($rolUsuario, ['admin', 'mesero'])): ?>
            <a href="<?= base_url('facturas/agregar') ?>" class="btn">+ Agregar factura</a>
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
            <strong><?= $totalFacturas ?></strong> Total Facturas
        </div>
        <div class="stat-card">
            <strong><?= $formatearPrecio($totalVentas) ?></strong> Total Ventas
        </div>
    </div>

    <div class="filtros">
        <h3>Filtros de Busqueda</h3>
        <form action="<?= base_url('facturas') ?>" method="get" class="fila-filtros">
            <div>
                <label>Buscar</label>
                <input type="text" name="buscar" placeholder="Documento Cliente">
            </div>
            <div>
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde">
            </div>
            <div>
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta">
            </div>
            <button type="submit" class="btn-buscar">Buscar</button>
        </form>
    </div>

    <div class="tabla">
        <h3>Listado de Facturas</h3>
        <?php if ($totalFacturas > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Factura</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Id Mesa</th>
                        <th>Id Mesero</th>
                        <th>Documento Cliente</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <?php if ($rolUsuario === 'admin'): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Bucle: mostrar cada factura
                    foreach ($facturas as $factura): 
                    ?>
                        <tr>
                            <td><?= esc($factura['id'] ?? 'N/A') ?></td>
                            <td><?= esc($factura['fecha'] ?? '') ?></td>
                            <td><?= esc($factura['hora'] ?? '') ?></td>
                            <td><?= esc($factura['id_mesa'] ?? '') ?></td>
                            <td><?= esc($factura['id_mesero'] ?? '') ?></td>
                            <td><?= esc($factura['documento_cliente'] ?? '') ?></td>
                            <td><?= esc($factura['cliente'] ?? '') ?></td>
                            <td class="total"><?= $formatearPrecio($factura['total'] ?? 0) ?></td>
                            <?php if ($rolUsuario === 'admin'): ?>
                                <td>
                                    <a href="<?= base_url('facturas/editar/' . ($factura['id'] ?? 0)) ?>">
                                        <button class="edit">Editar</button>
                                    </a>
                                    <button class="delete" onclick="confirmarEliminar(<?= $factura['id'] ?? 0 ?>)">Eliminar</button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No hay facturas registradas.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function confirmarEliminar(id) {
    if (confirm('Esta seguro de eliminar esta factura?')) {
        window.location.href = '<?= base_url('facturas/eliminar/') ?>' + id;
    }
}
</script>
</body>
</html>
