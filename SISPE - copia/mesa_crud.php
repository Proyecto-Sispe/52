<?php
session_start();
include "Inicio de Sesion Conexion.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch($action) {
    case 'create':
        createMesa();
        break;
    case 'read':
        readMesas();
        break;
    case 'update':
        updateMesa();
        break;
    case 'delete':
        deleteMesa();
        break;
    case 'get':
        getMesa();
        break;
    default:
        readMesas();
}

// CREATE - Crear nueva mesa
function createMesa() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Agregar Mesa.Html");
        exit();
    }
    
    $id_mesa = intval($_POST['id_mesa']);
    $capacidad = intval($_POST['capacidad']);
    $ubicacion = intval($_POST['ubicacion']);
    $estado = intval($_POST['estado']);
    
    // Validaciones
    if ($id_mesa <= 0 || $capacidad <= 0) {
        echo "<script>alert('Por favor ingrese valores validos.'); history.back();</script>";
        exit();
    }
    
    // Verificar si la mesa ya existe
    $check = $connection->prepare("SELECT id_Mesa FROM Mesa WHERE id_Mesa = ?");
    $check->bind_param("i", $id_mesa);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Ya existe una mesa con ese numero.'); history.back();</script>";
        exit();
    }
    $check->close();
    
    try {
        $stmt = $connection->prepare("INSERT INTO Mesa (id_Mesa, Capacidad, Ubicacion, Estado) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $id_mesa, $capacidad, $ubicacion, $estado);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Mesa creada exitosamente.'); window.location.href='Mesas2.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al crear mesa: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// READ - Leer todas las mesas
function readMesas() {
    global $connection;
    
    $where = "1=1";
    $params = [];
    $types = "";
    
    // Filtros
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $buscar = intval($_GET['buscar']);
        $where .= " AND id_Mesa = ?";
        $params[] = $buscar;
        $types .= "i";
    }
    
    if (isset($_GET['estado']) && $_GET['estado'] !== '') {
        $where .= " AND Estado = ?";
        $params[] = intval($_GET['estado']);
        $types .= "i";
    }
    
    $sql = "SELECT * FROM Mesa WHERE $where ORDER BY id_Mesa";
    
    if (!empty($params)) {
        $stmt = $connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($sql);
    }
    
    $mesas = [];
    while ($row = $result->fetch_assoc()) {
        $mesas[] = $row;
    }
    
    return $mesas;
}

// UPDATE - Actualizar mesa
function updateMesa() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Mesas2.php");
        exit();
    }
    
    $id_mesa_original = intval($_POST['id_mesa_original']);
    $capacidad = intval($_POST['capacidad']);
    $ubicacion = intval($_POST['ubicacion']);
    $estado = intval($_POST['estado']);
    
    try {
        $stmt = $connection->prepare("UPDATE Mesa SET Capacidad=?, Ubicacion=?, Estado=? WHERE id_Mesa=?");
        $stmt->bind_param("iiii", $capacidad, $ubicacion, $estado, $id_mesa_original);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Mesa actualizada exitosamente.'); window.location.href='Mesas2.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// DELETE - Eliminar mesa
function deleteMesa() {
    global $connection;
    
    $id_mesa = intval($_GET['id']);
    
    // Verificar si hay facturas asociadas
    $check = $connection->prepare("SELECT id_factura FROM Factura WHERE pkfk_id_Mesa = ?");
    $check->bind_param("i", $id_mesa);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('No se puede eliminar la mesa porque tiene facturas asociadas.'); history.back();</script>";
        exit();
    }
    $check->close();
    
    try {
        $stmt = $connection->prepare("DELETE FROM Mesa WHERE id_Mesa = ?");
        $stmt->bind_param("i", $id_mesa);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Mesa eliminada exitosamente.'); window.location.href='Mesas2.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al eliminar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// GET - Obtener una mesa especifica
function getMesa() {
    global $connection;
    
    $id_mesa = intval($_GET['id']);
    
    $stmt = $connection->prepare("SELECT * FROM Mesa WHERE id_Mesa = ?");
    $stmt->bind_param("i", $id_mesa);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Funcion helper para obtener estado como texto
function getEstadoTexto($estado) {
    switch($estado) {
        case 0: return 'Libre';
        case 1: return 'Ocupada';
        case 2: return 'Reservada';
        default: return 'Desconocido';
    }
}

// Funcion helper para obtener ubicacion como texto
function getUbicacionTexto($ubicacion) {
    switch($ubicacion) {
        case 1: return 'Piso 1';
        case 2: return 'Piso 2';
        case 3: return 'Terraza';
        default: return 'Piso ' . $ubicacion;
    }
}
?>
