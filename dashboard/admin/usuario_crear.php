<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo'] ?? '');
    $tipoForm = $_POST['tipo'] ?? 'Cliente';  // Cliente | Empleado | Administrador
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['passwordConfirm'] ?? '');
    $permisos = [
        'puede_registrar' => isset($_POST['perm_registrar']) ? 1 : 0,
        'puede_modificar' => isset($_POST['perm_modificar']) ? 1 : 0,
        'puede_eliminar'  => isset($_POST['perm_eliminar'])  ? 1 : 0,
        'puede_consultar' => isset($_POST['perm_consultar']) ? 1 : 0,
    ];
    if ($permisos['puede_eliminar'] || $permisos['puede_modificar']) {
        $permisos['puede_consultar'] = 1;
    }

    if (!validarCorreo($correo)) {
        $mensaje = "Correo inválido.";
        $tipoMensaje = "error";

    } elseif (usuarioExiste($conn, $correo)) {
        $mensaje = "El correo ya está registrado.";
        $tipoMensaje = "error";

    } elseif (!validarPassword($password)) {
        $mensaje = "La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.";
        $tipoMensaje = "error";

    } elseif ($password !== $confirm) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipoMensaje = "error";

    } else {
        $ok = false;
        $idUsuarioNuevo = null;

        if ($tipoForm === 'Cliente') {
            // Usa la función que también crea el Cliente y enlaza idRelacionado
            $ok = registrarUsuario($conn, $correo, $password, 'Cliente');

        } else {
            // Empleado o Administrador -> tipo en Usuario es EMPLEADO
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $puesto = ($tipoForm === 'Administrador') ? 'Administrador' : 'Empleado';

            // 1) Crear registro en EMPLEADO
            $sqlEmp = "INSERT INTO empleado (Nombre, Apellido, Puesto, Telefono, Salario, Correo)
                       VALUES ('', '', ?, NULL, NULL, ?)";
            $stmtEmp = $conn->prepare($sqlEmp);
            $stmtEmp->bind_param("ss", $puesto, $correo);
            $okEmp = $stmtEmp->execute();

            if ($okEmp) {
                $idEmpleado = $stmtEmp->insert_id;

                // 2) Crear registro en USUARIO enlazado al empleado
                $sqlUsr = "INSERT INTO usuario (correo, contrasena, tipo, idRelacionado, estado)
                           VALUES (?, ?, 'Empleado', ?, 'Activo')";
                $stmtUsr = $conn->prepare($sqlUsr);
                $stmtUsr->bind_param("ssi", $correo, $hash, $idEmpleado);
                $ok = $stmtUsr->execute();
                if ($ok) {
                    $idUsuarioNuevo = $stmtUsr->insert_id;
                }
            } else {
                $ok = false;
            }
        }

        if ($ok && $idUsuarioNuevo && $tipoForm !== 'Cliente') {
            // Guardar permisos para empleados/administradores
            $ok = guardarPermisosProductos($conn, $idUsuarioNuevo, $permisos);
        }

        if ($ok) {
            header("Location: usuarios.php?msg=" . urlencode("Usuario creado correctamente.") . "&type=exito");
            exit();
        } else {
            $mensaje = "Error al crear el usuario.";
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador · Nuevo usuario</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-layout">

  <!-- SIDEBAR ADMIN (sin includes) -->
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
        <h1>Nuevo usuario</h1>
        <p>Crear una cuenta de acceso al sistema.</p>
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
          <input type="email" name="correo" class="field-input" required>
        </div>

        <div class="field-group">
          <span class="field-label">Tipo de usuario</span>
          <select name="tipo" class="field-select" id="tipo-usuario">
            <option value="Cliente">Cliente</option>
            <option value="Empleado">Empleado</option>
            <option value="Administrador">Administrador</option>
          </select>
        </div>

        <div class="field-group">
          <span class="field-label">Contraseña</span>
          <input type="password" name="password" class="field-input" required>
          <small class="helper">Mínimo 8 caracteres, una mayúscula y un número.</small>
        </div>

        <div class="field-group">
          <span class="field-label">Confirmar contraseña</span>
          <input type="password" name="passwordConfirm" class="field-input" required>
        </div>

        <div id="permisos-wrapper" style="display:none; margin-top:12px;">
          <div class="field-label" style="font-size:12px; color:#6b7280; margin-bottom:6px;">Permisos sobre productos</div>
          <div style="display:grid; grid-template-columns:repeat(2,minmax(220px,1fr)); gap:12px 10px;">
            <label class="checkbox-pill">
              <input type="checkbox" name="perm_registrar">
              <span>Registrar productos</span>
            </label>
            <label class="checkbox-pill">
              <input type="checkbox" name="perm_modificar">
              <span>Modificar productos</span>
            </label>
            <label class="checkbox-pill">
              <input type="checkbox" name="perm_eliminar">
              <span>Eliminar productos</span>
            </label>
            <label class="checkbox-pill">
              <input type="checkbox" name="perm_consultar" checked onclick="if(this.checked===false){this.checked=true;return false;}">
              <span>Consultar productos</span>
            </label>
          </div>
        </div>

        <div class="admin-form-actions">
          <a href="usuarios.php" class="btn btn-ghost">Cancelar</a>
          <button type="submit" class="btn btn-primary">Crear usuario</button>
        </div>
      </form>
    </section>

  </main>
</div>

<script>
  const tipoSelect = document.getElementById('tipo-usuario');
  const permisosWrapper = document.getElementById('permisos-wrapper');
  const togglePermisos = () => {
    const val = tipoSelect.value;
    permisosWrapper.style.display = (val === 'Empleado' || val === 'Administrador') ? 'block' : 'none';
  };
  tipoSelect.addEventListener('change', togglePermisos);
  togglePermisos();
</script>
</body>
</html>
