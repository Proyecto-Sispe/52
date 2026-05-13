<?php
session_start();
require_once 'conexion.php';

// Verificar si hay una sesion de mesa activa
$mesa_activa = isset($_SESSION['mesa_id']) ? $_SESSION['mesa_id'] : null;
$factura_activa = isset($_SESSION['factura_id']) ? $_SESSION['factura_id'] : null;

// Acceder a una mesa con codigo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acceder_mesa'])) {
    $codigo = strtoupper(limpiar($conexion, $_POST['codigo_mesa']));
    
    // Verificar codigo de sesion
    $sql = "SELECT sm.*, m.Capacidad, m.Ubicacion FROM Sesion_Mesa sm 
            INNER JOIN Mesa m ON sm.id_mesa = m.id_Mesa 
            WHERE sm.codigo_acceso = '$codigo' AND sm.activa = 1";
    $result = mysqli_query($conexion, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $sesion = mysqli_fetch_assoc($result);
        $_SESSION['mesa_id'] = $sesion['id_mesa'];
        $_SESSION['sesion_mesa_id'] = $sesion['id_sesion'];
        $mesa_activa = $sesion['id_mesa'];
        
        // Buscar factura activa para esta mesa
        $sql_factura = "SELECT id_factura FROM Factura WHERE pkfk_id_Mesa = $mesa_activa ORDER BY Fecha_hora DESC LIMIT 1";
        $res_factura = mysqli_query($conexion, $sql_factura);
        if (mysqli_num_rows($res_factura) > 0) {
            $factura = mysqli_fetch_assoc($res_factura);
            $_SESSION['factura_id'] = $factura['id_factura'];
            $factura_activa = $factura['id_factura'];
        }
    } else {
        $error_acceso = "Codigo de mesa invalido o sesion expirada";
    }
}

// Agregar pedido
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_pedido']) && $factura_activa) {
    $id_menu = intval($_POST['id_menu']);
    $cantidad = intval($_POST['cantidad']);
    $observaciones = limpiar($conexion, $_POST['observaciones']);
    
    // Obtener precio del producto
    $sql_precio = "SELECT Precio FROM Menu WHERE id_menu = $id_menu";
    $res_precio = mysqli_query($conexion, $sql_precio);
    $producto = mysqli_fetch_assoc($res_precio);
    $valor_venta = $producto['Precio'] * $cantidad;
    
    // Verificar si ya existe este producto en la factura
    $sql_existe = "SELECT * FROM Pedido WHERE pkfk_id_factura = $factura_activa AND pkfk_id_menu = $id_menu AND estado IN ('pendiente', 'en_preparacion')";
    $res_existe = mysqli_query($conexion, $sql_existe);
    
    if (mysqli_num_rows($res_existe) > 0) {
        // Actualizar cantidad
        $sql = "UPDATE Pedido SET cantidad = cantidad + $cantidad, 
                observaciones = CONCAT(IFNULL(observaciones, ''), ' | ', '$observaciones'),
                valor_venta = valor_venta + $valor_venta
                WHERE pkfk_id_factura = $factura_activa AND pkfk_id_menu = $id_menu AND estado IN ('pendiente', 'en_preparacion')";
    } else {
        // Insertar nuevo pedido
        $sql = "INSERT INTO Pedido (pkfk_id_factura, pkfk_id_menu, cantidad, observaciones, valor_venta, estado, fecha_pedido) 
                VALUES ($factura_activa, $id_menu, $cantidad, '$observaciones', $valor_venta, 'pendiente', NOW())";
    }
    
    if (mysqli_query($conexion, $sql)) {
        // Actualizar total de factura
        $sql_total = "UPDATE Factura SET Total = (SELECT SUM(valor_venta) FROM Pedido WHERE pkfk_id_factura = $factura_activa) WHERE id_factura = $factura_activa";
        mysqli_query($conexion, $sql_total);
        $mensaje_exito = "Pedido agregado correctamente";
    } else {
        $mensaje_error = "Error al agregar el pedido";
    }
}

// Obtener categorias y productos
$sql_categorias = "SELECT * FROM Categoria ORDER BY nom_categoria";
$categorias = mysqli_query($conexion, $sql_categorias);

$categoria_seleccionada = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$sql_productos = "SELECT m.*, c.nom_categoria FROM Menu m INNER JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria";
if ($categoria_seleccionada > 0) {
    $sql_productos .= " WHERE m.pkfk_id_categoria = $categoria_seleccionada";
}
$sql_productos .= " ORDER BY c.nom_categoria, m.Productos";
$productos = mysqli_query($conexion, $sql_productos);

// Obtener pedidos del cliente
$mis_pedidos = null;
if ($factura_activa) {
    $sql_pedidos = "SELECT p.*, m.Productos, m.descripcion, c.nom_categoria 
                    FROM Pedido p 
                    INNER JOIN Menu m ON p.pkfk_id_menu = m.id_menu 
                    INNER JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                    WHERE p.pkfk_id_factura = $factura_activa
                    ORDER BY p.fecha_pedido DESC";
    $mis_pedidos = mysqli_query($conexion, $sql_pedidos);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Digital - Divina Comida</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #764ba2;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .logo span {
            color: #764ba2;
        }
        
        .mesa-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .mesa-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Pantalla de acceso */
        .acceso-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        
        .acceso-card {
            background: #fff;
            border-radius: 24px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        
        .acceso-card .icon {
            font-size: 4rem;
            color: #764ba2;
            margin-bottom: 1.5rem;
        }
        
        .acceso-card h2 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .acceso-card p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .codigo-input {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .codigo-input input {
            width: 100%;
            max-width: 200px;
            padding: 1rem;
            font-size: 1.5rem;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5rem;
            font-weight: 600;
        }
        
        .codigo-input input:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .btn-acceder {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-acceder:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(118, 75, 162, 0.4);
        }
        
        .error-msg {
            background: #fee;
            color: #c00;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        /* Navegacion por tabs */
        .tabs {
            display: flex;
            background: #fff;
            border-radius: 16px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .tab-btn {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
        
        .tab-btn .badge {
            background: #e74c3c;
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Categorias */
        .categorias {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        
        .categoria-btn {
            background: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .categoria-btn:hover, .categoria-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
        
        /* Grid de productos */
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .producto-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .producto-card:hover {
            transform: translateY(-5px);
        }
        
        .producto-img {
            height: 160px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .producto-img i {
            font-size: 4rem;
            color: rgba(255,255,255,0.5);
        }
        
        .producto-info {
            padding: 1.5rem;
        }
        
        .producto-categoria {
            color: #764ba2;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .producto-nombre {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .producto-descripcion {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .producto-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .producto-precio {
            font-size: 1.25rem;
            font-weight: 700;
            color: #764ba2;
        }
        
        .btn-agregar {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-agregar:hover {
            transform: scale(1.1);
        }
        
        /* Modal de pedido */
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
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            font-size: 1.25rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #eee;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .cantidad-control {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .cantidad-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #764ba2;
            background: #fff;
            color: #764ba2;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cantidad-btn:hover {
            background: #764ba2;
            color: #fff;
        }
        
        .cantidad-valor {
            font-size: 1.5rem;
            font-weight: 600;
            min-width: 50px;
            text-align: center;
        }
        
        .btn-confirmar {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: transform 0.3s ease;
        }
        
        .btn-confirmar:hover {
            transform: translateY(-2px);
        }
        
        /* Mis pedidos */
        .pedidos-lista {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .pedido-item {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .pedido-estado {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .pedido-estado.pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .pedido-estado.en_preparacion {
            background: #cce5ff;
            color: #004085;
        }
        
        .pedido-estado.listo {
            background: #d4edda;
            color: #155724;
        }
        
        .pedido-estado.entregado {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .pedido-item h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .pedido-item .detalles {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9rem;
        }
        
        .pedido-observaciones {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 10px;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #666;
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
        
        .total-cuenta {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .total-cuenta h3 {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .total-cuenta .monto {
            font-size: 2.5rem;
            font-weight: 700;
            color: #764ba2;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .productos-grid {
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
        <?php if ($mesa_activa): ?>
            <div class="mesa-info">
                <div class="mesa-badge">
                    <i class="fas fa-chair"></i>
                    Mesa <?php echo $mesa_activa; ?>
                </div>
            </div>
        <?php endif; ?>
    </header>
    
    <div class="container">
        <?php if (!$mesa_activa): ?>
            <!-- Pantalla de acceso -->
            <div class="acceso-container">
                <div class="acceso-card">
                    <i class="fas fa-qrcode icon"></i>
                    <h2>Bienvenido</h2>
                    <p>Ingresa el codigo de tu mesa para ver el menu y hacer tu pedido</p>
                    
                    <?php if (isset($error_acceso)): ?>
                        <div class="error-msg">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error_acceso; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="codigo-input">
                            <input type="text" name="codigo_mesa" maxlength="6" placeholder="CODIGO" required>
                        </div>
                        <button type="submit" name="acceder_mesa" class="btn-acceder">
                            <i class="fas fa-sign-in-alt"></i> Acceder al Menu
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
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
            
            <!-- Navegacion por tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('menu')">
                    <i class="fas fa-book-open"></i> Menu
                </button>
                <button class="tab-btn" onclick="showTab('pedidos')">
                    <i class="fas fa-receipt"></i> Mis Pedidos
                    <?php 
                    $count_activos = 0;
                    if ($mis_pedidos && mysqli_num_rows($mis_pedidos) > 0) {
                        mysqli_data_seek($mis_pedidos, 0);
                        while ($p = mysqli_fetch_assoc($mis_pedidos)) {
                            if ($p['estado'] != 'entregado') $count_activos++;
                        }
                        mysqli_data_seek($mis_pedidos, 0);
                    }
                    if ($count_activos > 0): ?>
                        <span class="badge"><?php echo $count_activos; ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <!-- Tab Menu -->
            <div id="tab-menu" class="tab-content active">
                <!-- Categorias -->
                <div class="categorias">
                    <a href="Vista_Cliente.php" class="categoria-btn <?php echo $categoria_seleccionada == 0 ? 'active' : ''; ?>">
                        Todos
                    </a>
                    <?php 
                    mysqli_data_seek($categorias, 0);
                    while ($cat = mysqli_fetch_assoc($categorias)): ?>
                        <a href="Vista_Cliente.php?categoria=<?php echo $cat['id_categoria']; ?>" 
                           class="categoria-btn <?php echo $categoria_seleccionada == $cat['id_categoria'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['nom_categoria']); ?>
                        </a>
                    <?php endwhile; ?>
                </div>
                
                <!-- Grid de productos -->
                <div class="productos-grid">
                    <?php while ($prod = mysqli_fetch_assoc($productos)): ?>
                        <div class="producto-card">
                            <div class="producto-img">
                                <?php
                                $icono = 'fa-utensils';
                                if (stripos($prod['nom_categoria'], 'hamburguesa') !== false) $icono = 'fa-burger';
                                elseif (stripos($prod['nom_categoria'], 'perro') !== false) $icono = 'fa-hotdog';
                                elseif (stripos($prod['nom_categoria'], 'salchi') !== false) $icono = 'fa-bowl-food';
                                ?>
                                <i class="fas <?php echo $icono; ?>"></i>
                            </div>
                            <div class="producto-info">
                                <p class="producto-categoria"><?php echo htmlspecialchars($prod['nom_categoria']); ?></p>
                                <h3 class="producto-nombre"><?php echo htmlspecialchars($prod['Productos']); ?></h3>
                                <p class="producto-descripcion"><?php echo htmlspecialchars($prod['descripcion']); ?></p>
                                <div class="producto-footer">
                                    <span class="producto-precio">$<?php echo number_format($prod['Precio'], 0, ',', '.'); ?></span>
                                    <button class="btn-agregar" onclick="abrirModal(<?php echo $prod['id_menu']; ?>, '<?php echo addslashes($prod['Productos']); ?>', <?php echo $prod['Precio']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Tab Mis Pedidos -->
            <div id="tab-pedidos" class="tab-content">
                <?php if ($mis_pedidos && mysqli_num_rows($mis_pedidos) > 0): ?>
                    <div class="pedidos-lista">
                        <?php 
                        mysqli_data_seek($mis_pedidos, 0);
                        $total_cuenta = 0;
                        while ($pedido = mysqli_fetch_assoc($mis_pedidos)): 
                            $total_cuenta += $pedido['valor_venta'];
                            $estado_texto = [
                                'pendiente' => 'Pendiente',
                                'en_preparacion' => 'En Preparacion',
                                'listo' => 'Listo para Entregar',
                                'entregado' => 'Entregado'
                            ];
                            $estado_icono = [
                                'pendiente' => 'fa-clock',
                                'en_preparacion' => 'fa-fire',
                                'listo' => 'fa-check-circle',
                                'entregado' => 'fa-check-double'
                            ];
                        ?>
                            <div class="pedido-item">
                                <span class="pedido-estado <?php echo $pedido['estado']; ?>">
                                    <i class="fas <?php echo $estado_icono[$pedido['estado']]; ?>"></i>
                                    <?php echo $estado_texto[$pedido['estado']]; ?>
                                </span>
                                <h4><?php echo htmlspecialchars($pedido['Productos']); ?></h4>
                                <div class="detalles">
                                    <span>Cantidad: <?php echo $pedido['cantidad']; ?></span>
                                    <span>$<?php echo number_format($pedido['valor_venta'], 0, ',', '.'); ?></span>
                                </div>
                                <?php if (!empty($pedido['observaciones'])): ?>
                                    <div class="pedido-observaciones">
                                        <i class="fas fa-comment"></i> <?php echo htmlspecialchars($pedido['observaciones']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="total-cuenta">
                        <h3>Total de tu cuenta</h3>
                        <div class="monto">$<?php echo number_format($total_cuenta, 0, ',', '.'); ?></div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; background: #fff; border-radius: 16px;">
                        <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <h3>No tienes pedidos aun</h3>
                        <p style="color: #666;">Explora nuestro menu y agrega tus platos favoritos</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal para agregar pedido -->
    <div id="modal-pedido" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-producto-nombre">Agregar producto</h3>
                <button class="modal-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="id_menu" id="modal-id-menu">
                
                <div class="form-group">
                    <label>Cantidad</label>
                    <div class="cantidad-control">
                        <button type="button" class="cantidad-btn" onclick="cambiarCantidad(-1)">-</button>
                        <span class="cantidad-valor" id="cantidad-display">1</span>
                        <button type="button" class="cantidad-btn" onclick="cambiarCantidad(1)">+</button>
                    </div>
                    <input type="hidden" name="cantidad" id="cantidad-input" value="1">
                </div>
                
                <div class="form-group">
                    <label>Comentarios especiales (opcional)</label>
                    <textarea name="observaciones" rows="3" placeholder="Ej: Sin cebolla, extra queso, etc."></textarea>
                </div>
                
                <div class="form-group">
                    <p style="text-align: center; font-size: 1.25rem;">
                        Subtotal: <strong id="modal-subtotal">$0</strong>
                    </p>
                </div>
                
                <button type="submit" name="agregar_pedido" class="btn-confirmar">
                    <i class="fas fa-cart-plus"></i> Agregar al pedido
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let precioActual = 0;
        let cantidadActual = 1;
        
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.closest('.tab-btn').classList.add('active');
        }
        
        function abrirModal(id, nombre, precio) {
            precioActual = precio;
            cantidadActual = 1;
            document.getElementById('modal-id-menu').value = id;
            document.getElementById('modal-producto-nombre').textContent = nombre;
            document.getElementById('cantidad-display').textContent = cantidadActual;
            document.getElementById('cantidad-input').value = cantidadActual;
            actualizarSubtotal();
            document.getElementById('modal-pedido').classList.add('active');
        }
        
        function cerrarModal() {
            document.getElementById('modal-pedido').classList.remove('active');
        }
        
        function cambiarCantidad(cambio) {
            cantidadActual = Math.max(1, cantidadActual + cambio);
            document.getElementById('cantidad-display').textContent = cantidadActual;
            document.getElementById('cantidad-input').value = cantidadActual;
            actualizarSubtotal();
        }
        
        function actualizarSubtotal() {
            const subtotal = precioActual * cantidadActual;
            document.getElementById('modal-subtotal').textContent = '$' + subtotal.toLocaleString('es-CO');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modal-pedido').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });
        
        // Auto-refresh para ver estados de pedidos
        <?php if ($mesa_activa): ?>
        setInterval(function() {
            const tabPedidos = document.getElementById('tab-pedidos');
            if (tabPedidos.classList.contains('active')) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
