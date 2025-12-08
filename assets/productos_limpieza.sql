-- =====================================================
-- SUPER LIMPIO - Base de datos completa
-- =====================================================
-- IMPORTANTE: Todas las tablas están en MINÚSCULAS
-- Compatible con XAMPP local y Hostinger
-- =====================================================
-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-12-2025 a las 02:41:39
-- Versión del servidor: 10.4.19-MariaDB
-- Versión de PHP: 8.3.27
-- Actualizado: 07-12-2025 - Tablas en minúsculas
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `productos_limpieza`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `idCategoria` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`idCategoria`, `Nombre`, `Descripcion`) VALUES
(1, 'Desinfectantes', 'Productos para desinfección general'),
(2, 'Detergentes', 'Limpieza de ropa y superficies'),
(3, 'Hogar', 'Productos para limpieza del hogar'),
(4, 'Baño', 'Limpieza y desinfección del baño'),
(5, 'Cocina', 'Limpieza de cocina y utensilios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `idCliente` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `Telefono` varchar(20) DEFAULT NULL,
  `Direccion` varchar(255) DEFAULT NULL,
  `Correo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idCliente`, `Nombre`, `Apellido`, `Telefono`, `Direccion`, `Correo`) VALUES
(1, 'Juan', '', '4567578768', 'Calle Falsa 123', 'juan@example.com'),
(2, 'Cliente de mostrador', '', NULL, NULL, NULL),
(3, 'Carlos', 'Matias', '9511324673', NULL, 'Carlos@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleventa`
--

CREATE TABLE `detalleventa` (
  `idDetalleVenta` int(11) NOT NULL,
  `idVenta` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `PrecioUnitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `detalleventa`
--

INSERT INTO `detalleventa` (`idDetalleVenta`, `idVenta`, `idProducto`, `Cantidad`, `PrecioUnitario`, `subtotal`) VALUES
(3, 2, 9, 1, '19.80', '19.80'),
(4, 2, 45, 1, '27.60', '27.60'),
(5, 3, 50, 1, '26.40', '26.40'),
(6, 3, 49, 1, '31.70', '31.70'),
(7, 4, 50, 1, '26.40', '26.40'),
(8, 4, 49, 1, '31.70', '31.70'),
(9, 4, 47, 1, '33.90', '33.90'),
(10, 5, 50, 1, '26.40', '26.40'),
(11, 6, 49, 5, '31.70', '158.50'),
(12, 6, 34, 1, '31.80', '31.80');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `idEmpleado` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Apellido` varchar(100) NOT NULL,
  `Puesto` varchar(100) DEFAULT NULL,
  `Telefono` varchar(20) DEFAULT NULL,
  `Salario` decimal(10,2) DEFAULT NULL,
  `Correo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`idEmpleado`, `Nombre`, `Apellido`, `Puesto`, `Telefono`, `Salario`, `Correo`) VALUES
(1, 'Ana', 'García', 'Vendedora', '555-5678', '7500.00', 'ana@example.com'),
(2, 'Admin', 'General', 'Administrador', NULL, NULL, 'admin@empresa.com'),
(3, '', '', 'Administrador', NULL, NULL, 'segundoAdmin@gmail.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `idProducto` int(11) NOT NULL,
  `Nombre` varchar(150) NOT NULL,
  `Descripcion` text DEFAULT NULL,
  `Precio` decimal(10,2) NOT NULL,
  `Stock` int(11) NOT NULL DEFAULT 0,
  `idCategoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`idProducto`, `Nombre`, `Descripcion`, `Precio`, `Stock`, `idCategoria`) VALUES
(1, 'Cloro Líquido 1L', 'Desinfectante multiusos para pisos y superficies del hogar.', '18.50', 120, 1),
(2, 'Cloro en Gel 750ml', 'Cloro en gel para limpieza profunda de baño y cocina.', '22.90', 80, 1),
(3, 'Desinfectante en Aerosol 400ml', 'Aerosol desinfectante para uso doméstico en muebles y telas.', '35.00', 60, 1),
(4, 'Sanitizante para Manos 500ml', 'Gel antibacterial con 70% de alcohol para manos.', '29.90', 150, 1),
(5, 'Limpiador Antibacterial para Pisos 1L', 'Limpiador líquido con acción antibacterial para todo tipo de pisos.', '26.50', 100, 1),
(6, 'Desinfectante Multiusos Floral 900ml', 'Desinfectante con fragancia floral para superficies lavables.', '24.75', 95, 1),
(7, 'Limpiador Desinfectante para Baño 1L', 'Desinfectante especializado para inodoros, lavabos y azulejos.', '28.30', 85, 1),
(8, 'Desinfectante Concentrado 500ml', 'Solución concentrada para diluir y desinfectar grandes áreas.', '31.90', 70, 1),
(9, 'Cloro para Cocina 1L', 'Cloro especialmente formulado para uso en cocina y utensilios.', '19.80', 109, 1),
(10, 'Toallitas Desinfectantes 50 pzas', 'Toallitas húmedas desinfectantes para limpieza rápida de superficies.', '32.00', 90, 1),
(11, 'Detergente en Polvo Ropa Blanca 1kg', 'Detergente en polvo para ropa blanca con blanqueador óptico.', '39.90', 75, 2),
(12, 'Detergente en Polvo Ropa Color 1kg', 'Detergente en polvo que protege el color de las prendas.', '38.50', 80, 2),
(13, 'Detergente Líquido Concentrado 900ml', 'Detergente líquido concentrado para lavadora automática.', '45.00', 60, 2),
(14, 'Detergente para Ropa Delicada 500ml', 'Detergente suave para prendas delicadas y ropa de bebé.', '34.90', 55, 2),
(15, 'Jabón Líquido para Manos 1L', 'Jabón líquido antibacterial para manos con aroma suave.', '27.50', 130, 2),
(16, 'Jabón para Trastes Limón 750ml', 'Lavatrastes líquido con aroma a limón y gran poder desengrasante.', '23.90', 140, 2),
(17, 'Jabón para Trastes Antibacterial 750ml', 'Lavatrastes antibacterial que elimina gérmenes y grasa difícil.', '25.50', 120, 2),
(18, 'Detergente para Lavavajillas Automático 1kg', 'Detergente en polvo para lavavajillas automáticas.', '52.00', 40, 2),
(19, 'Jabón en Barra para Lavandería 400g', 'Jabón en barra para remover manchas difíciles en ropa.', '15.75', 150, 2),
(20, 'Suavizante de Telas Floral 900ml', 'Suavizante líquido con fragancia floral y efecto antiestático.', '29.90', 95, 2),
(21, 'Limpiador Multiusos 1L', 'Limpiador líquido multiusos para superficies lavables del hogar.', '24.00', 110, 3),
(22, 'Limpiador de Vidrios 500ml', 'Limpiador en spray para vidrios y espejos sin dejar marcas.', '21.50', 90, 3),
(23, 'Limpiador de Muebles 500ml', 'Limpiador y abrillantador para muebles de madera.', '28.90', 70, 3),
(24, 'Limpiador de Pisos de Madera 1L', 'Limpiador especializado para pisos de madera y laminados.', '32.50', 60, 3),
(25, 'Limpiador de Pisos Cerámicos 1L', 'Limpiador para pisos cerámicos con aroma cítrico.', '26.90', 85, 3),
(26, 'Aromatizante de Ambiente Lavanda 360ml', 'Aromatizante en aerosol para habitaciones y salas.', '19.80', 100, 3),
(27, 'Limpiador para Ventanas 500ml', 'Limpiador líquido para ventanas con efecto antiempañante.', '22.40', 75, 3),
(28, 'Limpiador de Polvo para Superficies 400ml', 'Limpiador en spray que atrapa el polvo y protege superficies.', '27.30', 65, 3),
(29, 'Limpiador Multiusos Lavanda 1L', 'Limpiador multiusos con fragancia a lavanda duradera.', '24.70', 95, 3),
(30, 'Desengrasante General para Hogar 1L', 'Desengrasante líquido para múltiples superficies del hogar.', '30.20', 80, 3),
(31, 'Limpiador para Baño 1L', 'Limpiador líquido para lavabos, regaderas e inodoros.', '27.90', 90, 4),
(32, 'Gel para WC 750ml', 'Gel espeso para limpieza profunda del inodoro.', '29.50', 70, 4),
(33, 'Pastillas para Tanque de WC 4 pzas', 'Pastillas limpiadoras para el tanque del WC de larga duración.', '33.00', 60, 4),
(34, 'Limpiador Antisarro 500ml', 'Limpiador especializado para remover sarro en baño y cocina.', '31.80', 64, 4),
(35, 'Limpiador de Regaderas 500ml', 'Limpiador para eliminar residuos de jabón y sarro en regaderas.', '28.40', 55, 4),
(36, 'Limpiador para Azulejos 1L', 'Limpiador líquido para azulejos de baño y cocina.', '26.70', 80, 4),
(37, 'Espuma Limpiadora para Baño 500ml', 'Espuma en spray para limpieza rápida de superficies del baño.', '29.90', 60, 4),
(38, 'Limpiador de Drenajes 500ml', 'Producto químico para destapar y limpiar drenajes.', '36.50', 40, 4),
(39, 'Limpiador de Espejos para Baño 400ml', 'Limpiador de espejos resistente al empañado por vapor.', '22.90', 75, 4),
(40, 'Aromatizante para Baño 250ml', 'Aromatizante líquido para mantener fresco el baño.', '18.20', 100, 4),
(41, 'Desengrasante de Cocina 1L', 'Desengrasante potente para estufas, paredes y campanas.', '34.90', 85, 5),
(42, 'Limpiador de Hornos 500ml', 'Limpiador en gel para hornos y parrillas con grasa quemada.', '38.50', 55, 5),
(43, 'Limpiador de Parrillas 500ml', 'Limpiador líquido para parrillas y planchas metálicas.', '35.40', 50, 5),
(44, 'Limpiador de Campana Extractora 500ml', 'Desengrasante para filtros y superficies de campana.', '32.80', 60, 5),
(45, 'Limpiador de Microondas 400ml', 'Limpiador en spray para hornos de microondas.', '27.60', 69, 5),
(46, 'Limpiador de Refrigerador 500ml', 'Limpiador desodorizante para interiores de refrigerador.', '29.10', 65, 5),
(47, 'Limpiador de Acero Inoxidable 400ml', 'Limpiador y abrillantador para superficies de acero inoxidable.', '33.90', 54, 5),
(48, 'Limpiador de Superficies de Mármol 500ml', 'Limpiador suave para cubiertas de mármol y granito.', '37.20', 45, 5),
(49, 'Desengrasante para Estufa 750ml', 'Desengrasante líquido con atomizador para estufas.', '31.70', 73, 5),
(50, 'Limpiador de Utensilios y Licuadoras 500ml', 'Limpiador líquido para utensilios de cocina y licuadoras.', '26.40', 95, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recuperacion`
--

CREATE TABLE `recuperacion` (
  `idRecuperacion` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `codigoRecuperacion` varchar(255) NOT NULL,
  `fechaSolicitud` datetime DEFAULT current_timestamp(),
  `fechaExpiracion` datetime NOT NULL,
  `estado` enum('Activo','Usado','Expirado') DEFAULT 'Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `recuperacion`
--

INSERT INTO `recuperacion` (`idRecuperacion`, `idUsuario`, `codigoRecuperacion`, `fechaSolicitud`, `fechaExpiracion`, `estado`) VALUES
(5, 2, 'd2d690d3', '2025-11-24 21:15:51', '2025-11-25 04:30:51', 'Usado'),
(6, 1, '92ed439d', '2025-12-04 00:21:08', '2025-12-04 06:36:08', 'Usado'),
(7, 2, 'dd6b4e18', '2025-12-04 01:07:15', '2025-12-04 07:22:15', 'Usado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registroeventos`
--

CREATE TABLE `registroeventos` (
  `idRegistro` int(11) NOT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `fechaHora` datetime DEFAULT current_timestamp(),
  `evento` varchar(100) DEFAULT NULL,
  `resultado` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `registroeventos`
--

INSERT INTO `registroeventos` (`idRegistro`, `idUsuario`, `fechaHora`, `evento`, `resultado`, `descripcion`) VALUES
(1, 1, '2025-10-20 08:26:51', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(2, 1, '2025-10-20 08:27:24', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(3, 1, '2025-10-20 08:28:10', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(4, NULL, '2025-10-20 08:31:07', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(5, NULL, '2025-10-20 08:31:37', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(6, NULL, '2025-10-20 08:37:20', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(7, NULL, '2025-10-20 08:37:32', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(8, NULL, '2025-10-20 09:02:49', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(9, NULL, '2025-10-20 09:04:07', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(10, NULL, '2025-10-20 09:06:14', 'Recuperación', 'Exitoso', 'Contraseña actualizada.'),
(11, NULL, '2025-10-20 09:06:28', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(12, NULL, '2025-10-20 09:12:40', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(13, NULL, '2025-10-20 09:12:57', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(14, 4, '2025-10-20 09:17:30', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(15, NULL, '2025-10-20 09:26:01', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(16, NULL, '2025-10-20 09:39:07', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(17, NULL, '2025-10-20 09:39:30', 'Recuperación', 'Exitoso', 'Contraseña actualizada.'),
(18, NULL, '2025-10-20 09:41:25', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(19, NULL, '2025-11-11 22:51:32', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(20, 6, '2025-11-12 00:07:27', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(21, 6, '2025-11-12 00:07:53', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(22, 6, '2025-11-12 00:08:05', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(23, NULL, '2025-11-12 00:22:01', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(24, 8, '2025-11-12 00:29:34', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(25, 8, '2025-11-12 00:33:00', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(26, 8, '2025-11-12 01:25:45', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(27, 8, '2025-11-12 02:17:26', 'Producto - Crear', 'Exitoso', 'Se creó \'{jsjs\'.'),
(28, 8, '2025-11-12 02:58:32', 'Producto - Crear', 'Exitoso', 'Se creó \'bus\'.'),
(29, 8, '2025-11-12 03:04:19', 'Producto - Crear', 'Exitoso', 'Se creó \'Biologia\'.'),
(30, 8, '2025-11-12 03:05:04', 'Producto - Crear', 'Exitoso', 'Se creó \'jsj\'.'),
(31, 8, '2025-11-12 04:22:34', 'Producto - Editar', 'Exitoso', 'Producto #6 actualizado.'),
(32, 8, '2025-11-12 04:23:26', 'Producto - Editar', 'Exitoso', 'Producto #6 actualizado.'),
(33, 8, '2025-11-12 04:33:26', 'Producto - Eliminar', 'Exitoso', 'Producto #6 eliminado.'),
(34, 8, '2025-11-12 04:33:56', 'Producto - Eliminar', 'Exitoso', 'Producto #3 eliminado.'),
(35, 8, '2025-11-12 05:03:49', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(36, 8, '2025-11-12 05:04:11', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(37, 8, '2025-11-12 09:28:26', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(38, 8, '2025-11-12 09:30:42', 'Producto - Crear', 'Exitoso', 'Se creó \'Escoba\'.'),
(39, 8, '2025-11-12 09:31:50', 'Producto - Editar', 'Exitoso', 'Producto #7 actualizado.'),
(40, 8, '2025-11-12 09:32:10', 'Producto - Eliminar', 'Exitoso', 'Producto #4 eliminado.'),
(41, NULL, '2025-11-13 16:17:36', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(42, NULL, '2025-11-13 19:29:29', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(43, NULL, '2025-11-13 22:26:47', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(44, 8, '2025-11-13 22:54:25', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(45, NULL, '2025-11-14 02:55:03', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(46, NULL, '2025-11-14 02:55:30', 'Recuperación', 'Exitoso', 'Contraseña actualizada.'),
(47, NULL, '2025-11-14 02:55:46', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(48, NULL, '2025-11-14 03:12:10', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(49, NULL, '2025-11-14 03:12:32', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(50, NULL, '2025-11-14 03:12:45', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(51, 8, '2025-11-14 03:13:49', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(52, 8, '2025-11-14 03:14:01', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(53, 8, '2025-11-14 03:18:50', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(54, 8, '2025-11-14 03:28:32', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(55, 8, '2025-11-14 21:38:09', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(56, 8, '2025-11-18 11:33:58', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(57, NULL, '2025-11-18 11:47:08', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(58, 8, '2025-11-18 11:47:50', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(59, 8, '2025-11-18 11:48:41', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(60, 8, '2025-11-18 11:48:52', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(61, 8, '2025-11-18 11:49:06', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(62, NULL, '2025-11-18 11:57:52', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(63, 10, '2025-11-18 12:11:25', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(64, 10, '2025-11-18 12:11:41', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(65, 8, '2025-11-18 12:24:10', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(66, 8, '2025-11-18 12:48:19', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(67, 8, '2025-11-18 13:20:57', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(68, 8, '2025-11-18 16:14:09', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(69, 8, '2025-11-18 16:14:38', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(70, NULL, '2025-11-19 15:12:22', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(71, 8, '2025-11-20 10:37:10', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(72, 2, '2025-11-24 21:15:51', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(73, 2, '2025-11-24 21:16:16', 'Recuperación', 'Exitoso', 'Contraseña actualizada.'),
(74, 2, '2025-11-24 21:16:35', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(75, 2, '2025-11-24 22:51:44', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(76, 2, '2025-11-24 22:53:27', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(77, 8, '2025-11-27 11:10:25', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(78, 8, '2025-12-03 22:34:54', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(79, NULL, '2025-12-03 22:45:20', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(80, 1, '2025-12-04 00:21:08', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(81, 1, '2025-12-04 00:21:30', 'Recuperación', 'Exitoso', 'Contraseña actualizada.'),
(82, 1, '2025-12-04 00:21:43', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(83, 1, '2025-12-04 00:49:01', 'Cliente - Actualizar perfil', 'Exitoso', 'Se actualizó el perfil del cliente id=1'),
(84, 1, '2025-12-04 00:49:14', 'Cliente - Actualizar perfil', 'Exitoso', 'Se actualizó el perfil del cliente id=1'),
(85, 1, '2025-12-04 00:49:32', 'Cliente - Actualizar perfil', 'Exitoso', 'Se actualizó el perfil del cliente id=1'),
(86, 1, '2025-12-04 01:02:55', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(87, 2, '2025-12-04 01:06:58', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(88, 2, '2025-12-04 01:07:08', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(89, 2, '2025-12-04 01:07:15', 'Recuperación', 'Exitoso', 'Código generado para recuperación.'),
(90, 2, '2025-12-04 01:07:44', 'Recuperación', 'Exitoso', 'Contraseña actualizada.'),
(91, 2, '2025-12-04 01:07:56', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(92, NULL, '2025-12-04 01:35:31', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(93, NULL, '2025-12-04 01:35:43', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(94, 2, '2025-12-04 01:36:04', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(95, 12, '2025-12-04 01:45:20', 'Registro', 'Exitoso', 'Usuario registrado correctamente.'),
(96, 12, '2025-12-04 01:45:36', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(97, 2, '2025-12-04 01:45:54', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(98, 12, '2025-12-04 01:46:52', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(99, 12, '2025-12-04 01:47:08', 'Cliente - Actualizar perfil', 'Exitoso', 'Se actualizó el perfil del cliente id=3'),
(100, 2, '2025-12-04 01:47:25', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(101, 8, '2025-12-04 01:52:24', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(102, 8, '2025-12-04 02:11:16', 'Usuario', 'Exitoso', 'Se desactivó el usuario ID 6'),
(103, 13, '2025-12-04 02:22:21', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(104, 2, '2025-12-04 10:29:16', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(105, 2, '2025-12-04 10:29:26', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(106, 2, '2025-12-04 10:42:47', 'Producto - Editar (vendedor)', 'Exitoso', 'Producto #50 actualizado por vendedor.'),
(107, 8, '2025-12-04 10:50:50', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(108, 2, '2025-12-04 10:56:05', 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.'),
(109, 2, '2025-12-04 10:56:16', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(110, 12, '2025-12-04 11:01:24', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(111, 2, '2025-12-04 11:08:20', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(112, 2, '2025-12-04 11:09:10', 'Producto - Editar (vendedor)', 'Exitoso', 'Producto #49 actualizado por vendedor.'),
(113, 2, '2025-12-04 11:10:17', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(114, 2, '2025-12-04 11:10:49', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(115, 2, '2025-12-04 11:11:03', 'Producto - Editar (vendedor)', 'Exitoso', 'Producto #49 actualizado por vendedor.'),
(116, 8, '2025-12-04 11:13:00', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(117, 12, '2025-12-04 11:19:46', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.'),
(118, 12, '2025-12-04 12:26:27', 'Cliente - Actualizar perfil', 'Exitoso', 'Se actualizó el perfil del cliente id=3'),
(119, 2, '2025-12-07 20:34:45', 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion`
--

CREATE TABLE `sesion` (
  `idSesion` int(11) NOT NULL,
  `idUsuario` int(11) NOT NULL,
  `fechaInicio` datetime DEFAULT current_timestamp(),
  `fechaFin` datetime DEFAULT NULL,
  `estado` enum('Activa','Cerrada') DEFAULT 'Activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `sesion`
--

INSERT INTO `sesion` (`idSesion`, `idUsuario`, `fechaInicio`, `fechaFin`, `estado`) VALUES
(7, 6, '2025-11-12 00:07:53', NULL, 'Activa'),
(8, 8, '2025-11-12 00:33:00', '2025-12-04 02:22:10', 'Cerrada'),
(9, 8, '2025-11-12 01:25:45', '2025-12-04 02:22:10', 'Cerrada'),
(10, 8, '2025-11-12 05:04:11', '2025-12-04 02:22:10', 'Cerrada'),
(11, 8, '2025-11-12 09:28:26', '2025-12-04 02:22:10', 'Cerrada'),
(12, 8, '2025-11-13 22:54:25', '2025-12-04 02:22:10', 'Cerrada'),
(15, 8, '2025-11-14 03:14:01', '2025-12-04 02:22:10', 'Cerrada'),
(16, 8, '2025-11-14 03:18:50', '2025-12-04 02:22:10', 'Cerrada'),
(17, 8, '2025-11-14 03:28:32', '2025-12-04 02:22:10', 'Cerrada'),
(18, 8, '2025-11-14 21:38:09', '2025-12-04 02:22:10', 'Cerrada'),
(19, 8, '2025-11-18 11:33:58', '2025-12-04 02:22:10', 'Cerrada'),
(20, 8, '2025-11-18 11:48:52', '2025-12-04 02:22:10', 'Cerrada'),
(21, 8, '2025-11-18 11:49:06', '2025-12-04 02:22:10', 'Cerrada'),
(22, 10, '2025-11-18 12:11:41', '2025-11-18 12:11:44', 'Cerrada'),
(23, 8, '2025-11-18 12:24:10', '2025-12-04 02:22:10', 'Cerrada'),
(24, 8, '2025-11-18 12:48:19', '2025-12-04 02:22:10', 'Cerrada'),
(25, 8, '2025-11-18 13:20:57', '2025-12-04 02:22:10', 'Cerrada'),
(26, 8, '2025-11-18 16:14:09', '2025-12-04 02:22:10', 'Cerrada'),
(27, 8, '2025-11-18 16:14:38', '2025-12-04 02:22:10', 'Cerrada'),
(28, 8, '2025-11-20 10:37:10', '2025-12-04 02:22:10', 'Cerrada'),
(29, 2, '2025-11-24 21:16:35', '2025-11-24 22:50:43', 'Cerrada'),
(30, 2, '2025-11-24 22:51:44', '2025-11-24 22:51:52', 'Cerrada'),
(31, 2, '2025-11-24 22:53:27', '2025-11-24 23:00:38', 'Cerrada'),
(32, 8, '2025-11-27 11:10:25', '2025-12-04 02:22:10', 'Cerrada'),
(33, 8, '2025-12-03 22:34:54', '2025-12-04 02:22:10', 'Cerrada'),
(35, 1, '2025-12-04 00:21:43', '2025-12-04 01:02:28', 'Cerrada'),
(36, 1, '2025-12-04 01:02:55', '2025-12-04 01:04:38', 'Cerrada'),
(37, 2, '2025-12-04 01:07:56', '2025-12-04 01:34:17', 'Cerrada'),
(39, 2, '2025-12-04 01:36:04', '2025-12-04 01:44:48', 'Cerrada'),
(40, 12, '2025-12-04 01:45:36', '2025-12-04 01:45:40', 'Cerrada'),
(41, 2, '2025-12-04 01:45:54', '2025-12-04 01:46:34', 'Cerrada'),
(42, 12, '2025-12-04 01:46:52', '2025-12-04 01:47:11', 'Cerrada'),
(43, 2, '2025-12-04 01:47:25', '2025-12-04 01:47:45', 'Cerrada'),
(44, 8, '2025-12-04 01:52:24', '2025-12-04 02:22:10', 'Cerrada'),
(45, 13, '2025-12-04 02:22:21', '2025-12-04 02:27:10', 'Cerrada'),
(46, 2, '2025-12-04 10:29:26', '2025-12-04 10:49:50', 'Cerrada'),
(47, 8, '2025-12-04 10:50:50', '2025-12-04 10:51:18', 'Cerrada'),
(48, 2, '2025-12-04 10:56:16', '2025-12-04 11:01:12', 'Cerrada'),
(49, 12, '2025-12-04 11:01:24', NULL, 'Activa'),
(50, 2, '2025-12-04 11:08:20', '2025-12-04 11:09:39', 'Cerrada'),
(51, 2, '2025-12-04 11:10:17', '2025-12-04 11:10:36', 'Cerrada'),
(52, 2, '2025-12-04 11:10:49', '2025-12-04 11:12:48', 'Cerrada'),
(53, 8, '2025-12-04 11:13:00', '2025-12-04 11:13:52', 'Cerrada'),
(54, 12, '2025-12-04 11:19:46', NULL, 'Activa'),
(55, 2, '2025-12-07 20:34:45', NULL, 'Activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idUsuario` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `tipo` enum('Cliente','Empleado') NOT NULL,
  `idRelacionado` int(11) DEFAULT NULL,
  `estado` enum('Activo','Inactivo','Bloqueado') DEFAULT 'Activo',
  `intentosFallidos` int(11) DEFAULT 0,
  `fechaCreacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idUsuario`, `correo`, `contrasena`, `tipo`, `idRelacionado`, `estado`, `intentosFallidos`, `fechaCreacion`) VALUES
(1, 'juan@example.com', '$2y$10$HwIu2Gk0bx0iKdZQ8zexlOoRvvjON35m0E2iinpdg05Aa/C5IL1mS', 'Cliente', 1, 'Activo', 0, '2025-10-20 14:17:14'),
(2, 'ana@example.com', '$2y$10$h5/9gxhPG5K5P0a9EIlUi.r7guvcqIz2VcnIFfQAZRRD/pl6UkJrq', 'Empleado', 1, 'Activo', 0, '2025-10-20 14:17:14'),
(4, 'Ivan@gmail.com', '$2y$10$AEXe8OOUKHQ329Vn3UPSDOy7boFjN7BPdqAhwKTeVmFfV/viQu50y', 'Cliente', NULL, 'Activo', 0, '2025-10-20 15:17:30'),
(6, 'CarlosIvan@gmail.com', '$2y$10$rOFTk7uK7GVZ2cHCE6UFXe5udfaa53gipE0WEWGbwGcls4XMltUz.', 'Cliente', NULL, 'Bloqueado', 1, '2025-11-12 06:07:27'),
(8, 'admin@empresa.com', '$2y$10$j5OTtGjsjz1cADGW6eMxB.A47m16sCNGfIAkOU9rCig0dzUvquRJy', 'Empleado', NULL, 'Activo', 0, '2025-11-12 06:29:34'),
(10, 'ejemploRe@gmail.com', '$2y$10$NT25Ku4rlWo/oGZzjUcM7.hpXWDrjt6hYwtvMoM8QfYC.GNIQCJvW', 'Cliente', NULL, 'Activo', 0, '2025-11-18 18:11:25'),
(12, 'Carlos@gmail.com', '$2y$10$AMgQNgviIjpTvmKDM5pavu32FcaFTUtRWVLcAkyWgjkIHI9iXhYO.', 'Cliente', 3, 'Activo', 0, '2025-12-04 07:45:20'),
(13, 'segundoAdmin@gmail.com', '$2y$10$HpfW.tmWg6fWpTpezyp27OIYxF3iX1gPGKAYl5AZfH26LtCXETfjG', 'Empleado', 3, 'Activo', 0, '2025-12-04 08:22:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `idVenta` int(11) NOT NULL,
  `Fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `Total` decimal(10,2) NOT NULL,
  `idCliente` int(11) NOT NULL,
  `idEmpleado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`idVenta`, `Fecha`, `Total`, `idCliente`, `idEmpleado`) VALUES
(2, '2025-11-24 22:44:17', '47.40', 2, 1),
(3, '2025-11-24 22:45:27', '58.10', 2, 1),
(4, '2025-11-24 22:50:17', '92.00', 2, 1),
(5, '2025-12-04 01:34:01', '26.40', 1, 1),
(6, '2025-12-04 11:12:40', '190.30', 3, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idCategoria`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idCliente`);

--
-- Indices de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD PRIMARY KEY (`idDetalleVenta`),
  ADD KEY `idVenta` (`idVenta`),
  ADD KEY `idProducto` (`idProducto`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`idEmpleado`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`idProducto`),
  ADD KEY `idCategoria` (`idCategoria`);

--
-- Indices de la tabla `recuperacion`
--
ALTER TABLE `recuperacion`
  ADD PRIMARY KEY (`idRecuperacion`),
  ADD KEY `idUsuario` (`idUsuario`);

--
-- Indices de la tabla `registroeventos`
--
ALTER TABLE `registroeventos`
  ADD PRIMARY KEY (`idRegistro`),
  ADD KEY `idUsuario` (`idUsuario`);

--
-- Indices de la tabla `sesion`
--
ALTER TABLE `sesion`
  ADD PRIMARY KEY (`idSesion`),
  ADD KEY `idUsuario` (`idUsuario`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `idRelacionado` (`idRelacionado`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`idVenta`),
  ADD KEY `idCliente` (`idCliente`),
  ADD KEY `idEmpleado` (`idEmpleado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idCategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idCliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  MODIFY `idDetalleVenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `idEmpleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `idProducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `recuperacion`
--
ALTER TABLE `recuperacion`
  MODIFY `idRecuperacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `registroeventos`
--
ALTER TABLE `registroeventos`
  MODIFY `idRegistro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT de la tabla `sesion`
--
ALTER TABLE `sesion`
  MODIFY `idSesion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `idVenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD CONSTRAINT `detalleventa_ibfk_1` FOREIGN KEY (`idVenta`) REFERENCES `venta` (`idVenta`),
  ADD CONSTRAINT `detalleventa_ibfk_2` FOREIGN KEY (`idProducto`) REFERENCES `producto` (`idProducto`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`idCategoria`) REFERENCES `categoria` (`idCategoria`);

--
-- Filtros para la tabla `recuperacion`
--
ALTER TABLE `recuperacion`
  ADD CONSTRAINT `recuperacion_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `registroeventos`
--
ALTER TABLE `registroeventos`
  ADD CONSTRAINT `registroeventos_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sesion`
--
ALTER TABLE `sesion`
  ADD CONSTRAINT `sesion_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`idRelacionado`) REFERENCES `cliente` (`idCliente`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`),
  ADD CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`idEmpleado`) REFERENCES `empleado` (`idEmpleado`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
