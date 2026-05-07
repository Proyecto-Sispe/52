<?php
session_start();
include "Inicio de Sesion Conexion.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch($action) {
    case 'create':
        createPersona();
        break;
    case 'read':
        readPersonas();
        break;
    case 'update':
        updatePersona();
        break;
    case 'delete':
        deletePersona();
        break;
    case 'get':
        getPersona();
        break;
    default:
        readPersonas();
}

// CREATE - Crear nueva persona
function createPersona() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Agregar Persona.Html");
        exit();
    }
    
    $tipo_doc = intval($_POST['tipo_doc']);
    $id_usuario = intval($_POST['id_usuario']);
    $nom1 = trim($_POST['nom1']);
    $nom2 = trim($_POST['nom2']);
    $ape1 = trim($_POST['ape1']);
    $ape2 = trim($_POST['ape2']);
    $telefono = intval($_POST['telefono']);
    $rol = intval($_POST['rol']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validaciones
    if (empty($nom1) || empty($ape1) || empty($email) || empty($password)) {
        echo "<script>alert('Por favor complete todos los campos obligatorios.'); history.back();</script>";
        exit();
    }
    
    // Verificar si el usuario ya existe
    $check = $connection->prepare("SELECT id_usuario FROM Persona WHERE id_usuario = ? AND pkfk_Tipo_doc = ?");
    $check->bind_param("ii", $id_usuario, $tipo_doc);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('Ya existe una persona con ese documento.'); history.back();</script>";
        exit();
    }
    $check->close();
    
    // Iniciar transaccion
    $connection->begin_transaction();
    
    try {
        // Insertar en Persona
        $stmt = $connection->prepare("INSERT INTO Persona (pkfk_Tipo_doc, id_usuario, Nom1_usu, Nom2_usu, Ape1_usu, Ape2_usu, Telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssi", $tipo_doc, $id_usuario, $nom1, $nom2, $ape1, $ape2, $telefono);
        $stmt->execute();
        $stmt->close();
        
        // Insertar en Persona_has_Rol
        $stmt = $connection->prepare("INSERT INTO Persona_has_Rol (pkfk_Tipo_doc, pkfk_id_usuario, pkfk_idRol) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $tipo_doc, $id_usuario, $rol);
        $stmt->execute();
        $stmt->close();
        
        // Si tiene email y password, crear usuario
        if (!empty($email) && !empty($password)) {
            $stmt = $connection->prepare("INSERT INTO Usuario (Correo_usu, Password, pkfk_Tipo_doc, pkfk_id_usuario) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $email, $password, $tipo_doc, $id_usuario);
            $stmt->execute();
            $stmt->close();
        }
        
        // Insertar en tabla de rol especifica
        switch($rol) {
            case 1: // Administrador
                $stmt = $connection->prepare("INSERT INTO Admin (pkfk_Tipo_doc, pkfk_id_usuario) VALUES (?, ?)");
                break;
            case 2: // Cocinero
                $stmt = $connection->prepare("INSERT INTO Cocinero (pkfk_Tipo_doc, pkfk_id_usuario) VALUES (?, ?)");
                break;
            case 3: // Mesero
                $stmt = $connection->prepare("INSERT INTO Mesero (pkfk_Tipo_doc, pkfk_id_usuario) VALUES (?, ?)");
                break;
            case 4: // Cliente
                $stmt = $connection->prepare("INSERT INTO Cliente (pkfk_Tipo_doc, pkfk_id_usuario) VALUES (?, ?)");
                break;
        }
        if (isset($stmt)) {
            $stmt->bind_param("ii", $tipo_doc, $id_usuario);
            $stmt->execute();
            $stmt->close();
        }
        
        $connection->commit();
        echo "<script>alert('Persona creada exitosamente.'); window.location.href='Persona.php';</script>";
        
    } catch (Exception $e) {
        $connection->rollback();
        echo "<script>alert('Error al crear persona: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// READ - Leer todas las personas
function readPersonas() {
    global $connection;
    
    $where = "1=1";
    $params = [];
    $types = "";
    
    // Filtros
    if (isset($_GET['tipo_doc']) && !empty($_GET['tipo_doc'])) {
        $where .= " AND p.pkfk_Tipo_doc = ?";
        $params[] = intval($_GET['tipo_doc']);
        $types .= "i";
    }
    
    if (isset($_GET['rol']) && !empty($_GET['rol'])) {
        $where .= " AND pr.pkfk_idRol = ?";
        $params[] = intval($_GET['rol']);
        $types .= "i";
    }
    
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $buscar = "%" . trim($_GET['buscar']) . "%";
        $where .= " AND (p.Nom1_usu LIKE ? OR p.id_usuario LIKE ?)";
        $params[] = $buscar;
        $params[] = $buscar;
        $types .= "ss";
    }
    
    $sql = "SELECT p.*, td.tipo_doc, r.Nom_rol, u.Correo_usu, u.Password
            FROM Persona p
            LEFT JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
            LEFT JOIN Persona_has_Rol pr ON p.id_usuario = pr.pkfk_id_usuario AND p.pkfk_Tipo_doc = pr.pkfk_Tipo_doc
            LEFT JOIN Rol r ON pr.pkfk_idRol = r.idRol
            LEFT JOIN Usuario u ON p.id_usuario = u.pkfk_id_usuario AND p.pkfk_Tipo_doc = u.pkfk_Tipo_doc
            WHERE $where
            ORDER BY p.id_usuario";
    
    if (!empty($params)) {
        $stmt = $connection->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connection->query($sql);
    }
    
    $personas = [];
    while ($row = $result->fetch_assoc()) {
        $personas[] = $row;
    }
    
    return $personas;
}

// UPDATE - Actualizar persona
function updatePersona() {
    global $connection;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: Persona.php");
        exit();
    }
    
    $tipo_doc_original = intval($_POST['tipo_doc_original']);
    $id_usuario_original = intval($_POST['id_usuario_original']);
    $nom1 = trim($_POST['nom1']);
    $nom2 = trim($_POST['nom2']);
    $ape1 = trim($_POST['ape1']);
    $ape2 = trim($_POST['ape2']);
    $telefono = intval($_POST['telefono']);
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    $connection->begin_transaction();
    
    try {
        // Actualizar Persona
        $stmt = $connection->prepare("UPDATE Persona SET Nom1_usu=?, Nom2_usu=?, Ape1_usu=?, Ape2_usu=?, Telefono=? WHERE id_usuario=? AND pkfk_Tipo_doc=?");
        $stmt->bind_param("ssssiis", $nom1, $nom2, $ape1, $ape2, $telefono, $id_usuario_original, $tipo_doc_original);
        $stmt->execute();
        $stmt->close();
        
        // Actualizar Usuario si existe
        if (!empty($email)) {
            $check = $connection->prepare("SELECT * FROM Usuario WHERE pkfk_id_usuario = ? AND pkfk_Tipo_doc = ?");
            $check->bind_param("ii", $id_usuario_original, $tipo_doc_original);
            $check->execute();
            
            if ($check->get_result()->num_rows > 0) {
                if (!empty($password)) {
                    $stmt = $connection->prepare("UPDATE Usuario SET Correo_usu=?, Password=? WHERE pkfk_id_usuario=? AND pkfk_Tipo_doc=?");
                    $stmt->bind_param("ssii", $email, $password, $id_usuario_original, $tipo_doc_original);
                } else {
                    $stmt = $connection->prepare("UPDATE Usuario SET Correo_usu=? WHERE pkfk_id_usuario=? AND pkfk_Tipo_doc=?");
                    $stmt->bind_param("sii", $email, $id_usuario_original, $tipo_doc_original);
                }
                $stmt->execute();
                $stmt->close();
            }
            $check->close();
        }
        
        $connection->commit();
        echo "<script>alert('Persona actualizada exitosamente.'); window.location.href='Persona.php';</script>";
        
    } catch (Exception $e) {
        $connection->rollback();
        echo "<script>alert('Error al actualizar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// DELETE - Eliminar persona
function deletePersona() {
    global $connection;
    
    $tipo_doc = intval($_GET['tipo_doc']);
    $id_usuario = intval($_GET['id_usuario']);
    
    $connection->begin_transaction();
    
    try {
        // Eliminar de tablas relacionadas en orden correcto
        $tables = ['Admin', 'Cocinero', 'Mesero', 'Cliente', 'Usuario', 'Persona_has_Rol'];
        
        foreach ($tables as $table) {
            $stmt = $connection->prepare("DELETE FROM $table WHERE pkfk_id_usuario = ? AND pkfk_Tipo_doc = ?");
            $stmt->bind_param("ii", $id_usuario, $tipo_doc);
            $stmt->execute();
            $stmt->close();
        }
        
        // Eliminar de Persona
        $stmt = $connection->prepare("DELETE FROM Persona WHERE id_usuario = ? AND pkfk_Tipo_doc = ?");
        $stmt->bind_param("ii", $id_usuario, $tipo_doc);
        $stmt->execute();
        $stmt->close();
        
        $connection->commit();
        echo "<script>alert('Persona eliminada exitosamente.'); window.location.href='Persona.php';</script>";
        
    } catch (Exception $e) {
        $connection->rollback();
        echo "<script>alert('Error al eliminar: " . $e->getMessage() . "'); history.back();</script>";
    }
}

// GET - Obtener una persona especifica
function getPersona() {
    global $connection;
    
    $tipo_doc = intval($_GET['tipo_doc']);
    $id_usuario = intval($_GET['id_usuario']);
    
    $stmt = $connection->prepare("SELECT p.*, td.tipo_doc, r.Nom_rol, r.idRol, u.Correo_usu, u.Password
                                  FROM Persona p
                                  LEFT JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
                                  LEFT JOIN Persona_has_Rol pr ON p.id_usuario = pr.pkfk_id_usuario AND p.pkfk_Tipo_doc = pr.pkfk_Tipo_doc
                                  LEFT JOIN Rol r ON pr.pkfk_idRol = r.idRol
                                  LEFT JOIN Usuario u ON p.id_usuario = u.pkfk_id_usuario AND p.pkfk_Tipo_doc = u.pkfk_Tipo_doc
                                  WHERE p.id_usuario = ? AND p.pkfk_Tipo_doc = ?");
    $stmt->bind_param("ii", $id_usuario, $tipo_doc);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>
