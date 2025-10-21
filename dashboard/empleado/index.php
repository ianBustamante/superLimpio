<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$id = $_SESSION['idUsuario'];
// Confirmar que sea Empleado
$stmt = $conn->prepare("SELECT correo, tipo FROM usuario WHERE idUsuario=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u || $u['tipo'] !== 'Empleado') {
    header("Location: ../../modules/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Empleado</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <h1>Bienvenido, empleado: <?= htmlspecialchars($u['correo']) ?></h1>
  <p>Este es un dashboard de prueba.</p>
  <p><a href="../../modules/logout.php">Cerrar sesión</a></p>
</body>
</html>
