<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// Solo administradores
if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

// Mensaje opcional (después de crear/editar/desactivar)
$mensaje = $_GET['msg'] ?? '';
$tipoMensaje = $_GET['type'] ?? '';

// Cargar usuarios con nombre (Cliente o Empleado)
$sql = "SELECT 
            u.idUsuario,
            u.correo,
            u.tipo,
            u.estado,
            u.intentosFallidos,
            u.fechaCreacion,
            u.idRelacionado,
            COALESCE(c.Nombre, e.Nombre)  AS Nombre,
            COALESCE(c.Apellido, e.Apellido) AS Apellido
        FROM usuario u
        LEFT JOIN cliente  c ON (u.tipo = 'Cliente'  AND c.idCliente  = u.idRelacionado)
        LEFT JOIN empleado e ON (u.tipo = 'Empleado' AND e.idEmpleado = u.idRelacionado)
        ORDER BY u.fechaCreacion DESC";

$res = $conn->query($sql);
$usuarios = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador · Usuarios</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-layout">

  <!-- SIDEBAR ADMIN -->
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

  <!-- CONTENIDO PRINCIPAL -->
  <main class="admin-main">

    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Usuarios</h1>
        <p>Gestión de cuentas de acceso al sistema.</p>
        <div class="admin-header-meta">
          <?= count($usuarios) ?> usuario<?= count($usuarios) === 1 ? '' : 's' ?> registrado<?= count($usuarios) === 1 ? '' : 's' ?>.
        </div>
      </div>
      <div>
        <a href="usuario_crear.php" class="btn btn-primary">+ Nuevo usuario</a>
      </div>
    </div>

    <?php if ($mensaje): ?>
      <div class="alert <?= htmlspecialchars($tipoMensaje) ?>" style="margin-bottom:12px;">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <section class="admin-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
          <tr>
            <th>ID</th>
            <th>Correo</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Intentos</th>
            <th>Creado</th>
            <th>Acciones</th>
          </tr>
          </thead>
          <tbody>

          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td class="c-center">#<?= $u['idUsuario'] ?></td>
              <td><?= htmlspecialchars($u['correo']) ?></td>
              <td>
                <?php
                $nom = trim(($u['Nombre'] ?? '') . ' ' . ($u['Apellido'] ?? ''));
                echo $nom !== '' ? htmlspecialchars($nom) : '<span style="color:#9ca3af;">(sin información)</span>';
                ?>
              </td>
              <td class="c-center"><?= htmlspecialchars($u['tipo']) ?></td>
              <td class="c-center"><?= htmlspecialchars($u['estado']) ?></td>
              <td class="c-center"><?= (int)$u['intentosFallidos'] ?></td>
              <td class="c-center"><?= htmlspecialchars($u['fechaCreacion']) ?></td>
              <td class="c-center">
                <a href="usuario_editar.php?id=<?= $u['idUsuario'] ?>" class="btn-link">
                  Editar
                </a>
                &nbsp;|&nbsp;
                <?php if ($u['estado'] === 'Activo'): ?>
                  <a href="usuario_eliminar.php?id=<?= $u['idUsuario'] ?>"
                     class="btn-link btn-link--danger"
                     onclick="return confirm('¿Desactivar este usuario?');">
                    Desactivar
                  </a>
                <?php else: ?>
                  <span style="color:#9ca3af;">Inactivo</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($usuarios)): ?>
            <tr>
              <td colspan="8" style="text-align:center; padding:16px; color:#9ca3af;">
                No hay usuarios registrados.
              </td>
            </tr>
          <?php endif; ?>

          </tbody>
        </table>
      </div>
    </section>

  </main>
</div>

</body>
</html>
