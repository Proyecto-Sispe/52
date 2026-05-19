<?php
/**
 * Vista del Cocinero
 * Sistema SISPE - CodeIgniter 4
 * Panel de control para gestion de pedidos en cocina
 */

$nombreUsuario = session('nombre') ?? 'Cocinero';
$rolUsuario = session('rol') ?? 'cocinero';

// Datos de estadisticas
$stats = $estadisticas ?? [
    'pendientes' => 0,
    'preparacion' => 0,
    'listos' => 0,
    'urgentes' => 0
];

// Pedidos por estado
$pedidosPendientes = $pedidos_pendientes ?? [];
$pedidosPreparacion = $pedidos_preparacion ?? [];
$pedidosListos = $pedidos_listos ?? [];

// Formatear precio
$formatearPrecio = function(int $precio): string {
    return '$' . number_format($precio, 0, ',', '.');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Cocina - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        
        /* Navegacion */
        nav { background: #0f0f1a; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .nav-left { display: flex; align-items: center; gap: 1.5rem; }
        .nav-left img { height: 40px; }
        .nav-left h1 { font-size: 1.3rem; color: #4fc3f7; }
        .nav-links { display: flex; gap: 1rem; }
        .nav-links a { color: #fff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; transition: background 0.3s; }
        .nav-links a:hover { background: rgba(79, 195, 247, 0.2); }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .user-info span { color: #b0b0b0; }
        .btn-logout { background: #e74c3c; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; transition: background 0.3s; }
        .btn-logout:hover { background: #c0392b; }

        /* Contenedor principal */
        .container { padding: 1.5rem; max-width: 1600px; margin: 0 auto; }

        /* Estadisticas */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: rgba(255,255,255,0.05); padding: 1.2rem; border-radius: 12px; text-align: center; border-left: 4px solid #4fc3f7; }
        .stat-card.pendiente { border-left-color: #f39c12; }
        .stat-card.preparacion { border-left-color: #3498db; }
        .stat-card.listo { border-left-color: #27ae60; }
        .stat-card.urgente { border-left-color: #e74c3c; }
        .stat-card h3 { color: #b0b0b0; font-size: 0.85rem; margin-bottom: 0.3rem; }
        .stat-card .valor { font-size: 2rem; font-weight: 700; }
        .stat-card.pendiente .valor { color: #f39c12; }
        .stat-card.preparacion .valor { color: #3498db; }
        .stat-card.listo .valor { color: #27ae60; }
        .stat-card.urgente .valor { color: #e74c3c; }

        /* Filtros y busqueda */
        .filtros { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        .filtros input, .filtros select { background: rgba(255,255,255,0.1); border: 1px solid #333; padding: 0.6rem 1rem; border-radius: 8px; color: #fff; font-size: 0.95rem; }
        .filtros input::placeholder { color: #888; }
        .filtros input:focus, .filtros select:focus { outline: none; border-color: #4fc3f7; }
        .btn-refresh { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .btn-refresh:hover { background: #29b6f6; }
        .auto-refresh { display: flex; align-items: center; gap: 0.5rem; color: #b0b0b0; font-size: 0.9rem; margin-left: auto; }

        /* Columnas de pedidos */
        .pedidos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        @media (max-width: 1200px) { .pedidos-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .pedidos-grid { grid-template-columns: 1fr; } }

        .columna-pedidos { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 1rem; }
        .columna-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid; }
        .columna-header.pendiente { border-color: #f39c12; }
        .columna-header.preparacion { border-color: #3498db; }
        .columna-header.listo { border-color: #27ae60; }
        .columna-header h2 { font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; }
        .columna-header .count { background: rgba(255,255,255,0.1); padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.85rem; }

        /* Tarjeta de pedido */
        .pedido-card { background: rgba(255,255,255,0.05); border-radius: 10px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #4fc3f7; transition: transform 0.2s, box-shadow 0.2s; }
        .pedido-card:hover { transform: translateX(5px); box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .pedido-card.pendiente { border-left-color: #f39c12; }
        .pedido-card.preparacion { border-left-color: #3498db; }
        .pedido-card.listo { border-left-color: #27ae60; }
        .pedido-card.urgente { border-left-color: #e74c3c; animation: pulse 2s infinite; }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
        }

        .pedido-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; }
        .pedido-header .mesa { font-size: 1.2rem; font-weight: 700; color: #4fc3f7; }
        .pedido-header .tiempo { font-size: 0.85rem; color: #b0b0b0; display: flex; align-items: center; gap: 0.3rem; }
        .pedido-header .tiempo.urgente { color: #e74c3c; font-weight: 600; }

        .pedido-productos { margin-bottom: 0.8rem; }
        .producto-item { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .producto-item:last-child { border-bottom: none; }
        .producto-nombre { display: flex; align-items: center; gap: 0.5rem; }
        .producto-cantidad { background: #4fc3f7; color: #1a1a2e; padding: 0.1rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        .producto-observacion { font-size: 0.8rem; color: #f39c12; font-style: italic; margin-top: 0.2rem; }

        .pedido-acciones { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn-estado { flex: 1; padding: 0.6rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.3s; }
        .btn-estado.preparar { background: #3498db; color: #fff; }
        .btn-estado.preparar:hover { background: #2980b9; }
        .btn-estado.listo { background: #27ae60; color: #fff; }
        .btn-estado.listo:hover { background: #219a52; }
        .btn-estado.entregar { background: #9b59b6; color: #fff; }
        .btn-estado.entregar:hover { background: #8e44ad; }

        /* Sin pedidos */
        .sin-pedidos { text-align: center; padding: 2rem; color: #666; }
        .sin-pedidos svg { width: 48px; height: 48px; margin-bottom: 0.5rem; opacity: 0.5; }

        /* Notificacion */
        .notificacion { position: fixed; top: 80px; right: 20px; background: #27ae60; color: #fff; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); display: none; z-index: 1000; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* Ultimo refresh */
        .ultimo-refresh { text-align: center; color: #666; font-size: 0.85rem; margin-top: 1rem; }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <img src="<?= base_url('images/logo2.png') ?>" alt="Logo SISPE">
        <h1>Panel de Cocina</h1>
    </div>
    <div class="nav-links">
        <a href="<?= base_url('dashboard') ?>">Dashboard</a>
        <a href="<?= base_url('pedidos') ?>">Todos los Pedidos</a>
        <a href="<?= base_url('menu') ?>">Menu</a>
    </div>
    <div class="user-info">
        <span><?= esc($nombreUsuario) ?> (<?= ucfirst(esc($rolUsuario)) ?>)</span>
        <a href="<?= base_url('logout') ?>" class="btn-logout">Salir</a>
    </div>
</nav>

<div class="container">
    <!-- Estadisticas -->
    <div class="stats-grid">
        <div class="stat-card pendiente">
            <h3>Pendientes</h3>
            <div class="valor" id="stat-pendientes"><?= $stats['pendientes'] ?></div>
        </div>
        <div class="stat-card preparacion">
            <h3>En Preparacion</h3>
            <div class="valor" id="stat-preparacion"><?= $stats['preparacion'] ?></div>
        </div>
        <div class="stat-card listo">
            <h3>Listos</h3>
            <div class="valor" id="stat-listos"><?= $stats['listos'] ?></div>
        </div>
        <div class="stat-card urgente">
            <h3>Urgentes (+20 min)</h3>
            <div class="valor" id="stat-urgentes"><?= $stats['urgentes'] ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <input type="text" id="buscar" placeholder="Buscar por mesa o producto...">
        <select id="filtro-estado">
            <option value="todos">Todos los estados</option>
            <option value="pendiente">Pendientes</option>
            <option value="preparacion">En Preparacion</option>
            <option value="listo">Listos</option>
        </select>
        <button class="btn-refresh" onclick="actualizarPedidos()">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                <path d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
            </svg>
            Actualizar
        </button>
        <div class="auto-refresh">
            <input type="checkbox" id="auto-refresh" checked>
            <label for="auto-refresh">Auto-refresh (30s)</label>
        </div>
    </div>

    <!-- Columnas de pedidos -->
    <div class="pedidos-grid">
        <!-- Pendientes -->
        <div class="columna-pedidos">
            <div class="columna-header pendiente">
                <h2>
                    <svg width="20" height="20" fill="#f39c12" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    Pendientes
                </h2>
                <span class="count" id="count-pendientes"><?= count($pedidosPendientes) ?></span>
            </div>
            <div id="lista-pendientes">
                <?php if (empty($pedidosPendientes)): ?>
                    <div class="sin-pedidos">
                        <svg fill="#666" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.285 9.567a.5.5 0 0 1 .683.183A3.498 3.498 0 0 0 8 11.5a3.498 3.498 0 0 0 3.032-1.75.5.5 0 1 1 .866.5A4.498 4.498 0 0 1 8 12.5a4.498 4.498 0 0 1-3.898-2.25.5.5 0 0 1 .183-.683z"/>
                        </svg>
                        <p>No hay pedidos pendientes</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosPendientes as $pedido): ?>
                        <div class="pedido-card pendiente <?= $pedido['urgente'] ? 'urgente' : '' ?>" data-id="<?= $pedido['id'] ?>">
                            <div class="pedido-header">
                                <span class="mesa">Mesa <?= $pedido['mesa'] ?></span>
                                <span class="tiempo <?= $pedido['urgente'] ? 'urgente' : '' ?>">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <?= $pedido['tiempo_transcurrido'] ?> min
                                </span>
                            </div>
                            <div class="pedido-productos">
                                <?php foreach ($pedido['productos'] as $producto): ?>
                                    <div class="producto-item">
                                        <div>
                                            <div class="producto-nombre">
                                                <span class="producto-cantidad"><?= $producto['cantidad'] ?>x</span>
                                                <?= esc($producto['nombre']) ?>
                                            </div>
                                            <?php if (!empty($producto['observacion'])): ?>
                                                <div class="producto-observacion"><?= esc($producto['observacion']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pedido-acciones">
                                <button class="btn-estado preparar" onclick="cambiarEstado(<?= $pedido['id'] ?>, 'preparacion')">
                                    Iniciar Preparacion
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- En Preparacion -->
        <div class="columna-pedidos">
            <div class="columna-header preparacion">
                <h2>
                    <svg width="20" height="20" fill="#3498db" viewBox="0 0 16 16">
                        <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>
                        <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/>
                    </svg>
                    En Preparacion
                </h2>
                <span class="count" id="count-preparacion"><?= count($pedidosPreparacion) ?></span>
            </div>
            <div id="lista-preparacion">
                <?php if (empty($pedidosPreparacion)): ?>
                    <div class="sin-pedidos">
                        <svg fill="#666" viewBox="0 0 16 16">
                            <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>
                        </svg>
                        <p>No hay pedidos en preparacion</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosPreparacion as $pedido): ?>
                        <div class="pedido-card preparacion <?= $pedido['urgente'] ? 'urgente' : '' ?>" data-id="<?= $pedido['id'] ?>">
                            <div class="pedido-header">
                                <span class="mesa">Mesa <?= $pedido['mesa'] ?></span>
                                <span class="tiempo <?= $pedido['urgente'] ? 'urgente' : '' ?>">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <?= $pedido['tiempo_transcurrido'] ?> min
                                </span>
                            </div>
                            <div class="pedido-productos">
                                <?php foreach ($pedido['productos'] as $producto): ?>
                                    <div class="producto-item">
                                        <div>
                                            <div class="producto-nombre">
                                                <span class="producto-cantidad"><?= $producto['cantidad'] ?>x</span>
                                                <?= esc($producto['nombre']) ?>
                                            </div>
                                            <?php if (!empty($producto['observacion'])): ?>
                                                <div class="producto-observacion"><?= esc($producto['observacion']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pedido-acciones">
                                <button class="btn-estado listo" onclick="cambiarEstado(<?= $pedido['id'] ?>, 'listo')">
                                    Marcar Listo
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Listos -->
        <div class="columna-pedidos">
            <div class="columna-header listo">
                <h2>
                    <svg width="20" height="20" fill="#27ae60" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                    Listos para Servir
                </h2>
                <span class="count" id="count-listos"><?= count($pedidosListos) ?></span>
            </div>
            <div id="lista-listos">
                <?php if (empty($pedidosListos)): ?>
                    <div class="sin-pedidos">
                        <svg fill="#666" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <p>No hay pedidos listos</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosListos as $pedido): ?>
                        <div class="pedido-card listo" data-id="<?= $pedido['id'] ?>">
                            <div class="pedido-header">
                                <span class="mesa">Mesa <?= $pedido['mesa'] ?></span>
                                <span class="tiempo">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <?= $pedido['tiempo_transcurrido'] ?> min
                                </span>
                            </div>
                            <div class="pedido-productos">
                                <?php foreach ($pedido['productos'] as $producto): ?>
                                    <div class="producto-item">
                                        <div>
                                            <div class="producto-nombre">
                                                <span class="producto-cantidad"><?= $producto['cantidad'] ?>x</span>
                                                <?= esc($producto['nombre']) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pedido-acciones">
                                <button class="btn-estado entregar" onclick="cambiarEstado(<?= $pedido['id'] ?>, 'entregado')">
                                    Marcar Entregado
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ultimo-refresh">
        Ultima actualizacion: <span id="ultima-actualizacion"><?= date('H:i:s') ?></span>
    </div>
</div>

<!-- Notificacion -->
<div class="notificacion" id="notificacion"></div>

<script>
// Variables globales
let autoRefreshInterval = null;
const REFRESH_INTERVAL = 30000; // 30 segundos

// Inicializacion
document.addEventListener('DOMContentLoaded', function() {
    iniciarAutoRefresh();
    
    // Event listeners
    document.getElementById('auto-refresh').addEventListener('change', function() {
        if (this.checked) {
            iniciarAutoRefresh();
        } else {
            detenerAutoRefresh();
        }
    });

    document.getElementById('buscar').addEventListener('input', filtrarPedidos);
    document.getElementById('filtro-estado').addEventListener('change', filtrarPedidos);
});

// Auto-refresh
function iniciarAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(actualizarPedidos, REFRESH_INTERVAL);
}

function detenerAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Actualizar pedidos via AJAX
function actualizarPedidos() {
    fetch('<?= base_url('api/pedidos') ?>')
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                // Actualizar interfaz con nuevos datos
                document.getElementById('ultima-actualizacion').textContent = new Date().toLocaleTimeString();
                mostrarNotificacion('Pedidos actualizados', 'success');
            }
        })
        .catch(error => {
            console.error('Error al actualizar:', error);
        });
}

// Cambiar estado de pedido
function cambiarEstado(pedidoId, nuevoEstado) {
    fetch(`<?= base_url('cocina/pedido/') ?>${pedidoId}/${nuevoEstado}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            mostrarNotificacion(data.mensaje, 'success');
            // En produccion: actualizar lista de pedidos
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacion(data.mensaje, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al cambiar estado', 'error');
    });
}

// Filtrar pedidos
function filtrarPedidos() {
    const busqueda = document.getElementById('buscar').value.toLowerCase();
    const estado = document.getElementById('filtro-estado').value;
    
    document.querySelectorAll('.pedido-card').forEach(card => {
        const texto = card.textContent.toLowerCase();
        const cardEstado = card.classList.contains('pendiente') ? 'pendiente' : 
                          card.classList.contains('preparacion') ? 'preparacion' : 'listo';
        
        const coincideBusqueda = texto.includes(busqueda);
        const coincideEstado = estado === 'todos' || cardEstado === estado;
        
        card.style.display = (coincideBusqueda && coincideEstado) ? 'block' : 'none';
    });
}

// Mostrar notificacion
function mostrarNotificacion(mensaje, tipo) {
    const notif = document.getElementById('notificacion');
    notif.textContent = mensaje;
    notif.style.background = tipo === 'success' ? '#27ae60' : '#e74c3c';
    notif.style.display = 'block';
    
    setTimeout(() => {
        notif.style.display = 'none';
    }, 3000);
}

// Sonido de alerta para pedidos urgentes
function reproducirAlerta() {
    // En produccion: reproducir sonido
    // const audio = new Audio('<?= base_url('sounds/alert.mp3') ?>');
    // audio.play();
}
</script>
</body>
</html>
