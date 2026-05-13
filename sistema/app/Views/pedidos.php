<?php
/**
 * Vista de Gestion de Pedidos
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;
$rolUsuario = session('rol') ?? 'cliente';

// Obtener mensajes flash
$error = session()->getFlashdata('error');
$exito = session()->getFlashdata('exito');

// Datos de pedidos
$pedidos = $pedidos ?? [];
$totalPedidos = count($pedidos);

// Funcion para formatear precio
$formatearPrecio = function(int $precio): string {
    return '$' . number_format($precio, 0, ',', '.');
};

// Funcion para obtener clase segun estado
$obtenerClaseEstado = function(string $estado): string {
    $clases = [
        'pendiente' => 'estado-pendiente',
        'preparando' => 'estado-preparando',
        'listo' => 'estado-listo',
        'entregado' => 'estado-entregado'
    ];
    return $clases[strtolower($estado)] ?? 'estado-default';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Gestion de Pedidos - SISPE</title>
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
        .tabla { background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; overflow-x: auto; }
        .tabla h3 { margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #333; }
        th { background: rgba(79, 195, 247, 0.1); color: #4fc3f7; }
        tr:hover { background: rgba(255,255,255,0.03); }
        .edit { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; cursor: pointer; background: #27ae60; color: #fff; }
        .edit:hover { background: #2ecc71; }
        .alert { padding: 1rem; border-radius: 8px; margin: 0 5% 1rem; }
        .alert-error { background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #f44336; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        .empty-state { text-align: center; padding: 3rem; color: #888; }
        .estado-pendiente { color: #f39c12; }
        .estado-preparando { color: #3498db; }
        .estado-listo { color: #27ae60; }
        .estado-entregado { color: #95a5a6; }
        .estado-default { color: #7f8c8d; }
        .precio { color: #4fc3f7; font-weight: 600; }
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
        <h1>Gestion de Pedidos</h1>
        <p>Administra la informacion de clientes, empleados y administradores</p>
    </div>
    <div class="hero-boton">
        <?php if (in_array($rolUsuario, ['admin', 'mesero'])): ?>
            <a href="<?= base_url('pedidos/agregar') ?>" class="btn">+ Agregar pedido</a>
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
    <div class="tabla">
        <h3>Listado de Pedidos (<?= $totalPedidos ?>)</h3>
        <?php if ($totalPedidos > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Factura</th>
                        <th>ID Menu</th>
                        <th>Cantidad</th>
                        <th>Descripcion</th>
                        <th>Valor Venta</th>
                        <th>Numero Mesa</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Bucle: mostrar cada pedido
                    foreach ($pedidos as $pedido): 
                        $estado = $pedido['estado'] ?? 'pendiente';
                        $claseEstado = $obtenerClaseEstado($estado);
                    ?>
                        <tr>
                            <td><?= esc($pedido['id_factura'] ?? 'N/A') ?></td>
                            <td><?= esc($pedido['id_menu'] ?? '') ?></td>
                            <td><?= esc($pedido['cantidad'] ?? 1) ?></td>
                            <td><?= esc($pedido['descripcion'] ?? '') ?></td>
                            <td class="precio"><?= $formatearPrecio($pedido['valor_venta'] ?? 0) ?></td>
                            <td><?= esc($pedido['numero_mesa'] ?? '') ?></td>
                            <td class="<?= $claseEstado ?>"><?= ucfirst(esc($estado)) ?></td>
                            <td>
                                <?php 
                                // Condicion: mostrar boton segun estado y rol
                                if ($estado !== 'entregado' && in_array($rolUsuario, ['admin', 'mesero', 'cocinero'])): 
                                ?>
                                    <button class="edit" onclick="asignarPedido(<?= $pedido['id'] ?? 0 ?>)">
                                        <?php 
                                        // Condicion: texto del boton segun estado
                                        if ($estado === 'pendiente'): ?>
                                            Preparar
                                        <?php elseif ($estado === 'preparando'): ?>
                                            Listo
                                        <?php elseif ($estado === 'listo'): ?>
                                            Entregar
                                        <?php endif; ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No hay pedidos registrados.</p>
                <?php if (in_array($rolUsuario, ['admin', 'mesero'])): ?>
                    <a href="<?= base_url('pedidos/agregar') ?>" class="btn" style="display: inline-block; margin-top: 1rem; background: #4fc3f7; color: #1a1a2e; padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none;">Crear primer pedido</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function asignarPedido(id) {
    // Condicion: confirmar accion
    if (confirm('Cambiar estado del pedido?')) {
        window.location.href = '<?= base_url('pedidos/cambiar-estado/') ?>' + id;
    }
}
</script>
</body>
</html>
