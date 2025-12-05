<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// ==========================
// SEGURIDAD: sesión y rol
// ==========================
if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idAdmin = (int)$_SESSION['idUsuario'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ==========================
// VALIDAR ID
// ==========================
if ($id <= 0) {
    header("Location: usuarios.php?msg=" . urlencode("ID de usuario inválido.") . "&type=error");
    exit();
}

// ==========================
// NO PERMITIR AUTO-DESACTIVADO
// ==========================
if ($id === $idAdmin) {
    header("Location: usuarios.php?msg=" . urlencode("No puedes desactivar tu propia cuenta.") . "&type=error");
    exit();
}

// ==========================
// VERIFICAR QUE EL USUARIO EXISTA
// ==========================
$sqlCheck = "SELECT idUsuario FROM usuario WHERE idUsuario = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$res = $stmtCheck->get_result();

if ($res->num_rows === 0) {
    header("Location: usuarios.php?msg=" . urlencode("El usuario no existe.") . "&type=error");
    exit();
}

// ==========================
// DESACTIVAR USUARIO
// ==========================
$sql = "UPDATE usuario SET estado = 'Inactivo' WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$ok = $stmt->execute();

if ($ok) {
    registrarEvento($conn, $idAdmin, 'Usuario', 'Exitoso', "Se desactivó el usuario ID $id");
    $msg  = "Usuario desactivado correctamente.";
    $type = "exito";
} else {
    $msg  = "No se pudo desactivar el usuario.";
    $type = "error";
}

// ==========================
// REDIRECCIÓN
// ==========================
header("Location: usuarios.php?msg=" . urlencode($msg) . "&type=" . urlencode($type));
exit();
