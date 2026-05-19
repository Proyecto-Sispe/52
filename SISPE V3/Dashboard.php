<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario esta logueado
if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header("Location: Inicio de Sesion.Html");
    exit();
}

// Obtener datos del usuario
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'Invitado';

// Estadisticas generales
// Total de ventas del dia
$sql_ventas_hoy = "SELECT COALESCE(SUM(Total), 0) as total FROM Factura WHERE DATE(Fecha_hora) = CURDATE()";
$ventas_hoy = mysqli_fetch_assoc(mysqli_query($conexion, $sql_ventas_hoy))['total'];

// Total de pedidos del dia
$sql_pedidos_hoy = "SELECT COUNT(*) as total FROM Pedido p 
                    INNER JOIN Factura f ON p.pkfk_id_factura = f.id_factura 
                    WHERE DATE(f.Fecha_hora) = CURDATE()";
$pedidos_hoy = mysqli_fetch_assoc(mysqli_query($conexion, $sql_pedidos_hoy))['total'];

// Mesas ocupadas
$sql_mesas_ocupadas = "SELECT COUNT(*) as total FROM Mesa WHERE Estado = 1";
$mesas_ocupadas = mysqli_fetch_assoc(mysqli_query($conexion, $sql_mesas_ocupadas))['total'];

// Mesas totales
$sql_mesas_total = "SELECT COUNT(*) as total FROM Mesa";
$mesas_total = mysqli_fetch_assoc(mysqli_query($conexion, $sql_mesas_total))['total'];

// Pedidos por estado
$sql_pendientes = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'pendiente'";
$sql_preparacion = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'en_preparacion'";
$sql_listos = "SELECT COUNT(*) as total FROM Pedido WHERE estado = 'listo'";

$pedidos_pendientes = mysqli_fetch_assoc(mysqli_query($conexion, $sql_pendientes))['total'];
$pedidos_preparacion = mysqli_fetch_assoc(mysqli_query($conexion, $sql_preparacion))['total'];
$pedidos_listos = mysqli_fetch_assoc(mysqli_query($conexion, $sql_listos))['total'];

// Productos mas vendidos
$sql_top_productos = "SELECT m.Productos, SUM(p.cantidad) as total_vendido, SUM(p.valor_venta) as total_ingresos
                      FROM Pedido p
                      INNER JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                      INNER JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                      WHERE DATE(f.Fecha_hora) = CURDATE()
                      GROUP BY m.id_menu
                      ORDER BY total_vendido DESC
                      LIMIT 5";
$top_productos = mysqli_query($conexion, $sql_top_productos);

// Ultimas facturas
$sql_ultimas_facturas = "SELECT f.*, m.id_Mesa, pe.Nom1_usu, pe.Ape1_usu
                         FROM Factura f
                         INNER JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
                         LEFT JOIN Persona pe ON f.Cliente_Persona_id_usuario = pe.id_usuario
                         ORDER BY f.Fecha_hora DESC
                         LIMIT 5";
$ultimas_facturas = mysqli_query($conexion, $sql_ultimas_facturas);

// Ventas por categoria
$sql_ventas_categoria = "SELECT c.nom_categoria, SUM(p.valor_venta) as total
                         FROM Pedido p
                         INNER JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                         INNER JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                         INNER JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                         WHERE DATE(f.Fecha_hora) = CURDATE()
                         GROUP BY c.id_categoria
                         ORDER BY total DESC";
$ventas_categoria = mysqli_query($conexion, $sql_ventas_categoria);

// Total usuarios por rol
$sql_usuarios = "SELECT r.Nom_rol, COUNT(phr.pkfk_id_usuario) as total
                 FROM Rol r
                 LEFT JOIN Persona_has_Rol phr ON r.idRol = phr.pkfk_idRol
                 GROUP BY r.idRol
                 ORDER BY r.idRol";
$usuarios_rol = mysqli_query($conexion, $sql_usuarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SISPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="Logo 2.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
        }
        
        .sidebar-logo h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .sidebar-logo span {
            color: #f39c12;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #ccc;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
        }
        
        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-section {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #666;
            margin: 1.5rem 0 0.75rem;
            padding-left: 1rem;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.75rem;
            color: #333;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: #fff;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .user-info .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #f39c12, #e74c3c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
        }
        
        .user-info .name {
            font-weight: 500;
        }
        
        .user-info .role {
            font-size: 0.8rem;
            color: #888;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .stat-icon.ventas { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-icon.pedidos { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-icon.mesas { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .stat-icon.pendientes { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .stat-info h3 {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.25rem;
        }
        
        .stat-info .number {
            font-size: 1.75rem;
            font-weight: 700;
            color: #333;
        }
        
        .stat-info .subtext {
            font-size: 0.8rem;
            color: #888;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            font-size: 1.1rem;
            color: #333;
        }
        
        .card-header .badge {
            background: #f0f0f0;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #666;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .status-item {
            text-align: center;
            padding: 1rem;
            border-radius: 12px;
            background: #f8f9fa;
        }
        
        .status-item.pendiente { border-left: 4px solid #e74c3c; }
        .status-item.preparacion { border-left: 4px solid #f39c12; }
        .status-item.listo { border-left: 4px solid #27ae60; }
        
        .status-item .count {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .status-item.pendiente .count { color: #e74c3c; }
        .status-item.preparacion .count { color: #f39c12; }
        .status-item.listo .count { color: #27ae60; }
        
        .status-item .label {
            font-size: 0.8rem;
            color: #888;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #888;
            font-weight: 500;
        }
        
        td {
            font-size: 0.9rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        
        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .quick-action:hover {
            background: #f39c12;
            color: #fff;
            transform: translateY(-3px);
        }
        
        .quick-action i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .quick-action span {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .category-list {
            list-style: none;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-weight: 500;
        }
        
        .category-total {
            color: #27ae60;
            font-weight: 600;
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="Logo 2.png" alt="Logo">
            <h1>Divina <span>Comida</span></h1>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="Dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="Inicio.Html"><i class="fas fa-th-large"></i> Inicio</a></li>
        </ul>
        
        <div class="sidebar-section">Gestion</div>
        <ul class="sidebar-menu">
            <li><a href="Persona.php"><i class="fas fa-users"></i> Personas</a></li>
            <li><a href="Vista_Mesas.php"><i class="fas fa-chair"></i> Mesas</a></li>
            <li><a href="Menu.php"><i class="fas fa-book-open"></i> Menu</a></li>
            <li><a href="Productos.php"><i class="fas fa-hamburger"></i> Productos</a></li>
            <li><a href="Factura.php"><i class="fas fa-file-invoice-dollar"></i> Facturas</a></li>
            <li><a href="Pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a></li>
        </ul>
        
        <div class="sidebar-section">Roles</div>
        <ul class="sidebar-menu">
            <li><a href="Vista_Cocinero.php"><i class="fas fa-fire"></i> Panel Cocinero</a></li>
            <li><a href="Vista_Mesero.php"><i class="fas fa-concierge-bell"></i> Panel Mesero</a></li>
            <li><a href="Vista_Cliente.php"><i class="fas fa-utensils"></i> Menu Cliente</a></li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="header-right">
                <div class="user-info">
                    <div class="avatar"><?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?></div>
                    <div>
                        <div class="name"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                        <div class="role"><?php echo htmlspecialchars($rol_usuario); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </div>
        
        <!-- Estadisticas principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon ventas">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>Ventas de Hoy</h3>
                    <div class="number">$<?php echo number_format($ventas_hoy, 0, ',', '.'); ?></div>
                    <div class="subtext">Ingresos del dia</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pedidos">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3>Pedidos Hoy</h3>
                    <div class="number"><?php echo $pedidos_hoy; ?></div>
                    <div class="subtext">Ordenes procesadas</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon mesas">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="stat-info">
                    <h3>Mesas Ocupadas</h3>
                    <div class="number"><?php echo $mesas_ocupadas; ?>/<?php echo $mesas_total; ?></div>
                    <div class="subtext"><?php echo $mesas_total - $mesas_ocupadas; ?> disponibles</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pendientes">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pedidos Activos</h3>
                    <div class="number"><?php echo $pedidos_pendientes + $pedidos_preparacion + $pedidos_listos; ?></div>
                    <div class="subtext">En proceso</div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-grid">
            <div>
                <!-- Estado de pedidos -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2><i class="fas fa-tasks"></i> Estado de Pedidos</h2>
                        <a href="Pedidos.php" class="badge">Ver todos</a>
                    </div>
                    <div class="status-grid">
                        <div class="status-item pendiente">
                            <div class="count"><?php echo $pedidos_pendientes; ?></div>
                            <div class="label">Pendientes</div>
                        </div>
                        <div class="status-item preparacion">
                            <div class="count"><?php echo $pedidos_preparacion; ?></div>
                            <div class="label">En Preparacion</div>
                        </div>
                        <div class="status-item listo">
                            <div class="count"><?php echo $pedidos_listos; ?></div>
                            <div class="label">Listos</div>
                        </div>
                    </div>
                </div>
                
                <!-- Productos mas vendidos -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2><i class="fas fa-fire"></i> Productos Mas Vendidos (Hoy)</h2>
                    </div>
                    <?php if (mysqli_num_rows($top_productos) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pos = 1;
                            while ($prod = mysqli_fetch_assoc($top_productos)): ?>
                            <tr>
                                <td><strong><?php echo $pos++; ?></strong></td>
                                <td><?php echo htmlspecialchars($prod['Productos']); ?></td>
                                <td><strong><?php echo $prod['total_vendido']; ?></strong></td>
                                <td style="color: #27ae60; font-weight: 600;">$<?php echo number_format($prod['total_ingresos'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: #888; padding: 2rem;">No hay ventas registradas hoy</p>
                    <?php endif; ?>
                </div>
                
                <!-- Ultimas facturas -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-file-invoice"></i> Ultimas Facturas</h2>
                        <a href="Factura.php" class="badge">Ver todas</a>
                    </div>
                    <?php if (mysqli_num_rows($ultimas_facturas) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Mesa</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($factura = mysqli_fetch_assoc($ultimas_facturas)): ?>
                            <tr>
                                <td>#<?php echo $factura['id_factura']; ?></td>
                                <td>Mesa <?php echo $factura['id_Mesa']; ?></td>
                                <td><?php echo htmlspecialchars($factura['Nom1_usu'] . ' ' . $factura['Ape1_usu']); ?></td>
                                <td style="color: #27ae60; font-weight: 600;">$<?php echo number_format($factura['Total'], 0, ',', '.'); ?></td>
                                <td><?php echo date('d/m H:i', strtotime($factura['Fecha_hora'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: #888; padding: 2rem;">No hay facturas registradas</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <!-- Acciones rapidas -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> Acciones Rapidas</h2>
                    </div>
                    <div class="quick-actions">
                        <a href="Vista_Mesas.php" class="quick-action">
                            <i class="fas fa-plus-circle"></i>
                            <span>Abrir Mesa</span>
                        </a>
                        <a href="Vista_Cocinero.php" class="quick-action">
                            <i class="fas fa-fire"></i>
                            <span>Ver Cocina</span>
                        </a>
                        <a href="Agregar_Producto.php" class="quick-action">
                            <i class="fas fa-hamburger"></i>
                            <span>Nuevo Producto</span>
                        </a>
                        <a href="Agregar_Persona.php" class="quick-action">
                            <i class="fas fa-user-plus"></i>
                            <span>Nueva Persona</span>
                        </a>
                    </div>
                </div>
                
                <!-- Ventas por categoria -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-pie"></i> Ventas por Categoria</h2>
                    </div>
                    <?php if (mysqli_num_rows($ventas_categoria) > 0): ?>
                    <ul class="category-list">
                        <?php while ($cat = mysqli_fetch_assoc($ventas_categoria)): ?>
                        <li class="category-item">
                            <span class="category-name"><?php echo htmlspecialchars($cat['nom_categoria']); ?></span>
                            <span class="category-total">$<?php echo number_format($cat['total'], 0, ',', '.'); ?></span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p style="text-align: center; color: #888; padding: 1rem;">Sin ventas hoy</p>
                    <?php endif; ?>
                </div>
                
                <!-- Personal por rol -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-users"></i> Personal</h2>
                    </div>
                    <ul class="category-list">
                        <?php while ($rol = mysqli_fetch_assoc($usuarios_rol)): ?>
                        <li class="category-item">
                            <span class="category-name"><?php echo htmlspecialchars($rol['Nom_rol']); ?></span>
                            <span class="category-total"><?php echo $rol['total']; ?></span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Auto-refresh cada 60 segundos
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
