<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: usuarios.php?msg=" . urlencode("Usuario no encontrado.") . "&type=error");
    exit();
}

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo'] ?? '');
    $tipo     = $_POST['tipo'] ?? $usuario['tipo'];
    $estado   = $_POST['estado'] ?? $usuario['estado'];
    $intentos = (int)($_POST['intentosFallidos'] ?? 0);
    $passNew  = trim($_POST['passwordNew'] ?? '');

    if (!validarCorreo($correo)) {
        $mensaje = "Correo inválido.";
        $tipoMensaje = "error";

    } elseif ($correo !== $usuario['correo'] && usuarioExiste($conn, $correo)) {
        $mensaje = "El correo ya está en uso por otro usuario.";
        $tipoMensaje = "error";

    } elseif ($passNew !== '' && !validarPassword($passNew)) {
        $mensaje = "La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.";
        $tipoMensaje = "error";

    } else {
        // Actualizar datos básicos
        $sqlUp = "UPDATE usuario
                  SET correo = ?, tipo = ?, estado = ?, intentosFallidos = ?
                  WHERE idUsuario = ?";
        $stmtUp = $conn->prepare($sqlUp);
        $stmtUp->bind_param("sssii", $correo, $tipo, $estado, $intentos, $id);
        $ok = $stmtUp->execute();

        // Si hay nueva contraseña, actualizarla
        if ($ok && $passNew !== '') {
            $hash = password_hash($passNew, PASSWORD_DEFAULT);
            $sqlP = "UPDATE usuario SET contrasena = ? WHERE idUsuario = ?";
            $stmtP = $conn->prepare($sqlP);
            $stmtP->bind_param("si", $hash, $id);
            $ok = $stmtP->execute();
        }

        if ($ok) {
            header("Location: usuarios.php?msg=" . urlencode("Usuario actualizado correctamente.") . "&type=exito");
            exit();
        } else {
            $mensaje = "Error al actualizar el usuario.";
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador · Editar usuario</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-layout">

  <!-- SIDEBAR ADMIN (sin include) -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      Super Limpio
      <span>Panel de administración</span>
    </div>

    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-item">
        <span class="label">Productos</span>
      </a>

      <a href="usuarios.php" class="admin-nav-item is-active">
        <span class="label">Usuarios</span>
      </a>

      <a href="roles_permisos.php" class="admin-nav-item">
        <span class="label">Roles y permisos</span>
      </a>

      <a href="reportes_ventas.php" class="admin-nav-item">
        <span class="label">Reportes de ventas</span>
      </a>

      <a href="../../modules/logout.php" class="admin-nav-item">
        <span class="label">Cerrar sesión</span>
      </a>
    </nav>
  </aside>

  <main class="admin-main">

    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Editar usuario</h1>
        <p>Modificar datos de la cuenta #<?= $usuario['idUsuario'] ?></p>
      </div>
    </div>

    <?php if ($mensaje): ?>
      <div class="alert <?= htmlspecialchars($tipoMensaje); ?>" style="margin-bottom:12px;">
        <?= htmlspecialchars($mensaje); ?>
      </div>
    <?php endif; ?>

    <section class="admin-card">
      <form method="post" class="admin-form">

        <div class="field-group">
          <span class="field-label">Correo</span>
          <input type="email" name="correo" class="field-input" required
                 value="<?= htmlspecialchars($usuario['correo']) ?>">
        </div>

        <div class="field-group">
          <span class="field-label">Tipo</span>
          <select name="tipo" class="field-select">
            <option value="Cliente"  <?= $usuario['tipo'] === 'Cliente'  ? 'selected' : '' ?>>Cliente</option>
            <option value="Empleado" <?= $usuario['tipo'] === 'Empleado' ? 'selected' : '' ?>>Empleado</option>
          </select>
        </div>

        <div class="field-group">
          <span class="field-label">Estado</span>
          <select name="estado" class="field-select">
            <option value="Activo"    <?= $usuario['estado'] === 'Activo'    ? 'selected' : '' ?>>Activo</option>
            <option value="Inactivo"  <?= $usuario['estado'] === 'Inactivo'  ? 'selected' : '' ?>>Inactivo</option>
            <option value="Bloqueado" <?= $usuario['estado'] === 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
          </select>
        </div>

        <div class="field-group">
          <span class="field-label">Intentos fallidos</span>
          <input type="number" min="0" name="intentosFallidos" class="field-input"
                 value="<?= (int)$usuario['intentosFallidos'] ?>">
        </div>

        <hr class="admin-separator">

        <div class="field-group">
          <span class="field-label">Nueva contraseña (opcional)</span>
          <input type="password" name="passwordNew" class="field-input">
          <small class="helper">Déjalo vacío para no cambiar la contraseña.</small>
        </div>

        <div class="admin-form-actions">
          <a href="usuarios.php" class="btn btn-ghost">Cancelar</a>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>

      </form>
    </section>

  </main>
</div>

</body>
</html>
