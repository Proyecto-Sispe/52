create database divina_comida;
USE divina_comida;

create table Rol (
    idRol INT not null ,
    Nom_rol VARCHAR(45) not null,
    PRIMARY KEY (idRol)
);

create table Tipo_doc (
    id_doc INT not null ,
    tipo_doc VARCHAR(45) not null,
    estado TINYINT not null,
    PRIMARY KEY(id_doc)
);

create table Persona (
    pkfk_Tipo_doc INT not null,
    id_usuario INT not null,
    Nom1_usu VARCHAR(20) not null,
    Nom2_usu VARCHAR(20),
    Ape1_usu VARCHAR(20) not null,
    Ape2_usu VARCHAR(20),
    Telefono BIGINT not null,
    PRIMARY KEY (id_usuario, pkfk_Tipo_doc)
);

create table Persona_has_Rol (
    pkfk_Tipo_doc INT not null,
    pkfk_id_usuario INT not null,
    pkfk_idRol INT not null,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc,pkfk_idRol)
);

create table Usuario (
    Correo_usu VARCHAR(45) not null,
    Password VARCHAR(10) not null,
    pkfk_Tipo_doc INT not null,
    pkfk_id_usuario INT not null,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc)
);

create table Admin (
    pkfk_Tipo_doc INT not null,
    pkfk_id_usuario INT not null,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc)
);

create table Cocinero (
    pkfk_Tipo_doc INT not null,
    pkfk_id_usuario INT not null,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc)
);

create table Mesero (
    pkfk_Tipo_doc INT not null,
    pkfk_id_usuario INT not null,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc)
);

create table Cliente (
    pkfk_Tipo_doc INT not null,
    pkfk_id_usuario INT not null,
    PRIMARY KEY (pkfk_id_usuario,pkfk_Tipo_doc)
);

create table Mesa (
    id_Mesa INT not null,
    Capacidad MEDIUMINT not null,
    Ubicacion INT not null,
    Estado TINYINT not null,
    PRIMARY KEY(id_Mesa)
);

create table Factura (
    id_factura INT not null,
    Fecha_hora DATETIME not null,
    Total FLOAT not null,
    pkfk_id_Mesa INT not null,
    pkfk_Tipo_doc INT not null,
    pkfk_mesero_id_usuario INT not null,
    pkfk_cliente_tipo_doc INT NOT NULL,
    Cliente_Persona_id_usuario INT not null,
    PRIMARY KEY(id_factura)
);

create table Metodo_pago (
    id_pago INT not null,
    Tipo_pago VARCHAR(45) not null,
    PRIMARY KEY(id_pago)
);
 
create table Factura_has_Metodo_pago (
    pkfk_n_factura INT not null,
    pkfk_metodo_pago INT not null,
    monto FLOAT not null,
    PRIMARY KEY (pkfk_n_factura, pkfk_metodo_pago)
);

create table Categoria (
    id_categoria INT not null,
    nom_categoria TEXT not null,
    PRIMARY KEY(id_categoria)
);

create table Menu (
    id_menu INT not null,
    Productos VARCHAR(25) not null,
    Precio FLOAT not null,
    descripcion VARCHAR() not null,
    pkfk_id_categoria INT not null,
    PRIMARY KEY(id_menu)
);

create table Pedido (
    pkfk_id_factura INT not null,
    pkfk_id_menu INT not null,
    cantidad INT not null,
    observaciones TEXT,
    valor_venta FLOAT not null,
    PRIMARY KEY (pkfk_id_factura,pkfk_id_menu)
);

/*ejempos de alter tablas*/

ALTER TABLE Persona
ADD CONSTRAINT fk_persona_tipo_doc
FOREIGN KEY (pkfk_Tipo_doc)
REFERENCES Tipo_doc (id_doc);

ALTER TABLE Usuario
ADD CONSTRAINT fk_usuario_persona
FOREIGN KEY (pkfk_id_usuario, pkfk_Tipo_doc)
REFERENCES Persona(id_usuario, pkfk_Tipo_doc);

ALTER TABLE Persona_has_Rol
ADD CONSTRAINT fk_phr_persona
FOREIGN KEY (pkfk_id_usuario, pkfk_Tipo_doc)
REFERENCES Persona(id_usuario, pkfk_Tipo_doc);

ALTER TABLE Persona_has_Rol
ADD CONSTRAINT fk_phr_rol
FOREIGN KEY (pkfk_idRol)
REFERENCES Rol(idRol);

ALTER TABLE Admin
ADD CONSTRAINT fk_admin_usuario
FOREIGN KEY (pkfk_id_usuario, pkfk_Tipo_doc)
REFERENCES Usuario(pkfk_id_usuario, pkfk_Tipo_doc);

ALTER TABLE Cocinero
ADD CONSTRAINT fk_cocinero_usuario
FOREIGN KEY (pkfk_id_usuario, pkfk_Tipo_doc)
REFERENCES Usuario(pkfk_id_usuario, pkfk_Tipo_doc);

ALTER TABLE Mesero
ADD CONSTRAINT fk_mesero_persona
FOREIGN KEY (pkfk_id_usuario, pkfk_Tipo_doc)
REFERENCES Persona(id_usuario, pkfk_Tipo_doc);

ALTER TABLE Cliente
ADD CONSTRAINT fk_cliente_persona
FOREIGN KEY (pkfk_id_usuario, pkfk_Tipo_doc)
REFERENCES Persona(id_usuario, pkfk_Tipo_doc);

ALTER TABLE Factura
ADD CONSTRAINT fk_factura_mesa
FOREIGN KEY (pkfk_id_Mesa)
REFERENCES Mesa(id_Mesa);

ALTER TABLE Factura
ADD CONSTRAINT fk_factura_mesero
FOREIGN KEY (pkfk_mesero_id_usuario, pkfk_Tipo_doc)
REFERENCES Mesero(pkfk_id_usuario, pkfk_Tipo_doc);

ALTER TABLE Factura
ADD CONSTRAINT fk_factura_cliente
FOREIGN KEY (Cliente_Persona_id_usuario, pkfk_Tipo_doc)
REFERENCES Cliente(pkfk_id_usuario, pkfk_Tipo_doc);

ALTER TABLE Factura_has_Metodo_pago
ADD CONSTRAINT fk_fmp_factura
FOREIGN KEY (pkfk_n_factura)
REFERENCES Factura(id_factura);

ALTER TABLE Factura_has_Metodo_pago
ADD CONSTRAINT fk_fmp_metodo
FOREIGN KEY (pkfk_metodo_pago)
REFERENCES Metodo_pago(id_pago);

ALTER TABLE Menu
ADD CONSTRAINT fk_menu_categoria
FOREIGN KEY (pkfk_id_categoria)
REFERENCES Categoria(id_categoria);

ALTER TABLE Pedido
ADD CONSTRAINT fk_pedido_factura
FOREIGN KEY (pkfk_id_factura)
REFERENCES Factura(id_factura);

ALTER TABLE Pedido
ADD CONSTRAINT fk_pedido_menu
FOREIGN KEY (pkfk_id_menu)
REFERENCES Menu(id_menu);

/*insert*/

INSERT INTO Rol (idRol,Nom_rol) VALUES
(1, 'Administrador'),
(2, 'Cocinero'),
(3, 'Mesero'),
(4, 'Mesa');

INSERT INTO Tipo_doc (id_doc,tipo_doc,estado) VALUES 
('1', 'Cedula de cuidadania', 1),
('2', 'Tarjeta de indentidad', 1);

INSERT INTO Persona (pkfk_Tipo_doc,id_usuario,Nom1_usu,Nom2_usu,Ape1_usu,Ape2_usu,Telefono) VALUES 
('1', 1002655550, 'Juan', 'Carlos', 'Perez', 'Lopez', 3001234567),
('1', 1053804357, 'Maria', 'Fernanda', 'Gomez', 'Rodriguez', 3019876543),
('1', 1053872530, 'Luis', NULL, 'Martinez', 'Diaz', 3024567890),
('1', 1152693247, 'Ana', 'Sofia', 'Ramirez', 'Torres', 3035678901),
('1', 1070919081, 'Carlos', NULL, 'Hernandez', 'Morales', 3046789012),
('1', 1031422939, 'Victor', 'Manuel', 'Solano', 'Niño', 3134890742);

INSERT INTO Persona_has_Rol (pkfk_Tipo_doc,pkfk_id_usuario,pkfk_idRol) VALUES
('1', 1002655550, 1),
('1', 1053804357, 3),
('1', 1053872530, 2),
('1', 1152693247, 3),
('1', 1070919081, 2),
('1', 1031422939, 4);

INSERT INTO Usuario (Correo_usu,Password,pkfk_Tipo_doc,pkfk_id_usuario) VALUES
('admin@gmail.com', '1243567894', '1', 1002655550),
('cocina1@gmail.com', '1545786485', '1', 1053872530),
('cocina2@gmail.com', '1346798523', '1', 1070919081);


INSERT INTO Admin (pkfk_Tipo_doc,pkfk_id_usuario) VALUES
('1', 1002655550);

INSERT INTO cocinero (pkfk_Tipo_doc,pkfk_id_usuario)  VALUES
('1', 1053872530),
('1', 1070919081);

INSERT INTO mesero (pkfk_Tipo_doc,pkfk_id_usuario)  VALUES
('1', 1053804357),
('1', 1152693247);

INSERT INTO Cliente (pkfk_Tipo_doc,pkfk_id_usuario) VALUES
('1', 1031422939);

INSERT INTO Mesa (id_Mesa,Capacidad,Ubicacion,Estado) VALUES
(1, 4, 1, 0),
(2, 2, 1, 0),
(3, 6, 2, 0),
(4, 4, 2, 0);

INSERT INTO Factura (id_factura,Fecha_hora,Total,pkfk_id_Mesa,pkfk_Tipo_doc,pkfk_mesero_id_usuario,pkfk_cliente_tipo_doc,Cliente_Persona_id_usuario) VALUES
(1, NOW(), 30000, 1, '1', 1053804357, '1', 1031422939),
(2, NOW(), 23000, 2, '1', 1152693247, '1', 1031422939);

INSERT INTO Metodo_pago (id_pago,Tipo_pago) VALUES
(1, 'Efectivo'),
(2, 'Nequi'),
(3, 'Tarjeta');

INSERT INTO Factura_has_Metodo_pago VALUES
(1, 1, 44000),
(2, 2, 36000);

INSERT INTO Categoria (id_categoria, nom_categoria) VALUES
(1, 'Hamburguesas'),
(2, 'Perros Calientes'),
(3, 'Salchipapa');

INSERT INTO Menu (id_menu,Productos,Precio,descripcion,pkfk_id_categoria) VALUES 
(1, 'Hamburguesa Divina ', 14000, 'Carne, queso fundido, papa chip, cebolla caramelizada, tomate, lechuga y salsas', 1),
(2, 'Hamburguesa Soleada', 16000, 'Carne, huevo frito, queso fundido, papa chip, cebolla caramelizada, tomate, lechuga y salsas', 1),
(3, 'Hamburguesa Verano', 16000, 'Carne, piña asada, queso fundido, jamón, tomate, lechuga y salsas', 1),
(4, 'Hamburguesa Ritmo', 16000, 'Carne, plátano maduro, queso fundido, papa chip, cebolla caramelizada, tomate, lechuga y salsas', 1),
(5, 'Hamburguesa Crispy Bacon', 17000, 'Carne, doble tocineta, queso fundido, crispy onion, tomate, lechuga y salsas', 1),
(6, 'Hamburguesa Paraiso Onion', 17000, 'Carne, aros de cebolla apanados, queso fundido, papa chip, cebolla caramelizada, tomate y lechuga', 1),
(7, 'Hamburguesa Pasión', 17000, 'Carne, pepperoni, queso fundido, papa chip, cebolla caramelizada, tomate y lechuga', 1),
(8, 'Perro Nube', 13000, 'Salchicha o chorizo, salsas, queso, papa chip y huevos de codorniz', 2),
(9, 'Perro Crispy Bacon', 15000, 'Salchicha o chorizo, tocineta, queso fundido, crispy onion y huevos de codorniz', 2),
(10, 'Perro Dulzura', 15000, 'Salchicha o chorizo, piña caramelizada, jamón, salsas, queso y papa chip', 2),
(11, 'Perro Ensueño', 17000, 'Salchicha o chorizo, carne, pollo desmechado, salsas, queso, papa chip y huevos de codorniz', 2),
(12, 'Perro Güey', 16000, 'Salchicha o chorizo, pico de gallo, jalapeños, guacamole, tostacos y salsas', 2),
(13, 'Perro La Divina', 19000, 'Salchicha o chorizo, carne, pollo desmechado, tocineta, aros de cebolla, queso y huevos de codorniz', 2),
(14, 'Salchipapa Tentación', 10000, 'Papas fritas con salchicha americana y huevos de codorniz', 3),
(15, 'Salchipapa Edén', 10000, 'Papas fritas con chorizo santarrosano y huevos de codorniz', 3),
(16, 'Salchipapa Sabor Divino', 23000, 'Papas fritas con salchicha, carne, pollo desmechado, queso gratinado y huevos de codorniz', 3),
(17, 'Salchipapa La Divina', 27000, 'Papas fritas con salchicha, tocineta, maíz, pollo, carne desmechada, queso fundido y salsas', 3);

INSERT INTO Pedido (pkfk_id_factura,pkfk_id_menu,cantidad,observaciones,valor_venta) VALUES
(1, 1, 2, 'Sin cebolla', 14000),
(1, 2, 1, 'Con extra queso', 16000),
(2, 8, 2, 'Sin queso', 13000),
(2, 14, 1, 'Sin salsa', 10000);

