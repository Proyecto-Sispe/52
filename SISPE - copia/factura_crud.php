<?php
session_start();
include "Inicio de Sesion Conexion.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch($action) {
    case 'create':
        createFactura();
        break;
    case 'read':
        readFacturas();
        break;
    case 'update':
        updateFactura();
        break;
    case 'delete':
        deleteFactura();
        break;
    case 'get':
        getFactura();
        break;
    case 'meseros':
        getMeseros();
        break;
    case 'clientes':
        getClientes();
        break;
    case 'mesas':
        getMesasDisponibles();
        break;
    default:
        readFacturas();
}

// CREATE - Crear nueva factura
function createFactura() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Agregar Factura.Html");
        exit();
    }
    
    $id_factura = intval($_POST['id_factura']);
    $fecha_hora = $_POST['fecha'] . ' ' . $_POST['hora'] . ':00';
    $total = floatval($_POST['total']);
    $id_mesa = intval($_POST['id_mesa']);
    $tipo_doc_mesero = intval($_POST['tipo_doc_mesero']);
    $id_mesero = intval($_POST['id_mesero']);
    $tipo_doc_cliente = intval($_POST['tipo_doc_cliente']);
    $id_cliente = intval($_POST['id_cliente']);
    
    // Validaciones
    if ($id_factura <= 0 || $total < 0) {
        echo "<script>alert('Por favor ingrese valores validos.'); history.back();</script>";
        exit();
    }
    
    // Verificar si la factura ya existe
    $check = $connection->prepare("SELECT id_factura FROM Factura WHERE id_factura = ?");
    $check->bind_param("i", $id_factura);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Ya existe una factura con ese numero.'); history.back();</script>";
        exit();
    }
    $check->close();
    
    try {
        $stmt = $connection->prepare("INSERT INTO Factura (id_factura, Fecha_hora, Total, pkfk_id_Mesa, pkfk_Tipo_doc, pkfk_mesero_id_usuario, pkfk_cliente_tipo_doc, Cliente_Persona_id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdiiiii", $id_factura, $fecha_hora, $total, $id_mesa, $tipo_doc_mesero, $id_mesero, $tipo_doc_cliente, $id_cliente);
        $stmt->execute();
        $stmt->close();
        
        // Actualizar estado de la mesa a ocupada
        $stmt = $connection->prepare("UPDATE Mesa SET Estado = 1 WHERE id_Mesa = ?");
        $stmt->bind_param("i", $id_mesa);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Factura creada exitosamente.'); window.location.href='Factura.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al crear factura: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// READ - Leer todas las facturas
function readFacturas() {
    global $connection;
    
    $where = "1=1";
    $params = [];
    $types = "";
    
    // Filtros
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $buscar = "%" . trim($_GET['buscar']) . "%";
        $where .= " AND (f.id_factura LIKE ? OR c.id_usuario LIKE ? OR CONCAT(c.Nom1_usu, ' ', c.Ape1_usu) LIKE ?)";
        $params[] = $buscar;
        $params[] = $buscar;
        $params[] = $buscar;
        $types .= "sss";
    }
    
    if (isset($_GET['fecha']) && !empty($_GET['fecha'])) {
        $where .= " AND DATE(f.Fecha_hora) = ?";
        $params[] = $_GET['fecha'];
        $types .= "s";
    }
    
    $sql = "SELECT f.*, 
                   m.Capacidad as mesa_capacidad,
                   CONCAT(mes.Nom1_usu, ' ', mes.Ape1_usu) as mesero_nombre,
                   CONCAT(c.Nom1_usu, ' ', c.Ape1_usu) as cliente_nombre,
                   c.id_usuario as cliente_documento
            FROM Factura f
            LEFT JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
            LEFT JOIN Persona mes ON f.pkfk_mesero_id_usuario = mes.id_usuario AND f.pkfk_Tipo_doc = mes.pkfk_Tipo_doc
            LEFT JOIN Persona c ON f.Cliente_Persona_id_usuario = c.id_usuario AND f.pkfk_cliente_tipo_doc = c.pkfk_Tipo_doc
            WHERE $where
            ORDER BY f.Fecha_hora DESC";
    
    if (!empty($params)) {
        $stmt = $connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($sql);
    }
    
    $facturas = [];
    while ($row = $result->fetch_assoc()) {
        $facturas[] = $row;
    }
    
    return $facturas;
}

// UPDATE - Actualizar factura
function updateFactura() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Factura.php");
        exit();
    }
    
    $id_factura_original = intval($_POST['id_factura_original']);
    $fecha_hora = $_POST['fecha'] . ' ' . $_POST['hora'] . ':00';
    $total = floatval($_POST['total']);
    $id_mesa = intval($_POST['id_mesa']);
    
    try {
        $stmt = $connection->prepare("UPDATE Factura SET Fecha_hora=?, Total=?, pkfk_id_Mesa=? WHERE id_factura=?");
        $stmt->bind_param("sdii", $fecha_hora, $total, $id_mesa, $id_factura_original);
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Factura actualizada exitosamente.'); window.location.href='Factura.php';</script>";
        
    } catch (Exception $e) {
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// DELETE - Eliminar factura
function deleteFactura() {
    global $connection;
    
    $id_factura = intval($_GET['id']);
    
    $connection->begin_transaction();
    
    try {
        // Obtener la mesa de la factura para liberar
        $stmt = $connection->prepare("SELECT pkfk_id_Mesa FROM Factura WHERE id_factura = ?");
        $stmt->bind_param("i", $id_factura);
        $stmt->execute();
        $result = $stmt->get_result();
        $factura = $result->fetch_assoc();
        $stmt->close();
        
        // Eliminar pedidos asociados
        $stmt = $connection->prepare("DELETE FROM Pedido WHERE pkfk_id_factura = ?");
        $stmt->bind_param("i", $id_factura);
        $stmt->execute();
        $stmt->close();
        
        // Eliminar metodos de pago asociados
        $stmt = $connection->prepare("DELETE FROM Factura_has_Metodo_pago WHERE pkfk_n_factura = ?");
        $stmt->bind_param("i", $id_factura);
        $stmt->execute();
        $stmt->close();
        
        // Eliminar factura
        $stmt = $connection->prepare("DELETE FROM Factura WHERE id_factura = ?");
        $stmt->bind_param("i", $id_factura);
        $stmt->execute();
        $stmt->close();
        
        // Liberar la mesa
        if ($factura) {
            $stmt = $connection->prepare("UPDATE Mesa SET Estado = 0 WHERE id_Mesa = ?");
            $stmt->bind_param("i", $factura['pkfk_id_Mesa']);
            $stmt->execute();
            $stmt->close();
        }
        
        $connection->commit();
        echo "<script>alert('Factura eliminada exitosamente.'); window.location.href='Factura.php';</script>";
        
    } catch (Exception $e) {
        $connection->rollback();
        echo "<script>alert('Error al eliminar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// GET - Obtener una factura especifica
function getFactura() {
    global $connection;
    
    $id_factura = intval($_GET['id']);
    
    $stmt = $connection->prepare("SELECT f.*, 
                                         CONCAT(mes.Nom1_usu, ' ', mes.Ape1_usu) as mesero_nombre,
                                         CONCAT(c.Nom1_usu, ' ', c.Ape1_usu) as cliente_nombre
                                  FROM Factura f
                                  LEFT JOIN Persona mes ON f.pkfk_mesero_id_usuario = mes.id_usuario AND f.pkfk_Tipo_doc = mes.pkfk_Tipo_doc
                                  LEFT JOIN Persona c ON f.Cliente_Persona_id_usuario = c.id_usuario AND f.pkfk_cliente_tipo_doc = c.pkfk_Tipo_doc
                                  WHERE f.id_factura = ?");
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// GET MESEROS - Obtener lista de meseros
function getMeseros() {
    global $connection;
    
    $sql = "SELECT p.id_usuario, p.pkfk_Tipo_doc, CONCAT(p.Nom1_usu, ' ', p.Ape1_usu) as nombre
            FROM Persona p
            INNER JOIN Mesero m ON p.id_usuario = m.pkfk_id_usuario AND p.pkfk_Tipo_doc = m.pkfk_Tipo_doc
            ORDER BY p.Nom1_usu";
    
    $result = $connection->query($sql);
    
    $meseros = [];
    while ($row = $result->fetch_assoc()) {
        $meseros[] = $row;
    }
    
    return $meseros;
}

// GET CLIENTES - Obtener lista de clientes
function getClientes() {
    global $connection;
    
    $sql = "SELECT p.id_usuario, p.pkfk_Tipo_doc, CONCAT(p.Nom1_usu, ' ', p.Ape1_usu) as nombre
            FROM Persona p
            INNER JOIN Cliente c ON p.id_usuario = c.pkfk_id_usuario AND p.pkfk_Tipo_doc = c.pkfk_Tipo_doc
            ORDER BY p.Nom1_usu";
    
    $result = $connection->query($sql);
    
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    
    return $clientes;
}

// GET MESAS DISPONIBLES
function getMesasDisponibles() {
    global $connection;
    
    $result = $connection->query("SELECT * FROM Mesa WHERE Estado = 0 ORDER BY id_Mesa");
    
    $mesas = [];
    while ($row = $result->fetch_assoc()) {
        $mesas[] = $row;
    }
    
    return $mesas;
}
?>
