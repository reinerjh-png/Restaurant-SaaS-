-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-05-2026 a las 15:12:54
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

--
-- Volcado de datos para la tabla `comprobantes`
--

INSERT INTO `comprobantes` (`id`, `restaurante_id`, `pedido_id`, `usuario_id`, `tipo`, `serie`, `correlativo`, `numero_comprobante`, `tipo_documento`, `numero_documento`, `nombre_cliente`, `direccion_cliente`, `distrito`, `provincia`, `departamento`, `subtotal`, `igv`, `total`, `descuento`, `cargo_extra`, `anulado`, `motivo_anulacion`, `items_json`, `pagos_json`, `created_at`) VALUES
(1, 1, 10, 2, 'boleta', 'B001', 1, 'B001-00001', 'dni', '75847785', 'Reiner Jiménez', NULL, NULL, NULL, NULL, 30.51, 5.49, 36.00, 0.00, 0.00, 0, NULL, '[{\"cantidad\":1,\"precio_unitario\":\"18.00\",\"subtotal\":\"18.00\",\"producto_nombre\":\"Tacacho con Chicharrón\",\"opciones_texto\":null},{\"cantidad\":1,\"precio_unitario\":\"18.00\",\"subtotal\":\"18.00\",\"producto_nombre\":\"Tacacho con Chorizo\",\"opciones_texto\":null}]', '[{\"metodo\":\"yape\",\"monto\":36,\"referencia\":\"\"}]', '2026-05-13 20:05:57'),
(2, 1, 13, 2, 'simple', 'B001', 2, 'B001-00002', NULL, NULL, 'Cliente', NULL, NULL, NULL, NULL, 40.25, 7.25, 47.50, 0.00, 1.50, 0, NULL, '[{\"cantidad\":1,\"precio_unitario\":\"26.00\",\"subtotal\":\"26.00\",\"producto_nombre\":\"Chilcano de Carachama\",\"opciones_texto\":null},{\"cantidad\":1,\"precio_unitario\":\"20.00\",\"subtotal\":\"20.00\",\"producto_nombre\":\"Fetuccinni a lo Alfredo\",\"opciones_texto\":null}]', '[{\"metodo\":\"yape\",\"monto\":17.5,\"referencia\":\"\"},{\"metodo\":\"efectivo\",\"monto\":30}]', '2026-05-29 03:50:48'),
(3, 1, 14, 2, 'simple', 'B001', 3, 'B001-00003', NULL, NULL, 'Cliente', NULL, NULL, NULL, NULL, 50.85, 9.15, 60.00, 0.00, 2.00, 0, NULL, '[{\"cantidad\":1,\"precio_unitario\":\"22.00\",\"subtotal\":\"22.00\",\"producto_nombre\":\"Tacacho Combinado\",\"opciones_texto\":null},{\"cantidad\":1,\"precio_unitario\":\"18.00\",\"subtotal\":\"18.00\",\"producto_nombre\":\"Tacacho con Cecina\",\"opciones_texto\":null},{\"cantidad\":1,\"precio_unitario\":\"18.00\",\"subtotal\":\"18.00\",\"producto_nombre\":\"Tacacho con Chicharrón\",\"opciones_texto\":null}]', '[{\"metodo\":\"efectivo\",\"monto\":58},{\"metodo\":\"yape\",\"monto\":2,\"referencia\":\"\"}]', '2026-05-30 03:47:30');

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
  `correlativo_boleta` int(11) DEFAULT 0,
  `correlativo_factura` int(11) DEFAULT 0,
  `pie_mensaje` varchar(300) DEFAULT '¡Gracias por su visita!',
  `logo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturacion_config`
--

INSERT INTO `facturacion_config` (`id`, `restaurante_id`, `ruc`, `razon_social`, `nombre_comercial`, `direccion_fiscal`, `telefono`, `serie_boleta`, `serie_factura`, `correlativo_boleta`, `correlativo_factura`, `pie_mensaje`, `logo`, `updated_at`) VALUES
(1, 1, NULL, 'Sabor Perú', 'Sabor Perú', '', NULL, 'B001', 'F001', 3, 0, '¡Gracias por su visita! Vuelva pronto 😊', '/system-restaurant/assets/logos/logo_rest_1.png', '2026-05-30 03:47:30');

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

--
-- Volcado de datos para la tabla `logs_acceso`
--

INSERT INTO `logs_acceso` (`id`, `usuario_id`, `accion`, `ip`, `created_at`) VALUES
(1, 3, 'logout', '::1', '2026-04-14 19:24:56'),
(2, 1, 'login', '::1', '2026-04-14 19:27:00'),
(3, 1, 'logout', '::1', '2026-04-14 19:28:54'),
(4, 2, 'login', '::1', '2026-04-14 19:29:15'),
(5, 2, 'logout', '::1', '2026-04-14 19:29:48'),
(6, 3, 'login', '::1', '2026-04-14 19:30:01'),
(7, 3, 'logout', '::1', '2026-04-14 19:30:39'),
(8, 4, 'login', '::1', '2026-04-14 19:30:55'),
(9, 4, 'logout', '::1', '2026-04-14 19:44:29'),
(10, 3, 'login', '::1', '2026-04-14 19:44:45'),
(11, 3, 'logout', '::1', '2026-04-14 19:45:35'),
(12, 4, 'login', '::1', '2026-04-14 19:45:49'),
(13, 4, 'logout', '::1', '2026-04-14 19:45:58'),
(14, 3, 'login', '::1', '2026-04-14 19:46:12'),
(15, 1, 'login', '::1', '2026-04-14 21:55:50'),
(16, 1, 'logout', '::1', '2026-04-14 22:09:19'),
(17, 2, 'login', '::1', '2026-04-14 22:09:30'),
(18, 2, 'logout', '::1', '2026-04-14 22:13:51'),
(19, 1, 'login', '::1', '2026-04-14 22:13:59'),
(20, 1, 'logout', '::1', '2026-04-14 22:27:55'),
(21, 3, 'login', '::1', '2026-04-14 22:28:12'),
(22, 3, 'logout', '::1', '2026-04-14 23:33:44'),
(23, 4, 'login', '::1', '2026-04-14 23:33:59'),
(24, 4, 'logout', '::1', '2026-04-14 23:46:53'),
(25, 3, 'login', '::1', '2026-04-14 23:47:08'),
(26, 3, 'logout', '::1', '2026-04-15 00:10:09'),
(27, 2, 'login', '::1', '2026-04-15 00:10:26'),
(28, 2, 'logout', '::1', '2026-04-15 00:18:09'),
(29, 3, 'login', '::1', '2026-04-15 02:35:37'),
(30, 4, 'login', '::1', '2026-04-15 02:38:35'),
(31, 3, 'logout', '::1', '2026-04-15 03:25:03'),
(32, 3, 'login', '::1', '2026-04-15 03:25:32'),
(33, 3, 'login', '::1', '2026-04-15 13:06:31'),
(34, 3, 'logout', '::1', '2026-04-15 13:08:31'),
(35, 2, 'login', '::1', '2026-04-15 13:08:49'),
(36, 2, 'logout', '::1', '2026-04-15 18:33:57'),
(37, 3, 'login', '::1', '2026-04-15 18:34:27'),
(38, 3, 'logout', '::1', '2026-04-15 19:38:04'),
(39, 3, 'login', '::1', '2026-04-15 19:38:16'),
(40, 3, 'logout', '::1', '2026-04-15 19:38:19'),
(41, 2, 'login', '::1', '2026-04-15 19:38:30'),
(42, 2, 'logout', '::1', '2026-04-15 23:37:35'),
(43, 3, 'login', '::1', '2026-04-15 23:37:48'),
(44, 1, 'login', '::1', '2026-04-18 23:22:47'),
(45, 1, 'logout', '::1', '2026-04-19 01:00:24'),
(46, 1, 'login', '::1', '2026-04-19 01:01:36'),
(47, 1, 'logout', '::1', '2026-04-19 01:15:26'),
(48, 1, 'login', '::1', '2026-04-19 01:15:46'),
(49, 1, 'logout', '::1', '2026-04-19 01:15:48'),
(50, 2, 'login', '::1', '2026-04-19 01:16:00'),
(51, 2, 'login', '::1', '2026-04-19 15:17:33'),
(52, 2, 'logout', '::1', '2026-04-19 15:19:09'),
(53, 2, 'login', '::1', '2026-04-19 15:43:34'),
(54, 2, 'logout', '::1', '2026-04-19 15:43:39'),
(55, 2, 'login', '::1', '2026-05-13 01:22:42'),
(56, 2, 'login', '::1', '2026-05-14 11:47:32'),
(57, 2, 'logout', '::1', '2026-05-14 12:10:22'),
(58, 1, 'login', '::1', '2026-05-14 12:10:43'),
(59, 2, 'login', '::1', '2026-05-14 12:20:57'),
(60, 2, 'logout', '::1', '2026-05-14 12:21:05'),
(61, 1, 'login', '::1', '2026-05-14 12:22:08'),
(62, 1, 'logout', '::1', '2026-05-14 13:08:21'),
(63, 2, 'login', '::1', '2026-05-14 13:08:35'),
(64, 2, 'logout', '::1', '2026-05-14 13:09:03'),
(65, 1, 'login', '::1', '2026-05-14 13:09:22'),
(66, 1, 'logout', '::1', '2026-05-14 13:16:54'),
(67, 2, 'login', '::1', '2026-05-14 13:17:09'),
(68, 2, 'logout', '::1', '2026-05-14 13:17:21'),
(69, 1, 'login', '::1', '2026-05-14 13:17:30'),
(70, 2, 'login', '::1', '2026-05-14 18:42:42'),
(71, 4, 'login', '::1', '2026-05-14 18:45:34'),
(72, 2, 'logout', '::1', '2026-05-14 19:51:06'),
(73, 4, 'login', '::1', '2026-05-14 19:51:30'),
(74, 4, 'logout', '::1', '2026-05-14 20:02:02'),
(75, 2, 'login', '::1', '2026-05-14 20:02:17'),
(76, 1, 'login', '::1', '2026-05-24 18:17:33'),
(77, 1, 'logout', '::1', '2026-05-24 18:18:12'),
(78, 3, 'login', '::1', '2026-05-24 18:18:25'),
(79, 3, 'logout', '::1', '2026-05-24 18:35:16'),
(80, 2, 'login', '::1', '2026-05-24 18:35:41'),
(81, 2, 'logout', '::1', '2026-05-24 18:49:46'),
(82, 3, 'login', '::1', '2026-05-24 18:50:04'),
(83, 3, 'logout', '::1', '2026-05-24 18:52:57'),
(84, 4, 'login', '::1', '2026-05-24 18:57:47'),
(85, 4, 'logout', '::1', '2026-05-24 19:41:01'),
(86, 2, 'login', '::1', '2026-05-28 12:30:12'),
(87, 2, 'logout', '::1', '2026-05-30 05:05:48'),
(88, 4, 'login', '::1', '2026-05-30 05:06:02'),
(89, 4, 'logout', '::1', '2026-05-30 11:41:41'),
(90, 3, 'login', '::1', '2026-05-30 11:45:22'),
(91, 3, 'logout', '::1', '2026-05-30 11:47:23'),
(92, 4, 'login', '::1', '2026-05-30 11:48:01'),
(93, 4, 'logout', '::1', '2026-05-30 11:50:27'),
(94, 2, 'login', '::1', '2026-05-30 11:50:49');

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opciones_valor`
--

CREATE TABLE `opciones_valor` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `valor` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `pedido_id`, `metodo`, `monto`, `referencia`, `usuario_id`, `created_at`) VALUES
(1, 1, 'yape', 19.00, NULL, 3, '2026-04-14 19:46:33'),
(2, 3, 'efectivo', 36.00, NULL, 3, '2026-04-14 23:47:20'),
(3, 5, 'yape', 30.00, NULL, 3, '2026-04-15 03:19:44'),
(4, 5, 'efectivo', 6.00, NULL, 3, '2026-04-15 03:19:44'),
(5, 6, 'tarjeta', 62.00, NULL, 3, '2026-04-15 03:34:09'),
(6, 7, 'yape', 90.00, NULL, 3, '2026-04-15 04:08:07'),
(7, 10, 'yape', 36.00, NULL, 2, '2026-05-13 20:05:57'),
(8, 13, 'yape', 17.50, NULL, 2, '2026-05-29 03:50:48'),
(9, 13, 'efectivo', 30.00, NULL, 2, '2026-05-29 03:50:48'),
(10, 14, 'efectivo', 58.00, NULL, 2, '2026-05-30 03:47:30'),
(11, 14, 'yape', 2.00, NULL, 2, '2026-05-30 03:47:30');

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

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `restaurante_id`, `mesa_id`, `usuario_id`, `tipo`, `estado`, `total`, `descuento`, `cargo_extra`, `notas`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 'aqui', 'cobrado', 19.00, 0.00, 0.00, NULL, '2026-04-14 19:30:10', '2026-04-14 19:46:33'),
(2, 1, 1, 3, 'aqui', 'cancelado', 0.00, 0.00, 0.00, NULL, '2026-04-14 22:28:26', '2026-04-14 23:33:21'),
(3, 1, 2, 3, 'llevar', 'cobrado', 36.00, 0.00, 0.00, NULL, '2026-04-14 23:27:02', '2026-04-14 23:47:20'),
(4, 1, 6, 3, 'aqui', 'cancelado', 0.00, 0.00, 0.00, NULL, '2026-04-14 23:47:27', '2026-04-15 02:39:37'),
(5, 1, 3, 3, 'aqui', 'cobrado', 36.00, 0.00, 0.00, NULL, '2026-04-15 02:40:04', '2026-04-15 03:19:44'),
(6, 1, 10, 3, 'llevar', 'cobrado', 62.00, 0.00, 0.00, NULL, '2026-04-15 03:20:03', '2026-04-15 03:34:09'),
(7, 1, 2, 3, 'aqui', 'cobrado', 90.00, 0.00, 0.00, NULL, '2026-04-15 03:34:17', '2026-04-15 04:08:07'),
(8, 1, 2, 3, 'aqui', 'cancelado', 23.00, 0.00, 0.00, NULL, '2026-04-15 04:08:37', '2026-04-19 01:16:22'),
(9, 1, 5, 3, 'llevar', 'cancelado', 42.00, 0.00, 0.00, NULL, '2026-04-15 13:07:14', '2026-04-19 01:16:25'),
(10, 1, 2, 2, 'aqui', 'cobrado', 36.00, 0.00, 0.00, NULL, '2026-05-13 20:01:09', '2026-05-13 20:05:57'),
(11, 1, 2, 2, 'llevar', 'cancelado', 44.00, 0.00, 0.00, NULL, '2026-05-13 20:53:35', '2026-05-14 18:43:04'),
(12, 1, 1, 2, 'llevar', 'cancelado', 43.00, 0.00, 0.00, NULL, '2026-05-14 18:43:10', '2026-05-14 18:46:03'),
(13, 1, 10, 2, 'llevar', 'cobrado', 47.50, 0.00, 1.50, '1 huevo sancochado adicional', '2026-05-14 18:46:08', '2026-05-29 03:50:48'),
(14, 1, 1, 2, 'aqui', 'cobrado', 60.00, 0.00, 2.00, NULL, '2026-05-30 03:46:28', '2026-05-30 03:47:30'),
(15, 1, 1, 2, 'aqui', 'cancelado', 58.00, 0.00, 0.00, NULL, '2026-05-30 05:05:25', '2026-05-30 11:50:57'),
(16, 1, 2, 3, 'llevar', 'cancelado', 195.00, 0.00, 0.00, NULL, '2026-05-30 11:45:34', '2026-05-30 11:51:00'),
(17, 1, 3, 3, 'aqui', 'cancelado', 131.00, 0.00, 0.00, NULL, '2026-05-30 11:45:59', '2026-05-30 11:51:03'),
(18, 1, 4, 3, 'llevar', 'cancelado', 149.00, 0.00, 0.00, NULL, '2026-05-30 11:46:35', '2026-05-30 11:51:06'),
(19, 1, 5, 3, 'aqui', 'cancelado', 263.00, 0.00, 0.00, NULL, '2026-05-30 11:46:50', '2026-05-30 11:51:10'),
(20, 1, 6, 3, 'aqui', 'cancelado', 129.00, 0.00, 0.00, NULL, '2026-05-30 11:47:02', '2026-05-30 11:51:14'),
(21, 1, 1, 2, 'aqui', 'cancelado', 0.00, 0.00, 0.00, NULL, '2026-05-30 11:51:48', '2026-05-30 13:07:23');

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

--
-- Volcado de datos para la tabla `pedido_items`
--

INSERT INTO `pedido_items` (`id`, `pedido_id`, `producto_id`, `nombre_producto`, `cantidad`, `precio_unitario`, `subtotal`, `notas`, `estado`, `created_at`) VALUES
(1, 1, 13, 'Caldo de Gallina', 1, 19.00, 19.00, NULL, 'pendiente', '2026-04-14 19:30:30'),
(2, 3, 1, 'Tacacho con Chicharrón', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-14 23:27:06'),
(3, 3, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-14 23:27:06'),
(4, 5, 13, 'Caldo de Gallina', 1, 19.00, 19.00, NULL, 'pendiente', '2026-04-15 03:09:17'),
(5, 5, 118, '1L Limonada', 1, 17.00, 17.00, NULL, 'pendiente', '2026-04-15 03:09:17'),
(6, 6, 13, 'Caldo de Gallina', 1, 19.00, 19.00, 'pierna, arroz y tacacho', 'pendiente', '2026-04-15 03:22:09'),
(7, 6, 30, 'Chaufa de Pollo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-15 03:22:09'),
(8, 6, 113, '½ Camu Camu', 1, 9.00, 9.00, 'sin helar', 'pendiente', '2026-04-15 03:22:09'),
(9, 6, 141, 'Sour Clásico', 1, 16.00, 16.00, NULL, 'pendiente', '2026-04-15 03:24:31'),
(10, 7, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-15 03:42:58'),
(11, 7, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-15 03:42:58'),
(12, 7, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-15 03:42:58'),
(13, 7, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-15 03:44:48'),
(14, 7, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, 'yuca en vez de tacacho', 'pendiente', '2026-04-15 03:45:44'),
(15, 8, 1, 'Tacacho con Chicharrón', 1, 18.00, 18.00, NULL, 'pendiente', '2026-04-15 13:07:04'),
(16, 8, 131, 'Café Pasado', 1, 5.00, 5.00, NULL, 'pendiente', '2026-04-15 13:07:04'),
(17, 9, 35, 'Saltado Amazónico', 1, 25.00, 25.00, NULL, 'pendiente', '2026-04-15 13:08:26'),
(19, 9, 114, '1L Chicha Morada', 1, 17.00, 17.00, 'sin helar', 'pendiente', '2026-04-15 13:08:26'),
(20, 10, 1, 'Tacacho con Chicharrón', 1, 18.00, 18.00, NULL, 'pendiente', '2026-05-13 20:01:14'),
(21, 10, 3, 'Tacacho con Chorizo', 1, 18.00, 18.00, NULL, 'pendiente', '2026-05-13 20:01:14'),
(22, 11, 10, 'Juane a lo Pobre', 1, 20.00, 20.00, NULL, 'pendiente', '2026-05-13 20:53:46'),
(23, 11, 37, 'Tallarín Saltado (Pollo)', 1, 24.00, 24.00, NULL, 'pendiente', '2026-05-13 20:53:46'),
(24, 12, 13, 'Caldo de Gallina', 1, 19.00, 19.00, 'piernas', 'pendiente', '2026-05-14 18:44:20'),
(25, 12, 34, 'Pollo Saltado', 1, 24.00, 24.00, 'papas', 'pendiente', '2026-05-14 18:44:20'),
(26, 13, 14, 'Chilcano de Carachama', 1, 26.00, 26.00, 'yuca', 'en_preparacion', '2026-05-14 18:47:27'),
(27, 13, 63, 'Fetuccinni a lo Alfredo', 1, 20.00, 20.00, 'patacones', 'en_preparacion', '2026-05-14 18:47:28'),
(28, 14, 4, NULL, 1, 22.00, 22.00, NULL, 'pendiente', '2026-05-30 03:46:32'),
(29, 14, 2, NULL, 1, 18.00, 18.00, NULL, 'pendiente', '2026-05-30 03:46:32'),
(30, 14, 1, NULL, 1, 18.00, 18.00, NULL, 'pendiente', '2026-05-30 03:46:32'),
(31, 15, 4, NULL, 1, 22.00, 22.00, NULL, 'entregado', '2026-05-30 05:05:29'),
(32, 15, 2, NULL, 1, 18.00, 18.00, NULL, 'entregado', '2026-05-30 05:05:29'),
(33, 15, 1, NULL, 1, 18.00, 18.00, NULL, 'entregado', '2026-05-30 05:05:29'),
(34, 16, 13, NULL, 1, 19.00, 19.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(35, 16, 14, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(36, 16, 16, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(37, 16, 15, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(38, 16, 18, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(39, 16, 17, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(40, 16, 22, NULL, 1, 30.00, 30.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(41, 16, 21, NULL, 1, 16.00, 16.00, NULL, 'pendiente', '2026-05-30 11:45:53'),
(42, 17, 12, NULL, 1, 35.00, 35.00, NULL, 'pendiente', '2026-05-30 11:46:18'),
(43, 17, 10, NULL, 1, 20.00, 20.00, NULL, 'pendiente', '2026-05-30 11:46:18'),
(44, 17, 11, NULL, 1, 35.00, 35.00, NULL, 'pendiente', '2026-05-30 11:46:18'),
(45, 17, 7, NULL, 1, 19.00, 19.00, NULL, 'pendiente', '2026-05-30 11:46:18'),
(46, 17, 8, NULL, 1, 16.00, 16.00, NULL, 'pendiente', '2026-05-30 11:46:18'),
(47, 17, 9, NULL, 1, 6.00, 6.00, NULL, 'pendiente', '2026-05-30 11:46:18'),
(48, 18, 13, NULL, 1, 19.00, 19.00, NULL, 'pendiente', '2026-05-30 11:46:44'),
(49, 18, 14, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:44'),
(50, 18, 16, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:44'),
(51, 18, 15, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:44'),
(52, 18, 17, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:44'),
(53, 18, 18, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:44'),
(54, 19, 22, NULL, 1, 30.00, 30.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(55, 19, 21, NULL, 1, 16.00, 16.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(56, 19, 19, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(57, 19, 20, NULL, 1, 26.00, 26.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(58, 19, 24, NULL, 1, 45.00, 45.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(59, 19, 26, NULL, 1, 60.00, 60.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(60, 19, 25, NULL, 1, 60.00, 60.00, NULL, 'pendiente', '2026-05-30 11:46:58'),
(61, 20, 27, NULL, 1, 24.00, 24.00, NULL, 'pendiente', '2026-05-30 11:47:11'),
(62, 20, 28, NULL, 1, 20.00, 20.00, NULL, 'pendiente', '2026-05-30 11:47:11'),
(63, 20, 31, NULL, 1, 20.00, 20.00, NULL, 'pendiente', '2026-05-30 11:47:11'),
(64, 20, 29, NULL, 1, 22.00, 22.00, NULL, 'pendiente', '2026-05-30 11:47:11'),
(65, 20, 32, NULL, 1, 25.00, 25.00, NULL, 'pendiente', '2026-05-30 11:47:11'),
(66, 20, 30, NULL, 1, 18.00, 18.00, NULL, 'pendiente', '2026-05-30 11:47:11');

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
(6, 1, 1, 'Patacón Achorado', 'Patacones acompañados de cecina, chicharrón y chorizo', 25.00, NULL, 0, 1),
(7, 1, 2, 'Juane de Gallina', 'Acompañado de tacacho y ensalada criolla', 19.00, NULL, 0, 1),
(8, 1, 2, 'Juane de Pollo', 'Acompañado de tacacho y ensalada criolla', 16.00, NULL, 0, 1),
(9, 1, 2, 'Juanesito', 'Acompañado de tacacho', 6.00, NULL, 0, 1),
(10, 1, 2, 'Juane a lo Pobre', 'Juane de pollo acompañado de maduro frito y huevo a la inglesa', 20.00, NULL, 0, 1),
(11, 1, 2, 'Juane Cevichero', 'Juane + Ceviche amazónico', 35.00, NULL, 0, 1),
(12, 1, 2, 'El Tunche', 'Chaufa Amazónico + Tallarín saltado de pollo', 35.00, NULL, 0, 1),
(13, 1, 3, 'Caldo de Gallina', 'Con arroz o fideos', 19.00, NULL, 0, 1),
(14, 1, 3, 'Chilcano de Carachama', 'Acompañado de tacacho o yucas al vapor', 26.00, NULL, 0, 1),
(15, 1, 3, 'Chilcano de Paco', 'Acompañado de tacacho o yucas al vapor', 26.00, NULL, 0, 1),
(16, 1, 3, 'Chilcano de Dorado', 'Acompañado de tacacho o yucas al vapor', 26.00, NULL, 0, 1),
(17, 1, 3, 'Sudado de Paco', 'Acompañado de arroz blanco y yucas al vapor', 26.00, NULL, 0, 1),
(18, 1, 3, 'Sudado de Dorado', 'Acompañado de arroz blanco y yucas al vapor', 26.00, NULL, 0, 1),
(19, 1, 4, 'Combo Chaufero', 'Chaufa + Patacones + Chicharrón', 26.00, NULL, 0, 1),
(20, 1, 4, 'Combo Juanero', 'Juane + Patacones + Chicharrón', 26.00, NULL, 0, 1),
(21, 1, 4, 'Combito Amazónico', 'Juanecito + Tacacho + Chicharrón', 16.00, NULL, 0, 1),
(22, 1, 4, 'Chaufa Lomero', 'Chaufa + Lomo saltado', 30.00, NULL, 0, 1),
(23, 1, 4, 'Pechuga Achorada', 'Chaufa + Pechuga al Grill', 28.00, NULL, 0, 1),
(24, 1, 4, 'Trío Amazónico', 'Cecina + Chicharrón + Chorizo + Guarniciones', 45.00, NULL, 0, 1),
(25, 1, 4, 'Piqueo Amazónico', 'Chaufa amazónico + Ceviche de dorado + Chicharrón de paiche + Guarniciones', 60.00, NULL, 0, 1),
(26, 1, 4, 'Ronda Amazónica', 'Juane + Chicharrón + Cecina + Chorizo + Guarniciones', 60.00, NULL, 0, 1),
(27, 1, 5, 'Chaufa Amazónico', 'Chicharrón + Cecina + Chorizo', 24.00, NULL, 0, 1),
(28, 1, 5, 'Chaufa de Cecina', 'Acompañado de maduro frito y patacones', 20.00, NULL, 0, 1),
(29, 1, 5, 'Chaufa de Langostinos', 'Acompañado de maduro frito y patacones', 22.00, NULL, 0, 1),
(30, 1, 5, 'Chaufa de Pollo', 'Acompañado de maduro frito y patacones', 18.00, NULL, 0, 1),
(31, 1, 5, 'Chaufa de Chancho', 'Acompañado de maduro frito y patacones', 20.00, NULL, 0, 1),
(32, 1, 5, 'Chaufa Mar y Selva', 'Cecina + Langostinos', 25.00, NULL, 0, 1),
(33, 1, 6, 'Lomo Saltado', 'Acompañado de arroz blanco y papas fritas', 26.00, NULL, 0, 1),
(34, 1, 6, 'Pollo Saltado', 'Acompañado de arroz blanco y papas fritas', 24.00, NULL, 0, 1),
(35, 1, 6, 'Saltado Amazónico', 'Cecina y Chorizo acompañado de patacones y yucas fritas', 25.00, NULL, 0, 1),
(36, 1, 6, 'Saltado Mar y Selva', 'Cecina, chorizo y langostinos', 28.00, NULL, 0, 1),
(37, 1, 6, 'Tallarín Saltado (Pollo)', 'Acompañado de patacones y yucas fritas', 24.00, NULL, 0, 1),
(38, 1, 6, 'Tallarín Saltado (Res)', 'Acompañado de patacones y yucas fritas', 26.00, NULL, 0, 1),
(39, 1, 6, 'Tallarín Saltado Mar y Selva', 'Cecina, chorizo y langostinos', 28.00, NULL, 0, 1),
(40, 1, 7, 'Aeropuerto Amazónico', 'Cecina + Chicharrón + Chorizo', 24.00, NULL, 0, 1),
(41, 1, 7, 'Aeropuerto de Pollo', 'Acompañado de maduro frito y patacones', 18.00, NULL, 0, 1),
(42, 1, 7, 'Aeropuerto Mar y Selva', 'Cecina + Langostinos', 25.00, NULL, 0, 1),
(43, 1, 7, 'Aeropuerto de Chancho', 'Acompañado de maduro frito y patacones', 20.00, NULL, 0, 1),
(44, 1, 8, 'Pechuga al Grill', 'Arroz + Papas fritas + Ensalada mixta', 25.00, NULL, 0, 1),
(45, 1, 8, 'Milanesa de Pollo', 'Arroz + Papas fritas + Ensalada mixta', 27.00, NULL, 0, 1),
(46, 1, 8, 'Chicharrón de Pollo', 'Arroz + Papas fritas + Ensalada mixta', 27.00, NULL, 0, 1),
(47, 1, 8, 'Pechuga al Vapor', 'Arroz, yucas al vapor y ensalada mixta', 25.00, NULL, 0, 1),
(48, 1, 8, 'Brochetas de Pollo', 'Papas fritas y ensalada mixta', 28.00, NULL, 0, 1),
(49, 1, 8, 'Pechuga Hawaiana', 'Papas fritas y ensalada mixta', 30.00, NULL, 0, 1),
(50, 1, 9, 'Dorado al Grill', 'Arroz + Patacones + Yucas fritas + Ensalada', 28.00, NULL, 0, 1),
(51, 1, 9, 'Paiche al Grill', 'Arroz + Patacones + Yucas fritas + Ensalada', 30.00, NULL, 0, 1),
(52, 1, 9, 'Paco Frito', 'Arroz + Patacones + Yucas fritas + Ensalada', 28.00, NULL, 0, 1),
(53, 1, 9, 'Chicharrón de Dorado', 'Arroz + Patacones + Yucas fritas + Ensalada', 30.00, NULL, 0, 1),
(54, 1, 9, 'Chicharrón de Paiche', 'Arroz + Patacones + Yucas fritas + Ensalada', 30.00, NULL, 0, 1),
(55, 1, 9, 'Ceviche Amazónico', 'Patacones, yucas fritas y chicharrón de pota', 30.00, NULL, 0, 1),
(56, 1, 9, 'Leche de Tigre Amazónico', 'Patacones, yucas fritas y chicharrón de pota', 22.00, NULL, 0, 1),
(57, 1, 10, 'Ceviche Clásico', NULL, 28.00, NULL, 0, 1),
(58, 1, 10, 'Causa Acevichada', NULL, 23.00, NULL, 0, 1),
(59, 1, 10, 'Arroz con Mariscos', NULL, 25.00, NULL, 0, 1),
(60, 1, 10, 'Chaufa de Mariscos', NULL, 20.00, NULL, 0, 1),
(61, 1, 10, 'Dúo Marino', '2 platos marinos a elegir', 30.00, NULL, 0, 1),
(62, 1, 10, 'Trío Marino', '3 platos marinos a elegir', 40.00, NULL, 0, 1),
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
(76, 1, 13, 'La Clásica', '160 gr de carnes seleccionadas, queso cheddar, tocino, lechuga y tomate', 15.00, NULL, 0, 1),
(77, 1, 13, 'La Palteada', 'Res seleccionada, queso cheddar, tocino y generosa porción de palta', 16.00, NULL, 0, 1),
(78, 1, 13, 'La Hawaiana', 'Res seleccionada, queso cheddar, tocino, piña golden, lechuga y tomate', 17.00, NULL, 0, 1),
(79, 1, 13, 'La Pechugona', 'Filete de pollo crispy, tocino, lechuga, tomate y salsa de palta', 15.00, NULL, 0, 1),
(80, 1, 13, 'La Doble', 'Res seleccionada, filete de pollo crispy, doble queso, doble tocino', 25.00, NULL, 0, 1),
(81, 1, 14, 'Personal (8 Alitas)', 'Sabores: BBQ, Acevichada, Maracuyá, Parrillera, Crispy', 20.00, NULL, 0, 1),
(82, 1, 14, 'Para Picar (18 Alitas)', 'Sabores: BBQ, Acevichada, Maracuyá, Parrillera, Crispy', 40.00, NULL, 0, 1),
(83, 1, 15, 'Super Clásica', 'Salchicha revueltas con huevo y papas fritas', 13.00, NULL, 0, 1),
(84, 1, 15, 'La Parrillera', 'Trozos de chorizo parrillero y pechuga, huevo a la inglesa y papas fritas', 18.00, NULL, 0, 1),
(85, 1, 15, 'Crispy Chicken', 'Trozos de salchicha y pollo crocante, huevo a la inglesa y papas fritas', 17.00, NULL, 0, 1),
(86, 1, 15, 'Amazónica', 'Trozos de cecina y chorizo ahumado, huevo a la inglesa y patacones', 20.00, NULL, 0, 1),
(87, 1, 15, 'Salchipork', 'Trozos de salchicha y chicharrón de cerdo, huevo a la inglesa y papas fritas', 18.00, NULL, 0, 1),
(88, 1, 16, 'Chicharrón', NULL, 12.00, NULL, 0, 1),
(89, 1, 16, 'Cecina', NULL, 12.00, NULL, 0, 1),
(90, 1, 16, 'Chorizo', NULL, 12.00, NULL, 0, 1),
(91, 1, 16, 'Pechuga', NULL, 12.00, NULL, 0, 1),
(92, 1, 16, 'Queso', NULL, 5.00, NULL, 0, 1),
(93, 1, 16, 'Huevo', NULL, 5.00, NULL, 0, 1),
(94, 1, 16, 'Palta', NULL, 5.00, NULL, 0, 1),
(95, 1, 16, 'Pollo', NULL, 6.00, NULL, 0, 1),
(96, 1, 16, 'Tortilla de Chorizo', NULL, 12.00, NULL, 0, 1),
(97, 1, 16, 'Tortilla de Cecina', NULL, 12.00, NULL, 0, 1),
(98, 1, 16, 'Tortilla de Pollo', NULL, 12.00, NULL, 0, 1),
(99, 1, 17, 'Porción de Arroz', NULL, 5.00, NULL, 0, 1),
(100, 1, 17, 'Porción de Yucas Fritas', NULL, 8.00, NULL, 0, 1),
(101, 1, 17, 'Porción de Patacones', NULL, 8.00, NULL, 0, 1),
(102, 1, 17, 'Porción de Maduro Frito', NULL, 8.00, NULL, 0, 1),
(103, 1, 17, 'Porción de Papas Fritas', NULL, 8.00, NULL, 0, 1),
(104, 1, 18, 'Papaya', NULL, 8.00, NULL, 0, 1),
(105, 1, 18, 'Piña', NULL, 8.00, NULL, 0, 1),
(106, 1, 18, 'Mango', NULL, 10.00, NULL, 0, 1),
(107, 1, 18, 'Fresa', NULL, 10.00, NULL, 0, 1),
(108, 1, 18, 'Surtido', NULL, 9.00, NULL, 0, 1),
(109, 1, 18, 'Especial', NULL, 12.00, NULL, 0, 1),
(110, 1, 19, '1L Cocona', NULL, 17.00, NULL, 0, 1),
(111, 1, 19, '½ Cocona', NULL, 9.00, NULL, 0, 1),
(112, 1, 19, '1L Camu Camu', NULL, 18.00, NULL, 0, 1),
(113, 1, 19, '½ Camu Camu', NULL, 9.00, NULL, 0, 1),
(114, 1, 19, '1L Chicha Morada', NULL, 17.00, NULL, 0, 1),
(115, 1, 19, '½ Chicha Morada', NULL, 9.00, NULL, 0, 1),
(116, 1, 19, '1L Aguajina', NULL, 20.00, NULL, 0, 1),
(117, 1, 19, '½ Aguajina', NULL, 10.00, NULL, 0, 1),
(118, 1, 20, '1L Limonada', NULL, 17.00, NULL, 0, 1),
(119, 1, 20, '½ Limonada', NULL, 9.00, NULL, 0, 1),
(120, 1, 20, '1L Maracuyá', NULL, 18.00, NULL, 0, 1),
(121, 1, 20, '½ Maracuyá', NULL, 9.00, NULL, 0, 1),
(122, 1, 20, '1L Mango', NULL, 18.00, NULL, 0, 1),
(123, 1, 20, '½ Mango', NULL, 9.00, NULL, 0, 1),
(124, 1, 20, '1L Fresa', NULL, 18.00, NULL, 0, 1),
(125, 1, 20, '½ Fresa', NULL, 9.00, NULL, 0, 1),
(126, 1, 20, '1L Maracumango', NULL, 18.00, NULL, 0, 1),
(127, 1, 20, '½ Maracumango', NULL, 9.00, NULL, 0, 1),
(128, 1, 21, 'Té', NULL, 3.00, NULL, 0, 1),
(129, 1, 21, 'Manzanilla', NULL, 3.00, NULL, 0, 1),
(130, 1, 21, 'Anís', NULL, 3.00, NULL, 0, 1),
(131, 1, 21, 'Café Pasado', NULL, 5.00, NULL, 0, 1),
(132, 1, 21, 'Café con Leche', NULL, 7.00, NULL, 0, 1),
(133, 1, 22, 'Coca o Inca Kola 300 ml', NULL, 4.00, NULL, 0, 1),
(134, 1, 22, 'Coca o Inca Kola 600 ml', NULL, 5.00, NULL, 0, 1),
(135, 1, 22, 'Coca o Inca Kola 1 L', NULL, 9.00, NULL, 0, 1),
(136, 1, 22, 'Inca Kola Gordita', NULL, 6.00, NULL, 0, 1),
(137, 1, 22, 'Coca o Inca Kola 2.25 L', NULL, 13.00, NULL, 0, 1),
(138, 1, 22, 'Coca o Inca Kola 3 L', NULL, 16.00, NULL, 0, 1),
(139, 1, 22, 'Agua San Luis', NULL, 4.00, NULL, 0, 1),
(140, 1, 22, 'Agua Benedictino', NULL, 4.00, NULL, 0, 1),
(141, 1, 23, 'Sour Clásico', NULL, 16.00, NULL, 0, 1),
(142, 1, 23, 'Sour de Maracuyá', NULL, 17.00, NULL, 0, 1),
(143, 1, 23, 'Sour de Fresa', NULL, 17.00, NULL, 0, 1),
(144, 1, 23, 'Sour de Coca', NULL, 17.00, NULL, 0, 1),
(145, 1, 23, 'Sour de Hierbas Andinas', NULL, 17.00, NULL, 0, 1),
(146, 1, 23, 'Jarra de Sour', NULL, 27.00, NULL, 0, 1),
(147, 1, 24, 'Mojito Clásico', NULL, 16.00, NULL, 0, 1),
(148, 1, 24, 'Mojito de Maracuyá', NULL, 17.00, NULL, 0, 1),
(149, 1, 24, 'Mojito de Fresa', NULL, 17.00, NULL, 0, 1),
(150, 1, 24, 'Mojito de Mango', NULL, 17.00, NULL, 0, 1),
(151, 1, 24, 'Mojito de Hierbas Andinas', NULL, 16.00, NULL, 0, 1),
(152, 1, 24, 'Mojito Blue', NULL, 16.00, NULL, 0, 1),
(153, 1, 24, 'Jarra de Mojito', NULL, 27.00, NULL, 0, 1),
(154, 1, 25, 'Chilcano Clásico', NULL, 15.00, NULL, 0, 1),
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
(175, 1, 27, 'Pasión Amazónica', NULL, 20.00, NULL, 0, 1),
(176, 1, 27, 'Elixir del Inca', NULL, 20.00, NULL, 0, 1),
(177, 1, 27, 'Green Day', NULL, 20.00, NULL, 0, 1),
(178, 1, 27, 'Susurro del Tunche', NULL, 20.00, NULL, 0, 1),
(179, 1, 28, 'Pisco Macerado', NULL, 7.00, NULL, 0, 1),
(180, 1, 28, 'Tequila', NULL, 10.00, NULL, 0, 1),
(181, 1, 28, 'Whiskey', NULL, 10.00, NULL, 0, 1),
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
  `total_general` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `restaurante_id`, `usuario_id`, `inicio`, `fin`, `total_efectivo`, `total_yape`, `total_transferencia`, `total_tarjeta`, `total_otros`, `total_general`) VALUES
(1, 1, 3, '2026-04-14 19:24:35', NULL, 42.00, 139.00, 0.00, 62.00, 0.00, 243.00);

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
  ADD KEY `usuario_id` (`usuario_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `opciones_grupo`
--
ALTER TABLE `opciones_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `opciones_valor`
--
ALTER TABLE `opciones_valor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `pedido_items`
--
ALTER TABLE `pedido_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
