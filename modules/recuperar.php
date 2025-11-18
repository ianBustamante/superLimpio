<?php
require_once '../includes/functions.php';

$mensaje = "";
$tipoMensaje = "";
$autoRedirect = false; // Redirigir al login tras éxito de cambio

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Etapa 1: Generar código de recuperación
    if (isset($_POST['correo_recuperacion'])) {
        $correo = trim($_POST['correo_recuperacion'] ?? '');

        if (!validarCorreo($correo)) {
            $mensaje = "Ingrese un correo válido.";
            $tipoMensaje = 'error';
        } else {
            $codigoGenerado = generarCodigoRecuperacion($conn, $correo);

            if ($codigoGenerado) {
                // En entorno real se enviaría por correo; aquí lo mostramos para la práctica
                $mensaje = "Código de recuperación generado: <strong>$codigoGenerado</strong><br>Úsalo en el segundo formulario.";
                $tipoMensaje = 'exito';
            } else {
                $mensaje = "Correo no registrado.";
                $tipoMensaje = 'error';
            }
        }
    }

    // Etapa 2: Actualizar contraseña
    if (isset($_POST['codigo'], $_POST['nueva_contrasena'], $_POST['confirmar_contrasena'])) {
        $codigo = trim($_POST['codigo'] ?? '');
        $nueva = trim($_POST['nueva_contrasena'] ?? '');
        $confirmar = trim($_POST['confirmar_contrasena'] ?? '');

        if ($nueva !== $confirmar) {
            $mensaje = "Las contraseñas no coinciden.";
            $tipoMensaje = 'error';
        } elseif (!validarPassword($nueva)) {
            $mensaje = "Contraseña insegura. Debe tener mínimo 8 caracteres, una mayúscula y un número.";
            $tipoMensaje = 'error';
        } else {
            $resultado = actualizarContrasena($conn, $codigo, $nueva);
            if ($resultado === true) {
                $mensaje = "Contraseña actualizada correctamente. Redirigiendo al inicio de sesión...";
                $tipoMensaje = 'exito';
                $autoRedirect = true;
            } else {
                $mensaje = $resultado;
                $tipoMensaje = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recuperación de Contraseña | Super Limpio</title>

  <!-- Estilos globales -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- Estilos específicos para recuperación -->
  <link rel="stylesheet" href="../assets/css/recovery.css">
</head>
<body>

<div class="auth-page">
  <header class="login-header">
    <div class="header-icon">
      <img src="../assets/img/blue-key-recovery.png" alt="Icono recuperación">
    </div>
    <h1>Recuperar acceso</h1>
    <p>Sigue los pasos para restablecer tu contraseña.</p>
  </header>

  <?php if($mensaje): ?>
    <div class="alert <?= $tipoMensaje; ?>"><?= $mensaje; ?></div>
  <?php endif; ?>

  <!-- Paso 1: Solicitar código -->
  <form id="reqCodeForm" method="POST" action="" style="margin-bottom:18px">
    <div class="input-group">
      <label class="label" for="correo_recuperacion">Correo registrado</label>
      <div class="input-wrapper">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2m0 4l-8 5L4 8V6l8 5l8-5Z"/></svg>
        </span>
        <input class="input" type="email" name="correo_recuperacion" id="correo_recuperacion"
               placeholder="tucorreo@dominio.com" required>
      </div>
    </div>

    <div class="form-footer">
      <button class="btn-primary" type="submit">Generar código</button>
      <a class="link" href="login.php">Volver a iniciar sesión</a>
    </div>
  </form>

  <hr>

  <!-- Paso 2: Aplicar el código y nueva contraseña -->
  <form id="applyCodeForm" method="POST" action="">
    <div class="input-group">
      <label class="label" for="codigo">Código de recuperación</label>
      <div class="input-wrapper">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 17a2 2 0 1 0 2 2a2 2 0 0 0-2-2m6-8h-1V6a5 5 0 0 0-10 0v3H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2M8 9V6a4 4 0 0 1 8 0v3Z"/></svg>
        </span>
        <input class="input" type="text" name="codigo" id="codigo"
               placeholder="Ej. A1B2C3D4" required>
      </div>
    </div>

    <div class="input-group">
      <label class="label" for="nueva_contrasena">Nueva contraseña</label>
      <div class="input-wrapper">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/></svg>
        </span>
        <input class="input" type="password" name="nueva_contrasena" id="nueva_contrasena"
               placeholder="••••••••" required>
        <button id="toggleNew" class="toggle-pass" aria-label="mostrar/ocultar">Mostrar</button>
      </div>
      <div class="helper">Mínimo 8 caracteres, una mayúscula y un número.</div>
    </div>

    <div class="input-group">
      <label class="label" for="confirmar_contrasena">Confirmar contraseña</label>
      <div class="input-wrapper">
        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 1a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1V6a5 5 0 0 1 5-5m3 8V6a3 3 0 0 0-6 0v3h6Z"/></svg>
        </span>
        <input class="input" type="password" name="confirmar_contrasena" id="confirmar_contrasena"
               placeholder="••••••••" required>
        <button id="toggleNew2" class="toggle-pass" aria-label="mostrar/ocultar">Mostrar</button>
      </div>
    </div>

    <div class="form-footer">
      <button class="btn-primary" type="submit">Actualizar contraseña</button>
    </div>
  </form>
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
