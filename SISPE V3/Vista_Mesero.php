<?php
session_start();
require_once 'conexion.php';

// Marcar pedido como entregado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['entregar_pedido'])) {
    $id_factura = intval($_POST['id_factura']);
    $id_menu = intval($_POST['id_menu']);
    
    $sql = "UPDATE Pedido SET estado = 'entregado' WHERE pkfk_id_factura = $id_factura AND pkfk_id_menu = $id_menu";
    
    if (mysqli_query($conexion, $sql)) {
        // Marcar notificacion como leida
        $sql_notif = "UPDATE Notificaciones SET leida = 1 WHERE id_pedido_factura = $id_factura AND id_pedido_menu = $id_menu";
        mysqli_query($conexion, $sql_notif);
        $mensaje_exito = "Pedido marcado como entregado";
    } else {
        $mensaje_error = "Error al actualizar el pedido";
    }
}

// Marcar notificacion como leida
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['marcar_leida'])) {
    $id_notif = intval($_POST['id_notificacion']);
    mysqli_query($conexion, "UPDATE Notificaciones SET leida = 1 WHERE id_notificacion = $id_notif");
}

// Obtener notificaciones no leidas
$sql_notificaciones = "SELECT n.*, m.Capacidad, m.Ubicacion 
                       FROM Notificaciones n 
                       LEFT JOIN Mesa m ON n.id_mesa = m.id_Mesa 
                       WHERE n.leida = 0 AND n.tipo = 'pedido_listo'
                       ORDER BY n.fecha_creacion DESC";
$notificaciones = mysqli_query($conexion, $sql_notificaciones);
$count_notificaciones = mysqli_num_rows($notificaciones);

// Filtros
$filtro_estado = isset($_GET['estado']) ? limpiar($conexion, $_GET['estado']) : '';
$filtro_mesa = isset($_GET['mesa']) ? intval($_GET['mesa']) : 0;
$busqueda = isset($_GET['busqueda']) ? limpiar($conexion, $_GET['busqueda']) : '';

// Obtener pedidos activos
$sql_pedidos = "SELECT p.*, m.Productos, m.descripcion, f.pkfk_id_Mesa, f.Fecha_hora,
                c.nom_categoria, pe.Nom1_usu, pe.Ape1_usu, me.id_Mesa, me.Ubicacion
                FROM Pedido p
                INNER JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                INNER JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                INNER JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                INNER JOIN Mesa me ON f.pkfk_id_Mesa = me.id_Mesa
                LEFT JOIN Persona pe ON f.Cliente_Persona_id_usuario = pe.id_usuario
                WHERE 1=1";

if ($filtro_estado != '') {
    $sql_pedidos .= " AND p.estado = '$filtro_estado'";
} else {
    $sql_pedidos .= " AND p.estado != 'entregado'";
}

if ($filtro_mesa > 0) {
    $sql_pedidos .= " AND f.pkfk_id_Mesa = $filtro_mesa";
}

if ($busqueda != '') {
    $sql_pedidos .= " AND (m.Productos LIKE '%$busqueda%' OR pe.Nom1_usu LIKE '%$busqueda%')";
}

$sql_pedidos .= " ORDER BY 
                  CASE p.estado 
                    WHEN 'listo' THEN 1 
                    WHEN 'en_preparacion' THEN 2 
                    WHEN 'pendiente' THEN 3 
                  END,
                  p.fecha_pedido ASC";

$pedidos = mysqli_query($conexion, $sql_pedidos);

// Obtener mesas
$sql_mesas = "SELECT * FROM Mesa ORDER BY id_Mesa";
$mesas = mysqli_query($conexion, $sql_mesas);

// Contar pedidos por estado
$sql_count_listos = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'listo'";
$sql_count_preparacion = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'en_preparacion'";
$sql_count_pendientes = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'pendiente'";

$count_listos = mysqli_fetch_assoc(mysqli_query($conexion, $sql_count_listos))['total'];
$count_preparacion = mysqli_fetch_assoc(mysqli_query($conexion, $sql_count_preparacion))['total'];
$count_pendientes = mysqli_fetch_assoc(mysqli_query($conexion, $sql_count_pendientes))['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Mesero - Divina Comida</title>
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .logo i {
            font-size: 2rem;
            color: #11998e;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .logo span {
            color: #11998e;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .notif-btn {
            position: relative;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .notif-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74c3c;
            color: #fff;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.listos {
            border-left: 4px solid #27ae60;
        }
        
        .stat-card.preparacion {
            border-left: 4px solid #f39c12;
        }
        
        .stat-card.pendientes {
            border-left: 4px solid #e74c3c;
        }
        
        .stat-card.notificaciones {
            border-left: 4px solid #9b59b6;
        }
        
        .stat-card h3 {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .stat-card.listos .number { color: #27ae60; }
        .stat-card.preparacion .number { color: #f39c12; }
        .stat-card.pendientes .number { color: #e74c3c; }
        .stat-card.notificaciones .number { color: #9b59b6; }
        
        .filters {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
            border: 2px solid #eee;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #11998e;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }
        
        .filter-select {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #eee;
            font-family: 'Poppins', sans-serif;
            min-width: 150px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #11998e;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-btn {
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            border: none;
            background: #f5f5f5;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: #fff;
        }
        
        /* Tabla de pedidos */
        .table-container {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: #fff;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            font-size: 1.25rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .mesa-numero {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        
        .mesa-numero .icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #11998e, #38ef7d);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .producto-info {
            display: flex;
            flex-direction: column;
        }
        
        .producto-nombre {
            font-weight: 600;
        }
        
        .producto-categoria {
            font-size: 0.8rem;
            color: #888;
        }
        
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .estado-badge.pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .estado-badge.en_preparacion {
            background: #cce5ff;
            color: #004085;
        }
        
        .estado-badge.listo {
            background: #d4edda;
            color: #155724;
            animation: pulse-green 2s infinite;
        }
        
        @keyframes pulse-green {
            0%, 100% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(39, 174, 96, 0); }
        }
        
        .estado-badge.entregado {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .btn-entregar {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-entregar:hover {
            transform: scale(1.05);
        }
        
        .tiempo-espera {
            font-size: 0.9rem;
            color: #888;
        }
        
        .tiempo-espera.urgente {
            color: #e74c3c;
            font-weight: 600;
        }
        
        /* Panel de notificaciones */
        .notif-panel {
            display: none;
            position: fixed;
            top: 70px;
            right: 2rem;
            width: 350px;
            max-height: 500px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 1000;
            overflow: hidden;
        }
        
        .notif-panel.active {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notif-header {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: #fff;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notif-header h3 {
            font-size: 1rem;
        }
        
        .notif-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notif-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: background 0.3s ease;
        }
        
        .notif-item:hover {
            background: #f8f9fa;
        }
        
        .notif-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex-shrink: 0;
        }
        
        .notif-content {
            flex: 1;
        }
        
        .notif-content h4 {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }
        
        .notif-content p {
            font-size: 0.85rem;
            color: #666;
        }
        
        .notif-content .time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.25rem;
        }
        
        .notif-empty {
            padding: 2rem;
            text-align: center;
            color: #888;
        }
        
        .notif-empty i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        
        .mensaje {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .mensaje.exito {
            background: #d4edda;
            color: #155724;
        }
        
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .sin-pedidos {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .sin-pedidos i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .sin-pedidos h3 {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .sin-pedidos p {
            color: #999;
        }
        
        @media (max-width: 1024px) {
            table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .filter-buttons {
                width: 100%;
                overflow-x: auto;
            }
            
            .notif-panel {
                right: 1rem;
                left: 1rem;
                width: auto;
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
        <div class="header-actions">
            <button class="notif-btn" onclick="toggleNotificaciones()">
                <i class="fas fa-bell"></i>
                <?php if ($count_notificaciones > 0): ?>
                    <span class="notif-badge"><?php echo $count_notificaciones; ?></span>
                <?php endif; ?>
            </button>
            <span class="user-badge">
                <i class="fas fa-user-tie"></i> Mesero
            </span>
            <a href="Inicio.Html" style="color: #666; text-decoration: none; padding: 0.5rem;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>
    
    <!-- Panel de notificaciones -->
    <div id="notif-panel" class="notif-panel">
        <div class="notif-header">
            <h3><i class="fas fa-bell"></i> Notificaciones</h3>
            <span><?php echo $count_notificaciones; ?> nuevas</span>
        </div>
        <div class="notif-list">
            <?php if ($count_notificaciones > 0): ?>
                <?php while ($notif = mysqli_fetch_assoc($notificaciones)): ?>
                    <div class="notif-item">
                        <div class="notif-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="notif-content">
                            <h4>Pedido Listo</h4>
                            <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                            <p class="time">
                                <i class="fas fa-clock"></i>
                                <?php 
                                $tiempo = new DateTime($notif['fecha_creacion']);
                                echo $tiempo->format('H:i');
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="notif-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No hay notificaciones nuevas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
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
            <div class="stat-card listos">
                <h3><i class="fas fa-check-circle"></i> Listos para Entregar</h3>
                <div class="number"><?php echo $count_listos; ?></div>
            </div>
            <div class="stat-card preparacion">
                <h3><i class="fas fa-fire"></i> En Preparacion</h3>
                <div class="number"><?php echo $count_preparacion; ?></div>
            </div>
            <div class="stat-card pendientes">
                <h3><i class="fas fa-clock"></i> Pendientes</h3>
                <div class="number"><?php echo $count_pendientes; ?></div>
            </div>
            <div class="stat-card notificaciones">
                <h3><i class="fas fa-bell"></i> Notificaciones</h3>
                <div class="number"><?php echo $count_notificaciones; ?></div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <form method="GET" style="display: contents;">
                    <input type="text" name="busqueda" placeholder="Buscar producto o cliente..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <input type="hidden" name="estado" value="<?php echo $filtro_estado; ?>">
                    <input type="hidden" name="mesa" value="<?php echo $filtro_mesa; ?>">
                </form>
            </div>
            
            <form method="GET" style="display: contents;">
                <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                <input type="hidden" name="estado" value="<?php echo $filtro_estado; ?>">
                <select name="mesa" class="filter-select" onchange="this.form.submit()">
                    <option value="0">Todas las mesas</option>
                    <?php 
                    mysqli_data_seek($mesas, 0);
                    while ($mesa = mysqli_fetch_assoc($mesas)): ?>
                        <option value="<?php echo $mesa['id_Mesa']; ?>" <?php echo $filtro_mesa == $mesa['id_Mesa'] ? 'selected' : ''; ?>>
                            Mesa <?php echo $mesa['id_Mesa']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
            
            <div class="filter-buttons">
                <a href="Vista_Mesero.php" class="filter-btn <?php echo $filtro_estado == '' ? 'active' : ''; ?>">
                    Todos
                </a>
                <a href="Vista_Mesero.php?estado=listo" class="filter-btn <?php echo $filtro_estado == 'listo' ? 'active' : ''; ?>">
                    <i class="fas fa-check"></i> Listos
                </a>
                <a href="Vista_Mesero.php?estado=en_preparacion" class="filter-btn <?php echo $filtro_estado == 'en_preparacion' ? 'active' : ''; ?>">
                    <i class="fas fa-fire"></i> Preparando
                </a>
                <a href="Vista_Mesero.php?estado=pendiente" class="filter-btn <?php echo $filtro_estado == 'pendiente' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pendientes
                </a>
            </div>
        </div>
        
        <!-- Tabla de pedidos -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list-alt"></i> Listado de Pedidos</h2>
                <span><?php echo mysqli_num_rows($pedidos); ?> pedidos activos</span>
            </div>
            
            <?php if (mysqli_num_rows($pedidos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mesa</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Tiempo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pedido = mysqli_fetch_assoc($pedidos)): ?>
                            <?php
                            $tiempo_transcurrido = '';
                            $es_urgente = false;
                            if ($pedido['fecha_pedido']) {
                                $fecha_pedido = new DateTime($pedido['fecha_pedido']);
                                $ahora = new DateTime();
                                $diferencia = $ahora->diff($fecha_pedido);
                                $minutos = ($diferencia->h * 60) + $diferencia->i;
                                
                                if ($diferencia->h > 0) {
                                    $tiempo_transcurrido = $diferencia->h . 'h ' . $diferencia->i . 'min';
                                } else {
                                    $tiempo_transcurrido = $diferencia->i . ' min';
                                }
                                
                                if ($minutos > 20) $es_urgente = true;
                            }
                            
                            $estado_texto = [
                                'pendiente' => 'Pendiente',
                                'en_preparacion' => 'En Preparacion',
                                'listo' => 'Listo',
                                'entregado' => 'Entregado'
                            ];
                            $estado_icono = [
                                'pendiente' => 'fa-clock',
                                'en_preparacion' => 'fa-fire',
                                'listo' => 'fa-check-circle',
                                'entregado' => 'fa-check-double'
                            ];
                            ?>
                            <tr>
                                <td>
                                    <div class="mesa-numero">
                                        <div class="icon">
                                            <i class="fas fa-chair"></i>
                                        </div>
                                        <span>Mesa <?php echo $pedido['pkfk_id_Mesa']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="producto-info">
                                        <span class="producto-nombre"><?php echo htmlspecialchars($pedido['Productos']); ?></span>
                                        <span class="producto-categoria"><?php echo htmlspecialchars($pedido['nom_categoria']); ?></span>
                                    </div>
                                </td>
                                <td><strong><?php echo $pedido['cantidad']; ?></strong></td>
                                <td><?php echo htmlspecialchars($pedido['Nom1_usu'] . ' ' . $pedido['Ape1_usu']); ?></td>
                                <td>
                                    <span class="estado-badge <?php echo $pedido['estado']; ?>">
                                        <i class="fas <?php echo $estado_icono[$pedido['estado']]; ?>"></i>
                                        <?php echo $estado_texto[$pedido['estado']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="tiempo-espera <?php echo $es_urgente ? 'urgente' : ''; ?>">
                                        <i class="fas fa-clock"></i> <?php echo $tiempo_transcurrido ?: '-'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($pedido['estado'] == 'listo'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="id_factura" value="<?php echo $pedido['pkfk_id_factura']; ?>">
                                            <input type="hidden" name="id_menu" value="<?php echo $pedido['pkfk_id_menu']; ?>">
                                            <button type="submit" name="entregar_pedido" class="btn-entregar">
                                                <i class="fas fa-check"></i> Entregar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.85rem;">
                                            <?php 
                                            if ($pedido['estado'] == 'pendiente') echo 'Esperando cocina';
                                            elseif ($pedido['estado'] == 'en_preparacion') echo 'En cocina';
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="sin-pedidos">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>No hay pedidos activos</h3>
                    <p>Todos los pedidos han sido entregados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleNotificaciones() {
            const panel = document.getElementById('notif-panel');
            panel.classList.toggle('active');
        }
        
        // Cerrar panel al hacer clic fuera
        document.addEventListener('click', function(e) {
            const panel = document.getElementById('notif-panel');
            const btn = document.querySelector('.notif-btn');
            if (!panel.contains(e.target) && !btn.contains(e.target)) {
                panel.classList.remove('active');
            }
        });
        
        // Auto-refresh cada 15 segundos
        setTimeout(function() {
            location.reload();
        }, 15000);
        
        // Sonido de notificacion
        <?php if ($count_notificaciones > 0): ?>
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleAoWbqfW3a17FCBnl8LQrlEcQHSZzNqYVAsxcs3c56RgAwFbkvDstm0FAD1s7/LSqGgGAU5p4vXQqW0FAERo0fTUpXEFAU1o0vTUpXEFAU5p0/TSpW4FAVBr1PPQomsFAVNt1PLMnWYEAVZv1fDHlF4DAVpz1u+/iVQCAV532ey4fEgBAWF72ey3dD0AAGaA3eqxbTYAAGyF4OiqZTAAAnKM4+ajXioAAniT5uOcVSUAA36Z6eGXTSEABIOe6+CQRR0ABYij7d6KPRQAB42o79uDNg8ACZOu8th8Lg0ACpiz9NV1JgwADJ259NJuHwsADqS+9s9nGQoAEKnD98xgFAkAEq7I+MlZDwgAFLTN+cZSCwgAFrnR+cNLBwcAGL7W+sAFBAYAGsLa+70ABQYAHMbf/LkABAUAHsrj/LYABAUAIM3n/bQABAUAItHq/bIABQUAJNXu/q8ABgUAJtny/q0ABwUAKN31/qsACQUAKuD4/6kACwQALOP8/6cADQQALuX//6UADwQAMOgCAKMAEQMAMuoGAKEAEwIANO0KAJ8AFgEANu8NAJ4AGAAAOPIRAJwAGgAAOvQUAJoAHAAAO/cYAJgAHwAAP/kbAJYAIQAAQfweAJQAJAAAQ/4hAJMAJgAARQAlAJEAKQAARwIoAJAAKwAASSIqAI8ALQAASyYtAI4AMAAANSowAI0AMgAANy0zAIwAMwAASjE2AIsANQAAVTQ4AIsANwAAWDc7AIsAOQAA');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Audio blocked'));
        <?php endif; ?>
    </script>
</body>
</html>
