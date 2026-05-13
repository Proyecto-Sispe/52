<?php
session_start();
include "Inicio de Sesion Conexion.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch($action) {
    case 'create':
        createPedido();
        break;
    case 'read':
        readPedidos();
        break;
    case 'update':
        updatePedido();
        break;
    case 'delete':
        deletePedido();
        break;
    case 'get':
        getPedido();
        break;
    case 'menus':
        getMenus();
        break;
    case 'facturas':
        getFacturasActivas();
        break;
    default:
        readPedidos();
}

// CREATE - Crear nuevo pedido
function createPedido() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Agregar Pedido.Html");
        exit();
    }
    
    $id_factura = intval($_POST['id_factura']);
    $id_menu = intval($_POST['id_menu']);
    $cantidad = intval($_POST['cantidad']);
    $observaciones = trim($_POST['observaciones']);
    
    // Obtener precio del producto
    $stmt = $connection->prepare("SELECT Precio FROM Menu WHERE id_menu = ?");
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    if (!$producto) {
        echo "<script>alert('Producto no encontrado.'); history.back();</script>";
        exit();
    }
    
    $valor_venta = $producto['Precio'] * $cantidad;
    
    // Verificar si ya existe este pedido
    $check = $connection->prepare("SELECT * FROM Pedido WHERE pkfk_id_factura = ? AND pkfk_id_menu = ?");
    $check->bind_param("ii", $id_factura, $id_menu);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        // Actualizar cantidad si ya existe
        $stmt = $connection->prepare("UPDATE Pedido SET cantidad = cantidad + ?, valor_venta = valor_venta + ? WHERE pkfk_id_factura = ? AND pkfk_id_menu = ?");
        $stmt->bind_param("idii", $cantidad, $valor_venta, $id_factura, $id_menu);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insertar nuevo pedido
        $stmt = $connection->prepare("INSERT INTO Pedido (pkfk_id_factura, pkfk_id_menu, cantidad, observaciones, valor_venta) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisd", $id_factura, $id_menu, $cantidad, $observaciones, $valor_venta);
        $stmt->execute();
        $stmt->close();
    }
    $check->close();
    
    // Actualizar total de la factura
    actualizarTotalFactura($id_factura);
    
    echo "<script>alert('Pedido creado exitosamente.'); window.location.href='Pedidos.php';</script>";
}

// READ - Leer todos los pedidos
function readPedidos() {
    global $connection;
    
    $where = "1=1";
    $params = [];
    $types = "";
    
    // Filtros
    if (isset($_GET['factura']) && !empty($_GET['factura'])) {
        $where .= " AND p.pkfk_id_factura = ?";
        $params[] = intval($_GET['factura']);
        $types .= "i";
    }
    
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $buscar = "%" . trim($_GET['buscar']) . "%";
        $where .= " AND (m.Productos LIKE ? OR p.pkfk_id_factura LIKE ?)";
        $params[] = $buscar;
        $params[] = $buscar;
        $types .= "ss";
    }
    
    $sql = "SELECT p.*, m.Productos, m.Precio, f.pkfk_id_Mesa
            FROM Pedido p
            LEFT JOIN Menu m ON p.pkfk_id_menu = m.id_menu
            LEFT JOIN Factura f ON p.pkfk_id_factura = f.id_factura
            WHERE $where
            ORDER BY p.pkfk_id_factura DESC";
    
    if (!empty($params)) {
        $stmt = $connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($sql);
    }
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
    
    return $pedidos;
}

// UPDATE - Actualizar pedido
function updatePedido() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Pedidos.php");
        exit();
    }
    
    $id_factura = intval($_POST['id_factura_original']);
    $id_menu = intval($_POST['id_menu_original']);
    $cantidad = intval($_POST['cantidad']);
    $observaciones = trim($_POST['observaciones']);
    
    // Obtener precio del producto
    $stmt = $connection->prepare("SELECT Precio FROM Menu WHERE id_menu = ?");
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    $valor_venta = $producto['Precio'] * $cantidad;
    
    try {
        $stmt = $connection->prepare("UPDATE Pedido SET cantidad=?, observaciones=?, valor_venta=? WHERE pkfk_id_factura=? AND pkfk_id_menu=?");
        $stmt->bind_param("isdii", $cantidad, $observaciones, $valor_venta, $id_factura, $id_menu);
        $stmt->execute();
        $stmt->close();
        
        // Actualizar total de la factura
        actualizarTotalFactura($id_factura);
        
        echo "<script>alert('Pedido actualizado exitosamente.'); window.location.href='Pedidos.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// DELETE - Eliminar pedido
function deletePedido() {
    global $connection;
    
    $id_factura = intval($_GET['factura']);
    $id_menu = intval($_GET['menu']);
    
    try {
        $stmt = $connection->prepare("DELETE FROM Pedido WHERE pkfk_id_factura = ? AND pkfk_id_menu = ?");
        $stmt->bind_param("ii", $id_factura, $id_menu);
        $stmt->execute();
        $stmt->close();
        
        // Actualizar total de la factura
        actualizarTotalFactura($id_factura);
        
        echo "<script>alert('Pedido eliminado exitosamente.'); window.location.href='Pedidos.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// GET - Obtener un pedido especifico
function getPedido() {
    global $connection;
    
    $id_factura = intval($_GET['factura']);
    $id_menu = intval($_GET['menu']);
    
    $stmt = $connection->prepare("SELECT p.*, m.Productos, m.Precio
                                  FROM Pedido p
                                  LEFT JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                                  WHERE p.pkfk_id_factura = ? AND p.pkfk_id_menu = ?");
    $stmt->bind_param("ii", $id_factura, $id_menu);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// GET MENUS - Obtener lista de productos del menu
function getMenus() {
    global $connection;
    
    $sql = "SELECT m.id_menu, m.Productos, m.Precio, c.nom_categoria
            FROM Menu m
            LEFT JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
            ORDER BY c.nom_categoria, m.Productos";
    
    $result = $connection->query($sql);
    
    $menus = [];
    while ($row = $result->fetch_assoc()) {
        $menus[] = $row;
    }
    
    return $menus;
}

// GET FACTURAS ACTIVAS
function getFacturasActivas() {
    global $connection;
    
    $sql = "SELECT f.id_factura, f.pkfk_id_Mesa, m.Capacidad
            FROM Factura f
            LEFT JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
            WHERE DATE(f.Fecha_hora) = CURDATE()
            ORDER BY f.id_factura DESC";
    
    $result = $connection->query($sql);
    
    $facturas = [];
    while ($row = $result->fetch_assoc()) {
        $facturas[] = $row;
    }
    
    return $facturas;
}

// Funcion helper para actualizar el total de la factura
function actualizarTotalFactura($id_factura) {
    global $connection;
    
    $stmt = $connection->prepare("SELECT SUM(valor_venta) as total FROM Pedido WHERE pkfk_id_factura = ?");
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $total = $row['total'] ? $row['total'] : 0;
    
    $stmt = $connection->prepare("UPDATE Factura SET Total = ? WHERE id_factura = ?");
    $stmt->bind_param("di", $total, $id_factura);
    $stmt->execute();
    $stmt->close();
}
?>
