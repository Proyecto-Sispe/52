<?php
session_start();
require_once 'conexion.php';

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    $id_factura = intval($_POST['id_factura']);
    $id_menu = intval($_POST['id_menu']);
    $nuevo_estado = limpiar($conexion, $_POST['nuevo_estado']);
    
    $sql = "UPDATE Pedido SET estado = '$nuevo_estado' WHERE pkfk_id_factura = $id_factura AND pkfk_id_menu = $id_menu";
    
    if (mysqli_query($conexion, $sql)) {
        // Si el pedido esta listo, crear notificacion para meseros
        if ($nuevo_estado == 'listo') {
            $sql_mesa = "SELECT f.pkfk_id_Mesa FROM Factura f WHERE f.id_factura = $id_factura";
            $res_mesa = mysqli_query($conexion, $sql_mesa);
            $mesa_data = mysqli_fetch_assoc($res_mesa);
            $id_mesa = $mesa_data['pkfk_id_Mesa'];
            
            $sql_notif = "INSERT INTO Notificaciones (tipo, mensaje, id_mesa, id_pedido_factura, id_pedido_menu, destinatario_rol) 
                          VALUES ('pedido_listo', 'Pedido listo para mesa $id_mesa', $id_mesa, $id_factura, $id_menu, 3)";
            mysqli_query($conexion, $sql_notif);
        }
        $mensaje_exito = "Estado actualizado correctamente";
    } else {
        $mensaje_error = "Error al actualizar el estado";
    }
}

// Filtros
$filtro_estado = isset($_GET['estado']) ? limpiar($conexion, $_GET['estado']) : '';
$busqueda = isset($_GET['busqueda']) ? limpiar($conexion, $_GET['busqueda']) : '';

// Consulta de pedidos
$sql_pedidos = "SELECT p.*, m.Productos, m.Precio, m.descripcion, f.pkfk_id_Mesa, f.Fecha_hora,
                c.nom_categoria, pe.Nom1_usu, pe.Ape1_usu
                FROM Pedido p
                INNER JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                INNER JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                INNER JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                LEFT JOIN Persona pe ON f.Cliente_Persona_id_usuario = pe.id_usuario
                WHERE p.estado != 'entregado'";

if ($filtro_estado != '') {
    $sql_pedidos .= " AND p.estado = '$filtro_estado'";
}
if ($busqueda != '') {
    $sql_pedidos .= " AND (m.Productos LIKE '%$busqueda%' OR f.pkfk_id_Mesa LIKE '%$busqueda%')";
}

$sql_pedidos .= " ORDER BY 
                  CASE p.estado 
                    WHEN 'pendiente' THEN 1 
                    WHEN 'en_preparacion' THEN 2 
                    WHEN 'listo' THEN 3 
                  END,
                  CASE p.prioridad WHEN 'urgente' THEN 0 ELSE 1 END,
                  p.fecha_pedido ASC";

$pedidos = mysqli_query($conexion, $sql_pedidos);

// Contar pedidos por estado
$sql_count_pendientes = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'pendiente'";
$sql_count_preparacion = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'en_preparacion'";
$sql_count_listos = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'listo'";

$count_pendientes = mysqli_fetch_assoc(mysqli_query($conexion, $sql_count_pendientes))['total'];
$count_preparacion = mysqli_fetch_assoc(mysqli_query($conexion, $sql_count_preparacion))['total'];
$count_listos = mysqli_fetch_assoc(mysqli_query($conexion, $sql_count_listos))['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Cocinero - Divina Comida</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo i {
            font-size: 2rem;
            color: #f39c12;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #fff;
        }
        
        .logo span {
            color: #f39c12;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info .badge {
            background: #f39c12;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.pendientes {
            border-left: 4px solid #e74c3c;
        }
        
        .stat-card.preparacion {
            border-left: 4px solid #f39c12;
        }
        
        .stat-card.listos {
            border-left: 4px solid #27ae60;
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .stat-card.pendientes .number { color: #e74c3c; }
        .stat-card.preparacion .number { color: #f39c12; }
        .stat-card.listos .number { color: #27ae60; }
        
        .filters {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
        }
        
        .search-box input::placeholder {
            color: #888;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-btn {
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #f39c12;
        }
        
        .pedidos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .pedido-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        .pedido-card.urgente {
            border: 2px solid #e74c3c;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
        }
        
        .pedido-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pedido-header.pendiente { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .pedido-header.en_preparacion { background: linear-gradient(135deg, #f39c12, #d68910); }
        .pedido-header.listo { background: linear-gradient(135deg, #27ae60, #1e8449); }
        
        .mesa-numero {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .mesa-numero i {
            font-size: 1.25rem;
        }
        
        .mesa-numero span {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .tiempo {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .pedido-body {
            padding: 1.5rem;
        }
        
        .producto-nombre {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .producto-categoria {
            color: #f39c12;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        .pedido-detalles {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detalle-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem;
            border-radius: 10px;
        }
        
        .detalle-item label {
            font-size: 0.75rem;
            color: #888;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .detalle-item span {
            font-weight: 500;
        }
        
        .observaciones {
            background: rgba(255, 193, 7, 0.1);
            border-left: 3px solid #f39c12;
            padding: 1rem;
            border-radius: 0 10px 10px 0;
            margin-bottom: 1rem;
        }
        
        .observaciones h4 {
            font-size: 0.85rem;
            color: #f39c12;
            margin-bottom: 0.5rem;
        }
        
        .observaciones p {
            font-size: 0.9rem;
            color: #ccc;
        }
        
        .pedido-acciones {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-estado {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-preparar {
            background: #f39c12;
            color: #fff;
        }
        
        .btn-preparar:hover {
            background: #d68910;
        }
        
        .btn-listo {
            background: #27ae60;
            color: #fff;
        }
        
        .btn-listo:hover {
            background: #1e8449;
        }
        
        .btn-entregar {
            background: #3498db;
            color: #fff;
        }
        
        .btn-entregar:hover {
            background: #2980b9;
        }
        
        .mensaje {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .mensaje.exito {
            background: rgba(39, 174, 96, 0.2);
            border: 1px solid #27ae60;
        }
        
        .mensaje.error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
        }
        
        .sin-pedidos {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
        }
        
        .sin-pedidos i {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }
        
        .sin-pedidos h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .sin-pedidos p {
            color: #888;
        }
        
        /* Auto-refresh indicator */
        .refresh-indicator {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: rgba(39, 174, 96, 0.9);
            padding: 1rem 1.5rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .refresh-indicator i {
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .pedidos-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .filter-buttons {
                width: 100%;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-utensils"></i>
            <h1>Divina <span>Comida</span></h1>
        </div>
        <div class="user-info">
            <span class="badge"><i class="fas fa-user-chef"></i> Cocinero</span>
            <a href="Inicio.Html" style="color: #fff; text-decoration: none;">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($mensaje_exito)): ?>
            <div class="mensaje exito">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($mensaje_error)): ?>
            <div class="mensaje error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Estadisticas -->
        <div class="stats-grid">
            <div class="stat-card pendientes">
                <h3><i class="fas fa-clock"></i> Pedidos Pendientes</h3>
                <div class="number"><?php echo $count_pendientes; ?></div>
            </div>
            <div class="stat-card preparacion">
                <h3><i class="fas fa-fire"></i> En Preparacion</h3>
                <div class="number"><?php echo $count_preparacion; ?></div>
            </div>
            <div class="stat-card listos">
                <h3><i class="fas fa-check"></i> Listos para Entregar</h3>
                <div class="number"><?php echo $count_listos; ?></div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <form method="GET" style="display: contents;">
                    <input type="text" name="busqueda" placeholder="Buscar producto o mesa..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <input type="hidden" name="estado" value="<?php echo $filtro_estado; ?>">
                </form>
            </div>
            <div class="filter-buttons">
                <a href="Vista_Cocinero.php" class="filter-btn <?php echo $filtro_estado == '' ? 'active' : ''; ?>">
                    Todos
                </a>
                <a href="Vista_Cocinero.php?estado=pendiente" class="filter-btn <?php echo $filtro_estado == 'pendiente' ? 'active' : ''; ?>">
                    Pendientes
                </a>
                <a href="Vista_Cocinero.php?estado=en_preparacion" class="filter-btn <?php echo $filtro_estado == 'en_preparacion' ? 'active' : ''; ?>">
                    En Preparacion
                </a>
                <a href="Vista_Cocinero.php?estado=listo" class="filter-btn <?php echo $filtro_estado == 'listo' ? 'active' : ''; ?>">
                    Listos
                </a>
            </div>
        </div>
        
        <!-- Lista de Pedidos -->
        <?php if (mysqli_num_rows($pedidos) > 0): ?>
            <div class="pedidos-grid">
                <?php while ($pedido = mysqli_fetch_assoc($pedidos)): ?>
                    <?php
                    $tiempo_transcurrido = '';
                    if ($pedido['fecha_pedido']) {
                        $fecha_pedido = new DateTime($pedido['fecha_pedido']);
                        $ahora = new DateTime();
                        $diferencia = $ahora->diff($fecha_pedido);
                        if ($diferencia->h > 0) {
                            $tiempo_transcurrido = $diferencia->h . 'h ' . $diferencia->i . 'min';
                        } else {
                            $tiempo_transcurrido = $diferencia->i . ' min';
                        }
                    }
                    ?>
                    <div class="pedido-card <?php echo $pedido['prioridad'] == 'urgente' ? 'urgente' : ''; ?>">
                        <div class="pedido-header <?php echo $pedido['estado']; ?>">
                            <div class="mesa-numero">
                                <i class="fas fa-chair"></i>
                                <span>Mesa <?php echo $pedido['pkfk_id_Mesa']; ?></span>
                            </div>
                            <div class="tiempo">
                                <i class="fas fa-clock"></i> <?php echo $tiempo_transcurrido ?: 'Reciente'; ?>
                            </div>
                        </div>
                        <div class="pedido-body">
                            <h3 class="producto-nombre"><?php echo htmlspecialchars($pedido['Productos']); ?></h3>
                            <p class="producto-categoria">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($pedido['nom_categoria']); ?>
                            </p>
                            
                            <div class="pedido-detalles">
                                <div class="detalle-item">
                                    <label>Cantidad</label>
                                    <span><?php echo $pedido['cantidad']; ?> unidad(es)</span>
                                </div>
                                <div class="detalle-item">
                                    <label>Cliente</label>
                                    <span><?php echo htmlspecialchars($pedido['Nom1_usu'] . ' ' . $pedido['Ape1_usu']); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($pedido['observaciones'])): ?>
                                <div class="observaciones">
                                    <h4><i class="fas fa-comment-alt"></i> Observaciones</h4>
                                    <p><?php echo htmlspecialchars($pedido['observaciones']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pedido-acciones">
                                <?php if ($pedido['estado'] == 'pendiente'): ?>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="id_factura" value="<?php echo $pedido['pkfk_id_factura']; ?>">
                                        <input type="hidden" name="id_menu" value="<?php echo $pedido['pkfk_id_menu']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="en_preparacion">
                                        <button type="submit" name="cambiar_estado" class="btn-estado btn-preparar">
                                            <i class="fas fa-fire"></i> Preparar
                                        </button>
                                    </form>
                                <?php elseif ($pedido['estado'] == 'en_preparacion'): ?>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="id_factura" value="<?php echo $pedido['pkfk_id_factura']; ?>">
                                        <input type="hidden" name="id_menu" value="<?php echo $pedido['pkfk_id_menu']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="listo">
                                        <button type="submit" name="cambiar_estado" class="btn-estado btn-listo">
                                            <i class="fas fa-check"></i> Marcar Listo
                                        </button>
                                    </form>
                                <?php elseif ($pedido['estado'] == 'listo'): ?>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="id_factura" value="<?php echo $pedido['pkfk_id_factura']; ?>">
                                        <input type="hidden" name="id_menu" value="<?php echo $pedido['pkfk_id_menu']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="entregado">
                                        <button type="submit" name="cambiar_estado" class="btn-estado btn-entregar">
                                            <i class="fas fa-truck"></i> Entregar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="sin-pedidos">
                <i class="fas fa-clipboard-check"></i>
                <h3>No hay pedidos pendientes</h3>
                <p>Todos los pedidos han sido completados. Esperando nuevos pedidos...</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="refresh-indicator">
        <i class="fas fa-sync-alt"></i>
        <span>Actualizacion automatica</span>
    </div>
    
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Sonido de notificacion para nuevos pedidos urgentes
        const pedidosUrgentes = document.querySelectorAll('.pedido-card.urgente');
        if (pedidosUrgentes.length > 0) {
            // Reproducir sonido de alerta
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleAoWbqfW3a17FCBnl8LQrlEcQHSZzNqYVAsxcs3c56RgAwFbkvDstm0FAD1s7/LSqGgGAU5p4vXQqW0FAERo0fTUpXEFAU1o0vTUpXEFAU5p0/TSpW4FAVBr1PPQomsFAVNt1PLMnWYEAVZv1fDHlF4DAVpz1u+/iVQCAV532e64fEgBAWF72ey3dD0AAGaA3eqxbTYAAGyF4OiqZTAAAnKM4+ajXioAAniT5uOcVSUAA36Z6eGXTSEABIOe6+CQRR0ABYij7d6KPRQAB42o79uDNg8ACZOu8th8Lg0ACpiz9NV1JgwADJ259NJuHwsADqS+9s9nGQoAEKnD98xgFAkAEq7I+MlZDwgAFLTN+cZSCwgAFrnR+cNLBwcAGL7W+sAFBAYAGsLa+70ABQYAHMbf/LkABAUAHsrj/LYABAUAIM3n/bQABAUAItHq/bIABQUAJNXu/q8ABgUAJtny/q0ABwUAKN31/qsACQUAKuD4/6kACwQALOP8/6cADQQALuX//6UADwQAMOgCAKMAEQMAMuoGAKEAEwIANO0KAJ8AFgEANu8NAJ4AGAAAOPIRAJwAGgAAOvQUAJoAHAAAO/cYAJgAHwAAP/kbAJYAIQAAQfweAJQAJAAAQ/4hAJMAJgAARQAlAJEAKQAARwIoAJAAKwAASSIqAI8ALQAASyYtAI4AMAAANSowAI0AMgAANy0zAIwAMwAASjE2AIsANQAAVTQ4AIsANwAAWDc7AIsAOQAA');
            audio.volume = 0.3;
            audio.play().catch(e => console.log('Audio autoplay blocked'));
        }
    </script>
</body>
</html>
