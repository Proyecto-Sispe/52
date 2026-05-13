<?php
session_start();
include "Inicio de Sesion Conexion.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch($action) {
    case 'create':
        createProducto();
        break;
    case 'read':
        readProductos();
        break;
    case 'update':
        updateProducto();
        break;
    case 'delete':
        deleteProducto();
        break;
    case 'get':
        getProducto();
        break;
    case 'categorias':
        getCategorias();
        break;
    default:
        readProductos();
}

// CREATE - Crear nuevo producto
function createProducto() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Formulario Menu.Html");
        exit();
    }
    
    $id_menu = intval($_POST['id_menu']);
    $producto = trim($_POST['producto']);
    $precio = floatval($_POST['precio']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = intval($_POST['categoria']);
    
    // Validaciones
    if (empty($producto) || $precio <= 0) {
        echo "<script>alert('Por favor complete todos los campos correctamente.'); history.back();</script>";
        exit();
    }
    
    // Verificar si el producto ya existe
    $check = $connection->prepare("SELECT id_menu FROM Menu WHERE id_menu = ?");
    $check->bind_param("i", $id_menu);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Ya existe un producto con ese ID.'); history.back();</script>";
        exit();
    }
    $check->close();
    
    try {
        $stmt = $connection->prepare("INSERT INTO Menu (id_menu, Productos, Precio, descripcion, pkfk_id_categoria) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsi", $id_menu, $producto, $precio, $descripcion, $categoria);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Producto creado exitosamente.'); window.location.href='Productos.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al crear producto: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// READ - Leer todos los productos
function readProductos() {
    global $connection;
    
    $where = "1=1";
    $params = [];
    $types = "";
    
    // Filtros
    if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
        $where .= " AND m.pkfk_id_categoria = ?";
        $params[] = intval($_GET['categoria']);
        $types .= "i";
    }
    
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $buscar = "%" . trim($_GET['buscar']) . "%";
        $where .= " AND (m.Productos LIKE ? OR m.id_menu LIKE ?)";
        $params[] = $buscar;
        $params[] = $buscar;
        $types .= "ss";
    }
    
    $sql = "SELECT m.*, c.nom_categoria 
            FROM Menu m
            LEFT JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
            WHERE $where
            ORDER BY m.id_menu";
    
    if (!empty($params)) {
        $stmt = $connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($sql);
    }
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    return $productos;
}

// UPDATE - Actualizar producto
function updateProducto() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Productos.php");
        exit();
    }
    
    $id_menu_original = intval($_POST['id_menu_original']);
    $producto = trim($_POST['producto']);
    $precio = floatval($_POST['precio']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = intval($_POST['categoria']);
    
    try {
        $stmt = $connection->prepare("UPDATE Menu SET Productos=?, Precio=?, descripcion=?, pkfk_id_categoria=? WHERE id_menu=?");
        $stmt->bind_param("sdsii", $producto, $precio, $descripcion, $categoria, $id_menu_original);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Producto actualizado exitosamente.'); window.location.href='Productos.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// DELETE - Eliminar producto
function deleteProducto() {
    global $connection;
    
    $id_menu = intval($_GET['id']);
    
    // Verificar si hay pedidos asociados
    $check = $connection->prepare("SELECT pkfk_id_menu FROM Pedido WHERE pkfk_id_menu = ?");
    $check->bind_param("i", $id_menu);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('No se puede eliminar el producto porque tiene pedidos asociados.'); history.back();</script>";
        exit();
    }
    $check->close();
    
    try {
        $stmt = $connection->prepare("DELETE FROM Menu WHERE id_menu = ?");
        $stmt->bind_param("i", $id_menu);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Producto eliminado exitosamente.'); window.location.href='Productos.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// GET - Obtener un producto especifico
function getProducto() {
    global $connection;
    
    $id_menu = intval($_GET['id']);
    
    $stmt = $connection->prepare("SELECT m.*, c.nom_categoria 
                                  FROM Menu m
                                  LEFT JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                                  WHERE m.id_menu = ?");
    $stmt->bind_param("i", $id_menu);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// GET CATEGORIAS - Obtener todas las categorias
function getCategorias() {
    global $connection;
    
    $result = $connection->query("SELECT * FROM Categoria ORDER BY nom_categoria");
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    
    return $categorias;
}
?>
