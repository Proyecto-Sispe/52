<?php
/**
 * Vista del Mesero
 * Sistema SISPE - CodeIgniter 4
 * Panel de control para meseros con notificaciones
 */

$nombreUsuario = session('nombre') ?? 'Mesero';
$rolUsuario = session('rol') ?? 'mesero';

// Datos
$pedidosActivos = $pedidos ?? [];
$notificacionesListas = $notificaciones ?? [];
$mesasDisponibles = $mesas ?? [];
$stats = $estadisticas ?? [
    'pedidos_activos' => 0,
    'pedidos_listos' => 0,
    'mesas_ocupadas' => 0,
    'mesas_disponibles' => 0
];

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
    <title>Mesero - SISPE</title>
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
        .btn-logout { background: #e74c3c; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; }

        /* Contenedor */
        .container { padding: 1.5rem; max-width: 1600px; margin: 0 auto; }

        /* Panel de notificaciones */
        .notificaciones-panel { background: rgba(39, 174, 96, 0.1); border: 2px solid #27ae60; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; display: <?= count($notificacionesListas) > 0 ? 'block' : 'none' ?>; }
        .notificaciones-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .notificaciones-header h2 { color: #27ae60; display: flex; align-items: center; gap: 0.5rem; font-size: 1.2rem; }
        .notificaciones-header .badge { background: #27ae60; color: #fff; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
        .notificaciones-lista { display: flex; gap: 1rem; flex-wrap: wrap; }
        .notif-card { background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; border-left: 4px solid #27ae60; min-width: 200px; }
        .notif-card .mesa { font-weight: 700; color: #27ae60; font-size: 1.1rem; }
        .notif-card .productos { color: #b0b0b0; font-size: 0.9rem; margin: 0.3rem 0; }
        .notif-card .btn-entregar { background: #27ae60; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 0.5rem; }
        .notif-card .btn-entregar:hover { background: #219a52; }

        /* Estadisticas */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: rgba(255,255,255,0.05); padding: 1.2rem; border-radius: 12px; text-align: center; }
        .stat-card h3 { color: #b0b0b0; font-size: 0.85rem; margin-bottom: 0.3rem; }
        .stat-card .valor { font-size: 2rem; font-weight: 700; color: #4fc3f7; }
        .stat-card.listos .valor { color: #27ae60; }
        .stat-card.ocupadas .valor { color: #e74c3c; }
        .stat-card.disponibles .valor { color: #27ae60; }

        /* Filtros */
        .filtros { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        .filtros input, .filtros select { background: rgba(255,255,255,0.1); border: 1px solid #333; padding: 0.6rem 1rem; border-radius: 8px; color: #fff; }
        .filtros input::placeholder { color: #888; }
        .filtros input:focus, .filtros select:focus { outline: none; border-color: #4fc3f7; }
        .btn-refresh { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .auto-refresh { display: flex; align-items: center; gap: 0.5rem; color: #b0b0b0; font-size: 0.9rem; margin-left: auto; }

        /* Tabla de pedidos */
        .tabla-container { background: rgba(255,255,255,0.03); border-radius: 12px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: rgba(79, 195, 247, 0.2); padding: 1rem; text-align: left; font-weight: 600; color: #4fc3f7; }
        td { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        tr:hover { background: rgba(255,255,255,0.05); }
        
        /* Estados */
        .estado { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .estado.pendiente { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .estado.preparacion { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .estado.listo { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .estado.entregado { background: rgba(155, 89, 182, 0.2); color: #9b59b6; }

        /* Tiempo de espera */
        .tiempo-espera { display: flex; align-items: center; gap: 0.3rem; }
        .tiempo-espera.urgente { color: #e74c3c; font-weight: 600; }

        /* Botones de accion */
        .btn-accion { padding: 0.4rem 0.8rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s; }
        .btn-accion.entregar { background: #27ae60; color: #fff; }
        .btn-accion.entregar:hover { background: #219a52; }
        .btn-accion.ver { background: #3498db; color: #fff; }
        .btn-accion.ver:hover { background: #2980b9; }

        /* Responsive */
        @media (max-width: 768px) {
            .tabla-container { overflow-x: auto; }
            table { min-width: 700px; }
        }

        /* Alerta sonora */
        .alerta-sonora { position: fixed; bottom: 20px; right: 20px; background: #27ae60; color: #fff; padding: 1rem; border-radius: 8px; display: none; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

        /* Ultimo refresh */
        .ultimo-refresh { text-align: center; color: #666; font-size: 0.85rem; margin-top: 1rem; }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <img src="<?= base_url('images/logo2.png') ?>" alt="Logo SISPE">
        <h1>Panel de Mesero</h1>
    </div>
    <div class="nav-links">
        <a href="<?= base_url('dashboard') ?>">Dashboard</a>
        <a href="<?= base_url('mesero/mesas') ?>">Ver Mesas</a>
        <a href="<?= base_url('pedidos/agregar') ?>">Nuevo Pedido</a>
        <a href="<?= base_url('facturas') ?>">Facturas</a>
    </div>
    <div class="user-info">
        <span><?= esc($nombreUsuario) ?></span>
        <a href="<?= base_url('logout') ?>" class="btn-logout">Salir</a>
    </div>
</nav>

<div class="container">
    <!-- Panel de Notificaciones (Pedidos Listos) -->
    <div class="notificaciones-panel" id="notificaciones-panel">
        <div class="notificaciones-header">
            <h2>
                <svg width="24" height="24" fill="#27ae60" viewBox="0 0 16 16">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/>
                </svg>
                Pedidos Listos para Entregar
            </h2>
            <span class="badge" id="badge-listos"><?= count($notificacionesListas) ?></span>
        </div>
        <div class="notificaciones-lista" id="lista-notificaciones">
            <?php foreach ($notificacionesListas as $notif): ?>
                <div class="notif-card" data-id="<?= $notif['id'] ?>">
                    <div class="mesa">Mesa <?= $notif['mesa'] ?></div>
                    <div class="productos"><?= esc($notif['productos']) ?></div>
                    <div class="tiempo" style="color: #888; font-size: 0.8rem;">Hace <?= $notif['tiempo_espera'] ?> min</div>
                    <button class="btn-entregar" onclick="marcarEntregado(<?= $notif['id'] ?>)">Marcar Entregado</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Estadisticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Pedidos Activos</h3>
            <div class="valor"><?= $stats['pedidos_activos'] ?></div>
        </div>
        <div class="stat-card listos">
            <h3>Listos para Entregar</h3>
            <div class="valor"><?= $stats['pedidos_listos'] ?></div>
        </div>
        <div class="stat-card ocupadas">
            <h3>Mesas Ocupadas</h3>
            <div class="valor"><?= $stats['mesas_ocupadas'] ?></div>
        </div>
        <div class="stat-card disponibles">
            <h3>Mesas Disponibles</h3>
            <div class="valor"><?= $stats['mesas_disponibles'] ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <input type="text" id="buscar" placeholder="Buscar por mesa o cliente...">
        <select id="filtro-mesa">
            <option value="">Todas las mesas</option>
            <?php foreach ($mesasDisponibles as $mesa): ?>
                <option value="<?= $mesa['numero'] ?>">Mesa <?= $mesa['numero'] ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filtro-estado">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="preparacion">En Preparacion</option>
            <option value="listo">Listo</option>
        </select>
        <button class="btn-refresh" onclick="actualizarPedidos()">Actualizar</button>
        <div class="auto-refresh">
            <input type="checkbox" id="auto-refresh" checked>
            <label for="auto-refresh">Auto-refresh (15s)</label>
        </div>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="tabla-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mesa</th>
                    <th>Cliente</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th>Tiempo</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-pedidos">
                <?php if (empty($pedidosActivos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: #666;">No hay pedidos activos</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidosActivos as $pedido): ?>
                        <tr data-id="<?= $pedido['id'] ?>" data-mesa="<?= $pedido['mesa'] ?>" data-estado="<?= $pedido['estado'] ?>">
                            <td>#<?= $pedido['id'] ?></td>
                            <td><strong>Mesa <?= $pedido['mesa'] ?></strong></td>
                            <td><?= esc($pedido['cliente']) ?></td>
                            <td><?= esc($pedido['productos']) ?></td>
                            <td>
                                <span class="estado <?= $pedido['estado'] ?>">
                                    <?= ucfirst($pedido['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="tiempo-espera <?= $pedido['urgente'] ? 'urgente' : '' ?>">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <?= $pedido['tiempo_espera'] ?> min
                                    <?= $pedido['urgente'] ? '(Urgente)' : '' ?>
                                </span>
                            </td>
                            <td><strong><?= $formatearPrecio($pedido['total']) ?></strong></td>
                            <td>
                                <?php if ($pedido['estado'] === 'listo'): ?>
                                    <button class="btn-accion entregar" onclick="marcarEntregado(<?= $pedido['id'] ?>)">Entregar</button>
                                <?php else: ?>
                                    <button class="btn-accion ver" onclick="verDetalle(<?= $pedido['id'] ?>)">Ver</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="ultimo-refresh">
        Ultima actualizacion: <span id="ultima-actualizacion"><?= date('H:i:s') ?></span>
    </div>
</div>

<!-- Alerta Sonora -->
<div class="alerta-sonora" id="alerta-sonora">
    <strong>Pedido Listo!</strong>
    <span id="alerta-mensaje"></span>
</div>

<!-- Audio para alertas -->
<audio id="sonido-alerta" preload="auto">
    <source src="<?= base_url('sounds/notification.mp3') ?>" type="audio/mpeg">
</audio>

<script>
// Variables globales
let autoRefreshInterval = null;
let pedidosListosAnteriores = <?= count($notificacionesListas) ?>;
const REFRESH_INTERVAL = 15000; // 15 segundos

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
    document.getElementById('filtro-mesa').addEventListener('change', filtrarPedidos);
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

// Actualizar pedidos
function actualizarPedidos() {
    fetch('<?= base_url('mesero/api/pedidos') ?>')
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                document.getElementById('ultima-actualizacion').textContent = new Date().toLocaleTimeString();
                
                // Verificar si hay nuevos pedidos listos
                const pedidosListosNuevos = data.datos.filter(p => p.estado === 'listo').length;
                if (pedidosListosNuevos > pedidosListosAnteriores) {
                    reproducirAlerta();
                    mostrarAlerta('Hay pedidos listos para entregar!');
                }
                pedidosListosAnteriores = pedidosListosNuevos;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Marcar pedido como entregado
function marcarEntregado(pedidoId) {
    if (!confirm('Marcar pedido #' + pedidoId + ' como entregado?')) return;

    fetch(`<?= base_url('mesero/entregar/') ?>${pedidoId}`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            // Remover de la lista
            const card = document.querySelector(`.notif-card[data-id="${pedidoId}"]`);
            if (card) card.remove();
            
            const row = document.querySelector(`tr[data-id="${pedidoId}"]`);
            if (row) row.remove();
            
            // Actualizar contador
            const badge = document.getElementById('badge-listos');
            const count = parseInt(badge.textContent) - 1;
            badge.textContent = count;
            
            if (count === 0) {
                document.getElementById('notificaciones-panel').style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Ver detalle del pedido
function verDetalle(pedidoId) {
    window.location.href = '<?= base_url('pedidos/ver/') ?>' + pedidoId;
}

// Filtrar pedidos
function filtrarPedidos() {
    const busqueda = document.getElementById('buscar').value.toLowerCase();
    const mesa = document.getElementById('filtro-mesa').value;
    const estado = document.getElementById('filtro-estado').value;

    document.querySelectorAll('#tabla-pedidos tr').forEach(row => {
        if (!row.dataset.id) return;
        
        const texto = row.textContent.toLowerCase();
        const rowMesa = row.dataset.mesa;
        const rowEstado = row.dataset.estado;

        const coincideBusqueda = texto.includes(busqueda);
        const coincideMesa = !mesa || rowMesa === mesa;
        const coincideEstado = !estado || rowEstado === estado;

        row.style.display = (coincideBusqueda && coincideMesa && coincideEstado) ? '' : 'none';
    });
}

// Reproducir alerta sonora
function reproducirAlerta() {
    const audio = document.getElementById('sonido-alerta');
    if (audio) {
        audio.play().catch(() => {}); // Ignorar error si no puede reproducir
    }
}

// Mostrar alerta visual
function mostrarAlerta(mensaje) {
    const alerta = document.getElementById('alerta-sonora');
    document.getElementById('alerta-mensaje').textContent = mensaje;
    alerta.style.display = 'block';
    
    setTimeout(() => {
        alerta.style.display = 'none';
    }, 5000);
}
</script>
</body>
</html>
