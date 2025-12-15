<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php"); exit();
}

$permisosProd = obtenerPermisosProductos($conn, $_SESSION['idUsuario']);
if (!$permisosProd['puede_registrar']) {
  header("Location: index.php?type=error&msg=" . urlencode("No tienes permiso para registrar productos.")); exit();
}

$cats = obtenerCategorias($conn);
$mensaje = ''; $tipo = '';

// Mantener valores si hay error
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = $_POST['precio'] ?? '';
$stock  = $_POST['stock']  ?? '';
$idCat  = $_POST['idCategoria'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Normalizar
  $precioF = is_numeric($precio) ? floatval($precio) : -1;
  $stockI  = is_numeric($stock)  ? intval($stock)  : -1;
  $idCatI  = intval($idCat);

  // Validaciones (precio y stock > 0)
  if ($nombre === '' || $precio === '' || $stock === '' || $idCat === '') {
    $mensaje = 'Todos los campos marcados son obligatorios.'; $tipo='error';
  } elseif ($precioF <= 0) {
    $mensaje = 'Precio inválido. Debe ser mayor a 0.'; $tipo='error';
  } elseif ($stockI <= 0) { // 👈 CAMBIO: ya no se permite 0
    $mensaje = 'Stock inválido. Debe ser mayor a 0.'; $tipo='error';
  } elseif ($idCatI <= 0) {
    $mensaje = 'Selecciona una categoría.'; $tipo='error';
  } elseif (productoExiste($conn, $nombre)) {
    $mensaje = 'El nombre del producto ya existe. Evita duplicados.'; $tipo='error';
  } else {
    if (crearProducto($conn, $nombre, $descripcion, $precioF, $stockI, $idCatI)) {
  echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
      // Crear modal visual
      const overlay = document.createElement('div');
      overlay.style.position = 'fixed';
      overlay.style.top = '0';
      overlay.style.left = '0';
      overlay.style.width = '100%';
      overlay.style.height = '100%';
      overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
      overlay.style.display = 'flex';
      overlay.style.alignItems = 'center';
      overlay.style.justifyContent = 'center';
      overlay.style.zIndex = '9999';

      const modal = document.createElement('div');
      modal.style.background = '#fff';
      modal.style.padding = '30px 40px';
      modal.style.borderRadius = '16px';
      modal.style.boxShadow = '0 4px 20px rgba(0,0,0,0.2)';
      modal.style.textAlign = 'center';
      modal.style.fontFamily = 'Arial, sans-serif';
      modal.style.color = '#0b2240';
      modal.style.fontWeight = '700';
      modal.innerHTML = '<h2>✅ Producto agregado correctamente</h2>';

      overlay.appendChild(modal);
      document.body.appendChild(overlay);

      // Redirigir después de 2.5 segundos
      setTimeout(() => {
        window.location.href = \"index.php\";
      }, 2500);
    });
  </script>";
  exit;
}
else {
      $mensaje = 'Ocurrió un error al registrar el producto.'; $tipo='error';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de producto</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    :root{ --ink:#0b2240; --sidebar:#e6f3f3; --pill:#f4f5ef; --line:#e5e7eb; }
    body{background:#fff;}
    .wrap-reg{display:flex; min-height:100vh;}
    .left-pane{
      flex:4; min-width:360px; background:var(--sidebar);
      padding:48px 32px; display:flex; flex-direction:column; align-items:center; justify-content:center;
      border-right:3px solid #0b2240;
    }
    .left-pane .avatar{
      width:220px; height:220px; border-radius:999px;
      background:linear-gradient(145deg,#99b7d6,#7fa3c8);
      display:flex; align-items:center; justify-content:center; margin-bottom:36px;
    }
    .left-title{ color:#000; font-size:44px; font-weight:900; line-height:1.05; align-self:flex-start; margin-left:18px; }
    .right-pane{flex:6; padding:48px 28px; display:flex; align-items:center; justify-content:center;}
    .form-block{width:min(720px,90%);}
    .form-title{ margin:0 0 28px; text-align:center; font-weight:900; font-size:48px; color:#000; letter-spacing:.6px; }

    .row{display:grid; grid-template-columns:56px 1fr; align-items:center; gap:18px; margin:18px 0;}
    .icon-cell{display:flex; align-items:center; justify-content:center;}
    .pill{ background:var(--pill); border-radius:28px; padding:12px 18px; display:flex; align-items:center; border:1px solid var(--line); width:100%; }
    .pill input, .pill select, .pill textarea{ width:100%; border:none; outline:none; background:transparent; font-size:18px; color:#111; }
    .pill textarea{ resize:vertical; min-height:52px; padding-top:4px; }
    .helper{font-size:.86rem; color:#6b7280; margin-top:6px; margin-left:74px;}
    .btn-area{display:flex; justify-content:center; margin-top:12px;}
    .btn-primary{background:#0b2240; color:#fff; border:1px solid #0b2240; border-radius:14px; padding:10px 18px; font-weight:800;}
    .btn-muted{border:1px solid var(--line); background:#fff; color:var(--ink); border-radius:14px; padding:10px 16px; margin-left:10px; font-weight:800;}
    .alert{padding:12px 14px; border-radius:10px; margin-bottom:14px; font-weight:700;}
    .alert.exito{background:#e7f5e8; color:#166534; border:1px solid #bbf7d0;}
    .alert.error{background:#fee2e2; color:#991b1b; border:1px solid #fecaca;}
  </style>
</head>
<body>

<div class="wrap-reg">
  <!-- Panel izquierdo -->
  <aside class="left-pane">
    <div class="avatar" aria-hidden="true">
      <!-- (Opcional) Avatar fijo -->
      <svg viewBox="0 0 64 64" width="140" height="140">
        <circle cx="32" cy="24" r="12" fill="#fff"/><path d="M8,60a24,18 0 1,1 48,0" fill="#fff"/>
      </svg>
    </div>
    <div class="left-title">GESTIÓN DE<br>PRODUCTOS</div>
  </aside>

  <!-- Formulario -->
  <main class="right-pane">
    <div class="form-block">
      <h1 class="form-title">REGISTRO</h1>

      <?php if($mensaje): ?>
        <div class="alert <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <!-- Nombre -->
        <div class="row">
          <div class="icon-cell">
            <!-- 🔽 PON AQUÍ TU IMAGEN: reemplaza el SVG por un <img> -->
            <img src="../../assets/img/campo_nombre.jpg" alt="Nombre" width="50" height="50"> 
            </div>
          <div class="pill">
            <input type="text" name="nombre" placeholder="Nombre *" required value="<?= htmlspecialchars($nombre) ?>">
          </div>
        </div>

        <!-- Descripción -->
        <div class="row">
          <div class="icon-cell">
            <!-- 🔽 PON AQUÍ TU IMAGEN -->
            <img src="../../assets/img/campo_descripcion.png" alt="Descripción" width="45" height="45"> 
            </div>
          <div class="pill">
            <textarea name="descripcion" placeholder="Descripción" required value="<?= htmlspecialchars($descripcion) ?>"></textarea>
          </div>
        </div>

        <!-- Precio -->
        <div class="row">
          <div class="icon-cell">
            <!-- 🔽 PON AQUÍ TU IMAGEN -->
            <img src="../../assets/img/campo_precio.png" alt="Precio" width="35" height="35"> 
            </div>
          <div class="pill">
            <input type="number" name="precio" min="0.01" step="0.01" placeholder="Precio *" required value="<?= htmlspecialchars($precio) ?>">
          </div>
         
        </div>

        <!-- Stock -->
        <div class="row">
          <div class="icon-cell">
            <!-- 🔽 PON AQUÍ TU IMAGEN -->
            <img src="../../assets/img/campo_stock.png" alt="Stock" width="32" height="32">
            </div>
          <div class="pill">
            <input type="number" name="stock" min="1" step="1" placeholder="Stock *" required value="<?= htmlspecialchars($stock) ?>">
          </div>
          
        </div>

        <!-- Categoría -->
        <div class="row">
          <div class="icon-cell">
            <!-- 🔽 PON AQUÍ TU IMAGEN -->
            <img src="../../assets/img/campo_categoria.png" alt="Categoría" width="50" height="50"> 
            </div>
          <div class="pill">
            <select name="idCategoria" required>
              <option value="">Categoría *</option>
              <?php foreach($cats as $c): ?>
                <option value="<?= $c['idCategoria'] ?>" <?= ($idCat==$c['idCategoria'])?'selected':'' ?>>
                  <?= htmlspecialchars($c['Nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="btn-area">
          <button class="btn-primary" type="submit">Guardar</button>
          <a class="btn-muted" href="index.php">Cancelar</a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
