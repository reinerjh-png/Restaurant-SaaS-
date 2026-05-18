-- ============================================================
-- INSTALL.SQL — Sistema SaaS Restaurante/Cevichería
-- Autor: Reiner Jiménez | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS restaurante_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE restaurante_db;

-- ─────────────────────────────────────────
-- TABLA: restaurantes
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restaurantes (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    nombre     VARCHAR(100) NOT NULL,
    logo       VARCHAR(255) DEFAULT NULL,
    activo     TINYINT(1)   DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: usuarios
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id  INT          DEFAULT NULL,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(100) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    rol             ENUM('superadmin','admin','atencion','cocina') NOT NULL,
    activo          TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: mesas
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mesas (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id  INT NOT NULL,
    numero          INT NOT NULL,
    capacidad       INT DEFAULT 4,
    estado          ENUM('libre','ocupada','reservada') DEFAULT 'libre',
    activo          TINYINT(1) DEFAULT 1,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_mesa_numero (restaurante_id, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: categorias
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categorias (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id  INT NOT NULL,
    nombre          VARCHAR(80)  NOT NULL,
    icono           VARCHAR(10)  DEFAULT '🍽️',
    orden           INT          DEFAULT 0,
    activo          TINYINT(1)   DEFAULT 1,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: productos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS productos (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id  INT          NOT NULL,
    categoria_id    INT          NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    descripcion     TEXT         DEFAULT NULL,
    precio          DECIMAL(8,2) NOT NULL,
    imagen          VARCHAR(255) DEFAULT NULL,
    tiene_opciones  TINYINT(1)   DEFAULT 0,
    activo          TINYINT(1)   DEFAULT 1,
    FOREIGN KEY (categoria_id)   REFERENCES categorias(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: opciones_grupo
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS opciones_grupo (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT          NOT NULL,
    nombre      VARCHAR(100) NOT NULL,
    orden       INT          DEFAULT 1,
    requerido   TINYINT(1)   DEFAULT 1,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: opciones_valor
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS opciones_valor (
    id       INT PRIMARY KEY AUTO_INCREMENT,
    grupo_id INT          NOT NULL,
    valor    VARCHAR(100) NOT NULL,
    FOREIGN KEY (grupo_id) REFERENCES opciones_grupo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: pedidos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedidos (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id  INT  NOT NULL,
    mesa_id         INT  DEFAULT NULL,
    usuario_id      INT  NOT NULL,
    tipo            ENUM('aqui','llevar') NOT NULL,
    estado          ENUM('activo','cobrado','cancelado') DEFAULT 'activo',
    total           DECIMAL(10,2) DEFAULT 0.00,
    notas           TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mesa_id)         REFERENCES mesas(id)    ON DELETE SET NULL,
    FOREIGN KEY (usuario_id)      REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (restaurante_id)  REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: pedido_items
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedido_items (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id       INT          NOT NULL,
    producto_id     INT          NOT NULL,
    cantidad        INT          DEFAULT 1,
    precio_unitario DECIMAL(8,2) NOT NULL,
    subtotal        DECIMAL(8,2) NOT NULL,
    notas           TEXT         DEFAULT NULL,
    estado          ENUM('pendiente','en_preparacion','listo','entregado') DEFAULT 'pendiente',
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)   ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: pedido_item_opciones
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedido_item_opciones (
    id        INT PRIMARY KEY AUTO_INCREMENT,
    item_id   INT NOT NULL,
    grupo_id  INT NOT NULL,
    valor_id  INT NOT NULL,
    FOREIGN KEY (item_id)  REFERENCES pedido_items(id)   ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES opciones_grupo(id) ON DELETE CASCADE,
    FOREIGN KEY (valor_id) REFERENCES opciones_valor(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: pagos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pagos (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    pedido_id   INT          NOT NULL,
    metodo      ENUM('efectivo','yape','transferencia','tarjeta','otro') NOT NULL,
    monto       DECIMAL(10,2) NOT NULL,
    referencia  VARCHAR(100)  DEFAULT NULL,
    usuario_id  INT           NOT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id)  REFERENCES pedidos(id)   ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: turnos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS turnos (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id      INT           NOT NULL,
    usuario_id          INT           NOT NULL,
    inicio              TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    fin                 TIMESTAMP     NULL,
    total_efectivo      DECIMAL(10,2) DEFAULT 0.00,
    total_yape          DECIMAL(10,2) DEFAULT 0.00,
    total_transferencia DECIMAL(10,2) DEFAULT 0.00,
    total_tarjeta       DECIMAL(10,2) DEFAULT 0.00,
    total_otros         DECIMAL(10,2) DEFAULT 0.00,
    total_general       DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: logs_acceso
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS logs_acceso (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id  INT          DEFAULT NULL,
    accion      VARCHAR(100) NOT NULL,
    ip          VARCHAR(45)  DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════
-- DATOS INICIALES
-- ═══════════════════════════════════════════

-- Restaurante demo
INSERT INTO restaurantes (nombre, activo) VALUES ('Sabor Perú', 1);

-- Usuarios (con contraseñas únicas en bcrypt)
INSERT INTO usuarios (restaurante_id, nombre, email, password, rol, activo) VALUES
(NULL, 'Reiner Jiménez', 'reiner',
 '$2y$10$n/9kaLtyk/D3j9X5DE.1KOt/5TEKs7atxGXN8GikTbZmLBVFUrX7u', 'superadmin', 1),
(1, 'Admin Restaurante', 'admin',
 '$2y$10$oRGkEsE3.ybMfe9Mfmy8y.2BMLtcedbFbgyrwDd4zfwTBN0mulMva', 'admin', 1),
(1, 'María Atención', 'atencion',
 '$2y$10$MWXb4ZAXFnd..MviYuJQB.C8X6miTVYkMRskhU1pA/5Q1GqyFocpe', 'atencion', 1),
(1, 'Juan Cocina', 'cocina',
 '$2y$10$twu9eIEU3x3w1ZVrD6cpO.dbhZ4T8IPGMSqhoQAVAy891uTgPkr1q', 'cocina', 1);

-- Mesas (10 mesas)
INSERT INTO mesas (restaurante_id, numero, capacidad, estado) VALUES
(1,1,4,'libre'),(1,2,4,'libre'),(1,3,4,'libre'),(1,4,2,'libre'),
(1,5,2,'libre'),(1,6,6,'libre'),(1,7,6,'libre'),(1,8,4,'libre'),
(1,9,4,'libre'),(1,10,8,'libre');


-- Categorías del menú
INSERT INTO categorias (restaurante_id, nombre, icono, orden, activo) VALUES
(1,'Tacachos','🍌',1,1),
(1,'Juanes','🍃',2,1),
(1,'Caldos y Chilcanos','🥣',3,1),
(1,'Combos','🍱',4,1),
(1,'Chaufas','🍚',5,1),
(1,'Salteados','🥘',6,1),
(1,'Aeropuertos','✈️',7,1),
(1,'Pollo','🍗',8,1),
(1,'Pescados','🐟',9,1),
(1,'Marinos','🍤',10,1),
(1,'Pastas','🍝',11,1),
(1,'Los Recomendados','⭐',12,1),
(1,'Hamburguesas','🍔',13,1),
(1,'Alitas','🍗',14,1),
(1,'Salchipapas','🍟',15,1),
(1,'Sandwiches','🥪',16,1),
(1,'Guarniciones','🥔',17,1),
(1,'Jugos','🧃',18,1),
(1,'Refrescos','🍹',19,1),
(1,'Frozen','🍧',20,1),
(1,'Infusiones','☕',21,1),
(1,'Gaseosas','🥤',22,1),
(1,'Sour','🍸',23,1),
(1,'Mojitos','🌿',24,1),
(1,'Chilcanos de Bar','🥃',25,1),
(1,'Cócteles Clásicos','🍹',26,1),
(1,'De Autor','✨',27,1),
(1,'Shots','🥃',28,1),
(1,'Cervezas','🍺',29,1);

-- Productos
INSERT INTO productos (restaurante_id, categoria_id, nombre, descripcion, precio, tiene_opciones, activo) VALUES
(1,1,'Tacacho con Chicharrón','Cerdo frito y ensalada criolla',18.00,0,1),
(1,1,'Tacacho con Cecina','Cecina ahumada y ensalada criolla',18.00,0,1),
(1,1,'Tacacho con Chorizo','Chorizo ahumado y ensalada criolla',18.00,0,1),
(1,1,'Tacacho Combinado','2 presas a elegir',22.00,0,1),
(1,1,'Tacacho Mixto','Cecina + chicharrón + chorizo',25.00,0,1),
(1,1,'Patacón Achorado','Patacones acompañados de cecina, chicharrón y chorizo',25.00,0,1),
(1,2,'Juane de Gallina','Acompañado de tacacho y ensalada criolla',19.00,0,1),
(1,2,'Juane de Pollo','Acompañado de tacacho y ensalada criolla',16.00,0,1),
(1,2,'Juanesito','Acompañado de tacacho',6.00,0,1),
(1,2,'Juane a lo Pobre','Juane de pollo acompañado de maduro frito y huevo a la inglesa',20.00,0,1),
(1,2,'Juane Cevichero','Juane + Ceviche amazónico',35.00,0,1),
(1,2,'El Tunche','Chaufa Amazónico + Tallarín saltado de pollo',35.00,0,1),
(1,3,'Caldo de Gallina','Con arroz o fideos',19.00,0,1),
(1,3,'Chilcano de Carachama','Acompañado de tacacho o yucas al vapor',26.00,0,1),
(1,3,'Chilcano de Paco','Acompañado de tacacho o yucas al vapor',26.00,0,1),
(1,3,'Chilcano de Dorado','Acompañado de tacacho o yucas al vapor',26.00,0,1),
(1,3,'Sudado de Paco','Acompañado de arroz blanco y yucas al vapor',26.00,0,1),
(1,3,'Sudado de Dorado','Acompañado de arroz blanco y yucas al vapor',26.00,0,1),
(1,4,'Combo Chaufero','Chaufa + Patacones + Chicharrón',26.00,0,1),
(1,4,'Combo Juanero','Juane + Patacones + Chicharrón',26.00,0,1),
(1,4,'Combito Amazónico','Juanecito + Tacacho + Chicharrón',16.00,0,1),
(1,4,'Chaufa Lomero','Chaufa + Lomo saltado',30.00,0,1),
(1,4,'Pechuga Achorada','Chaufa + Pechuga al Grill',28.00,0,1),
(1,4,'Trío Amazónico','Cecina + Chicharrón + Chorizo + Guarniciones',45.00,0,1),
(1,4,'Piqueo Amazónico','Chaufa amazónico + Ceviche de dorado + Chicharrón de paiche + Guarniciones',60.00,0,1),
(1,4,'Ronda Amazónica','Juane + Chicharrón + Cecina + Chorizo + Guarniciones',60.00,0,1),
(1,5,'Chaufa Amazónico','Chicharrón + Cecina + Chorizo',24.00,0,1),
(1,5,'Chaufa de Cecina','Acompañado de maduro frito y patacones',20.00,0,1),
(1,5,'Chaufa de Langostinos','Acompañado de maduro frito y patacones',22.00,0,1),
(1,5,'Chaufa de Pollo','Acompañado de maduro frito y patacones',18.00,0,1),
(1,5,'Chaufa de Chancho','Acompañado de maduro frito y patacones',20.00,0,1),
(1,5,'Chaufa Mar y Selva','Cecina + Langostinos',25.00,0,1),
(1,6,'Lomo Saltado','Acompañado de arroz blanco y papas fritas',26.00,0,1),
(1,6,'Pollo Saltado','Acompañado de arroz blanco y papas fritas',24.00,0,1),
(1,6,'Saltado Amazónico','Cecina y Chorizo acompañado de patacones y yucas fritas',25.00,0,1),
(1,6,'Saltado Mar y Selva','Cecina, chorizo y langostinos',28.00,0,1),
(1,6,'Tallarín Saltado (Pollo)','Acompañado de patacones y yucas fritas',24.00,0,1),
(1,6,'Tallarín Saltado (Res)','Acompañado de patacones y yucas fritas',26.00,0,1),
(1,6,'Tallarín Saltado Mar y Selva','Cecina, chorizo y langostinos',28.00,0,1),
(1,7,'Aeropuerto Amazónico','Cecina + Chicharrón + Chorizo',24.00,0,1),
(1,7,'Aeropuerto de Pollo','Acompañado de maduro frito y patacones',18.00,0,1),
(1,7,'Aeropuerto Mar y Selva','Cecina + Langostinos',25.00,0,1),
(1,7,'Aeropuerto de Chancho','Acompañado de maduro frito y patacones',20.00,0,1),
(1,8,'Pechuga al Grill','Arroz + Papas fritas + Ensalada mixta',25.00,0,1),
(1,8,'Milanesa de Pollo','Arroz + Papas fritas + Ensalada mixta',27.00,0,1),
(1,8,'Chicharrón de Pollo','Arroz + Papas fritas + Ensalada mixta',27.00,0,1),
(1,8,'Pechuga al Vapor','Arroz, yucas al vapor y ensalada mixta',25.00,0,1),
(1,8,'Brochetas de Pollo','Papas fritas y ensalada mixta',28.00,0,1),
(1,8,'Pechuga Hawaiana','Papas fritas y ensalada mixta',30.00,0,1),
(1,9,'Dorado al Grill','Arroz + Patacones + Yucas fritas + Ensalada',28.00,0,1);
INSERT INTO productos (restaurante_id, categoria_id, nombre, descripcion, precio, tiene_opciones, activo) VALUES
(1,9,'Paiche al Grill','Arroz + Patacones + Yucas fritas + Ensalada',30.00,0,1),
(1,9,'Paco Frito','Arroz + Patacones + Yucas fritas + Ensalada',28.00,0,1),
(1,9,'Chicharrón de Dorado','Arroz + Patacones + Yucas fritas + Ensalada',30.00,0,1),
(1,9,'Chicharrón de Paiche','Arroz + Patacones + Yucas fritas + Ensalada',30.00,0,1),
(1,9,'Ceviche Amazónico','Patacones, yucas fritas y chicharrón de pota',30.00,0,1),
(1,9,'Leche de Tigre Amazónico','Patacones, yucas fritas y chicharrón de pota',22.00,0,1),
(1,10,'Ceviche Clásico',NULL,28.00,0,1),
(1,10,'Causa Acevichada',NULL,23.00,0,1),
(1,10,'Arroz con Mariscos',NULL,25.00,0,1),
(1,10,'Chaufa de Mariscos',NULL,20.00,0,1),
(1,10,'Dúo Marino','2 platos marinos a elegir',30.00,0,1),
(1,10,'Trío Marino','3 platos marinos a elegir',40.00,0,1),
(1,11,'Fetuccinni a lo Alfredo',NULL,20.00,0,1),
(1,11,'Fetuccinni a la Huancaína con Pechuga al Grill',NULL,28.00,0,1),
(1,11,'Fetuccinni a la Huancaína con Lomo Saltado',NULL,30.00,0,1),
(1,11,'Fetuccinni a la Huancaína con Brochetas de Pollo',NULL,30.00,0,1),
(1,11,'Fetuccinni a la Huancaína con Dorado al Grill',NULL,30.00,0,1),
(1,11,'Fetuccinni Amazónico','Cecina y chorizo ahumado en salsa amazónica',28.00,0,1),
(1,11,'Fetuccinni en Salsa de Mariscos',NULL,25.00,0,1),
(1,12,'Asado de Picuro','Arroz blanco, patacones, yucas fritas y ensalada',40.00,0,1),
(1,12,'Tacu Tacu con Lomo Saltado','Maduro frito y huevo a la inglesa',28.00,0,1),
(1,12,'Bisteck a lo Pobre','Maduro frito, papas fritas, arroz y huevo a la inglesa',30.00,0,1),
(1,12,'Tacu Tacu en Salsa de Mariscos','Maduro frito y huevo a la inglesa',25.00,0,1),
(1,12,'Chuleta al Grill','Arroz blanco, papas fritas y ensalada mixta',22.00,0,1),
(1,12,'Costillas BBQ','Papas fritas y chaufa',28.00,0,1),
(1,13,'La Clásica','160 gr de carnes seleccionadas, queso cheddar, tocino, lechuga y tomate',15.00,0,1),
(1,13,'La Palteada','Res seleccionada, queso cheddar, tocino y generosa porción de palta',16.00,0,1),
(1,13,'La Hawaiana','Res seleccionada, queso cheddar, tocino, piña golden, lechuga y tomate',17.00,0,1),
(1,13,'La Pechugona','Filete de pollo crispy, tocino, lechuga, tomate y salsa de palta',15.00,0,1),
(1,13,'La Doble','Res seleccionada, filete de pollo crispy, doble queso, doble tocino',25.00,0,1),
(1,14,'Personal (8 Alitas)','Sabores: BBQ, Acevichada, Maracuyá, Parrillera, Crispy',20.00,0,1),
(1,14,'Para Picar (18 Alitas)','Sabores: BBQ, Acevichada, Maracuyá, Parrillera, Crispy',40.00,0,1),
(1,15,'Super Clásica','Salchicha revueltas con huevo y papas fritas',13.00,0,1),
(1,15,'La Parrillera','Trozos de chorizo parrillero y pechuga, huevo a la inglesa y papas fritas',18.00,0,1),
(1,15,'Crispy Chicken','Trozos de salchicha y pollo crocante, huevo a la inglesa y papas fritas',17.00,0,1),
(1,15,'Amazónica','Trozos de cecina y chorizo ahumado, huevo a la inglesa y patacones',20.00,0,1),
(1,15,'Salchipork','Trozos de salchicha y chicharrón de cerdo, huevo a la inglesa y papas fritas',18.00,0,1),
(1,16,'Chicharrón',NULL,12.00,0,1),
(1,16,'Cecina',NULL,12.00,0,1),
(1,16,'Chorizo',NULL,12.00,0,1),
(1,16,'Pechuga',NULL,12.00,0,1),
(1,16,'Queso',NULL,5.00,0,1),
(1,16,'Huevo',NULL,5.00,0,1),
(1,16,'Palta',NULL,5.00,0,1),
(1,16,'Pollo',NULL,6.00,0,1),
(1,16,'Tortilla de Chorizo',NULL,12.00,0,1),
(1,16,'Tortilla de Cecina',NULL,12.00,0,1),
(1,16,'Tortilla de Pollo',NULL,12.00,0,1),
(1,17,'Porción de Arroz',NULL,5.00,0,1),
(1,17,'Porción de Yucas Fritas',NULL,8.00,0,1);
INSERT INTO productos (restaurante_id, categoria_id, nombre, descripcion, precio, tiene_opciones, activo) VALUES
(1,17,'Porción de Patacones',NULL,8.00,0,1),
(1,17,'Porción de Maduro Frito',NULL,8.00,0,1),
(1,17,'Porción de Papas Fritas',NULL,8.00,0,1),
(1,18,'Papaya',NULL,8.00,0,1),
(1,18,'Piña',NULL,8.00,0,1),
(1,18,'Mango',NULL,10.00,0,1),
(1,18,'Fresa',NULL,10.00,0,1),
(1,18,'Surtido',NULL,9.00,0,1),
(1,18,'Especial',NULL,12.00,0,1),
(1,19,'1L Cocona',NULL,17.00,0,1),
(1,19,'½ Cocona',NULL,9.00,0,1),
(1,19,'1L Camu Camu',NULL,18.00,0,1),
(1,19,'½ Camu Camu',NULL,9.00,0,1),
(1,19,'1L Chicha Morada',NULL,17.00,0,1),
(1,19,'½ Chicha Morada',NULL,9.00,0,1),
(1,19,'1L Aguajina',NULL,20.00,0,1),
(1,19,'½ Aguajina',NULL,10.00,0,1),
(1,20,'1L Limonada',NULL,17.00,0,1),
(1,20,'½ Limonada',NULL,9.00,0,1),
(1,20,'1L Maracuyá',NULL,18.00,0,1),
(1,20,'½ Maracuyá',NULL,9.00,0,1),
(1,20,'1L Mango',NULL,18.00,0,1),
(1,20,'½ Mango',NULL,9.00,0,1),
(1,20,'1L Fresa',NULL,18.00,0,1),
(1,20,'½ Fresa',NULL,9.00,0,1),
(1,20,'1L Maracumango',NULL,18.00,0,1),
(1,20,'½ Maracumango',NULL,9.00,0,1),
(1,21,'Té',NULL,3.00,0,1),
(1,21,'Manzanilla',NULL,3.00,0,1),
(1,21,'Anís',NULL,3.00,0,1),
(1,21,'Café Pasado',NULL,5.00,0,1),
(1,21,'Café con Leche',NULL,7.00,0,1),
(1,22,'Coca o Inca Kola 300 ml',NULL,4.00,0,1),
(1,22,'Coca o Inca Kola 600 ml',NULL,5.00,0,1),
(1,22,'Coca o Inca Kola 1 L',NULL,9.00,0,1),
(1,22,'Inca Kola Gordita',NULL,6.00,0,1),
(1,22,'Coca o Inca Kola 2.25 L',NULL,13.00,0,1),
(1,22,'Coca o Inca Kola 3 L',NULL,16.00,0,1),
(1,22,'Agua San Luis',NULL,4.00,0,1),
(1,22,'Agua Benedictino',NULL,4.00,0,1),
(1,23,'Sour Clásico',NULL,16.00,0,1),
(1,23,'Sour de Maracuyá',NULL,17.00,0,1),
(1,23,'Sour de Fresa',NULL,17.00,0,1),
(1,23,'Sour de Coca',NULL,17.00,0,1),
(1,23,'Sour de Hierbas Andinas',NULL,17.00,0,1),
(1,23,'Jarra de Sour',NULL,27.00,0,1),
(1,24,'Mojito Clásico',NULL,16.00,0,1),
(1,24,'Mojito de Maracuyá',NULL,17.00,0,1),
(1,24,'Mojito de Fresa',NULL,17.00,0,1),
(1,24,'Mojito de Mango',NULL,17.00,0,1);
INSERT INTO productos (restaurante_id, categoria_id, nombre, descripcion, precio, tiene_opciones, activo) VALUES
(1,24,'Mojito de Hierbas Andinas',NULL,16.00,0,1),
(1,24,'Mojito Blue',NULL,16.00,0,1),
(1,24,'Jarra de Mojito',NULL,27.00,0,1),
(1,25,'Chilcano Clásico',NULL,15.00,0,1),
(1,25,'Chilcano de Maracuyá',NULL,16.00,0,1),
(1,25,'Chilcano de Fresa',NULL,16.00,0,1),
(1,25,'Chilcano de Mango',NULL,16.00,0,1),
(1,25,'Mojito de Coca',NULL,15.00,0,1),
(1,25,'Jarra de Chilcano',NULL,25.00,0,1),
(1,26,'Piña Colada',NULL,17.00,0,1),
(1,26,'Blue Hawaiian',NULL,17.00,0,1),
(1,26,'Pantera Rosa',NULL,17.00,0,1),
(1,26,'Laguna Azul',NULL,16.00,0,1),
(1,26,'Machu Picchu',NULL,17.00,0,1),
(1,26,'Caipirinha',NULL,15.00,0,1),
(1,26,'Margarita Clásico',NULL,17.00,0,1),
(1,26,'Margarita Blue',NULL,17.00,0,1),
(1,26,'Cuba Libre',NULL,15.00,0,1),
(1,26,'Peru Libre',NULL,15.00,0,1),
(1,26,'Gin Tonic',NULL,18.00,0,1),
(1,26,'Tequila Sunrise',NULL,17.00,0,1),
(1,26,'Daiquiri Clásico',NULL,16.00,0,1),
(1,26,'Daiquiri de Fresa y Mango',NULL,18.00,0,1),
(1,27,'La Tingaleza',NULL,20.00,0,1),
(1,27,'Pasión Amazónica',NULL,20.00,0,1),
(1,27,'Elixir del Inca',NULL,20.00,0,1),
(1,27,'Green Day',NULL,20.00,0,1),
(1,27,'Susurro del Tunche',NULL,20.00,0,1),
(1,28,'Pisco Macerado',NULL,7.00,0,1),
(1,28,'Tequila',NULL,10.00,0,1),
(1,28,'Whiskey',NULL,10.00,0,1),
(1,29,'Cusqueña',NULL,12.00,0,1),
(1,29,'San Juan',NULL,12.00,0,1),
(1,29,'Cristal',NULL,12.00,0,1),
(1,29,'Pilsen',NULL,12.00,0,1),
(1,29,'Artesanal',NULL,13.00,0,1);

-- Turno inicial del usuario atención
INSERT INTO turnos (restaurante_id, usuario_id) VALUES (1, 3);

-- ============================================================
-- 001_MODULO_FACTURACION.SQL — Módulo de Comprobantes
-- Sistema SaaS Restaurante | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI sobre restaurante_db
-- ============================================================

USE restaurante_db;

-- ─────────────────────────────────────────
-- TABLA: facturacion_config
-- Configuración de comprobantes por restaurante
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS facturacion_config (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id      INT          NOT NULL UNIQUE,
    ruc                 VARCHAR(11)  DEFAULT NULL,
    razon_social        VARCHAR(200) DEFAULT NULL,
    direccion_fiscal    VARCHAR(300) DEFAULT NULL,
    serie_boleta        VARCHAR(4)   DEFAULT 'B001',
    serie_factura       VARCHAR(4)   DEFAULT 'F001',
    correlativo_boleta  INT          DEFAULT 0,
    correlativo_factura INT          DEFAULT 0,
    pie_mensaje         VARCHAR(300) DEFAULT '¡Gracias por su visita!',
    logo                VARCHAR(255) DEFAULT NULL,
    updated_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLA: comprobantes
-- Registro de boletas y facturas emitidas
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comprobantes (
    id                  INT PRIMARY KEY AUTO_INCREMENT,
    restaurante_id      INT          NOT NULL,
    pedido_id           INT          NOT NULL,
    usuario_id          INT          NOT NULL,
    tipo                ENUM('boleta','factura') NOT NULL,
    serie               VARCHAR(4)   NOT NULL,
    correlativo         INT          NOT NULL,
    numero_comprobante  VARCHAR(20)  NOT NULL,       -- ej: B001-00001
    -- Datos del cliente
    tipo_documento      ENUM('dni','ruc') NOT NULL,
    numero_documento    VARCHAR(11)  NOT NULL,
    nombre_cliente      VARCHAR(200) NOT NULL,
    direccion_cliente   VARCHAR(300) DEFAULT NULL,   -- solo facturas
    distrito            VARCHAR(100) DEFAULT NULL,
    provincia           VARCHAR(100) DEFAULT NULL,
    departamento        VARCHAR(100) DEFAULT NULL,
    -- Montos
    subtotal            DECIMAL(10,2) NOT NULL,      -- antes de IGV
    igv                 DECIMAL(10,2) NOT NULL,      -- 18%
    total               DECIMAL(10,2) NOT NULL,
    -- Estado
    anulado             TINYINT(1)   DEFAULT 0,
    motivo_anulacion    VARCHAR(200) DEFAULT NULL,
    -- Snapshot JSON de items (para reimpresión exacta)
    items_json          JSON          DEFAULT NULL,
    -- Snapshot JSON de pagos
    pagos_json          JSON          DEFAULT NULL,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id)      REFERENCES pedidos(id)      ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)     ON DELETE RESTRICT,
    UNIQUE KEY uk_comprobante (restaurante_id, serie, correlativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- Índices adicionales
-- ─────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_comp_pedido     ON comprobantes (pedido_id);
CREATE INDEX IF NOT EXISTS idx_comp_documento  ON comprobantes (numero_documento);
CREATE INDEX IF NOT EXISTS idx_comp_fecha      ON comprobantes (created_at);
CREATE INDEX IF NOT EXISTS idx_comp_anulado    ON comprobantes (anulado);

-- ─────────────────────────────────────────
-- Registro inicial de config para restaurante demo (id=1)
-- ─────────────────────────────────────────
INSERT IGNORE INTO facturacion_config
    (restaurante_id, ruc, razon_social, direccion_fiscal, serie_boleta, serie_factura, pie_mensaje)
VALUES
    (1, NULL, 'Sabor Perú', NULL, 'B001', 'F001', '¡Gracias por su visita! Vuelva pronto 😊');

-- ============================================================
-- 002_BRANDING.SQL — Campos de identidad de marca por restaurante
-- Sistema SaaS Restaurante | R.DEV
-- Ejecutar en phpMyAdmin o MySQL CLI sobre restaurante_db
-- ============================================================

USE restaurante_db;

-- Agregar nombre_comercial (nombre visible en dashboard y comprobantes)
ALTER TABLE facturacion_config
    ADD COLUMN IF NOT EXISTS nombre_comercial VARCHAR(150) DEFAULT NULL
    AFTER razon_social;

-- Agregar teléfono del restaurante (aparece en comprobantes)
ALTER TABLE facturacion_config
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) DEFAULT NULL
    AFTER direccion_fiscal;

-- =========================================================================
-- OPTIMIZACIÓN DE ÍNDICES Sabor Perú / SaaS Restaurante
-- 
-- Ejecuta este script una sola vez en tu panel de phpMyAdmin en InfinityFree
-- para acelerar drásticamente las consultas del Dashboard, Cocina y Mesas.
-- =========================================================================

-- Tabla mesas: Mejorar búsqueda de mesas activas y su estado (para atención)
ALTER TABLE mesas 
ADD INDEX idx_mesas_rest_act (restaurante_id, activo, estado);

-- Tabla pedidos: Mejorar la búsqueda de pedidos activos o cobrados por fecha (Dashboard/Cocina/Historial)
ALTER TABLE pedidos 
ADD INDEX idx_pedidos_rest_estado_fecha (restaurante_id, estado, created_at);

-- Tabla pedido_items: Mejorar cruces cuando se buscan items no entregados de un pedido
ALTER TABLE pedido_items 
ADD INDEX idx_pedido_items_estado (pedido_id, estado);
