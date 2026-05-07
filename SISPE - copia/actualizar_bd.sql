-- Script para actualizar la base de datos con los nuevos campos necesarios
USE divina_comida;

-- Agregar campo de estado al pedido
ALTER TABLE Pedido ADD COLUMN estado ENUM('pendiente', 'en_preparacion', 'listo', 'entregado') DEFAULT 'pendiente';

-- Agregar campo de fecha/hora al pedido
ALTER TABLE Pedido ADD COLUMN fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Agregar campo de prioridad al pedido
ALTER TABLE Pedido ADD COLUMN prioridad ENUM('normal', 'urgente') DEFAULT 'normal';

-- Agregar campo de cocinero asignado
ALTER TABLE Pedido ADD COLUMN cocinero_asignado INT DEFAULT NULL;

-- Agregar campo de tiempo estimado de preparacion
ALTER TABLE Pedido ADD COLUMN tiempo_estimado INT DEFAULT 15;

-- Crear tabla de notificaciones
CREATE TABLE IF NOT EXISTS Notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('pedido_listo', 'nuevo_pedido', 'pedido_urgente') NOT NULL,
    mensaje TEXT NOT NULL,
    id_mesa INT,
    id_pedido_factura INT,
    id_pedido_menu INT,
    leida TINYINT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    destinatario_rol INT
);

-- Crear tabla de sesiones de mesa (para clientes)
CREATE TABLE IF NOT EXISTS Sesion_Mesa (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    codigo_acceso VARCHAR(6) NOT NULL,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME DEFAULT NULL,
    activa TINYINT DEFAULT 1,
    FOREIGN KEY (id_mesa) REFERENCES Mesa(id_Mesa)
);

-- Actualizar estados de pedidos existentes
UPDATE Pedido SET estado = 'entregado' WHERE estado IS NULL;
