-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-06-2026 a las 00:26:50
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `restaurante_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `icono` varchar(10) DEFAULT '?️',
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `restaurante_id`, `nombre`, `icono`, `orden`, `activo`) VALUES
(1, 1, 'Tacachos', '🍌', 1, 1),
(2, 1, 'Juanes', '🍃', 2, 1),
(3, 1, 'Caldos y Chilcanos', '🥣', 3, 1),
(4, 1, 'Combos', '🍱', 4, 1),
(5, 1, 'Chaufas', '🍚', 5, 1),
(6, 1, 'Salteados', '🥘', 6, 1),
(7, 1, 'Aeropuertos', '✈️', 7, 1),
(8, 1, 'Pollo', '🍗', 8, 1),
(9, 1, 'Pescados', '🐟', 9, 1),
(10, 1, 'Marinos', '🍤', 10, 1),
(11, 1, 'Pastas', '🍝', 11, 1),
(12, 1, 'Los Recomendados', '⭐', 12, 1),
(13, 1, 'Hamburguesas', '🍔', 13, 1),
(14, 1, 'Alitas', '🍗', 14, 1),
(15, 1, 'Salchipapas', '🍟', 15, 1),
(16, 1, 'Sandwiches', '🥪', 16, 1),
(17, 1, 'Guarniciones', '🥔', 17, 1),
(18, 1, 'Jugos', '🧃', 18, 1),
(19, 1, 'Refrescos', '🍹', 19, 1),
(20, 1, 'Frozen', '🍧', 20, 1),
(21, 1, 'Infusiones', '☕', 21, 1),
(22, 1, 'Gaseosas', '🥤', 22, 1),
(23, 1, 'Sour', '🍸', 23, 1),
(24, 1, 'Mojitos', '🌿', 24, 1),
(25, 1, 'Chilcanos de Bar', '🥃', 25, 1),
(26, 1, 'Cócteles Clásicos', '🍹', 26, 1),
(27, 1, 'De Autor', '✨', 27, 1),
(28, 1, 'Shots', '🥃', 28, 1),
(29, 1, 'Cervezas', '🍺', 29, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobantes`
--

CREATE TABLE `comprobantes` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('boleta','factura','simple') NOT NULL,
  `serie` varchar(4) NOT NULL,
  `correlativo` int(11) NOT NULL,
  `numero_comprobante` varchar(20) NOT NULL,
  `tipo_documento` enum('dni','ruc') DEFAULT NULL,
  `numero_documento` varchar(11) DEFAULT NULL,
  `nombre_cliente` varchar(200) NOT NULL,
  `direccion_cliente` varchar(300) DEFAULT NULL,
  `distrito` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `igv` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `cargo_extra` decimal(10,2) DEFAULT 0.00,
  `anulado` tinyint(1) DEFAULT 0,
  `motivo_anulacion` varchar(200) DEFAULT NULL,
  `items_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items_json`)),
  `pagos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pagos_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturacion_config`
--

CREATE TABLE `facturacion_config` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `ruc` varchar(11) DEFAULT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `direccion_fiscal` varchar(300) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `serie_boleta` varchar(4) DEFAULT 'B001',
  `serie_factura` varchar(4) DEFAULT 'F001',
  `serie_simple` varchar(4) DEFAULT 'T001',
  `correlativo_boleta` int(11) DEFAULT 0,
  `correlativo_factura` int(11) DEFAULT 0,
  `correlativo_simple` int(11) DEFAULT 0,
  `pie_mensaje` varchar(300) DEFAULT '¡Gracias por su visita!',
  `logo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturacion_config`
--

INSERT INTO `facturacion_config` (`id`, `restaurante_id`, `ruc`, `razon_social`, `nombre_comercial`, `direccion_fiscal`, `telefono`, `serie_boleta`, `serie_factura`, `serie_simple`, `correlativo_boleta`, `correlativo_factura`, `correlativo_simple`, `pie_mensaje`, `logo`, `updated_at`) VALUES
(1, 1, '10042128797', 'Sabor Perú', 'Sabor Perú', 'Av. Tito Jaime 514, Tingo María 10131', '+51 999 247 162', 'B001', 'F001', 'T001', 0, 0, 0, '¡Gracias por su visita! Vuelva pronto 😊', '/system-restaurant/assets/logos/logo_rest_1.png', '2026-06-01 21:56:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

CREATE TABLE `gastos` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_acceso`
--

CREATE TABLE `logs_acceso` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

CREATE TABLE `mesas` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `capacidad` int(11) DEFAULT 4,
  `estado` enum('libre','ocupada','reservada') DEFAULT 'libre',
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id`, `restaurante_id`, `numero`, `capacidad`, `estado`, `activo`) VALUES
(1, 1, 1, 4, 'libre', 1),
(2, 1, 2, 4, 'libre', 1),
(3, 1, 3, 4, 'libre', 1),
(4, 1, 4, 2, 'libre', 1),
(5, 1, 5, 2, 'libre', 1),
(6, 1, 6, 6, 'libre', 1),
(7, 1, 7, 6, 'libre', 1),
(8, 1, 8, 4, 'libre', 1),
(9, 1, 9, 4, 'libre', 1),
(10, 1, 10, 8, 'libre', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opciones_grupo`
--

CREATE TABLE `opciones_grupo` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `orden` int(11) DEFAULT 1,
  `requerido` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `opciones_grupo`
--

INSERT INTO `opciones_grupo` (`id`, `producto_id`, `nombre`, `orden`, `requerido`) VALUES
(1, 13, 'Presa:', 1, 1),
(2, 13, '¿Fideos o Arroz?', 2, 1),
(3, 13, 'Guarnición:', 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opciones_valor`
--

CREATE TABLE `opciones_valor` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `valor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `opciones_valor`
--

INSERT INTO `opciones_valor` (`id`, `grupo_id`, `valor`) VALUES
(1, 1, 'Pierna'),
(2, 1, 'Entrepierna'),
(3, 1, 'Pecho'),
(4, 1, 'Ala'),
(5, 1, 'Rabadilla'),
(6, 1, 'Menudencia'),
(7, 2, 'Fideos'),
(8, 2, 'Arroz'),
(9, 3, 'Tacacho'),
(10, 3, 'Yuca'),
(11, 3, 'Plátano');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `metodo` enum('efectivo','yape','transferencia','tarjeta','otro') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `anulado` tinyint(1) DEFAULT 0,
  `anulado_por` int(11) DEFAULT NULL,
  `anulado_en` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `mesa_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('aqui','llevar') NOT NULL,
  `estado` enum('activo','cobrado','cancelado') DEFAULT 'activo',
  `total` decimal(10,2) DEFAULT 0.00,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `cargo_extra` decimal(10,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_items`
--

CREATE TABLE `pedido_items` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `nombre_producto` varchar(120) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `precio_unitario` decimal(8,2) NOT NULL,
  `subtotal` decimal(8,2) NOT NULL,
  `notas` text DEFAULT NULL,
  `estado` enum('pendiente','en_preparacion','listo','entregado') DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_item_opciones`
--

CREATE TABLE `pedido_item_opciones` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `valor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(8,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `tiene_opciones` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `restaurante_id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `imagen`, `tiene_opciones`, `activo`) VALUES
(1, 1, 1, 'Tacacho con Chicharrón', 'Cerdo frito y ensalada criolla', 18.00, NULL, 0, 1),
(2, 1, 1, 'Tacacho con Cecina', 'Cecina ahumada y ensalada criolla', 18.00, NULL, 0, 1),
(3, 1, 1, 'Tacacho con Chorizo', 'Chorizo ahumado y ensalada criolla', 18.00, NULL, 0, 1),
(4, 1, 1, 'Tacacho Combinado', '2 presas a elegir', 22.00, NULL, 0, 1),
(5, 1, 1, 'Tacacho Mixto', 'Cecina + chicharrón + chorizo', 25.00, NULL, 0, 1),
(6, 1, 1, 'Patacon Achorado', 'Patacones acompañados de cecina, chicharrón y chorizo', 25.00, NULL, 0, 1),
(7, 1, 2, 'Juane de Gallina', 'Acompañado de tacacho y ensalada criolla', 19.00, NULL, 0, 1),
(8, 1, 2, 'Juane de Pollo', 'Acompañado de tacacho y ensalada criolla', 16.00, NULL, 0, 1),
(9, 1, 2, 'Juanesito', 'Acompañado de tacacho', 6.00, NULL, 0, 1),
(10, 1, 2, 'Juane a lo Pobre', 'Juane de pollo acompañado de maduro frito y huevo a la inglesa', 20.00, NULL, 0, 1),
(11, 1, 2, 'Juane Cevichero', 'Juane + Ceviche amazónico', 35.00, NULL, 0, 1),
(12, 1, 2, 'El Tunche', 'Chaufa Amazónico + Tallarín saltado de pollo', 35.00, NULL, 0, 1),
(13, 1, 3, 'Caldo de Gallina', 'Con arroz o fideos', 19.00, NULL, 1, 1),
(14, 1, 3, 'Chilcano de Carachama', 'Acompañado de tacacho o yucas al vapor', 26.00, NULL, 0, 1),
(15, 1, 3, 'Chilcano de Paco', 'Acompañado de tacacho o yucas al vapor', 26.00, NULL, 0, 1),
(16, 1, 3, 'Chilcano de Dorado', 'Acompañado de tacacho o yucas al vapor', 26.00, NULL, 0, 1),
(17, 1, 3, 'Sudado de Paco', 'Acompañado de arroz blanco y yucas al vapor', 26.00, NULL, 0, 1),
(18, 1, 3, 'Sudado de Dorado', 'Acompañado de arroz blanco y yucas al vapor', 26.00, NULL, 0, 1),
(19, 1, 4, 'Combo Chaufero', 'Chaufa + Patacones + Chicharrón', 26.00, NULL, 0, 1),
(20, 1, 4, 'Combo Juanero', 'Juane + Patacones + Chicharrón', 26.00, NULL, 0, 1),
(21, 1, 4, 'Combito Amazonico', 'Juanecito + Tacacho + Chicharrón', 16.00, NULL, 0, 1),
(22, 1, 4, 'Chaufa Lomero', 'Chaufa + Lomo saltado', 30.00, NULL, 0, 1),
(23, 1, 4, 'Pechuga Achorada', 'Chaufa + Pechuga al Grill', 28.00, NULL, 0, 1),
(24, 1, 4, 'Trio Amazonico', 'Cecina + Chicharrón + Chorizo + Guarniciones', 45.00, NULL, 0, 1),
(25, 1, 4, 'Piqueo Amazonico', 'Chaufa amazónico + Ceviche de dorado + Chicharrón de paiche + Guarniciones', 60.00, NULL, 0, 1),
(26, 1, 4, 'Ronda Amazonica', 'Juane + Chicharrón + Cecina + Chorizo + Guarniciones', 60.00, NULL, 0, 1),
(27, 1, 5, 'Chaufa Amazonico', 'Chicharrón + Cecina + Chorizo', 24.00, NULL, 0, 1),
(28, 1, 5, 'Chaufa de Cecina', 'Acompañado de maduro frito y patacones', 20.00, NULL, 0, 1),
(29, 1, 5, 'Chaufa de Langostinos', 'Acompañado de maduro frito y patacones', 22.00, NULL, 0, 1),
(30, 1, 5, 'Chaufa de Pollo', 'Acompañado de maduro frito y patacones', 18.00, NULL, 0, 1),
(31, 1, 5, 'Chaufa de Chancho', 'Acompañado de maduro frito y patacones', 20.00, NULL, 0, 1),
(32, 1, 5, 'Chaufa Mar y Selva', 'Cecina + Langostinos', 25.00, NULL, 0, 1),
(33, 1, 6, 'Lomo Saltado', 'Acompañado de arroz blanco y papas fritas', 26.00, NULL, 0, 1),
(34, 1, 6, 'Pollo Saltado', 'Acompañado de arroz blanco y papas fritas', 24.00, NULL, 0, 1),
(35, 1, 6, 'Saltado Amazonico', 'Cecina y Chorizo acompañado de patacones y yucas fritas', 25.00, NULL, 0, 1),
(36, 1, 6, 'Saltado Mar y Selva', 'Cecina, chorizo y langostinos', 28.00, NULL, 0, 1),
(37, 1, 6, 'Tallarin Saltado (Pollo)', 'Acompañado de patacones y yucas fritas', 24.00, NULL, 0, 1),
(38, 1, 6, 'Tallarin Saltado (Res)', 'Acompañado de patacones y yucas fritas', 26.00, NULL, 0, 1),
(39, 1, 6, 'Tallarin Saltado Mar y Selva', 'Cecina, chorizo y langostinos', 28.00, NULL, 0, 1),
(40, 1, 7, 'Aeropuerto Amazónico', 'Cecina + Chicharrón + Chorizo', 24.00, NULL, 0, 1),
(41, 1, 7, 'Aeropuerto de Pollo', 'Acompañado de maduro frito y patacones', 18.00, NULL, 0, 1),
(42, 1, 7, 'Aeropuerto Mar y Selva', 'Cecina + Langostinos', 25.00, NULL, 0, 1),
(43, 1, 7, 'Aeropuerto de Chancho', 'Acompañado de maduro frito y patacones', 20.00, NULL, 0, 1),
(44, 1, 8, 'Pechuga al Grill', 'Arroz + Papas fritas + Ensalada mixta', 25.00, NULL, 0, 1),
(45, 1, 8, 'Milanesa de Pollo', 'Arroz + Papas fritas + Ensalada mixta', 27.00, NULL, 0, 1),
(46, 1, 8, 'Chicharron de Pollo', 'Arroz + Papas fritas + Ensalada mixta', 27.00, NULL, 0, 1),
(47, 1, 8, 'Pechuga al Vapor', 'Arroz, yucas al vapor y ensalada mixta', 25.00, NULL, 0, 1),
(48, 1, 8, 'Brochetas de Pollo', 'Papas fritas y ensalada mixta', 28.00, NULL, 0, 1),
(49, 1, 8, 'Pechuga Hawaiana', 'Papas fritas y ensalada mixta', 30.00, NULL, 0, 1),
(50, 1, 9, 'Dorado al Grill', 'Arroz + Patacones + Yucas fritas + Ensalada', 28.00, NULL, 0, 1),
(51, 1, 9, 'Paiche al Grill', 'Arroz + Patacones + Yucas fritas + Ensalada', 30.00, NULL, 0, 1),
(52, 1, 9, 'Paco Frito', 'Arroz + Patacones + Yucas fritas + Ensalada', 28.00, NULL, 0, 1),
(53, 1, 9, 'Chicharron de Dorado', 'Arroz + Patacones + Yucas fritas + Ensalada', 30.00, NULL, 0, 1),
(54, 1, 9, 'Chicharron de Paiche', 'Arroz + Patacones + Yucas fritas + Ensalada', 30.00, NULL, 0, 1),
(55, 1, 9, 'Ceviche Amazonico', 'Patacones, yucas fritas y chicharrón de pota', 30.00, NULL, 0, 1),
(56, 1, 9, 'Leche de Tigre Amazonico', 'Patacones, yucas fritas y chicharrón de pota', 22.00, NULL, 0, 1),
(57, 1, 10, 'Ceviche Clasico', NULL, 28.00, NULL, 0, 1),
(58, 1, 10, 'Causa Acevichada', NULL, 23.00, NULL, 0, 1),
(59, 1, 10, 'Arroz con Mariscos', NULL, 25.00, NULL, 0, 1),
(60, 1, 10, 'Chaufa de Mariscos', NULL, 20.00, NULL, 0, 1),
(61, 1, 10, 'Duo Marino', '2 platos marinos a elegir', 30.00, NULL, 0, 1),
(62, 1, 10, 'Trio Marino', '3 platos marinos a elegir', 40.00, NULL, 0, 1),
(63, 1, 11, 'Fetuccinni a lo Alfredo', NULL, 20.00, NULL, 0, 1),
(64, 1, 11, 'Fetuccinni a la Huancaína con Pechuga al Grill', NULL, 28.00, NULL, 0, 1),
(65, 1, 11, 'Fetuccinni a la Huancaína con Lomo Saltado', NULL, 30.00, NULL, 0, 1),
(66, 1, 11, 'Fetuccinni a la Huancaína con Brochetas de Pollo', NULL, 30.00, NULL, 0, 1),
(67, 1, 11, 'Fetuccinni a la Huancaína con Dorado al Grill', NULL, 30.00, NULL, 0, 1),
(68, 1, 11, 'Fetuccinni Amazónico', 'Cecina y chorizo ahumado en salsa amazónica', 28.00, NULL, 0, 1),
(69, 1, 11, 'Fetuccinni en Salsa de Mariscos', NULL, 25.00, NULL, 0, 1),
(70, 1, 12, 'Asado de Picuro', 'Arroz blanco, patacones, yucas fritas y ensalada', 40.00, NULL, 0, 1),
(71, 1, 12, 'Tacu Tacu con Lomo Saltado', 'Maduro frito y huevo a la inglesa', 28.00, NULL, 0, 1),
(72, 1, 12, 'Bisteck a lo Pobre', 'Maduro frito, papas fritas, arroz y huevo a la inglesa', 30.00, NULL, 0, 1),
(73, 1, 12, 'Tacu Tacu en Salsa de Mariscos', 'Maduro frito y huevo a la inglesa', 25.00, NULL, 0, 1),
(74, 1, 12, 'Chuleta al Grill', 'Arroz blanco, papas fritas y ensalada mixta', 22.00, NULL, 0, 1),
(75, 1, 12, 'Costillas BBQ', 'Papas fritas y chaufa', 28.00, NULL, 0, 1),
(76, 1, 13, 'Hamburguesa Clasica', '160 gr de carnes seleccionadas, queso cheddar, tocino, lechuga y tomate', 15.00, NULL, 0, 1),
(77, 1, 13, 'Hamburguesa Palteada', 'Res seleccionada, queso cheddar, tocino y generosa porción de palta', 16.00, NULL, 0, 1),
(78, 1, 13, 'Hamburguesa Hawaiana', 'Res seleccionada, queso cheddar, tocino, piña golden, lechuga y tomate', 17.00, NULL, 0, 1),
(79, 1, 13, 'Hamburguesa Pechugona', 'Filete de pollo crispy, tocino, lechuga, tomate y salsa de palta', 15.00, NULL, 0, 1),
(80, 1, 13, 'Hamburguesa Doble', 'Res seleccionada, filete de pollo crispy, doble queso, doble tocino', 25.00, NULL, 0, 1),
(81, 1, 14, 'Alias Personal (8 Alitas)', 'Sabores: BBQ, Acevichada, Maracuyá, Parrillera, Crispy', 20.00, NULL, 0, 1),
(82, 1, 14, 'Alias para Picar (18 Alitas)', 'Sabores: BBQ, Acevichada, Maracuyá, Parrillera, Crispy', 40.00, NULL, 0, 1),
(83, 1, 15, 'Salchipapa Super Clásica', 'Salchicha revueltas con huevo y papas fritas', 13.00, NULL, 0, 1),
(84, 1, 15, 'Salchipapa Parrillera', 'Trozos de chorizo parrillero y pechuga, huevo a la inglesa y papas fritas', 18.00, NULL, 0, 1),
(85, 1, 15, 'Salchipapa Crispy Chicken', 'Trozos de salchicha y pollo crocante, huevo a la inglesa y papas fritas', 17.00, NULL, 0, 1),
(86, 1, 15, 'Salchipapa Amazonica', 'Trozos de cecina y chorizo ahumado, huevo a la inglesa y patacones', 20.00, NULL, 0, 1),
(87, 1, 15, 'Salchipork', 'Trozos de salchicha y chicharrón de cerdo, huevo a la inglesa y papas fritas', 18.00, NULL, 0, 1),
(88, 1, 16, 'Sandwich Chicharron', NULL, 12.00, NULL, 0, 1),
(89, 1, 16, 'Sandwich de Cecina', NULL, 12.00, NULL, 0, 1),
(90, 1, 16, 'Sandwich de Chorizo', NULL, 12.00, NULL, 0, 1),
(91, 1, 16, 'Sandwich de Pechuga', NULL, 12.00, NULL, 0, 1),
(92, 1, 16, 'Sandwich de Queso', NULL, 5.00, NULL, 0, 1),
(93, 1, 16, 'Sandwich de Huevo', NULL, 5.00, NULL, 0, 1),
(94, 1, 16, 'Sandwich de Palta', NULL, 5.00, NULL, 0, 1),
(95, 1, 16, 'Sandwich de Pollo', NULL, 6.00, NULL, 0, 1),
(96, 1, 16, 'Tortilla de Chorizo', NULL, 12.00, NULL, 0, 1),
(97, 1, 16, 'Tortilla de Cecina', NULL, 12.00, NULL, 0, 1),
(98, 1, 16, 'Tortilla de Pollo', NULL, 12.00, NULL, 0, 1),
(99, 1, 17, 'Porcion de Arroz', NULL, 5.00, NULL, 0, 1),
(100, 1, 17, 'Porcion Yucas Fritas', NULL, 8.00, NULL, 0, 1),
(101, 1, 17, 'Porcion de Patacones', NULL, 8.00, NULL, 0, 1),
(102, 1, 17, 'Porcion Maduro Frito', NULL, 8.00, NULL, 0, 1),
(103, 1, 17, 'Porcion Papas Fritas', NULL, 8.00, NULL, 0, 1),
(104, 1, 18, 'Jugo de Papaya', NULL, 8.00, NULL, 0, 1),
(105, 1, 18, 'Jugo de Piña', NULL, 8.00, NULL, 0, 1),
(106, 1, 18, 'Jugo de Mango', NULL, 10.00, NULL, 0, 1),
(107, 1, 18, 'Jugo de Fresa', NULL, 10.00, NULL, 0, 1),
(108, 1, 18, 'Jugo Surtido', NULL, 9.00, NULL, 0, 1),
(109, 1, 18, 'Jugo Especial', NULL, 12.00, NULL, 0, 1),
(110, 1, 19, '1L Cocona', NULL, 17.00, NULL, 0, 1),
(111, 1, 19, '½ Cocona', NULL, 9.00, NULL, 0, 1),
(112, 1, 19, '1L Camu Camu', NULL, 18.00, NULL, 0, 1),
(113, 1, 19, '½ Camu Camu', NULL, 9.00, NULL, 0, 1),
(114, 1, 19, '1L Chicha Morada', NULL, 17.00, NULL, 0, 1),
(115, 1, 19, '½ Chicha Morada', NULL, 9.00, NULL, 0, 1),
(116, 1, 19, '1L Aguajina', NULL, 20.00, NULL, 0, 1),
(117, 1, 19, '½ Aguajina', NULL, 10.00, NULL, 0, 1),
(118, 1, 20, '1L Limonada Frozen', NULL, 17.00, NULL, 0, 1),
(119, 1, 20, '½ Limonada Frozen', NULL, 9.00, NULL, 0, 1),
(120, 1, 20, '1L Maracuya Frozen', NULL, 18.00, NULL, 0, 1),
(121, 1, 20, '½ Maracuya Frozen', NULL, 9.00, NULL, 0, 1),
(122, 1, 20, '1L Mango Frozen', NULL, 18.00, NULL, 0, 1),
(123, 1, 20, '½ Mango Frozen', NULL, 9.00, NULL, 0, 1),
(124, 1, 20, '1L Fresa Frozen', NULL, 18.00, NULL, 0, 1),
(125, 1, 20, '½ Fresa Frozen', NULL, 9.00, NULL, 0, 1),
(126, 1, 20, '1L Maracumango Frozen', NULL, 18.00, NULL, 0, 1),
(127, 1, 20, '½ Maracumango', NULL, 9.00, NULL, 0, 1),
(128, 1, 21, 'Te', NULL, 3.00, NULL, 0, 1),
(129, 1, 21, 'Manzanilla', NULL, 3.00, NULL, 0, 1),
(130, 1, 21, 'Anis', NULL, 3.00, NULL, 0, 1),
(131, 1, 21, 'Cafe Pasado', NULL, 5.00, NULL, 0, 1),
(132, 1, 21, 'Cafe con Leche', NULL, 7.00, NULL, 0, 1),
(133, 1, 22, 'Coca o Inca Kola 300 ml', NULL, 4.00, NULL, 0, 1),
(134, 1, 22, 'Coca o Inca Kola 600 ml', NULL, 5.00, NULL, 0, 1),
(135, 1, 22, 'Coca o Inca Kola 1 L', NULL, 9.00, NULL, 0, 1),
(136, 1, 22, 'Inca Kola Gordita', NULL, 6.00, NULL, 0, 1),
(137, 1, 22, 'Coca o Inca Kola 2.25 L', NULL, 13.00, NULL, 0, 1),
(138, 1, 22, 'Coca o Inca Kola 3 L', NULL, 16.00, NULL, 0, 1),
(139, 1, 22, 'Agua San Luis', NULL, 4.00, NULL, 0, 1),
(140, 1, 22, 'Agua Benedictino', NULL, 4.00, NULL, 0, 1),
(141, 1, 23, 'Sour Clasico', NULL, 16.00, NULL, 0, 1),
(142, 1, 23, 'Sour de Maracuyá', NULL, 17.00, NULL, 0, 1),
(143, 1, 23, 'Sour de Fresa', NULL, 17.00, NULL, 0, 1),
(144, 1, 23, 'Sour de Coca', NULL, 17.00, NULL, 0, 1),
(145, 1, 23, 'Sour de Hierbas Andinas', NULL, 17.00, NULL, 0, 1),
(146, 1, 23, 'Jarra de Sour', NULL, 27.00, NULL, 0, 1),
(147, 1, 24, 'Mojito Clasico', NULL, 16.00, NULL, 0, 1),
(148, 1, 24, 'Mojito de Maracuyá', NULL, 17.00, NULL, 0, 1),
(149, 1, 24, 'Mojito de Fresa', NULL, 17.00, NULL, 0, 1),
(150, 1, 24, 'Mojito de Mango', NULL, 17.00, NULL, 0, 1),
(151, 1, 24, 'Mojito de Hierbas Andinas', NULL, 16.00, NULL, 0, 1),
(152, 1, 24, 'Mojito Blue', NULL, 16.00, NULL, 0, 1),
(153, 1, 24, 'Jarra de Mojito', NULL, 27.00, NULL, 0, 1),
(154, 1, 25, 'Chilcano Clasico', NULL, 15.00, NULL, 0, 1),
(155, 1, 25, 'Chilcano de Maracuyá', NULL, 16.00, NULL, 0, 1),
(156, 1, 25, 'Chilcano de Fresa', NULL, 16.00, NULL, 0, 1),
(157, 1, 25, 'Chilcano de Mango', NULL, 16.00, NULL, 0, 1),
(158, 1, 25, 'Mojito de Coca', NULL, 15.00, NULL, 0, 1),
(159, 1, 25, 'Jarra de Chilcano', NULL, 25.00, NULL, 0, 1),
(160, 1, 26, 'Piña Colada', NULL, 17.00, NULL, 0, 1),
(161, 1, 26, 'Blue Hawaiian', NULL, 17.00, NULL, 0, 1),
(162, 1, 26, 'Pantera Rosa', NULL, 17.00, NULL, 0, 1),
(163, 1, 26, 'Laguna Azul', NULL, 16.00, NULL, 0, 1),
(164, 1, 26, 'Machu Picchu', NULL, 17.00, NULL, 0, 1),
(165, 1, 26, 'Caipirinha', NULL, 15.00, NULL, 0, 1),
(166, 1, 26, 'Margarita Clásico', NULL, 17.00, NULL, 0, 1),
(167, 1, 26, 'Margarita Blue', NULL, 17.00, NULL, 0, 1),
(168, 1, 26, 'Cuba Libre', NULL, 15.00, NULL, 0, 1),
(169, 1, 26, 'Peru Libre', NULL, 15.00, NULL, 0, 1),
(170, 1, 26, 'Gin Tonic', NULL, 18.00, NULL, 0, 1),
(171, 1, 26, 'Tequila Sunrise', NULL, 17.00, NULL, 0, 1),
(172, 1, 26, 'Daiquiri Clásico', NULL, 16.00, NULL, 0, 1),
(173, 1, 26, 'Daiquiri de Fresa y Mango', NULL, 18.00, NULL, 0, 1),
(174, 1, 27, 'La Tingaleza', NULL, 20.00, NULL, 0, 1),
(175, 1, 27, 'Pasion Amazonica', NULL, 20.00, NULL, 0, 1),
(176, 1, 27, 'Elixir del Inca', NULL, 20.00, NULL, 0, 1),
(177, 1, 27, 'Green Day', NULL, 20.00, NULL, 0, 1),
(178, 1, 27, 'Susurro del Tunche', NULL, 20.00, NULL, 0, 1),
(179, 1, 28, 'Pisco Macerado', NULL, 7.00, NULL, 0, 1),
(180, 1, 28, 'Shot de Tequila', NULL, 10.00, NULL, 0, 1),
(181, 1, 28, 'Shot Whiskey', NULL, 10.00, NULL, 0, 1),
(182, 1, 29, 'Cusqueña', NULL, 12.00, NULL, 0, 1),
(183, 1, 29, 'San Juan', NULL, 12.00, NULL, 0, 1),
(184, 1, 29, 'Cristal', NULL, 12.00, NULL, 0, 1),
(185, 1, 29, 'Pilsen', NULL, 12.00, NULL, 0, 1),
(186, 1, 29, 'Artesanal', NULL, 13.00, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `restaurantes`
--

CREATE TABLE `restaurantes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `restaurantes`
--

INSERT INTO `restaurantes` (`id`, `nombre`, `logo`, `activo`, `created_at`) VALUES
(1, 'Sabor Perú', NULL, 1, '2026-04-14 19:24:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fin` timestamp NULL DEFAULT NULL,
  `total_efectivo` decimal(10,2) DEFAULT 0.00,
  `total_yape` decimal(10,2) DEFAULT 0.00,
  `total_transferencia` decimal(10,2) DEFAULT 0.00,
  `total_tarjeta` decimal(10,2) DEFAULT 0.00,
  `total_otros` decimal(10,2) DEFAULT 0.00,
  `total_general` decimal(10,2) DEFAULT 0.00,
  `_turno_abierto_key` int(11) GENERATED ALWAYS AS (case when `fin` is null then `restaurante_id` else NULL end) VIRTUAL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('superadmin','admin','atencion','cocina') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `restaurante_id`, `nombre`, `email`, `password`, `rol`, `activo`, `created_at`) VALUES
(1, NULL, 'Reiner Jiménez', 'reiner', '$2y$10$msSoB.jvRfl7bXh7e60rW.TNjv4Iyy.yG/ZGWfl9IANRx4JVGgSYi', 'superadmin', 1, '2026-04-14 19:24:33'),
(2, 1, 'Admin Restaurante', 'admin', '$2y$10$oRGkEsE3.ybMfe9Mfmy8y.2BMLtcedbFbgyrwDd4zfwTBN0mulMva', 'admin', 1, '2026-04-14 19:24:33'),
(3, 1, 'María Atención', 'atencion', '$2y$10$MWXb4ZAXFnd..MviYuJQB.C8X6miTVYkMRskhU1pA/5Q1GqyFocpe', 'atencion', 1, '2026-04-14 19:24:33'),
(4, 1, 'Benjamín Cocina', 'cocina', '$2y$10$twu9eIEU3x3w1ZVrD6cpO.dbhZ4T8IPGMSqhoQAVAy891uTgPkr1q', 'cocina', 1, '2026-04-14 19:24:33');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_comprobante` (`restaurante_id`,`serie`,`correlativo`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_comp_pedido` (`pedido_id`),
  ADD KEY `idx_comp_documento` (`numero_documento`),
  ADD KEY `idx_comp_fecha` (`created_at`),
  ADD KEY `idx_comp_anulado` (`anulado`);

--
-- Indices de la tabla `facturacion_config`
--
ALTER TABLE `facturacion_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_gastos_rest_fecha` (`restaurante_id`,`fecha`);

--
-- Indices de la tabla `logs_acceso`
--
ALTER TABLE `logs_acceso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mesa_numero` (`restaurante_id`,`numero`);

--
-- Indices de la tabla `opciones_grupo`
--
ALTER TABLE `opciones_grupo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `opciones_valor`
--
ALTER TABLE `opciones_valor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grupo_id` (`grupo_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mesa_id` (`mesa_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `fk_pi_producto` (`producto_id`);

--
-- Indices de la tabla `pedido_item_opciones`
--
ALTER TABLE `pedido_item_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `valor_id` (`valor_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- Indices de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurante_id` (`restaurante_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD UNIQUE KEY `uq_turno_abierto_por_restaurante` (`_turno_abierto_key`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `restaurante_id` (`restaurante_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturacion_config`
--
ALTER TABLE `facturacion_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_acceso`
--
ALTER TABLE `logs_acceso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `opciones_grupo`
--
ALTER TABLE `opciones_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `opciones_valor`
--
ALTER TABLE `opciones_valor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido_item_opciones`
--
ALTER TABLE `pedido_item_opciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;

--
-- AUTO_INCREMENT de la tabla `restaurantes`
--
ALTER TABLE `restaurantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD CONSTRAINT `comprobantes_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comprobantes_ibfk_2` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `comprobantes_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `facturacion_config`
--
ALTER TABLE `facturacion_config`
  ADD CONSTRAINT `facturacion_config_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD CONSTRAINT `gastos_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gastos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `logs_acceso`
--
ALTER TABLE `logs_acceso`
  ADD CONSTRAINT `logs_acceso_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD CONSTRAINT `mesas_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `opciones_grupo`
--
ALTER TABLE `opciones_grupo`
  ADD CONSTRAINT `opciones_grupo_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `opciones_valor`
--
ALTER TABLE `opciones_valor`
  ADD CONSTRAINT `opciones_valor_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `opciones_grupo` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  ADD CONSTRAINT `fk_pi_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedido_items_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedido_item_opciones`
--
ALTER TABLE `pedido_item_opciones`
  ADD CONSTRAINT `pedido_item_opciones_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `pedido_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_item_opciones_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `opciones_grupo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_item_opciones_ibfk_3` FOREIGN KEY (`valor_id`) REFERENCES `opciones_valor` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
