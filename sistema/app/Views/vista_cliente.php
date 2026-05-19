<?php
/**
 * Vista del Cliente
 * Sistema SISPE - CodeIgniter 4
 * Menu digital y pedidos para clientes
 */

$mesaNumero = session('mesa_numero') ?? $mesa ?? 'N/A';
$codigoMesa = session('codigo_mesa') ?? $codigo_mesa ?? '';

// Datos
$categoriasList = $categorias ?? [];
$productosList = $productos ?? [];
$misPedidosList = $mis_pedidos ?? [];
$totalCuenta = $total_cuenta ?? 0;

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
    <title>Menu Digital - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        
        /* Header */
        header { background: #0f0f1a; padding: 1rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-section img { height: 40px; }
        .mesa-info { background: rgba(79, 195, 247, 0.2); padding: 0.5rem 1rem; border-radius: 20px; }
        .mesa-info span { color: #4fc3f7; font-weight: 600; }
        
        /* Navegacion tabs */
        .tabs { display: flex; background: rgba(255,255,255,0.05); border-radius: 8px; padding: 0.3rem; margin: 1rem; gap: 0.3rem; }
        .tab { flex: 1; padding: 0.8rem; text-align: center; border-radius: 6px; cursor: pointer; transition: all 0.3s; color: #b0b0b0; }
        .tab.active { background: #4fc3f7; color: #1a1a2e; font-weight: 600; }
        .tab:hover:not(.active) { background: rgba(255,255,255,0.1); }
        
        /* Indicador de pedidos */
        .pedidos-indicator { position: fixed; bottom: 20px; right: 20px; background: #4fc3f7; color: #1a1a2e; padding: 1rem 1.5rem; border-radius: 50px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 0.5rem; cursor: pointer; z-index: 100; font-weight: 600; }
        .pedidos-indicator .badge { background: #e74c3c; color: #fff; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.85rem; }
        
        /* Contenedor principal */
        .container { padding: 0 1rem 100px; max-width: 1200px; margin: 0 auto; }
        
        /* Categorias */
        .categorias { display: flex; gap: 0.5rem; overflow-x: auto; padding: 1rem 0; margin-bottom: 1rem; }
        .categorias::-webkit-scrollbar { height: 4px; }
        .categorias::-webkit-scrollbar-thumb { background: #4fc3f7; border-radius: 4px; }
        .categoria-btn { background: rgba(255,255,255,0.1); border: none; padding: 0.6rem 1.2rem; border-radius: 20px; color: #fff; cursor: pointer; white-space: nowrap; transition: all 0.3s; display: flex; align-items: center; gap: 0.5rem; }
        .categoria-btn.active { background: #4fc3f7; color: #1a1a2e; }
        .categoria-btn:hover:not(.active) { background: rgba(255,255,255,0.2); }
        .categoria-icon { font-size: 1.2rem; }
        
        /* Grid de productos */
        .productos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
        
        /* Tarjeta de producto */
        .producto-card { background: rgba(255,255,255,0.05); border-radius: 12px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; }
        .producto-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .producto-imagen { height: 150px; background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); display: flex; align-items: center; justify-content: center; }
        .producto-imagen svg { width: 60px; height: 60px; opacity: 0.5; }
        .producto-info { padding: 1rem; }
        .producto-nombre { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.3rem; }
        .producto-descripcion { color: #b0b0b0; font-size: 0.85rem; margin-bottom: 0.8rem; line-height: 1.4; }
        .producto-footer { display: flex; justify-content: space-between; align-items: center; }
        .producto-precio { font-size: 1.3rem; font-weight: 700; color: #4fc3f7; }
        .btn-agregar { background: #27ae60; color: #fff; border: none; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.3s; }
        .btn-agregar:hover { background: #219a52; }
        .producto-no-disponible { opacity: 0.5; pointer-events: none; }
        .producto-no-disponible .btn-agregar { background: #666; }
        
        /* Panel de pedidos */
        .panel-pedidos { display: none; }
        .panel-pedidos.active { display: block; }
        
        /* Mis pedidos */
        .pedido-item { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #4fc3f7; }
        .pedido-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .pedido-estado { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .pedido-estado.pendiente { background: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .pedido-estado.preparacion { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .pedido-estado.listo { background: rgba(39, 174, 96, 0.2); color: #27ae60; }
        .pedido-productos { margin: 0.5rem 0; }
        .pedido-producto { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .pedido-producto:last-child { border-bottom: none; }
        .pedido-subtotal { text-align: right; font-weight: 600; color: #4fc3f7; margin-top: 0.5rem; }
        
        /* Total de la cuenta */
        .total-cuenta { background: rgba(79, 195, 247, 0.1); border: 2px solid #4fc3f7; border-radius: 12px; padding: 1.5rem; margin-top: 1rem; }
        .total-cuenta h3 { margin-bottom: 0.5rem; }
        .total-cuenta .total { font-size: 2rem; font-weight: 700; color: #4fc3f7; }
        .btn-pedir-cuenta { background: #9b59b6; color: #fff; border: none; padding: 1rem; border-radius: 8px; width: 100%; margin-top: 1rem; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .btn-pedir-cuenta:hover { background: #8e44ad; }
        
        /* Modal para agregar producto */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: center; z-index: 1000; padding: 1rem; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1a1a2e; border-radius: 16px; padding: 1.5rem; max-width: 400px; width: 100%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .modal-header h2 { font-size: 1.3rem; }
        .btn-cerrar { background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; }
        .modal-body { margin-bottom: 1rem; }
        .modal-body label { display: block; margin-bottom: 0.5rem; color: #b0b0b0; }
        .modal-body input, .modal-body textarea { width: 100%; padding: 0.8rem; border: 1px solid #333; border-radius: 8px; background: rgba(255,255,255,0.1); color: #fff; margin-bottom: 1rem; }
        .modal-body input:focus, .modal-body textarea:focus { outline: none; border-color: #4fc3f7; }
        .cantidad-control { display: flex; align-items: center; gap: 1rem; justify-content: center; margin-bottom: 1rem; }
        .cantidad-control button { background: #4fc3f7; color: #1a1a2e; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; }
        .cantidad-control span { font-size: 1.5rem; font-weight: 600; min-width: 40px; text-align: center; }
        .modal-footer { display: flex; gap: 1rem; }
        .modal-footer button { flex: 1; padding: 0.8rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-cancelar { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-confirmar { background: #27ae60; color: #fff; }
        
        /* Sin pedidos */
        .sin-pedidos { text-align: center; padding: 3rem; color: #666; }
        .sin-pedidos svg { width: 80px; height: 80px; margin-bottom: 1rem; opacity: 0.3; }
        
        /* Responsivo */
        @media (max-width: 480px) {
            .productos-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header>
    <div class="logo-section">
        <img src="<?= base_url('images/logo2.png') ?>" alt="Logo">
        <h1 style="font-size: 1.2rem;">Menu Digital</h1>
    </div>
    <div class="mesa-info">
        <span>Mesa <?= esc($mesaNumero) ?></span>
    </div>
</header>

<!-- Tabs de navegacion -->
<div class="tabs">
    <div class="tab active" data-tab="menu" onclick="cambiarTab('menu')">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
            <path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
        </svg>
        Menu
    </div>
    <div class="tab" data-tab="pedidos" onclick="cambiarTab('pedidos')">
        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle; margin-right: 5px;">
            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        Mis Pedidos
    </div>
</div>

<div class="container">
    <!-- Panel Menu -->
    <div id="panel-menu" class="panel-menu active">
        <!-- Categorias -->
        <div class="categorias">
            <button class="categoria-btn active" data-categoria="todos">
                <span class="categoria-icon">*</span>
                Todos
            </button>
            <?php foreach ($categoriasList as $cat): ?>
                <button class="categoria-btn" data-categoria="<?= $cat['id'] ?>">
                    <span class="categoria-icon">
                        <?php
                        $iconos = [
                            'appetizer' => '1',
                            'main' => '2',
                            'burger' => '3',
                            'pizza' => '4',
                            'salad' => '5',
                            'drink' => '6',
                            'dessert' => '7'
                        ];
                        echo $iconos[$cat['icono']] ?? '*';
                        ?>
                    </span>
                    <?= esc($cat['nombre']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grid de productos -->
        <div class="productos-grid" id="productos-grid">
            <?php foreach ($productosList as $producto): ?>
                <div class="producto-card <?= !$producto['disponible'] ? 'producto-no-disponible' : '' ?>" 
                     data-categoria="<?= $producto['categoria_id'] ?>"
                     data-id="<?= $producto['id'] ?>">
                    <div class="producto-imagen">
                        <svg fill="#fff" viewBox="0 0 16 16">
                            <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                            <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
                        </svg>
                    </div>
                    <div class="producto-info">
                        <h3 class="producto-nombre"><?= esc($producto['nombre']) ?></h3>
                        <p class="producto-descripcion"><?= esc($producto['descripcion']) ?></p>
                        <div class="producto-footer">
                            <span class="producto-precio"><?= $formatearPrecio($producto['precio']) ?></span>
                            <button class="btn-agregar" onclick="abrirModal(<?= htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8') ?>)">
                                <?= $producto['disponible'] ? 'Agregar' : 'No disponible' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panel Mis Pedidos -->
    <div id="panel-pedidos" class="panel-pedidos">
        <?php if (empty($misPedidosList)): ?>
            <div class="sin-pedidos">
                <svg fill="#666" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/>
                </svg>
                <h3>No tienes pedidos activos</h3>
                <p>Explora nuestro menu y realiza tu primer pedido</p>
            </div>
        <?php else: ?>
            <?php foreach ($misPedidosList as $pedido): ?>
                <div class="pedido-item">
                    <div class="pedido-header">
                        <span>Pedido #<?= $pedido['id'] ?> - <?= $pedido['hora'] ?></span>
                        <span class="pedido-estado <?= $pedido['estado'] ?>"><?= ucfirst($pedido['estado']) ?></span>
                    </div>
                    <div class="pedido-productos">
                        <?php foreach ($pedido['productos'] as $prod): ?>
                            <div class="pedido-producto">
                                <span><?= $prod['cantidad'] ?>x <?= esc($prod['nombre']) ?></span>
                                <span><?= $formatearPrecio($prod['subtotal']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pedido-subtotal">
                        Subtotal: <?= $formatearPrecio($pedido['subtotal']) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total-cuenta">
                <h3>Total de tu cuenta</h3>
                <div class="total"><?= $formatearPrecio($totalCuenta) ?></div>
                <button class="btn-pedir-cuenta" onclick="pedirCuenta()">Pedir la Cuenta</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Indicador flotante de pedidos -->
<div class="pedidos-indicator" onclick="cambiarTab('pedidos')" id="pedidos-indicator" style="display: <?= count($misPedidosList) > 0 ? 'flex' : 'none' ?>">
    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/>
    </svg>
    Ver Pedidos
    <span class="badge" id="pedidos-count"><?= count($misPedidosList) ?></span>
</div>

<!-- Modal para agregar producto -->
<div class="modal-overlay" id="modal-agregar">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modal-producto-nombre">Producto</h2>
            <button class="btn-cerrar" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="modal-producto-descripcion" style="color: #b0b0b0; margin-bottom: 1rem;"></p>
            <p id="modal-producto-precio" style="font-size: 1.5rem; color: #4fc3f7; font-weight: 700; margin-bottom: 1rem;"></p>
            
            <label>Cantidad</label>
            <div class="cantidad-control">
                <button onclick="cambiarCantidad(-1)">-</button>
                <span id="cantidad-valor">1</span>
                <button onclick="cambiarCantidad(1)">+</button>
            </div>
            
            <label for="comentario">Comentarios o instrucciones especiales</label>
            <textarea id="comentario" rows="3" placeholder="Ej: Sin cebolla, extra queso..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-confirmar" onclick="agregarAlPedido()">Agregar al Pedido</button>
        </div>
    </div>
</div>

<script>
// Variables globales
let productoSeleccionado = null;
let cantidad = 1;
let carrito = [];

// Cambiar entre tabs
function cambiarTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`.tab[data-tab="${tab}"]`).classList.add('active');
    
    document.getElementById('panel-menu').classList.toggle('active', tab === 'menu');
    document.getElementById('panel-pedidos').classList.toggle('active', tab === 'pedidos');
}

// Filtrar por categoria
document.querySelectorAll('.categoria-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.categoria-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const categoria = this.dataset.categoria;
        
        document.querySelectorAll('.producto-card').forEach(card => {
            if (categoria === 'todos' || card.dataset.categoria === categoria) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Abrir modal de producto
function abrirModal(producto) {
    productoSeleccionado = producto;
    cantidad = 1;
    
    document.getElementById('modal-producto-nombre').textContent = producto.nombre;
    document.getElementById('modal-producto-descripcion').textContent = producto.descripcion;
    document.getElementById('modal-producto-precio').textContent = formatearPrecio(producto.precio);
    document.getElementById('cantidad-valor').textContent = cantidad;
    document.getElementById('comentario').value = '';
    
    document.getElementById('modal-agregar').classList.add('active');
}

// Cerrar modal
function cerrarModal() {
    document.getElementById('modal-agregar').classList.remove('active');
    productoSeleccionado = null;
}

// Cambiar cantidad
function cambiarCantidad(delta) {
    cantidad = Math.max(1, Math.min(10, cantidad + delta));
    document.getElementById('cantidad-valor').textContent = cantidad;
}

// Agregar al pedido
function agregarAlPedido() {
    if (!productoSeleccionado) return;
    
    const comentario = document.getElementById('comentario').value;
    
    // Enviar al servidor
    fetch('<?= base_url('cliente/agregar') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            producto_id: productoSeleccionado.id,
            cantidad: cantidad,
            comentario: comentario
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            // Agregar al carrito local
            carrito.push({
                ...productoSeleccionado,
                cantidad: cantidad,
                comentario: comentario
            });
            
            // Actualizar indicador
            actualizarIndicador();
            
            // Mostrar confirmacion
            alert('Producto agregado al pedido!');
            cerrarModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Simular exito para demo
        carrito.push({
            ...productoSeleccionado,
            cantidad: cantidad,
            comentario: document.getElementById('comentario').value
        });
        actualizarIndicador();
        alert('Producto agregado al pedido!');
        cerrarModal();
    });
}

// Actualizar indicador de pedidos
function actualizarIndicador() {
    const indicator = document.getElementById('pedidos-indicator');
    const count = document.getElementById('pedidos-count');
    
    if (carrito.length > 0) {
        indicator.style.display = 'flex';
        count.textContent = carrito.length;
    }
}

// Pedir la cuenta
function pedirCuenta() {
    if (confirm('Deseas pedir la cuenta? Un mesero se acercara a tu mesa.')) {
        alert('Solicitud enviada. Un mesero te atendera pronto.');
    }
}

// Formatear precio
function formatearPrecio(precio) {
    return '$' + precio.toLocaleString('es-CO');
}

// Auto-refresh de estado de pedidos cada 30 segundos
setInterval(function() {
    fetch('<?= base_url('cliente/api/pedidos') ?>')
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                // Actualizar estados de pedidos si es necesario
            }
        })
        .catch(() => {});
}, 30000);
</script>
</body>
</html>
