<?php
/**
 * Vista de Mesas
 * Sistema SISPE - CodeIgniter 4
 * Mapa visual de mesas del restaurante
 */

$nombreUsuario = session('nombre') ?? 'Usuario';
$rolUsuario = session('rol') ?? 'mesero';

// Datos de mesas
$mesasList = $mesas ?? [];

// Contar mesas por estado
$mesasDisponibles = array_filter($mesasList, fn($m) => $m['estado'] === 'disponible');
$mesasOcupadas = array_filter($mesasList, fn($m) => $m['estado'] === 'ocupada');
$mesasReservadas = array_filter($mesasList, fn($m) => $m['estado'] === 'reservada');
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
        
        /* Navegacion */
        nav { background: #0f0f1a; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .nav-left { display: flex; align-items: center; gap: 1.5rem; }
        .nav-left img { height: 40px; }
        .nav-left h1 { font-size: 1.3rem; color: #4fc3f7; }
        .nav-links { display: flex; gap: 1rem; }
        .nav-links a { color: #fff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; transition: background 0.3s; }
        .nav-links a:hover { background: rgba(79, 195, 247, 0.2); }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .btn-logout { background: #e74c3c; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; }

        /* Contenedor */
        .container { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }

        /* Leyenda */
        .leyenda { display: flex; gap: 2rem; margin-bottom: 1.5rem; flex-wrap: wrap; justify-content: center; }
        .leyenda-item { display: flex; align-items: center; gap: 0.5rem; }
        .leyenda-color { width: 20px; height: 20px; border-radius: 4px; }
        .leyenda-color.disponible { background: #27ae60; }
        .leyenda-color.ocupada { background: #e74c3c; }
        .leyenda-color.reservada { background: #f39c12; }

        /* Filtros */
        .filtros { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; justify-content: center; }
        .filtro-btn { background: rgba(255,255,255,0.1); border: none; padding: 0.6rem 1.2rem; border-radius: 20px; color: #fff; cursor: pointer; transition: all 0.3s; }
        .filtro-btn.active { background: #4fc3f7; color: #1a1a2e; }
        .filtro-btn:hover:not(.active) { background: rgba(255,255,255,0.2); }

        /* Estadisticas */
        .stats { display: flex; gap: 1rem; margin-bottom: 1.5rem; justify-content: center; flex-wrap: wrap; }
        .stat-item { background: rgba(255,255,255,0.05); padding: 1rem 2rem; border-radius: 12px; text-align: center; }
        .stat-item .valor { font-size: 2rem; font-weight: 700; }
        .stat-item.disponible .valor { color: #27ae60; }
        .stat-item.ocupada .valor { color: #e74c3c; }
        .stat-item.reservada .valor { color: #f39c12; }
        .stat-item h4 { color: #b0b0b0; font-size: 0.85rem; }

        /* Grid de mesas */
        .mesas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; }

        /* Tarjeta de mesa */
        .mesa-card { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 1.5rem; text-align: center; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; border: 3px solid transparent; }
        .mesa-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .mesa-card.disponible { border-color: #27ae60; }
        .mesa-card.ocupada { border-color: #e74c3c; }
        .mesa-card.reservada { border-color: #f39c12; }

        .mesa-numero { font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .mesa-card.disponible .mesa-numero { color: #27ae60; }
        .mesa-card.ocupada .mesa-numero { color: #e74c3c; }
        .mesa-card.reservada .mesa-numero { color: #f39c12; }

        .mesa-estado { display: inline-block; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.8rem; }
        .mesa-card.disponible .mesa-estado { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .mesa-card.ocupada .mesa-estado { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .mesa-card.reservada .mesa-estado { background: rgba(243, 156, 18, 0.2); color: #f39c12; }

        .mesa-info { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .mesa-info svg { vertical-align: middle; margin-right: 0.3rem; }

        .mesa-pedidos { background: rgba(79, 195, 247, 0.1); padding: 0.5rem; border-radius: 8px; margin: 0.5rem 0; }
        .mesa-pedidos span { color: #4fc3f7; font-weight: 600; }

        .mesa-acciones { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .btn-mesa { flex: 1; padding: 0.6rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.3s; }
        .btn-mesa.qr { background: #9b59b6; color: #fff; }
        .btn-mesa.qr:hover { background: #8e44ad; }
        .btn-mesa.ver { background: #3498db; color: #fff; }
        .btn-mesa.ver:hover { background: #2980b9; }
        .btn-mesa.ocupar { background: #e74c3c; color: #fff; }
        .btn-mesa.ocupar:hover { background: #c0392b; }
        .btn-mesa.liberar { background: #27ae60; color: #fff; }
        .btn-mesa.liberar:hover { background: #219a52; }

        /* Ubicacion tag */
        .ubicacion-tag { background: rgba(255,255,255,0.1); padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; color: #888; }

        /* Modal QR */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-qr { background: #1a1a2e; border-radius: 16px; padding: 2rem; text-align: center; max-width: 400px; width: 90%; }
        .modal-qr h2 { margin-bottom: 1rem; color: #4fc3f7; }
        .qr-code { background: #fff; padding: 1rem; border-radius: 12px; display: inline-block; margin: 1rem 0; }
        .qr-code svg { width: 200px; height: 200px; }
        .codigo-mesa { background: rgba(79, 195, 247, 0.1); padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .codigo-mesa .codigo { font-size: 2rem; font-weight: 700; color: #4fc3f7; letter-spacing: 0.3rem; }
        .btn-cerrar-modal { background: #4fc3f7; color: #1a1a2e; border: none; padding: 0.8rem 2rem; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 1rem; }

        /* Responsive */
        @media (max-width: 768px) {
            .mesas-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .mesas-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<nav>
    <div class="nav-left">
        <img src="<?= base_url('images/logo2.png') ?>" alt="Logo SISPE">
        <h1>Gestion de Mesas</h1>
    </div>
    <div class="nav-links">
        <a href="<?= base_url('dashboard') ?>">Dashboard</a>
        <a href="<?= base_url('mesero') ?>">Panel Mesero</a>
        <a href="<?= base_url('pedidos') ?>">Pedidos</a>
        <a href="<?= base_url('mesas/agregar') ?>">+ Nueva Mesa</a>
    </div>
    <div class="user-info">
        <span style="color: #b0b0b0;"><?= esc($nombreUsuario) ?></span>
        <a href="<?= base_url('logout') ?>" class="btn-logout">Salir</a>
    </div>
</nav>

<div class="container">
    <!-- Leyenda -->
    <div class="leyenda">
        <div class="leyenda-item">
            <div class="leyenda-color disponible"></div>
            <span>Disponible</span>
        </div>
        <div class="leyenda-item">
            <div class="leyenda-color ocupada"></div>
            <span>Ocupada</span>
        </div>
        <div class="leyenda-item">
            <div class="leyenda-color reservada"></div>
            <span>Reservada</span>
        </div>
    </div>

    <!-- Estadisticas -->
    <div class="stats">
        <div class="stat-item disponible">
            <div class="valor"><?= count($mesasDisponibles) ?></div>
            <h4>Disponibles</h4>
        </div>
        <div class="stat-item ocupada">
            <div class="valor"><?= count($mesasOcupadas) ?></div>
            <h4>Ocupadas</h4>
        </div>
        <div class="stat-item reservada">
            <div class="valor"><?= count($mesasReservadas) ?></div>
            <h4>Reservadas</h4>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <button class="filtro-btn active" data-filtro="todos">Todas</button>
        <button class="filtro-btn" data-filtro="disponible">Disponibles</button>
        <button class="filtro-btn" data-filtro="ocupada">Ocupadas</button>
        <button class="filtro-btn" data-filtro="reservada">Reservadas</button>
        <button class="filtro-btn" data-filtro="Interior">Interior</button>
        <button class="filtro-btn" data-filtro="Terraza">Terraza</button>
        <button class="filtro-btn" data-filtro="Barra">Barra</button>
    </div>

    <!-- Grid de Mesas -->
    <div class="mesas-grid" id="mesas-grid">
        <?php foreach ($mesasList as $mesa): ?>
            <div class="mesa-card <?= $mesa['estado'] ?>" 
                 data-id="<?= $mesa['id'] ?>" 
                 data-estado="<?= $mesa['estado'] ?>"
                 data-ubicacion="<?= $mesa['ubicacion'] ?>">
                
                <span class="ubicacion-tag"><?= esc($mesa['ubicacion']) ?></span>
                
                <div class="mesa-numero"><?= $mesa['numero'] ?></div>
                
                <div class="mesa-estado">
                    <?= ucfirst($mesa['estado']) ?>
                </div>
                
                <div class="mesa-info">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                        <path d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                        <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                    </svg>
                    Capacidad: <?= $mesa['capacidad'] ?> personas
                </div>
                
                <?php if ($mesa['pedidos_activos'] > 0): ?>
                    <div class="mesa-pedidos">
                        <span><?= $mesa['pedidos_activos'] ?></span> pedido(s) activo(s)
                    </div>
                <?php endif; ?>
                
                <div class="mesa-acciones">
                    <?php if ($mesa['estado'] === 'disponible'): ?>
                        <button class="btn-mesa qr" onclick="mostrarQR(<?= $mesa['id'] ?>, <?= $mesa['numero'] ?>)">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle;">
                                <path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0v-3zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5zM.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5zm15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5z"/>
                            </svg>
                            QR
                        </button>
                        <button class="btn-mesa ocupar" onclick="cambiarEstado(<?= $mesa['id'] ?>, 'ocupada')">Ocupar</button>
                    <?php elseif ($mesa['estado'] === 'ocupada'): ?>
                        <button class="btn-mesa ver" onclick="verPedidos(<?= $mesa['id'] ?>)">Ver Pedidos</button>
                        <button class="btn-mesa liberar" onclick="cambiarEstado(<?= $mesa['id'] ?>, 'disponible')">Liberar</button>
                    <?php else: ?>
                        <button class="btn-mesa ocupar" onclick="cambiarEstado(<?= $mesa['id'] ?>, 'ocupada')">Ocupar</button>
                        <button class="btn-mesa liberar" onclick="cambiarEstado(<?= $mesa['id'] ?>, 'disponible')">Cancelar</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal QR -->
<div class="modal-overlay" id="modal-qr">
    <div class="modal-qr">
        <h2>Codigo QR - Mesa <span id="qr-mesa-numero"></span></h2>
        <p style="color: #b0b0b0;">Escanea para acceder al menu digital</p>
        
        <div class="qr-code">
            <!-- Placeholder para QR -->
            <svg viewBox="0 0 100 100" fill="#1a1a2e">
                <rect x="10" y="10" width="20" height="20"/>
                <rect x="70" y="10" width="20" height="20"/>
                <rect x="10" y="70" width="20" height="20"/>
                <rect x="40" y="10" width="10" height="10"/>
                <rect x="40" y="30" width="10" height="10"/>
                <rect x="50" y="40" width="10" height="10"/>
                <rect x="30" y="50" width="10" height="10"/>
                <rect x="60" y="60" width="10" height="10"/>
                <rect x="70" y="50" width="10" height="20"/>
                <rect x="40" y="70" width="20" height="10"/>
            </svg>
        </div>
        
        <div class="codigo-mesa">
            <p style="color: #b0b0b0; margin-bottom: 0.5rem;">Codigo de acceso:</p>
            <div class="codigo" id="codigo-acceso">ABC123</div>
        </div>
        
        <p style="color: #888; font-size: 0.85rem;">El cliente debe ingresar este codigo en la app para hacer pedidos</p>
        
        <button class="btn-cerrar-modal" onclick="cerrarModalQR()">Cerrar</button>
    </div>
</div>

<script>
// Filtrar mesas
document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filtro = this.dataset.filtro;
        
        document.querySelectorAll('.mesa-card').forEach(card => {
            const estado = card.dataset.estado;
            const ubicacion = card.dataset.ubicacion;
            
            let mostrar = false;
            
            if (filtro === 'todos') {
                mostrar = true;
            } else if (['disponible', 'ocupada', 'reservada'].includes(filtro)) {
                mostrar = estado === filtro;
            } else {
                // Filtro por ubicacion
                mostrar = ubicacion === filtro;
            }
            
            card.style.display = mostrar ? 'block' : 'none';
        });
    });
});

// Mostrar modal QR
function mostrarQR(mesaId, mesaNumero) {
    document.getElementById('qr-mesa-numero').textContent = mesaNumero;
    
    // Generar codigo aleatorio
    const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numeros = '0123456789';
    let codigo = '';
    for (let i = 0; i < 3; i++) codigo += letras.charAt(Math.floor(Math.random() * letras.length));
    for (let i = 0; i < 3; i++) codigo += numeros.charAt(Math.floor(Math.random() * numeros.length));
    
    document.getElementById('codigo-acceso').textContent = codigo;
    document.getElementById('modal-qr').classList.add('active');
}

// Cerrar modal QR
function cerrarModalQR() {
    document.getElementById('modal-qr').classList.remove('active');
}

// Cambiar estado de mesa
function cambiarEstado(mesaId, nuevoEstado) {
    const accion = nuevoEstado === 'ocupada' ? 'ocupar' : 'liberar';
    
    if (!confirm(`Deseas ${accion} esta mesa?`)) return;
    
    fetch(`<?= base_url('mesas/estado/') ?>${mesaId}/${nuevoEstado}`, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            location.reload();
        } else {
            alert('Error: ' + data.mensaje);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Simular cambio para demo
        location.reload();
    });
}

// Ver pedidos de mesa
function verPedidos(mesaId) {
    window.location.href = '<?= base_url('pedidos') ?>?mesa=' + mesaId;
}

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalQR();
    }
});

// Cerrar modal clickeando fuera
document.getElementById('modal-qr').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalQR();
    }
});
</script>
</body>
</html>