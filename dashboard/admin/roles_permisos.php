<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php");
  exit();
}

function asegurarEmpleadoPorCorreo($conn, $correo, $puesto) {
  $stmt = $conn->prepare("SELECT idEmpleado, Puesto FROM empleado WHERE Correo = ? LIMIT 1");
  $stmt->bind_param("s", $correo);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();

  if ($res) {
    if ($res['Puesto'] !== $puesto) {
      $stmtUp = $conn->prepare("UPDATE empleado SET Puesto = ? WHERE idEmpleado = ?");
      $stmtUp->bind_param("si", $puesto, $res['idEmpleado']);
      $stmtUp->execute();
    }
    return (int)$res['idEmpleado'];
  }

  $stmtIns = $conn->prepare("INSERT INTO empleado (Nombre, Apellido, Puesto, Telefono, Salario, Correo) VALUES ('', '', ?, NULL, NULL, ?)");
  $stmtIns->bind_param("ss", $puesto, $correo);
  $stmtIns->execute();
  return (int)$stmtIns->insert_id;
}

function asegurarClientePorCorreo($conn, $correo) {
  $stmt = $conn->prepare("SELECT idCliente FROM cliente WHERE Correo = ? LIMIT 1");
  $stmt->bind_param("s", $correo);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  if ($res) return (int)$res['idCliente'];

  $stmtIns = $conn->prepare("INSERT INTO cliente (Nombre, Apellido, Telefono, Direccion, Correo) VALUES ('', '', NULL, NULL, ?)");
  $stmtIns->bind_param("s", $correo);
  $stmtIns->execute();
  return (int)$stmtIns->insert_id;
}

// Usuarios disponibles
$sqlUsuarios = "SELECT 
                  u.idUsuario,
                  u.correo,
                  u.tipo,
                  u.estado,
                  u.fechaCreacion,
                  u.idRelacionado,
                  COALESCE(c.Nombre, e.Nombre) AS Nombre,
                  COALESCE(c.Apellido, e.Apellido) AS Apellido
                FROM usuario u
                LEFT JOIN cliente  c ON (u.tipo = 'Cliente'  AND c.idCliente  = u.idRelacionado)
                LEFT JOIN empleado e ON (u.tipo = 'Empleado' AND e.idEmpleado = u.idRelacionado)
                WHERE u.tipo <> 'Cliente'
                ORDER BY u.correo";
$resUsuarios = $conn->query($sqlUsuarios);
$usuarios = $resUsuarios ? $resUsuarios->fetch_all(MYSQLI_ASSOC) : [];

$mensaje = $_GET['msg'] ?? '';
$tipoMensaje = $_GET['type'] ?? '';

$idSeleccionado = (int)($_GET['idUsuario'] ?? ($_POST['idUsuario'] ?? 0));
$idsDisponibles = array_map(fn($u) => (int)$u['idUsuario'], $usuarios);
if ($idSeleccionado && !in_array($idSeleccionado, $idsDisponibles, true)) {
  $idSeleccionado = 0;
}
$permisosActuales = $idSeleccionado ? obtenerPermisosProductos($conn, $idSeleccionado) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idSeleccionado > 0) {
  $rolNuevo = $_POST['rol'] ?? '';
  $permisos = [
    'puede_registrar' => isset($_POST['perm_registrar']) ? 1 : 0,
    'puede_modificar' => isset($_POST['perm_modificar']) ? 1 : 0,
    'puede_eliminar'  => isset($_POST['perm_eliminar'])  ? 1 : 0,
    'puede_consultar' => isset($_POST['perm_consultar']) ? 1 : 0,
  ];

  // Coherencia: si puede eliminar o modificar, debe poder consultar
  if ($permisos['puede_eliminar'] || $permisos['puede_modificar']) {
    $permisos['puede_consultar'] = 1;
  }

  // Validar usuario
  $stmtUser = $conn->prepare("SELECT idUsuario, correo, tipo, idRelacionado FROM usuario WHERE idUsuario = ? AND tipo <> 'Cliente'");
  $stmtUser->bind_param("i", $idSeleccionado);
  $stmtUser->execute();
  $usuarioSel = $stmtUser->get_result()->fetch_assoc();

  if (!$usuarioSel) {
    header("Location: roles_permisos.php?msg=" . urlencode("Usuario no encontrado.") . "&type=error");
    exit();
  }

  if (!in_array($rolNuevo, ['Empleado', 'Administrador'], true)) {
    header("Location: roles_permisos.php?idUsuario={$idSeleccionado}&msg=" . urlencode("Rol inválido.") . "&type=error");
    exit();
  }

  $idRelacionadoNuevo = $usuarioSel['idRelacionado'];
  $tipoNuevo = 'Empleado';

  $puesto = $rolNuevo === 'Administrador' ? 'Administrador' : 'Empleado';
  $idRelacionadoNuevo = asegurarEmpleadoPorCorreo($conn, $usuarioSel['correo'], $puesto);

  $stmtUp = $conn->prepare("UPDATE usuario SET tipo = ?, idRelacionado = ? WHERE idUsuario = ?");
  $stmtUp->bind_param("sii", $tipoNuevo, $idRelacionadoNuevo, $idSeleccionado);
  $okRol = $stmtUp->execute();

  $okPerm = $okRol ? guardarPermisosProductos($conn, $idSeleccionado, $permisos) : false;

  if ($okRol && $okPerm) {
    header("Location: roles_permisos.php?idUsuario={$idSeleccionado}&msg=" . urlencode("Rol y permisos actualizados.") . "&type=exito");
    exit();
  } else {
    $mensaje = "No se pudieron guardar los cambios.";
    $tipoMensaje = "error";
    $permisosActuales = $permisos;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador · Roles y permisos</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="admin-logo">
      Super Limpio
      <span>Panel de administración</span>
    </div>
    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-item">
        <span class="label">Productos</span>
      </a>
      <a href="usuarios.php" class="admin-nav-item">
        <span class="label">Usuarios</span>
      </a>
      <a href="roles_permisos.php" class="admin-nav-item is-active">
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
        <h1>Roles y permisos</h1>
        <p>Define el rol y los permisos de productos por usuario. Solo se muestran usuarios Empleado/Administrador (no clientes)..</p>
        <div class="admin-header-meta">
          <?= count($usuarios) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?> disponible<?= count($usuarios) === 1 ? '' : 's' ?>.
        </div>
      </div>
    </div>

    <?php if ($mensaje): ?>
      <div class="alert <?= htmlspecialchars($tipoMensaje) ?>" style="margin-bottom:12px;">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <?php if (empty($usuarios)): ?>
      <section class="admin-card">
        <div style="padding:18px; color:#9ca3af;">No hay usuarios para configurar.</div>
      </section>
    <?php else: ?>
      <section class="admin-card">
        <form method="get" class="admin-filters-form" style="padding:12px 16px;">
          <div class="field-group" style="min-width:260px;">
            <span class="field-label">Selecciona un usuario</span>
            <select name="idUsuario" class="field-select" onchange="this.form.submit()">
              <option value="" <?= $idSeleccionado ? '' : 'selected' ?> disabled hidden>Elige un usuario</option>
              <?php foreach ($usuarios as $u): ?>
                <?php
                $nombreComp = trim(($u['Nombre'] ?? '') . ' ' . ($u['Apellido'] ?? ''));
                $label = $nombreComp !== '' ? "{$nombreComp} ({$u['correo']})" : $u['correo'];
                ?>
                <option value="<?= $u['idUsuario'] ?>" <?= $u['idUsuario'] == $idSeleccionado ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>

        <?php if ($idSeleccionado && $permisosActuales !== null): ?>
          <?php
          $userMatches = array_values(array_filter($usuarios, fn($u) => (int)$u['idUsuario'] === $idSeleccionado));
          $userNow = $userMatches[0] ?? ($usuarios[0] ?? null);
          if ($userNow) {
            $idSeleccionado = (int)$userNow['idUsuario'];
          }
          ?>
          <div style="padding:16px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px; align-items:start;">
              <div style="border:1px solid #e5e7eb; border-radius:14px; background:#f9fafb; padding:14px;">
                <div style="font-weight:700; margin-bottom:8px;">Información del usuario</div>
                <?php if ($userNow): ?>
                  <div style="display:flex; flex-direction:column; gap:6px; font-size:14px; color:#111827;">
                    <div><strong>Correo:</strong> <?= htmlspecialchars($userNow['correo']) ?></div>
                    <div><strong>Nombre:</strong> <?= htmlspecialchars(trim(($userNow['Nombre'] ?? '') . ' ' . ($userNow['Apellido'] ?? '')) ?: '(sin nombre)') ?></div>
                    <div><strong>Rol actual:</strong> <?= esAdmin($conn, $idSeleccionado) ? 'Administrador' : htmlspecialchars($userNow['tipo']) ?></div>
                    <div><strong>Estado:</strong> <?= htmlspecialchars($userNow['estado']) ?></div>
                    <div><strong>Creado:</strong> <?= htmlspecialchars($userNow['fechaCreacion']) ?></div>
                  </div>
                <?php else: ?>
                  <div style="color:#9ca3af;">Usuario no encontrado.</div>
                <?php endif; ?>
              </div>

              <form method="post" class="admin-filters-form" style="flex-direction:column; gap:14px;">
                <input type="hidden" name="idUsuario" value="<?= $idSeleccionado ?>">

                <div class="field-group" style="min-width:220px;">
                  <span class="field-label">Rol</span>
                  <select name="rol" class="field-select">
                    <option value="Empleado" <?= $userNow && $userNow['tipo'] === 'Empleado' && !esAdmin($conn, $idSeleccionado) ? 'selected' : '' ?>>Empleado</option>
                    <option value="Administrador" <?= $userNow && esAdmin($conn, $idSeleccionado) ? 'selected' : '' ?>>Administrador</option>
                  </select>
                </div>

                <div class="field-group" style="width:100%; margin-top:4px;">
                  <span class="field-label">Permisos</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(2,minmax(220px,1fr)); gap:12px 10px; width:100%;">
                  <label class="checkbox-pill">
                    <input type="checkbox" name="perm_registrar" <?= $permisosActuales['puede_registrar'] ? 'checked' : '' ?>>
                    <span>Registrar productos</span>
                  </label>
                  <label class="checkbox-pill">
                    <input type="checkbox" name="perm_modificar" <?= $permisosActuales['puede_modificar'] ? 'checked' : '' ?>>
                    <span>Modificar productos</span>
                  </label>
                  <label class="checkbox-pill">
                    <input type="checkbox" name="perm_eliminar" <?= $permisosActuales['puede_eliminar'] ? 'checked' : '' ?>>
                    <span>Eliminar productos</span>
                  </label>
                  <label class="checkbox-pill">
                    <input type="checkbox" name="perm_consultar" <?= $permisosActuales['puede_consultar'] ? 'checked' : '' ?>>
                    <span>Consultar productos</span>
                  </label>
                </div>

                <div class="admin-form-actions" style="justify-content:center; width:100%; margin-top:12px;">
                  <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
              </form>
            </div>
          </div>
        <?php elseif (empty($usuarios)): ?>
          <div style="padding:16px; color:#9ca3af;">No hay usuarios disponibles.</div>
        <?php else: ?>
          <div style="padding:16px; color:#6b7280;">Selecciona un usuario para editar su rol y permisos.</div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
