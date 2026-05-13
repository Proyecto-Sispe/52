<?php
session_start();
require_once 'conexion.php';

// Generar codigo de sesion para una mesa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['abrir_mesa'])) {
    $id_mesa = intval($_POST['id_mesa']);
    $codigo = generarCodigoMesa();
    
    // Cerrar sesiones anteriores de esta mesa
    mysqli_query($conexion, "UPDATE Sesion_Mesa SET activa = 0 WHERE id_mesa = $id_mesa AND activa = 1");
    
    // Crear nueva sesion
    $sql = "INSERT INTO Sesion_Mesa (id_mesa, codigo_acceso) VALUES ($id_mesa, '$codigo')";
    
    if (mysqli_query($conexion, $sql)) {
        // Actualizar estado de mesa a ocupada
        mysqli_query($conexion, "UPDATE Mesa SET Estado = 1 WHERE id_Mesa = $id_mesa");
        $codigo_generado = $codigo;
        $mesa_abierta = $id_mesa;
    }
}

// Cerrar mesa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cerrar_mesa'])) {
    $id_mesa = intval($_POST['id_mesa']);
    
    // Cerrar sesiones activas
    mysqli_query($conexion, "UPDATE Sesion_Mesa SET activa = 0, fecha_fin = NOW() WHERE id_mesa = $id_mesa AND activa = 1");
    
    // Actualizar estado de mesa a libre
    mysqli_query($conexion, "UPDATE Mesa SET Estado = 0 WHERE id_Mesa = $id_mesa");
    
    $mensaje_exito = "Mesa $id_mesa cerrada correctamente";
}

// Filtros
$filtro_ubicacion = isset($_GET['ubicacion']) ? intval($_GET['ubicacion']) : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Obtener mesas
$sql_mesas = "SELECT m.*, 
              (SELECT COUNT(*) FROM Factura f WHERE f.pkfk_id_Mesa = m.id_Mesa AND DATE(f.Fecha_hora) = CURDATE()) as facturas_hoy,
              sm.codigo_acceso, sm.fecha_inicio
              FROM Mesa m
              LEFT JOIN Sesion_Mesa sm ON m.id_Mesa = sm.id_mesa AND sm.activa = 1
              WHERE 1=1";

if ($filtro_ubicacion > 0) {
    $sql_mesas .= " AND m.Ubicacion = $filtro_ubicacion";
}

if ($filtro_estado === '0') {
    $sql_mesas .= " AND m.Estado = 0";
} elseif ($filtro_estado === '1') {
    $sql_mesas .= " AND m.Estado = 1";
}

$sql_mesas .= " ORDER BY m.Ubicacion, m.id_Mesa";
$mesas = mysqli_query($conexion, $sql_mesas);

// Estadisticas
$sql_total = "SELECT COUNT(*) as total FROM Mesa";
$sql_ocupadas = "SELECT COUNT(*) as total FROM Mesa WHERE Estado = 1";
$sql_libres = "SELECT COUNT(*) as total FROM Mesa WHERE Estado = 0";

$total_mesas = mysqli_fetch_assoc(mysqli_query($conexion, $sql_total))['total'];
$mesas_ocupadas = mysqli_fetch_assoc(mysqli_query($conexion, $sql_ocupadas))['total'];
$mesas_libres = mysqli_fetch_assoc(mysqli_query($conexion, $sql_libres))['total'];

// Ubicaciones disponibles
$sql_ubicaciones = "SELECT DISTINCT Ubicacion FROM Mesa ORDER BY Ubicacion";
$ubicaciones = mysqli_query($conexion, $sql_ubicaciones);

// Obtener pedidos activos por mesa
function getPedidosActivosMesa($conexion, $id_mesa) {
    $sql = "SELECT COUNT(*) as total FROM Pedido p 
            INNER JOIN Factura f ON p.pkfk_id_factura = f.id_factura 
            WHERE f.pkfk_id_Mesa = $id_mesa AND p.estado != 'entregado'";
    return mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Mesas - Divina Comida</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
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
            color: #3498db;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .logo span {
            color: #3498db;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn-nav {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-nav:hover {
            transform: translateY(-2px);
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
        
        .stat-card.total {
            border-left: 4px solid #3498db;
        }
        
        .stat-card.ocupadas {
            border-left: 4px solid #e74c3c;
        }
        
        .stat-card.libres {
            border-left: 4px solid #27ae60;
        }
        
        .stat-card h3 {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .stat-card.total .number { color: #3498db; }
        .stat-card.ocupadas .number { color: #e74c3c; }
        .stat-card.libres .number { color: #27ae60; }
        
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
        
        .filter-select {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #eee;
            font-family: 'Poppins', sans-serif;
            min-width: 150px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            justify-content: flex-end;
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
            text-decoration: none;
            color: #333;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
        }
        
        /* Mapa de mesas */
        .mesas-section {
            margin-bottom: 2rem;
        }
        
        .seccion-titulo {
            background: #fff;
            padding: 1rem 1.5rem;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .seccion-titulo h2 {
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(255,255,255,0.9);
            border-radius: 0 0 16px 16px;
        }
        
        .mesa-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .mesa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        
        .mesa-card.libre {
            border-top: 4px solid #27ae60;
        }
        
        .mesa-card.ocupada {
            border-top: 4px solid #e74c3c;
        }
        
        .mesa-header {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .mesa-numero-grande {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .mesa-icono {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .mesa-card.libre .mesa-icono {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .mesa-card.ocupada .mesa-icono {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .mesa-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .mesa-info .ubicacion {
            font-size: 0.85rem;
            color: #888;
        }
        
        .mesa-estado {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .mesa-card.libre .mesa-estado {
            background: #d4edda;
            color: #155724;
        }
        
        .mesa-card.ocupada .mesa-estado {
            background: #f8d7da;
            color: #721c24;
        }
        
        .mesa-body {
            padding: 0 1.5rem 1.5rem;
        }
        
        .mesa-detalles {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detalle-item {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .detalle-item label {
            font-size: 0.7rem;
            color: #888;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .detalle-item span {
            font-weight: 600;
            font-size: 1rem;
        }
        
        .codigo-mesa {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .codigo-mesa label {
            font-size: 0.75rem;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }
        
        .codigo-mesa .codigo {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.5rem;
        }
        
        .mesa-acciones {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-mesa {
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
        
        .btn-abrir {
            background: #27ae60;
            color: #fff;
        }
        
        .btn-abrir:hover {
            background: #1e8449;
        }
        
        .btn-cerrar {
            background: #e74c3c;
            color: #fff;
        }
        
        .btn-cerrar:hover {
            background: #c0392b;
        }
        
        .btn-ver {
            background: #3498db;
            color: #fff;
        }
        
        .btn-ver:hover {
            background: #2980b9;
        }
        
        .pedidos-activos {
            background: #fff3cd;
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        /* Modal de codigo */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .modal-content .icon {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 1rem;
        }
        
        .modal-content h2 {
            margin-bottom: 0.5rem;
        }
        
        .modal-content p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .codigo-grande {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: 1rem;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }
        
        .btn-cerrar-modal {
            background: #f5f5f5;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
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
                justify-content: flex-start;
                overflow-x: auto;
            }
            
            .mesas-grid {
                grid-template-columns: 1fr;
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
            <a href="Vista_Cocinero.php" class="btn-nav">
                <i class="fas fa-fire"></i> Cocina
            </a>
            <a href="Vista_Mesero.php" class="btn-nav">
                <i class="fas fa-user-tie"></i> Meseros
            </a>
            <a href="Inicio.Html" class="btn-nav" style="background: #95a5a6;">
                <i class="fas fa-home"></i> Inicio
            </a>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($mensaje_exito)): ?>
            <div class="mensaje exito">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>
        
        <!-- Estadisticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h3><i class="fas fa-chair"></i> Total Mesas</h3>
                <div class="number"><?php echo $total_mesas; ?></div>
            </div>
            <div class="stat-card ocupadas">
                <h3><i class="fas fa-user-friends"></i> Ocupadas</h3>
                <div class="number"><?php echo $mesas_ocupadas; ?></div>
            </div>
            <div class="stat-card libres">
                <h3><i class="fas fa-check-circle"></i> Disponibles</h3>
                <div class="number"><?php echo $mesas_libres; ?></div>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters">
            <form method="GET" style="display: contents;">
                <select name="ubicacion" class="filter-select" onchange="this.form.submit()">
                    <option value="0">Todas las ubicaciones</option>
                    <?php 
                    mysqli_data_seek($ubicaciones, 0);
                    while ($ub = mysqli_fetch_assoc($ubicaciones)): ?>
                        <option value="<?php echo $ub['Ubicacion']; ?>" <?php echo $filtro_ubicacion == $ub['Ubicacion'] ? 'selected' : ''; ?>>
                            Zona <?php echo $ub['Ubicacion']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
            
            <div class="filter-buttons">
                <a href="Vista_Mesas.php" class="filter-btn <?php echo $filtro_estado === '' ? 'active' : ''; ?>">
                    Todas
                </a>
                <a href="Vista_Mesas.php?estado=0" class="filter-btn <?php echo $filtro_estado === '0' ? 'active' : ''; ?>">
                    <i class="fas fa-check"></i> Disponibles
                </a>
                <a href="Vista_Mesas.php?estado=1" class="filter-btn <?php echo $filtro_estado === '1' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Ocupadas
                </a>
            </div>
        </div>
        
        <!-- Grid de Mesas -->
        <div class="mesas-section">
            <div class="seccion-titulo">
                <h2><i class="fas fa-th-large"></i> Mapa de Mesas</h2>
                <span><?php echo mysqli_num_rows($mesas); ?> mesas</span>
            </div>
            
            <div class="mesas-grid">
                <?php while ($mesa = mysqli_fetch_assoc($mesas)): ?>
                    <?php
                    $pedidos_activos = getPedidosActivosMesa($conexion, $mesa['id_Mesa']);
                    $estado_clase = $mesa['Estado'] == 1 ? 'ocupada' : 'libre';
                    $ubicacion_texto = "Zona " . $mesa['Ubicacion'];
                    ?>
                    <div class="mesa-card <?php echo $estado_clase; ?>">
                        <div class="mesa-header">
                            <div class="mesa-numero-grande">
                                <div class="mesa-icono">
                                    <i class="fas fa-chair"></i>
                                </div>
                                <div class="mesa-info">
                                    <h3>Mesa <?php echo $mesa['id_Mesa']; ?></h3>
                                    <span class="ubicacion"><i class="fas fa-map-marker-alt"></i> <?php echo $ubicacion_texto; ?></span>
                                </div>
                            </div>
                            <span class="mesa-estado">
                                <?php echo $mesa['Estado'] == 1 ? 'Ocupada' : 'Disponible'; ?>
                            </span>
                        </div>
                        
                        <div class="mesa-body">
                            <div class="mesa-detalles">
                                <div class="detalle-item">
                                    <label>Capacidad</label>
                                    <span><i class="fas fa-users"></i> <?php echo $mesa['Capacidad']; ?></span>
                                </div>
                                <div class="detalle-item">
                                    <label>Hoy</label>
                                    <span><i class="fas fa-receipt"></i> <?php echo $mesa['facturas_hoy']; ?> ordenes</span>
                                </div>
                            </div>
                            
                            <?php if ($mesa['Estado'] == 1 && $mesa['codigo_acceso']): ?>
                                <div class="codigo-mesa">
                                    <label>Codigo de acceso</label>
                                    <div class="codigo"><?php echo $mesa['codigo_acceso']; ?></div>
                                </div>
                                
                                <?php if ($pedidos_activos > 0): ?>
                                    <div class="pedidos-activos">
                                        <i class="fas fa-utensils"></i>
                                        <?php echo $pedidos_activos; ?> pedido(s) activo(s)
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="mesa-acciones">
                                <?php if ($mesa['Estado'] == 0): ?>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="id_mesa" value="<?php echo $mesa['id_Mesa']; ?>">
                                        <button type="submit" name="abrir_mesa" class="btn-mesa btn-abrir">
                                            <i class="fas fa-door-open"></i> Abrir Mesa
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="id_mesa" value="<?php echo $mesa['id_Mesa']; ?>">
                                        <button type="submit" name="cerrar_mesa" class="btn-mesa btn-cerrar"
                                                onclick="return confirm('¿Cerrar mesa <?php echo $mesa['id_Mesa']; ?>?')">
                                            <i class="fas fa-door-closed"></i> Cerrar
                                        </button>
                                    </form>
                                    <a href="Vista_Mesero.php?mesa=<?php echo $mesa['id_Mesa']; ?>" class="btn-mesa btn-ver">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal de codigo generado -->
    <?php if (isset($codigo_generado)): ?>
    <div class="modal active" id="modal-codigo">
        <div class="modal-content">
            <i class="fas fa-check-circle icon"></i>
            <h2>Mesa <?php echo $mesa_abierta; ?> Abierta</h2>
            <p>Comparte este codigo con el cliente para que acceda al menu digital</p>
            <div class="codigo-grande"><?php echo $codigo_generado; ?></div>
            <button class="btn-cerrar-modal" onclick="document.getElementById('modal-codigo').classList.remove('active')">
                Entendido
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Auto-refresh cada 60 segundos
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
