CREATE DATABASE sistema;
USE sistema;

-- =========================
-- TABLAS
-- =========================

CREATE TABLE Rol (
    idRol INT NOT NULL,
    Nom_rol VARCHAR(45) NOT NULL,
    PRIMARY KEY (idRol)
);

CREATE TABLE Tipo_doc (
    id_doc INT NOT NULL,
    tipo_doc VARCHAR(45) NOT NULL,
    estado TINYINT NOT NULL,
    PRIMARY KEY(id_doc)
);

CREATE TABLE Persona (
    id_usuario INT NOT NULL,
    pkfk_Tipo_doc INT NOT NULL,

    Nom1_usu VARCHAR(20) NOT NULL,
    Nom2_usu VARCHAR(20),
    Ape1_usu VARCHAR(20) NOT NULL,
    Ape2_usu VARCHAR(20),

    Telefono BIGINT NOT NULL,

    Correo_usu VARCHAR(45) UNIQUE,
    Password VARCHAR(255),

    estado TINYINT DEFAULT 1,

    PRIMARY KEY (id_usuario, pkfk_Tipo_doc)
);

CREATE TABLE Persona_has_Rol (
    pkfk_Tipo_doc INT NOT NULL,
    pkfk_id_usuario INT NOT NULL,
    pkfk_idRol INT NOT NULL,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc,pkfk_idRol)
);

CREATE TABLE Mesa (
    id_Mesa INT NOT NULL,
    Capacidad MEDIUMINT NOT NULL,
    Ubicacion VARCHAR(50) NOT NULL,
    Estado TINYINT NOT NULL,
    PRIMARY KEY(id_Mesa)
);

CREATE TABLE Categoria (
    id_categoria INT NOT NULL,
    nom_categoria VARCHAR(100) NOT NULL,
    PRIMARY KEY(id_categoria)
);

CREATE TABLE Menu (
    id_menu INT NOT NULL,
    Productos VARCHAR(50) NOT NULL,
    Precio FLOAT NOT NULL,
    descripcion TEXT NOT NULL,
    pkfk_id_categoria INT NOT NULL,
    PRIMARY KEY(id_menu)
);

CREATE TABLE Pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,

    id_mesa INT NOT NULL,

    mesero_tipo_doc INT NOT NULL,
    mesero_id_usuario INT NOT NULL,

    cliente_tipo_doc INT,
    cliente_id_usuario INT,

    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,

    estado ENUM(
        'pendiente',
        'en_preparacion',
        'listo',
        'entregado'
    ) DEFAULT 'pendiente',

    prioridad ENUM(
        'normal',
        'urgente'
    ) DEFAULT 'normal',

    cocinero_asignado INT DEFAULT NULL,

    tiempo_estimado INT DEFAULT 15,

    observaciones TEXT
);

CREATE TABLE Detalle_Pedido (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,

    id_pedido INT NOT NULL,

    id_menu INT NOT NULL,

    cantidad INT NOT NULL,

    valor_venta FLOAT NOT NULL,

    observaciones TEXT
);

CREATE TABLE Factura (
    id_factura INT AUTO_INCREMENT PRIMARY KEY,

    id_pedido INT NOT NULL UNIQUE,

    Fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,

    Total FLOAT NOT NULL
);

CREATE TABLE Metodo_pago (
    id_pago INT NOT NULL,
    Tipo_pago VARCHAR(45) NOT NULL,
    PRIMARY KEY(id_pago)
);

CREATE TABLE Factura_has_Metodo_pago (
    pkfk_n_factura INT NOT NULL,
    pkfk_metodo_pago INT NOT NULL,
    monto FLOAT NOT NULL,

    PRIMARY KEY (
        pkfk_n_factura,
        pkfk_metodo_pago
    )
);

CREATE TABLE Notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,

    tipo ENUM(
        'pedido_listo',
        'nuevo_pedido',
        'pedido_urgente',
        'mesa_liberada'
    ) NOT NULL,

    mensaje TEXT NOT NULL,

    id_mesa INT DEFAULT NULL,

    id_pedido INT DEFAULT NULL,

    leida TINYINT DEFAULT 0,

    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,

    destinatario_rol INT DEFAULT NULL
);

CREATE TABLE Sesion_Mesa (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,

    id_mesa INT NOT NULL,

    codigo_acceso VARCHAR(6) NOT NULL UNIQUE,

    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,

    fecha_fin DATETIME DEFAULT NULL,

    activa TINYINT DEFAULT 1
);

-- =========================
-- CONSTRAINTS
-- =========================

ALTER TABLE Persona
ADD CONSTRAINT fk_persona_tipo_doc
FOREIGN KEY (pkfk_Tipo_doc)
REFERENCES Tipo_doc(id_doc);

ALTER TABLE Persona_has_Rol
ADD CONSTRAINT fk_phr_persona
FOREIGN KEY (
    pkfk_id_usuario,
    pkfk_Tipo_doc
)
REFERENCES Persona(
    id_usuario,
    pkfk_Tipo_doc
);

ALTER TABLE Persona_has_Rol
ADD CONSTRAINT fk_phr_rol
FOREIGN KEY (pkfk_idRol)
REFERENCES Rol(idRol);

ALTER TABLE Menu
ADD CONSTRAINT fk_menu_categoria
FOREIGN KEY (pkfk_id_categoria)
REFERENCES Categoria(id_categoria);

ALTER TABLE Pedido
ADD CONSTRAINT fk_pedido_mesa
FOREIGN KEY (id_mesa)
REFERENCES Mesa(id_Mesa);

ALTER TABLE Pedido
ADD CONSTRAINT fk_pedido_mesero
FOREIGN KEY (
    mesero_id_usuario,
    mesero_tipo_doc
)
REFERENCES Persona(
    id_usuario,
    pkfk_Tipo_doc
);

ALTER TABLE Pedido
ADD CONSTRAINT fk_pedido_cliente
FOREIGN KEY (
    cliente_id_usuario,
    cliente_tipo_doc
)
REFERENCES Persona(
    id_usuario,
    pkfk_Tipo_doc
);

ALTER TABLE Pedido
ADD CONSTRAINT fk_pedido_cocinero
FOREIGN KEY (
    cocinero_asignado,
    mesero_tipo_doc
)
REFERENCES Persona(
    id_usuario,
    pkfk_Tipo_doc
);

ALTER TABLE Detalle_Pedido
ADD CONSTRAINT fk_detalle_pedido
FOREIGN KEY (id_pedido)
REFERENCES Pedido(id_pedido);

ALTER TABLE Detalle_Pedido
ADD CONSTRAINT fk_detalle_menu
FOREIGN KEY (id_menu)
REFERENCES Menu(id_menu);

ALTER TABLE Factura
ADD CONSTRAINT fk_factura_pedido
FOREIGN KEY (id_pedido)
REFERENCES Pedido(id_pedido);

ALTER TABLE Factura_has_Metodo_pago
ADD CONSTRAINT fk_fmp_factura
FOREIGN KEY (pkfk_n_factura)
REFERENCES Factura(id_factura);

ALTER TABLE Factura_has_Metodo_pago
ADD CONSTRAINT fk_fmp_metodo
FOREIGN KEY (pkfk_metodo_pago)
REFERENCES Metodo_pago(id_pago);

ALTER TABLE Notificaciones
ADD CONSTRAINT fk_noti_mesa
FOREIGN KEY (id_mesa)
REFERENCES Mesa(id_Mesa)
ON DELETE SET NULL;

ALTER TABLE Notificaciones
ADD CONSTRAINT fk_noti_pedido
FOREIGN KEY (id_pedido)
REFERENCES Pedido(id_pedido)
ON DELETE SET NULL;

ALTER TABLE Notificaciones
ADD CONSTRAINT fk_noti_rol
FOREIGN KEY (destinatario_rol)
REFERENCES Rol(idRol)
ON DELETE SET NULL;

ALTER TABLE Sesion_Mesa
ADD CONSTRAINT fk_sesion_mesa
FOREIGN KEY (id_mesa)
REFERENCES Mesa(id_Mesa)
ON DELETE CASCADE;

-- =========================
-- INDICES
-- =========================

CREATE INDEX idx_pedido_estado ON Pedido(estado);
CREATE INDEX idx_pedido_fecha ON Pedido(fecha_pedido);
CREATE INDEX idx_notificacion_leida ON Notificaciones(leida);
CREATE INDEX idx_sesion_mesa_activa ON Sesion_Mesa(activa);
CREATE INDEX idx_sesion_mesa_codigo ON Sesion_Mesa(codigo_acceso);

-- =========================
-- INSERTS
-- =========================

INSERT INTO Rol VALUES
(1,'Administrador'),
(2,'Cocinero'),
(3,'Mesero'),
(4,'Cliente');

INSERT INTO Tipo_doc VALUES
(1, 'Cédula de Ciudadanía', 1),
(2, 'Tarjeta de Identidad', 1),
(3, 'Cédula de Extranjería', 1);

INSERT INTO Persona VALUES
(1002655550,1,'Juan','Carlos','Perez','Lopez',3001234567,'admin@gmail.com','1234',1),
(1053804357,1,'Maria','Fernanda','Gomez','Rodriguez',3019876543,'mesero1@gmail.com','1234',1),
(1053872530,1,'Luis',NULL,'Martinez','Diaz',3024567890,'cocina1@gmail.com','1234',1),
(1152693247,1,'Ana','Sofia','Ramirez','Torres',3035678901,'mesero2@gmail.com','1234',1),
(1070919081,1,'Carlos',NULL,'Hernandez','Morales',3046789012,'cocina2@gmail.com','1234',1),
(1031422939,1,'Victor','Manuel','Solano','Niño',3134890742,'cliente@gmail.com','1234',1);

INSERT INTO Persona_has_Rol VALUES
(1,1002655550,1),
(1,1053804357,3),
(1,1053872530,2),
(1,1152693247,3),
(1,1070919081,2),
(1,1031422939,4);

INSERT INTO Mesa VALUES
(1,4,'Primer Piso',0),
(2,2,'Primer Piso',0),
(3,6,'Segundo Piso',0),
(4,4,'Terraza',0);

INSERT INTO Categoria VALUES
(1,'Hamburguesas'),
(2,'Perros Calientes'),
(3,'Salchipapa');

INSERT INTO Menu VALUES
(1,'Hamburguesa Divina',14000,'Carne, queso fundido y vegetales',1),
(2,'Hamburguesa Soleada',16000,'Carne, huevo y queso fundido',1),
(3,'Perro Nube',13000,'Salchicha y queso',2),
(4,'Salchipapa Tentacion',10000,'Papas fritas y salchicha',3);

INSERT INTO Pedido (
    id_mesa,
    mesero_tipo_doc,
    mesero_id_usuario,
    cliente_tipo_doc,
    cliente_id_usuario,
    prioridad,
    estado,
    tiempo_estimado,
    cocinero_asignado,
    observaciones
)
VALUES
(
    1,
    1,
    1053804357,
    1,
    1031422939,
    'normal',
    'en_preparacion',
    20,
    1053872530,
    'Sin cebolla'
),
(
    2,
    1,
    1152693247,
    1,
    1031422939,
    'urgente',
    'pendiente',
    10,
    1070919081,
    'Sin queso'
);

INSERT INTO Detalle_Pedido (
    id_pedido,
    id_menu,
    cantidad,
    valor_venta,
    observaciones
)
VALUES
(1,1,2,14000,'Sin cebolla'),
(1,2,1,16000,'Extra queso'),
(2,3,2,13000,'Sin queso'),
(2,4,1,10000,'Sin salsa');

INSERT INTO Factura (
    id_pedido,
    Total
)
VALUES
(1,44000),
(2,36000);

INSERT INTO Metodo_pago VALUES
(1,'Efectivo'),
(2,'Nequi'),
(3,'Tarjeta');

INSERT INTO Factura_has_Metodo_pago VALUES
(1,1,44000),
(2,2,36000);

INSERT INTO Notificaciones (
    tipo,
    mensaje,
    id_mesa,
    id_pedido,
    destinatario_rol
)
VALUES
(
    'nuevo_pedido',
    'Nuevo pedido en mesa 1',
    1,
    1,
    2
),
(
    'pedido_urgente',
    'Pedido urgente en mesa 2',
    2,
    2,
    2
);

INSERT INTO Sesion_Mesa (
    id_mesa,
    codigo_acceso
)
VALUES
(1,'A1B2C3'),
(2,'X9Y8Z7');

-- =========================
-- ACTUALIZACIONES
-- =========================

UPDATE Pedido
SET estado = 'entregado'
WHERE estado IS NULL;

UPDATE Pedido
SET fecha_pedido = NOW()
WHERE fecha_pedido IS NULL;

UPDATE Pedido
SET prioridad = 'normal'
WHERE prioridad IS NULL;

-- =========================
-- PROCEDIMIENTOS ALMACENADOS
-- =========================

DELIMITER $$

CREATE PROCEDURE crear_factura(
    IN p_id_pedido INT,
    IN p_total FLOAT
)
BEGIN
    INSERT INTO Factura (
        id_pedido,
        Fecha_hora,
        Total
    )
    VALUES (
        p_id_pedido,
        NOW(),
        p_total
    );
END$$

DELIMITER ;

CALL crear_factura(1, 44000);

-- =========================

DELIMITER $$

CREATE PROCEDURE agregar_producto(
    IN p_id_pedido INT,
    IN p_menu INT,
    IN p_cantidad INT,
    IN p_obs TEXT
)
BEGIN
    INSERT INTO Detalle_Pedido (
        id_pedido,
        id_menu,
        cantidad,
        valor_venta,
        observaciones
    )
    VALUES (
        p_id_pedido,
        p_menu,
        p_cantidad,
        0,
        p_obs
    );
END$$

DELIMITER ;

CALL agregar_producto(1, 1, 2, 'Sin cebolla');

-- =========================

DELIMITER $$

CREATE PROCEDURE pagar_factura(
    IN p_factura INT,
    IN p_metodo INT,
    IN p_monto FLOAT
)
BEGIN
    INSERT INTO Factura_has_Metodo_pago (
        pkfk_n_factura,
        pkfk_metodo_pago,
        monto
    )
    VALUES (
        p_factura,
        p_metodo,
        p_monto
    );
END$$

DELIMITER ;

CALL pagar_factura(1, 1, 50000);

-- =========================

DELIMITER $$

CREATE PROCEDURE ver_factura(IN p_id INT)
BEGIN
    SELECT 
        f.id_factura,
        f.Fecha_hora,
        pe.Nom1_usu AS Cliente,
        me.Nom1_usu AS Mesero,
        men.Productos,
        dp.cantidad,
        dp.valor_venta,
        f.Total
    FROM Factura f

    JOIN Pedido ped 
        ON f.id_pedido = ped.id_pedido

    JOIN Persona pe 
        ON ped.cliente_id_usuario = pe.id_usuario

    JOIN Persona me 
        ON ped.mesero_id_usuario = me.id_usuario

    JOIN Detalle_Pedido dp 
        ON ped.id_pedido = dp.id_pedido

    JOIN Menu men 
        ON dp.id_menu = men.id_menu

    WHERE f.id_factura = p_id;
END$$

DELIMITER ;

CALL ver_factura(1);

-- =========================
-- TRIGGERS
-- =========================

DELIMITER $$

CREATE TRIGGER trg_calcular_valor_venta
BEFORE INSERT ON Detalle_Pedido
FOR EACH ROW
BEGIN
    DECLARE precio_producto FLOAT;

    SELECT Precio INTO precio_producto
    FROM Menu
    WHERE id_menu = NEW.id_menu;

    SET NEW.valor_venta = precio_producto * NEW.cantidad;
END$$

DELIMITER ;

-- =========================

DELIMITER $$

CREATE TRIGGER trg_actualizar_total_factura
AFTER INSERT ON Detalle_Pedido
FOR EACH ROW
BEGIN
    UPDATE Factura f
    JOIN Pedido p 
        ON f.id_pedido = p.id_pedido

    SET f.Total = (
        SELECT SUM(valor_venta)
        FROM Detalle_Pedido
        WHERE id_pedido = NEW.id_pedido
    )

    WHERE p.id_pedido = NEW.id_pedido;
END$$

DELIMITER ;

-- =========================

DELIMITER $$

CREATE TRIGGER trg_ocupar_mesa
AFTER INSERT ON Pedido
FOR EACH ROW
BEGIN
    UPDATE Mesa
    SET Estado = 1
    WHERE id_Mesa = NEW.id_mesa;
END$$

DELIMITER ;

-- =========================

DELIMITER $$

CREATE TRIGGER trg_liberar_mesa
AFTER INSERT ON Factura_has_Metodo_pago
FOR EACH ROW
BEGIN
    UPDATE Mesa
    SET Estado = 0
    WHERE id_Mesa = (
        SELECT p.id_mesa
        FROM Factura f
        JOIN Pedido p 
            ON f.id_pedido = p.id_pedido
        WHERE f.id_factura = NEW.pkfk_n_factura
    );
END$$

DELIMITER ;

-- =========================
-- TRIGGERS CONDICIONALES
-- =========================

DELIMITER $$

CREATE TRIGGER trg_validar_cantidad
BEFORE INSERT ON Detalle_Pedido
FOR EACH ROW
BEGIN
    IF NEW.cantidad <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La cantidad debe ser mayor a 0';
    END IF;
END$$

DELIMITER ;

-- =========================

DELIMITER $$

CREATE TRIGGER trg_validar_precio
BEFORE INSERT ON Menu
FOR EACH ROW
BEGIN
    IF NEW.Precio <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El precio debe ser mayor a 0';
    END IF;
END$$

DELIMITER ;

-- =========================
-- PRUEBA TRIGGER
-- =========================

INSERT INTO Detalle_Pedido (
    id_pedido,
    id_menu,
    cantidad,
    valor_venta,
    observaciones
)
VALUES (
    1,
    1,
    0,
    0,
    'Prueba'
);