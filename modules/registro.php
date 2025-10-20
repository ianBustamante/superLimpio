<?php
require_once '../includes/functions.php';

$mensaje = "";
$tipoMensaje = "";
$autoRedirect = false; // bandera para redirigir tras registro exitoso

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = trim($_POST['correo'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirmar = trim($_POST['passwordConfirm'] ?? '');
    $tipo = 'Cliente'; // por defecto

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
            $autoRedirect = true; // activar redirección
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
  <title>Registro de Usuario</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="login-wrapper">
  <!-- Panel visual izquierdo -->
  <section class="login-hero">
    <div class="avatar" aria-hidden="true">
      <svg viewBox="0 0 64 64" width="92" height="92">
        <circle cx="32" cy="24" r="12" fill="#fff"/>
        <path d="M8,60a24,18 0 1,1 48,0" fill="#fff"/>
      </svg>
    </div>
    <h1>CREAR CUENTA</h1>
    <p>Regístrate para acceder al sistema.</p>
  </section>

  <!-- Formulario -->
  <section class="login-form">
    <h2 class="form-title">Nuevo usuario</h2>

    <?php if($mensaje): ?>
      <div class="alert <?= $tipoMensaje; ?>"><?= $mensaje; ?></div>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="">
      <!-- Correo -->
      <div class="input-group input-icon">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2m0 4l-8 5L4 8V6l8 5l8-5Z"/></svg>
        </span>
        <label class="label" for="correo">Correo</label>
        <input class="input" type="email" name="correo" id="correo" placeholder="tucorreo@dominio.com" required>
      </div>

      <!-- Contraseña -->
      <div class="input-group input-icon">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/></svg>
        </span>
        <label class="label" for="password">Contraseña</label>
        <input class="input" type="password" name="password" id="password" placeholder="••••••••" required>
        <button id="togglePass1" class="toggle-pass" aria-label="mostrar/ocultar">Mostrar</button>
        <div class="strength"><span id="strengthBar"></span></div>
        <div class="helper">Mínimo 8 caracteres, una mayúscula y un número.</div>
      </div>

      <!-- Confirmación -->
      <div class="input-group input-icon">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/></svg>
        </span>
        <label class="label" for="passwordConfirm">Confirmar contraseña</label>
        <input class="input" type="password" name="passwordConfirm" id="passwordConfirm" placeholder="••••••••" required>
        <button id="togglePass2" class="toggle-pass" aria-label="mostrar/ocultar">Mostrar</button>
      </div>

      <div class="form-footer">
        <button class="btn btn-primary" type="submit">Registrarme</button>
        <a class="link" href="login.php">¿Ya tienes cuenta? Iniciar sesión</a>
      </div>
    </form>
  </section>
</div>

<?php if ($autoRedirect): ?>
  <script>
    setTimeout(function(){
      window.location.href = 'login.php';
    }, 3000);
  </script>
<?php endif; ?>

<script src="../assets/js/validation.js"></script>
</body>
</html>
