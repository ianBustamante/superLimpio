<?php
require_once '../includes/functions.php';

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);

    if (empty($correo) || empty($password)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = 'error';
    } else {
        $loginResult = iniciarSesion($conn, $correo, $password);

        if ($loginResult === true) {
            $usuario = obtenerUsuario($conn, $correo);

            if ($usuario['tipo'] === 'Cliente') {
                header("Location: ../dashboard/cliente/index.php");
            } else {
                // Por ahora todos los Empleado van al dashboard de empleado
                header("Location: ../dashboard/empleado/index.php");
            }
            exit();
        } else {
            $mensaje = $loginResult;
            $tipoMensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>

<div class="login-wrapper">
  <!-- Lado izquierdo (ilustración / bienvenida) -->
  <section class="login-hero">
    <div class="avatar" aria-hidden="true">
      <!-- simple avatar abstracto -->
      <svg viewBox="0 0 64 64" width="92" height="92">
        <circle cx="32" cy="24" r="12" fill="#fff"/>
        <path d="M8,60a24,18 0 1,1 48,0" fill="#fff"/>
      </svg>
    </div>
    <h1>ACCESO</h1>
    <p>Bienvenido al sistema de productos de limpieza.</p>
  </section>

  <!-- Lado derecho (formulario) -->
  <section class="login-form">
    <h2 class="form-title">¡Bienvenido!</h2>

    <?php if($mensaje): ?>
      <div class="alert <?= $tipoMensaje; ?>"><?= $mensaje; ?></div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="">
      <div class="input-group input-icon">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2m0 4l-8 5L4 8V6l8 5l8-5Z"/></svg>
        </span>
        <label class="label" for="correo">Correo</label>
        <input class="input" type="email" name="correo" id="correo" placeholder="tucorreo@dominio.com" required />
      </div>

      <div class="input-group input-icon">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/></svg>
        </span>
        <label class="label" for="password">Contraseña</label>
        <input class="input" type="password" name="password" id="password" placeholder="••••••••" required />
        <button id="togglePass" class="toggle-pass" aria-label="mostrar/ocultar">Mostrar</button>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Iniciar sesión</button>
        <a class="link" href="recuperar.php">Olvidé mi contraseña</a>
        <a class="link" href="registro.php">Registrarme</a>
      </div>

      
    </form>
  </section>
</div>

<script src="../assets/js/validation.js"></script>
</body>
</html>
