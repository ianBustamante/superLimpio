<?php


require_once 'connection.php';


// Validar formato de correo electrónico
function validarCorreo($correo) {
    return filter_var($correo, FILTER_VALIDATE_EMAIL);
}

// Validar seguridad de contraseña (mínimo 8 caracteres, 1 número y 1 mayúscula)
function validarPassword($password) {
    $mayuscula = preg_match('@[A-Z]@', $password);
    $numero = preg_match('@[0-9]@', $password);
    $longitud = strlen($password) >= 8;
    return ($mayuscula && $numero && $longitud);
}


//  FUNCIONES DE USUARIO (Registro / Login / Recuperación)


// Verificar si el correo ya está registrado
function usuarioExiste($conn, $correo) {
    $sql = "SELECT idUsuario FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return ($resultado->num_rows > 0);
}

// Registrar un nuevo usuario
// Registrar un nuevo usuario
function registrarUsuario($conn, $correo, $password, $tipo = 'Cliente') {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 1) Crear usuario (sin idRelacionado al inicio)
    $sql = "INSERT INTO usuario (correo, contrasena, tipo, estado)
            VALUES (?, ?, ?, 'Activo')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $correo, $hash, $tipo);

    if (!$stmt->execute()) {
        return false;
    }

    $idUsuario    = $stmt->insert_id;
    $idRelacionado = null;

    // 2) Si es Cliente, crear también registro en tabla CLIENTE
    if ($tipo === 'Cliente') {
        $sqlCli = "INSERT INTO cliente (Nombre, Apellido, Telefono, Direccion, Correo)
                   VALUES ('', '', NULL, NULL, ?)";
        $stmtCli = $conn->prepare($sqlCli);
        $stmtCli->bind_param("s", $correo);

        if ($stmtCli->execute()) {
            $idRelacionado = $stmtCli->insert_id;
        }
    }

    // (Opcional) si algún día registras Empleados desde aquí:
    /*
    if ($tipo === 'Empleado') {
        $sqlEmp = "INSERT INTO empleado (Nombre, Apellido, Puesto, Telefono, Salario, Correo)
                   VALUES ('', '', 'Empleado', NULL, NULL, ?)";
        $stmtEmp = $conn->prepare($sqlEmp);
        $stmtEmp->bind_param("s", $correo);

        if ($stmtEmp->execute()) {
            $idRelacionado = $stmtEmp->insert_id;
        }
    }
    */

    // 3) Actualizar el usuario con su idRelacionado
    if ($idRelacionado !== null) {
        $sqlUp = "UPDATE usuario SET idRelacionado = ? WHERE idUsuario = ?";
        $stmtUp = $conn->prepare($sqlUp);
        $stmtUp->bind_param("ii", $idRelacionado, $idUsuario);
        $stmtUp->execute();
    }

    registrarEvento($conn, $idUsuario, 'Registro', 'Exitoso', 'Usuario registrado correctamente.');
    return true;
}

// Iniciar sesión
function iniciarSesion($conn, $correo, $password) {
    $sql = "SELECT idUsuario, contrasena, estado, intentosFallidos, tipo FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        if ($usuario['estado'] !== 'Activo') {
            registrarEvento($conn, $usuario['idUsuario'], 'Inicio de sesión', 'Fallido', 'Usuario inactivo o bloqueado.');
            return "Cuenta inactiva o bloqueada.";
        }

        if (password_verify($password, $usuario['contrasena'])) {
            $conn->query("UPDATE usuario SET intentosFallidos = 0 WHERE idUsuario = {$usuario['idUsuario']}");
            crearSesion($conn, $usuario['idUsuario']);
            registrarEvento($conn, $usuario['idUsuario'], 'Inicio de sesión', 'Exitoso', 'Usuario autenticado correctamente.');
            return true;
        } else {
            $conn->query("UPDATE usuario SET intentosFallidos = intentosFallidos + 1 WHERE idUsuario = {$usuario['idUsuario']}");
            registrarEvento($conn, $usuario['idUsuario'], 'Inicio de sesión', 'Fallido', 'Contraseña incorrecta.');
            return "Contraseña incorrecta.";
        }
    } else {
        return "Usuario no encontrado.";
    }
}

// Generar código de recuperación
function generarCodigoRecuperacion($conn, $correo) {
    $sql = "SELECT idUsuario FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $codigo = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $fechaExp = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $sqlInsert = "INSERT INTO recuperacion (idUsuario, codigoRecuperacion, fechaExpiracion, estado)
                      VALUES (?, ?, ?, 'Activo')";
        $stmt2 = $conn->prepare($sqlInsert);
        $stmt2->bind_param("iss", $usuario['idUsuario'], $codigo, $fechaExp);
        $stmt2->execute();

        registrarEvento($conn, $usuario['idUsuario'], 'Recuperación', 'Exitoso', 'Código generado para recuperación.');
        return $codigo;
    } else {
        return false;
    }
}

// Validar y actualizar nueva contraseña
function actualizarContrasena($conn, $codigo, $nuevaContrasena) {
    $sql = "SELECT idRecuperacion, idUsuario, fechaExpiracion, estado 
            FROM recuperacion WHERE codigoRecuperacion = ? AND estado = 'Activo'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $data = $resultado->fetch_assoc();

        if (strtotime($data['fechaExpiracion']) < time()) {
            $conn->query("UPDATE recuperacion SET estado = 'Expirado' WHERE idRecuperacion = {$data['idRecuperacion']}");
            return "El código ha expirado.";
        }

        $hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);
        $conn->query("UPDATE usuario SET contrasena = '$hash' WHERE idUsuario = {$data['idUsuario']}");
        $conn->query("UPDATE recuperacion SET estado = 'Usado' WHERE idRecuperacion = {$data['idRecuperacion']}");

        registrarEvento($conn, $data['idUsuario'], 'Recuperación', 'Exitoso', 'Contraseña actualizada.');
        return true;
    } else {
        return "Código inválido o ya utilizado.";
    }
}


//  FUNCIONES DE SESIÓN Y EVENTOS


function crearSesion($conn, $idUsuario) {
    $sql = "INSERT INTO sesion (idUsuario, estado) VALUES (?, 'Activa')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();

    session_start();
    $_SESSION['idUsuario'] = $idUsuario;
}

function cerrarSesion($conn, $idUsuario) {
    $sql = "UPDATE sesion SET estado = 'Cerrada', fechaFin = NOW() WHERE idUsuario = ? AND estado = 'Activa'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();

    session_start();
    session_unset();
    session_destroy();
}

function registrarEvento($conn, $idUsuario, $evento, $resultado, $descripcion) {
    $sql = "INSERT INTO registroeventos (idUsuario, evento, resultado, descripcion)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $idUsuario, $evento, $resultado, $descripcion);
    $stmt->execute();
}

// ================================
// 🔎 Obtener usuario por correo (ÚNICA versión)
// ================================
function obtenerUsuario($conn, $correo) {
    $sql = "SELECT idUsuario, correo, contrasena, tipo, idRelacionado, estado, intentosFallidos
            FROM usuario
            WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ================================
// 🔐 Es administrador (según tu BD)
// Regla:
//  - usuario.tipo = 'Empleado'
//  - hay un registro en EMPLEADO con el MISMO correo y Puesto='Administrador'
// ================================
function esAdmin($conn, $idUsuario) {
    $sql = "SELECT u.tipo, u.correo, e.Puesto
            FROM usuario u
            LEFT JOIN empleado e ON e.Correo = u.correo
            WHERE u.idUsuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) return false;
    if ($res['tipo'] !== 'Empleado') return false;

    $puesto = $res['Puesto'] ?? '';
    return (strcasecmp($puesto, 'Administrador') === 0);
}

// ============================
//  PERMISOS SOBRE PRODUCTOS
// ============================
function asegurarTablaPermisosProductos($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS usuario_permisos_productos (
                idUsuario INT PRIMARY KEY,
                puede_registrar TINYINT(1) NOT NULL DEFAULT 0,
                puede_modificar TINYINT(1) NOT NULL DEFAULT 0,
                puede_eliminar  TINYINT(1) NOT NULL DEFAULT 0,
                puede_consultar TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT fk_perm_user_general FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) ON DELETE CASCADE
            ) ENGINE=InnoDB";
    $conn->query($sql);
}

function obtenerPermisosProductos($conn, $idUsuario) {
    asegurarTablaPermisosProductos($conn);
    $stmt = $conn->prepare("SELECT puede_registrar, puede_modificar, puede_eliminar, puede_consultar FROM usuario_permisos_productos WHERE idUsuario = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) return $res;
    // Por defecto solo consultar = 1
    return [
        'puede_registrar' => 0,
        'puede_modificar' => 0,
        'puede_eliminar'  => 0,
        'puede_consultar' => 1
    ];
}

function guardarPermisosProductos($conn, $idUsuario, $permisos) {
    asegurarTablaPermisosProductos($conn);
    $stmt = $conn->prepare(
        "INSERT INTO usuario_permisos_productos (idUsuario, puede_registrar, puede_modificar, puede_eliminar, puede_consultar)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           puede_registrar = VALUES(puede_registrar),
           puede_modificar = VALUES(puede_modificar),
           puede_eliminar  = VALUES(puede_eliminar),
           puede_consultar = VALUES(puede_consultar)"
    );
    $stmt->bind_param(
        "iiiii",
        $idUsuario,
        $permisos['puede_registrar'],
        $permisos['puede_modificar'],
        $permisos['puede_eliminar'],
        $permisos['puede_consultar']
    );
    return $stmt->execute();
}

/* ============================
   🔧 UTILIDADES PRODUCTOS (CRUD)
   ============================ */

// Categorías para selects
function obtenerCategorias($conn) {
    $sql = "SELECT idCategoria, Nombre FROM categoria ORDER BY Nombre";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// ¿ya existe un producto con ese nombre? (para evitar duplicados)
function productoExiste($conn, $nombre, $excluirId = null) {
    if ($excluirId) {
        $sql = "SELECT 1 FROM producto WHERE Nombre = ? AND idProducto <> ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nombre, $excluirId);
    } else {
        $sql = "SELECT 1 FROM producto WHERE Nombre = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->num_rows > 0;
}

// Crear producto
function crearProducto($conn, $nombre, $descripcion, $precio, $stock, $idCategoria) {
    $sql = "INSERT INTO producto (Nombre, Descripcion, Precio, Stock, idCategoria)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $idCategoria);
    $ok = $stmt->execute();

    if ($ok && isset($_SESSION['idUsuario'])) {
        registrarEvento($conn, $_SESSION['idUsuario'], 'Producto - Crear', 'Exitoso', "Se creó '$nombre'.");
    }
    return $ok;
}

// Listado con nombre de categoría
function obtenerProductos($conn) {
    $sql = "SELECT p.idProducto, p.Nombre, p.Descripcion, p.Precio, p.Stock,
                   c.Nombre AS Categoria, p.idCategoria
            FROM producto p
            INNER JOIN categoria c ON c.idCategoria = p.idCategoria
            ORDER BY p.idProducto DESC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Un producto
function obtenerProductoPorId($conn, $idProducto) {
    $sql = "SELECT idProducto, Nombre, Descripcion, Precio, Stock, idCategoria
            FROM producto WHERE idProducto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idProducto);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Actualizar producto
function actualizarProducto($conn, $id, $nombre, $descripcion, $precio, $stock, $idCategoria) {
    $sql = "UPDATE producto
               SET Nombre=?, Descripcion=?, Precio=?, Stock=?, idCategoria=?
             WHERE idProducto=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $idCategoria, $id);
    $ok = $stmt->execute();

    if ($ok && isset($_SESSION['idUsuario'])) {
        registrarEvento($conn, $_SESSION['idUsuario'], 'Producto - Editar', 'Exitoso', "Se actualizó '$nombre'.");
    }
    return $ok;
}

// ¿Se puede eliminar? (no si está en alguna venta)
function puedeEliminarProducto($conn, $idProducto) {
    $sql = "SELECT 1 FROM detalleventa WHERE idProducto = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idProducto);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 0;
}

// Eliminar producto
function eliminarProducto($conn, $idProducto) {
    if (!puedeEliminarProducto($conn, $idProducto)) return false;
    $sql = "DELETE FROM producto WHERE idProducto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idProducto);
    $ok = $stmt->execute();

    if ($ok && isset($_SESSION['idUsuario'])) {
        registrarEvento($conn, $_SESSION['idUsuario'], 'Producto - Eliminar', 'Exitoso', "id=$idProducto");
    }
    return $ok;
}

function obtenerNombreUsuario($conn, $idUsuario) {
    $sql = "SELECT tipo, idRelacionado FROM usuario WHERE idUsuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();

    if (!$info) return "";

    $tipo = $info['tipo'];
    $idRel = $info['idRelacionado'];

    if ($tipo === 'Cliente') {
        $sql2 = "SELECT Nombre FROM cliente WHERE idCliente = ?";
    } else {
        // tipo Empleado (incluye Administrador)
        $sql2 = "SELECT Nombre FROM empleado WHERE idEmpleado = ?";
    }

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $idRel);
    $stmt2->execute();
    $res = $stmt2->get_result()->fetch_assoc();

    return $res['Nombre'] ?? "";
}
// ============================
//  CLIENTE: obtener / actualizar datos de perfil
// ============================

// Obtener datos del cliente por idCliente
function obtenerClientePorId($conn, $idCliente) {
    $sql = "SELECT idCliente, Nombre, Apellido, Telefono 
            FROM cliente
            WHERE idCliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idCliente);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Actualizar datos básicos del cliente
function actualizarClientePerfil($conn, $idCliente, $nombre, $apellido, $telefono) {
    $sql = "UPDATE cliente
               SET Nombre = ?, Apellido = ?, Telefono = ?
             WHERE idCliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $idCliente);
    $ok = $stmt->execute();

    if ($ok && isset($_SESSION['idUsuario'])) {
        registrarEvento(
            $conn,
            $_SESSION['idUsuario'],
            'Cliente - Actualizar perfil',
            'Exitoso',
            "Se actualizó el perfil del cliente id=$idCliente"
        );
    }
    return $ok;
}

// Validar teléfono simple (sólo dígitos y mínimo 8)
function validarTelefono($telefono) {
    $telefono = preg_replace('/\s+/', '', $telefono); // quitar espacios
    if (!preg_match('/^[0-9]{8,15}$/', $telefono)) {
        return false;
    }
    return true;
}





?>
