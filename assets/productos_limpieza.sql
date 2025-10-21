-- ---------------------------------------------------------------------
-- SCRIPT DE BASE DE DATOS COMPLETA 
--
-- Propósito: Este archivo contiene todos los comandos SQL necesarios
--            para crear la estructura completa de la base de datos
--            y llenarla con datos.
--  
--            pueda "importar" en su gestor de bases de datos
--            para tener una copia idéntica del sistema.
-- ---------------------------------------------------------------------

-- --------------------------------------------------------
-- SECCIÓN 1: CREACIÓN DE TABLAS (ESTRUCTURA)
-- --------------------------------------------------------
-- El orden es importante. Se crean primero las tablas "maestras"
-- (que no dependen de otras) y al final las "transaccionales".
-- --------------------------------------------------------

--
-- Tabla: Categoria
-- Propósito:   Almacenar los tipos o rubros de productos.
--              Clasificar el inventario (ej. "Electrónica", "Ropa").
-- Contiene:    Un ID único y un nombre para la categoría.
-- Relaciones:  Es una tabla "padre". `Producto` se enlazará a ella.
--
CREATE TABLE Categoria (
  idCategoria INT(11) NOT NULL AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  Descripcion TEXT NULL,
  PRIMARY KEY (idCategoria)
);

--
-- Tabla: Cliente
-- Propósito:   Almacenar los datos demográficos de los compradores.
--              Tener un registro de quién realiza las compras.
-- Contiene:    Información de contacto (nombre, teléfono, correo, etc.).
-- Relaciones:  Es una tabla "padre". `Venta` se enlazará a ella.
--              `Usuario` también se enlazará lógicamente a ella.
--
CREATE TABLE Cliente (
  idCliente INT(11) NOT NULL AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  Apellido VARCHAR(100) NOT NULL,
  Telefono VARCHAR(20) NULL DEFAULT NULL,
  Direccion VARCHAR(255) NULL DEFAULT NULL,
  Correo VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (idCliente)
);

--
-- Tabla: Empleado
-- Propósito:   Almacenar los datos de los trabajadores de la tienda.
--              Saber qué empleado registró una venta y gestionar permisos.
-- Contiene:    Información del empleado (nombre, puesto, salario).
-- Relaciones:  Es una tabla "padre". `Venta` se enlazará a ella.
--              `Usuario` también se enlazará lógicamente a ella.
--
CREATE TABLE Empleado (
  idEmpleado INT(11) NOT NULL AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  Apellido VARCHAR(100) NOT NULL,
  Puesto VARCHAR(100) NULL DEFAULT NULL,
  Telefono VARCHAR(20) NULL DEFAULT NULL,
  Salario DECIMAL(10,2) NULL DEFAULT NULL,
  Correo VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (idEmpleado)
);

--
-- Tabla: Usuario
-- Propósito:   Gestionar el acceso (autenticación) al sistema.
--              Permitir que Clientes y Empleados inicien sesión.
-- Contiene:    Credenciales (correo, contraseña hasheada), el tipo 
--              (Cliente/Empleado) y su estado (Activo/Bloqueado).
-- Relaciones:  Es la tabla "padre" de todo el módulo de seguridad
--              (Sesion, Recuperacion, RegistroEventos).
--              `idRelacionado` apunta al ID de `Cliente` o `Empleado`.
--
CREATE TABLE Usuario (
  idUsuario INT(11) NOT NULL AUTO_INCREMENT,
  correo VARCHAR(100) NOT NULL,
  contrasena VARCHAR(255) NOT NULL,
  tipo ENUM('Cliente', 'Empleado') NOT NULL,
  idRelacionado INT(11) NULL DEFAULT NULL,
  estado ENUM('Activo', 'Inactivo', 'Bloqueado') NULL DEFAULT 'Activo',
  intentosFallidos INT(11) NULL DEFAULT 0,
  fechaCreacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idUsuario),
  UNIQUE KEY (correo) -- El correo debe ser único para iniciar sesión
);

--
-- Tabla: Producto
-- Propósito:   Almacenar el inventario de la tienda.
--              Tener un catálogo de lo que se vende, con su precio y stock.
-- Contiene:    Nombre, descripción, precio y stock de cada artículo.
-- Relaciones:  Es una tabla "hija".
--              - `idCategoria` es una Llave Foránea (FK) que se conecta a `Categoria`.
--
CREATE TABLE Producto (
  idProducto INT(11) NOT NULL AUTO_INCREMENT,
  Nombre VARCHAR(150) NOT NULL,
  Descripcion TEXT NULL,
  Precio DECIMAL(10,2) NOT NULL,
  Stock INT(11) NOT NULL DEFAULT 0,
  idCategoria INT(11) NOT NULL,
  PRIMARY KEY (idProducto),
  FOREIGN KEY (idCategoria) REFERENCES Categoria(idCategoria)
);

--
-- Tabla: Venta
-- Propósito:   Registrar la "cabecera" de una transacción o ticket.
--              Guardar el total, la fecha y quiénes participaron (cliente 
--              y empleado) en una venta.
-- Contiene:    El total de la venta, la fecha, y los IDs del cliente y empleado.
-- Relaciones:  Es una tabla "hija" (depende de Cliente y Empleado)
--              y "padre" (la tabla `DetalleVenta` depende de ella).
--              - `idCliente` es FK a `Cliente`.
--              - `idEmpleado` es FK a `Empleado`.
--
CREATE TABLE Venta (
  idVenta INT(11) NOT NULL AUTO_INCREMENT,
  Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Total DECIMAL(10,2) NOT NULL,
  idCliente INT(11) NOT NULL,
  idEmpleado INT(11) NOT NULL,
  PRIMARY KEY (idVenta),
  FOREIGN KEY (idCliente) REFERENCES Cliente(idCliente),
  FOREIGN KEY (idEmpleado) REFERENCES Empleado(idEmpleado)
);

--
-- Tabla: DetalleVenta
-- Propósito:   Es el "corazón" de la venta. Desglosa los productos del ticket.
--              Saber qué productos y qué cantidades se vendieron 
--              en CADA venta individual.
-- Contiene:    Cantidad, precio unitario y subtotal de un producto 
--              específico dentro de una venta específica.
-- Relaciones:  Es una tabla "pivote" o "hija".
--              - `idVenta` es FK a `Venta`.
--              - `idProducto` es FK a `Producto`.
--
CREATE TABLE DetalleVenta (
  idDetalleVenta INT(11) NOT NULL AUTO_INCREMENT,
  idVenta INT(11) NOT NULL,
  idProducto INT(11) NOT NULL,
  Cantidad INT(11) NOT NULL,
  PrecioUnitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (idDetalleVenta),
  FOREIGN KEY (idVenta) REFERENCES Venta(idVenta),
  FOREIGN KEY (idProducto) REFERENCES Producto(idProducto)
);

--
-- Tabla: Sesion
-- Propósito:   Rastrear los inicios de sesión de los usuarios.
--              Saber qué usuarios están activos, cuándo iniciaron 
--              sesión y cuándo la cerraron. Útil para seguridad.
-- Contiene:    El ID del usuario, y las fechas de inicio/fin de la sesión.
-- Relaciones:  Es una tabla "hija".
--              - `idUsuario` es FK a `Usuario`.
--
CREATE TABLE Sesion (
  idSesion INT(11) NOT NULL AUTO_INCREMENT,
  idUsuario INT(11) NOT NULL,
  fechaInicio DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  fechaFin DATETIME NULL DEFAULT NULL,
  estado ENUM('Activa', 'Cerrada') NULL DEFAULT 'Activa',
  PRIMARY KEY (idSesion),
  FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario)
);

--
-- Tabla: Recuperacion
-- Propósito:   Gestionar la recuperación de contraseñas olvidadas.
--              Almacenar un código temporal (token) y su fecha de expiración 
--              para validar al usuario que quiere cambiar su contraseña.
-- Contiene:    El ID del usuario, el código de recuperación y su estado.
-- Relaciones:  Es una tabla "hija".
--              - `idUsuario` es FK a `Usuario`.
--
CREATE TABLE Recuperacion (
  idRecuperacion INT(11) NOT NULL AUTO_INCREMENT,
  idUsuario INT(11) NOT NULL,
  codigoRecuperacion VARCHAR(255) NOT NULL,
  fechaSolicitud DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  fechaExpiracion DATETIME NOT NULL,
  estado ENUM('Activo', 'Usado', 'Expirado') NULL DEFAULT 'Activo',
  PRIMARY KEY (idRecuperacion),
  FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario)
);

--
-- Tabla: RegistroEventos
-- Propósito:   Funcionar como una "caja negra" o bitácora del sistema.
--              Auditar eventos importantes, como inicios de sesión 
--              fallidos, borrado de datos o errores críticos del sistema.
-- Contiene:    El evento, el resultado, una descripción y opcionalmente 
--              el usuario que lo generó.
-- Relaciones:  Es una tabla "hija".
--              - `idUsuario` es FK a `Usuario` (puede ser NULO si
--                es un evento generado por el propio sistema).
--
CREATE TABLE RegistroEventos (
  idRegistro INT(11) NOT NULL AUTO_INCREMENT,
  idUsuario INT(11) NULL DEFAULT NULL, 
  fechaHora DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  evento VARCHAR(100) NULL DEFAULT NULL,
  resultado VARCHAR(50) NULL DEFAULT NULL,
  descripcion TEXT NULL,
  PRIMARY KEY (idRegistro),
  FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario)
);

 