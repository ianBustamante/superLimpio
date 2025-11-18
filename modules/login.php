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

            if (esAdmin($conn, $_SESSION['idUsuario'])) {
                header("Location: ../dashboard/admin/index.php");
                exit;
            }

            if ($usuario['tipo'] === 'Empleado') {
                header("Location: ../dashboard/empleado/index.php");
                exit;
            }

            header("Location: ../dashboard/cliente/index.php");
            exit;
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
  <title>Iniciar Sesión | Super Limpio</title>

  <!-- Estilos globales -->
  <link rel="stylesheet" href="../assets/css/style.css" />
  <!-- Estilos específicos para login/registro -->
  <link rel="stylesheet" href="../assets/css/auth.css" />
</head>
<body>

<div class="login-page">
  
  <!-- Columna izquierda: formulario -->
  <section class="login-main">
    <header class="login-header">
      <div class="logo-icon"></div>
      <h1>Bienvenido</h1>
      <p>Ingresa tus datos para acceder al sistema.</p>
    </header>

    <?php if($mensaje): ?>
      <div class="alert <?= $tipoMensaje; ?>"><?= $mensaje; ?></div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="">
      <!-- Correo -->
      <div class="input-group">
        <label class="input-label" for="correo">Correo electrónico</label>
        <div class="input-wrapper">
          <span class="input-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2m0 4l-8 5L4 8V6l8 5l8-5Z"/>
            </svg>
          </span>
          <input
            class="input"
            type="email"
            name="correo"
            id="correo"
            placeholder="tucorreo@dominio.com"
            required
          />
        </div>
      </div>

      <!-- Contraseña -->
      <div class="input-group">
        <label class="input-label" for="password">Contraseña</label>
        <div class="input-wrapper">
          <span class="input-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/>
            </svg>
          </span>
          <input
            class="input"
            type="password"
            name="password"
            id="password"
            placeholder="••••••••"
            required
          />
          <button id="togglePass" class="toggle-pass" aria-label="mostrar/ocultar">
            Mostrar
          </button>
        </div>
        <div class="extra-actions">
          <span></span>
          <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
        </div>
      </div>

      <!-- Botón principal -->
      <button class="btn-primary" type="submit">Iniciar sesión</button>

      <!-- Enlaces inferiores -->
      <div class="links-bottom">
        <a href="registro.php">Crear una cuenta nueva</a>
      </div>
    </form>
  </section>

  <!-- Columna derecha: ilustración / fondo abstracto -->
  <section class="login-illustration">
    <div class="image-box">
      <img src="../assets/img/login.png" alt="Imagen decorativa del login">
    </div>
  </section>

</div>

<script src="../assets/js/validation.js"></script>
</body>
</html>
