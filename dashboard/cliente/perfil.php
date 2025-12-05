<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// =========================
//  Verificar sesión y tipo Cliente
// =========================
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];

$sql = "SELECT tipo, idRelacionado FROM usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$infoUsuario = $stmt->get_result()->fetch_assoc();

if (!$infoUsuario || $infoUsuario['tipo'] !== 'Cliente') {
    header("Location: ../../modules/login.php");
    exit();
}

$idCliente = (int)$infoUsuario['idRelacionado'];
$cliente   = obtenerClientePorId($conn, $idCliente);

$nombre   = $cliente['Nombre']   ?? '';
$apellido = $cliente['Apellido'] ?? '';
$telefono = $cliente['Telefono'] ?? '';

$mensaje = "";
$tipoMensaje = ""; // exito | error

// =========================
//  Procesar POST
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === '' || $telefono === '') {
        $mensaje = "El nombre y el teléfono son obligatorios.";
        $tipoMensaje = "error";
    } elseif (!validarTelefono($telefono)) {
        $mensaje = "El teléfono debe tener un formato válido. Ejemplo: 555-1234 o 5551234.";
        $tipoMensaje = "error";
    } else {
        if (actualizarClientePerfil($conn, $idCliente, $nombre, $apellido, $telefono)) {
            $mensaje = "Tu información fue actualizada correctamente.";
            $tipoMensaje = "exito";
        } else {
            $mensaje = "Ocurrió un error al actualizar tu información.";
            $tipoMensaje = "error";
        }
    }
}

$inicial = strtoupper(substr($nombre !== '' ? $nombre : 'C', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cliente · Mi perfil</title>

  <!-- MISMO ESTILO QUE EL CATÁLOGO -->
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      display: flex;
      height: 100vh;
      background: #0b1f6b;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
    }

    .sidebar {
      width: 190px;
      padding: 18px 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .sidebar-card {
      width: 100%;
      height: 100%;
      border-radius: 32px;
      padding: 26px 20px;
      background: linear-gradient(180deg, #2141ff, #04145a);
      color: #eef3ff;
      display: flex;
      flex-direction: column;
    }

    .logo {
      font-size: 24px;
      font-weight: 900;
      letter-spacing: 0.8px;
      margin-bottom: 32px;
    }

    .logo span { display: block; }

    .side-menu {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-top: 8px;
    }

    .side-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px;
      border-radius: 999px;
      text-decoration: none;
      color: inherit;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.18s ease, transform 0.12s ease;
    }

    .side-item:hover {
      background: rgba(255,255,255,0.14);
      transform: translateX(2px);
    }

    .side-item--active {
      background: #eef3ff;
      color: #1f3bbf;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(0,0,0,0.28);
    }

    .side-item-icon {
      width: 28px;
      height: 28px;
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,0.18);
    }

    .side-item--active .side-item-icon {
      background: #1f3bbf;
      color: #ffffff;
    }

    .side-item-icon svg {
      width: 18px;
      height: 18px;
    }

    .side-item-label { margin-top: 2px; }

    .side-footer {
      margin-top: auto;
      font-size: 11px;
      opacity: 0.75;
    }

    .content {
      flex: 1;
      padding: 26px 26px 26px 10px;
      background: #f3f5ff;
      border-top-left-radius: 32px;
      border-bottom-left-radius: 32px;
      box-shadow: -8px 0 18px rgba(0,0,0,0.35);
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    .perfil-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .perfil-header h1 {
      font-size: 24px;
      margin-bottom: 4px;
      color: #111827;
    }

    .perfil-header p {
      font-size: 14px;
      color: #6b7280;
    }

    .avatar-circle {
      width: 36px;
      height: 36px;
      border-radius: 999px;
      background: #1f3bbf;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
    }

    .perfil-wrapper {
      max-width: 500px;
    }

    .perfil-card {
      background: #ffffff;
      border-radius: 18px;
      padding: 20px 24px;
      box-shadow: 0 4px 12px rgba(15,23,42,0.08);
    }

    .perfil-card label {
      display: block;
      font-size: 14px;
      margin-bottom: 4px;
      color: #374151;
    }

    .perfil-card input {
      width: 100%;
      border-radius: 10px;
      border: 1px solid #d1d5db;
      padding: 8px 10px;
      margin-bottom: 12px;
      font-size: 14px;
      background: #f9fafb;
    }

    .perfil-card input:focus {
      outline: none;
      border-color: #1f3bbf;
      box-shadow: 0 0 0 1px rgba(31,59,191,0.2);
      background: #ffffff;
    }

    .perfil-card button {
      padding: 8px 16px;
      border-radius: 10px;
      border: none;
      background: #1f3bbf;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
      font-size: 14px;
    }

    .perfil-card button:hover { background: #162a85; }

    .alert {
      padding: 8px 10px;
      border-radius: 8px;
      margin-bottom: 12px;
      font-size: 13px;
    }

    .alert.exito {
      background: #dcfce7;
      color: #166534;
    }

    .alert.error {
      background: #fee2e2;
      color: #b91c1c;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-card">
      <div class="logo">
        <span>Super</span>
        <span>Limpio</span>
      </div>

      <nav class="side-menu">
        <a href="index.php" class="side-item">
          <div class="side-item-icon">
            <!-- inicio -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M240,208H224V136l2.34,2.34A8,8,0,0,0,237.66,127L139.31,28.68a16,16,0,0,0-22.62,0L18.34,127a8,8,0,0,0,11.32,11.31L32,136v72H16a8,8,0,0,0,0,16H240a8,8,0,0,0,0-16ZM48,120l80-80,80,80v88H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48Zm96,88H112V160h32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Catálogo</span>
        </a>

        <a href="perfil.php" class="side-item side-item--active">
          <div class="side-item-icon">
            <!-- usuario -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff" viewBox="0 0 256 256"><path d="M230.94,212.61a8,8,0,0,1-11.55.78C203.51,199,177,184,128,184s-75.52,15-91.39,29.39a8,8,0,1,1-10.78-11.78C44,184.14,74.64,168,128,168s84,16.14,102.17,33.61A8,8,0,0,1,230.94,212.61ZM128,152a56,56,0,1,0-56-56A56.06,56.06,0,0,0,128,152Zm0-96a40,40,0,1,1-40,40A40,40,0,0,1,128,56Z"></path></svg>
          </div>
          <span class="side-item-label">Mi perfil</span>
        </a>

        <a href="../../modules/logout.php" class="side-item">
          <div class="side-item-icon">
            <!-- logout -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M141.66,133.66l-40,40a8,8,0,0,1-11.32-11.32L116.69,136H24a8,8,0,0,1,0-16h92.69L90.34,93.66a8,8,0,0,1,11.32-11.32l40,40A8,8,0,0,1,141.66,133.66ZM200,32H136a8,8,0,0,0,0,16h56V208H136a8,8,0,0,0,0,16h64a8,8,0,0,0,8-8V40A8,8,0,0,0,200,32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Cerrar sesión</span>
        </a>
      </nav>

      <div class="side-footer">
        Cliente · POS
      </div>
    </div>
  </aside>

  <!-- CONTENIDO -->
  <main class="content">
    <div class="perfil-header">
      <div>
        <h1>Mi perfil</h1>
        <p>Actualiza tus datos personales.</p>
      </div>
      <div class="avatar-circle"><?php echo $inicial; ?></div>
    </div>

    <div class="perfil-wrapper">
      <div class="perfil-card">
        <?php if ($mensaje): ?>
          <div class="alert <?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
          </div>
        <?php endif; ?>

        <form method="post">
          <label for="nombre">Nombre *</label>
          <input type="text" id="nombre" name="nombre"
                 value="<?php echo htmlspecialchars($nombre); ?>" required>

          <label for="apellido">Apellido</label>
          <input type="text" id="apellido" name="apellido"
                 value="<?php echo htmlspecialchars($apellido); ?>">

          <label for="telefono">Teléfono *</label>
          <input type="text" id="telefono" name="telefono"
                 value="<?php echo htmlspecialchars($telefono); ?>" required>

          <button type="submit">Guardar cambios</button>
        </form>
      </div>
    </div>
  </main>
</body>
</html>
