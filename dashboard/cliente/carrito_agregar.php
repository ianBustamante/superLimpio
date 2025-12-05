<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// Verificar cliente
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];
$sql = "SELECT tipo, idRelacionado FROM usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info || $info['tipo'] !== 'Cliente') {
    header("Location: ../../modules/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$idProducto = isset($_POST['idProducto']) ? (int)$_POST['idProducto'] : 0;
if ($idProducto <= 0) {
    header("Location: index.php?msg=" . urlencode("Producto inválido.") . "&type=error");
    exit();
}

// Obtener producto
$sqlP = "SELECT idProducto, Nombre, Precio, Stock FROM Producto WHERE idProducto = ? AND Stock > 0";
$stmtP = $conn->prepare($sqlP);
$stmtP->bind_param("i", $idProducto);
$stmtP->execute();
$prod = $stmtP->get_result()->fetch_assoc();

if (!$prod) {
    header("Location: index.php?msg=" . urlencode("Producto sin stock o no encontrado.") . "&type=error");
    exit();
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$id = $prod['idProducto'];

if (isset($_SESSION['carrito'][$id])) {
    $nuevaCantidad = $_SESSION['carrito'][$id]['cantidad'] + 1;
    if ($nuevaCantidad > $prod['Stock']) {
        $nuevaCantidad = $prod['Stock'];
    }
    $_SESSION['carrito'][$id]['cantidad'] = $nuevaCantidad;
} else {
    $_SESSION['carrito'][$id] = [
        'idProducto' => $id,
        'nombre'     => $prod['Nombre'],
        'precio'     => (float)$prod['Precio'],
        'cantidad'   => 1,
        'stock'      => (int)$prod['Stock'],
    ];
}

header("Location: index.php?msg=" . urlencode("Producto agregado al carrito.") . "&type=exito");
exit();
