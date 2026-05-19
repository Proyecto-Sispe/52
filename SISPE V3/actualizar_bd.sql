-- Script para actualizar la base de datos con los nuevos campos necesarios
-- Ejecutar DESPUES de crear la base de datos con Divina_comida.sql
USE divina_comida;

-- =====================================================
-- ACTUALIZAR TABLA TIPO_DOC CON VALORES CORRECTOS
-- =====================================================
-- Primero eliminar los registros existentes si es necesario
DELETE FROM Tipo_doc WHERE id_doc IN (1, 2, 3);

-- Insertar los tipos de documento correctos
INSERT INTO Tipo_doc (id_doc, tipo_doc, estado) VALUES 
(1, 'Cédula de Ciudadanía', 1),
(2, 'Tarjeta de Identidad', 1),
(3, 'Cédula de Extranjería', 1)
ON DUPLICATE KEY UPDATE tipo_doc = VALUES(tipo_doc);

-- =====================================================
-- AGREGAR CAMPOS NUEVOS A TABLA PEDIDO
-- =====================================================
-- Verificar y agregar campo de estado al pedido
ALTER TABLE Pedido ADD COLUMN IF NOT EXISTS estado ENUM('pendiente', 'en_preparacion', 'listo', 'entregado') DEFAULT 'pendiente';

-- Agregar campo de fecha/hora al pedido
ALTER TABLE Pedido ADD COLUMN IF NOT EXISTS fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Agregar campo de prioridad al pedido
ALTER TABLE Pedido ADD COLUMN IF NOT EXISTS prioridad ENUM('normal', 'urgente') DEFAULT 'normal';

-- Agregar campo de cocinero asignado
ALTER TABLE Pedido ADD COLUMN IF NOT EXISTS cocinero_asignado INT DEFAULT NULL;

-- Agregar campo de tiempo estimado de preparacion
ALTER TABLE Pedido ADD COLUMN IF NOT EXISTS tiempo_estimado INT DEFAULT 15;

-- =====================================================
-- CREAR TABLA DE NOTIFICACIONES
-- =====================================================
CREATE TABLE IF NOT EXISTS Notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('pedido_listo', 'nuevo_pedido', 'pedido_urgente', 'mesa_liberada') NOT NULL,
    mensaje TEXT NOT NULL,
    id_mesa INT DEFAULT NULL,
    id_pedido_factura INT DEFAULT NULL,
    id_pedido_menu INT DEFAULT NULL,
    leida TINYINT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    destinatario_rol INT DEFAULT NULL,
    FOREIGN KEY (id_mesa) REFERENCES Mesa(id_Mesa) ON DELETE SET NULL,
    FOREIGN KEY (destinatario_rol) REFERENCES Rol(idRol) ON DELETE SET NULL
);

-- =====================================================
-- CREAR TABLA DE SESIONES DE MESA (PARA CLIENTES)
-- =====================================================
CREATE TABLE IF NOT EXISTS Sesion_Mesa (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    codigo_acceso VARCHAR(6) NOT NULL UNIQUE,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME DEFAULT NULL,
    activa TINYINT DEFAULT 1,
    FOREIGN KEY (id_mesa) REFERENCES Mesa(id_Mesa) ON DELETE CASCADE
);

-- =====================================================
-- CREAR INDICES PARA MEJORAR RENDIMIENTO
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_pedido_estado ON Pedido(estado);
CREATE INDEX IF NOT EXISTS idx_pedido_fecha ON Pedido(fecha_pedido);
CREATE INDEX IF NOT EXISTS idx_notificacion_leida ON Notificaciones(leida);
CREATE INDEX IF NOT EXISTS idx_sesion_mesa_activa ON Sesion_Mesa(activa);
CREATE INDEX IF NOT EXISTS idx_sesion_mesa_codigo ON Sesion_Mesa(codigo_acceso);

-- =====================================================
-- ACTUALIZAR ESTADOS DE PEDIDOS EXISTENTES
-- =====================================================
UPDATE Pedido SET estado = 'entregado' WHERE estado IS NULL;
UPDATE Pedido SET fecha_pedido = NOW() WHERE fecha_pedido IS NULL;
UPDATE Pedido SET prioridad = 'normal' WHERE prioridad IS NULL;

-- =====================================================
-- AGREGAR USUARIOS DE PRUEBA PARA MESEROS
-- =====================================================
-- Verificar si existen usuarios meseros, si no crearlos
INSERT INTO Usuario (Correo_usu, Password, pkfk_Tipo_doc, pkfk_id_usuario) 
SELECT 'mesero1@gmail.com', '1234567890', 1, 1053804357
WHERE NOT EXISTS (SELECT 1 FROM Usuario WHERE Correo_usu = 'mesero1@gmail.com');

INSERT INTO Usuario (Correo_usu, Password, pkfk_Tipo_doc, pkfk_id_usuario) 
SELECT 'mesero2@gmail.com', '1234567890', 1, 1152693247
WHERE NOT EXISTS (SELECT 1 FROM Usuario WHERE Correo_usu = 'mesero2@gmail.com');

-- =====================================================
-- CREAR ALGUNAS SESIONES DE MESA DE EJEMPLO
-- =====================================================
INSERT INTO Sesion_Mesa (id_mesa, codigo_acceso, activa) 
SELECT 1, 'ABC123', 1 WHERE NOT EXISTS (SELECT 1 FROM Sesion_Mesa WHERE codigo_acceso = 'ABC123');

INSERT INTO Sesion_Mesa (id_mesa, codigo_acceso, activa) 
SELECT 2, 'DEF456', 1 WHERE NOT EXISTS (SELECT 1 FROM Sesion_Mesa WHERE codigo_acceso = 'DEF456');

-- =====================================================
-- MENSAJE DE FINALIZACION
-- =====================================================
SELECT 'Base de datos actualizada correctamente' AS mensaje;
