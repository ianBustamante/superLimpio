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
function registrarUsuario($conn, $correo, $password, $tipo = 'Cliente') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuario (correo, contrasena, tipo, estado) VALUES (?, ?, ?, 'Activo')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $correo, $hash, $tipo);

    if ($stmt->execute()) {
        $idUsuario = $stmt->insert_id;
        registrarEvento($conn, $idUsuario, 'Registro', 'Exitoso', 'Usuario registrado correctamente.');
        return true;
    } else {
        return false;
    }
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



// Obtener datos de usuario
function obtenerUsuario($conn, $correo) {
    $sql = "SELECT * FROM usuario WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
