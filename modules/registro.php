<?php
require_once '../includes/functions.php';

$mensaje = "";
$tipoMensaje = "";
$autoRedirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = trim($_POST['correo'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirmar = trim($_POST['passwordConfirm'] ?? '');
    $tipo = 'Cliente';

    if (!validarCorreo($correo)) {
        $mensaje = "Correo inválido.";
        $tipoMensaje = "error";

    } elseif (!validarPassword($password)) {
        $mensaje = "Contraseña insegura. Debe tener mínimo 8 caracteres, una mayúscula y un número.";
        $tipoMensaje = "error";

    } elseif ($password !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipoMensaje = "error";

    } elseif (usuarioExiste($conn, $correo)) {
        $mensaje = "El correo ya está registrado.";
        $tipoMensaje = "error";

    } else {
        if (registrarUsuario($conn, $correo, $password, $tipo)) {
            $mensaje = "¡Registro exitoso! Redirigiendo al inicio de sesión...";
            $tipoMensaje = "exito";
            $autoRedirect = true;

        } else {
            $mensaje = "Error al registrar usuario. Intenta nuevamente.";
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro | Super Limpio</title>

  <!-- Estilos globales -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- Estilos específicos de registro -->
  <link rel="stylesheet" href="../assets/css/register.css">
</head>

<body>

<div class="auth-page">

  <header class="login-header">
    <div class="header-icon">
      <svg viewBox="0 0 24 24">
        <path d="M12 12a5 5 0 1 0-5-5a5 5 0 0 0 5 5m0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5Z"/>
      </svg>
    </div>
    <h1>Crear Cuenta</h1>
    <p>Regístrate para acceder al sistema</p>
  </header>

  <?php if($mensaje): ?>
    <div class="alert <?= $tipoMensaje; ?>"><?= $mensaje; ?></div>
  <?php endif; ?>

  <!-- 👇 IMPORTANTE: id="registerForm" para que lo detecte validation.js -->
  <form id="registerForm" method="POST">

    <div class="input-group">
      <label class="label" for="correo">Correo</label>
      <div class="input-wrapper">
        <span class="icon">
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
        >
      </div>
    </div>

    <div class="input-group">
      <label class="label" for="password">Contraseña</label>
      <div class="input-wrapper">
        <span class="icon">
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
        >
        <!-- 👇 id que usa validation.js -->
        <button class="toggle-pass" id="togglePass1" type="button">Mostrar</button>
      </div>
      <div class="helper">Mínimo 8 caracteres, una mayúscula y un número.</div>
    </div>

    <div class="input-group">
      <label class="label" for="passwordConfirm">Confirmar contraseña</label>
      <div class="input-wrapper">
        <span class="icon">
          <svg viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/>
          </svg>
        </span>
        <input
          class="input"
          type="password"
          name="passwordConfirm"
          id="passwordConfirm"
          placeholder="••••••••"
          required
        >
        <!-- 👇 id que usa validation.js -->
        <button class="toggle-pass" id="togglePass2" type="button">Mostrar</button>
      </div>
    </div>

    <button class="btn-primary" type="submit">Registrarme</button>
    <a href="login.php" class="link">¿Ya tienes cuenta? Iniciar sesión</a>

  </form>
</div>

<?php if ($autoRedirect): ?>
<script>
  setTimeout(() => { window.location.href = 'login.php'; }, 3000);
</script>
<?php endif; ?>


<script src="../assets/js/validation.js"></script>

</body>
</html>
