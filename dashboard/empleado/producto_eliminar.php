<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// Solo empleados autenticados
if (!isset($_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php");
  exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];

$stmt = $conn->prepare("SELECT tipo FROM usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$infoUsuario = $stmt->get_result()->fetch_assoc();

if (!$infoUsuario || $infoUsuario['tipo'] !== 'Empleado') {
  header("Location: ../../modules/login.php");
  exit();
}

$permisosProd = obtenerPermisosProductos($conn, $idUsuario);
if (!$permisosProd['puede_eliminar']) {
  header("Location: index.php?type=error&msg=" . urlencode("No tienes permiso para eliminar productos."));
  exit();
}

// Helpers locales
function obtenerProductoPorIdLocal($conn, $id) {
  $sql = "SELECT p.idProducto, p.Nombre, p.Descripcion, p.Precio, p.Stock, p.idCategoria,
                 c.Nombre AS Categoria
          FROM producto p
          LEFT JOIN categoria c ON c.idCategoria = p.idCategoria
          WHERE p.idProducto = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc();
}

function productoTieneVentas($conn, $idProducto) {
  $sql = "SELECT 1 FROM detalleventa WHERE idProducto = ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $idProducto);
  $stmt->execute();
  return ($stmt->get_result()->num_rows > 0);
}

function eliminarProductoBD($conn, $idProducto) {
  $sql = "DELETE FROM producto WHERE idProducto = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $idProducto);
  return $stmt->execute();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: index.php?type=error&msg=" . urlencode("Producto no válido."));
  exit();
}

$producto = obtenerProductoPorIdLocal($conn, $id);
if (!$producto) {
  header("Location: index.php?type=error&msg=" . urlencode("Producto no encontrado."));
  exit();
}

$mensaje = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (productoTieneVentas($conn, $id)) {
    $mensaje = "No se puede eliminar porque el producto está referenciado en ventas.";
    $tipo = "error";
  } else {
    if (eliminarProductoBD($conn, $id)) {
      registrarEvento($conn, $_SESSION['idUsuario'], 'Producto - Eliminar (vendedor)', 'Exitoso', "Producto #$id eliminado por vendedor.");

      echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
          const overlay = document.createElement('div');
          overlay.style.position='fixed'; overlay.style.inset='0'; overlay.style.background='rgba(0,0,0,.5)';
          overlay.style.display='flex'; overlay.style.alignItems='center'; overlay.style.justifyContent='center';
          overlay.style.zIndex='9999';
          const modal = document.createElement('div');
          modal.style.background='#fff'; modal.style.padding='30px 40px'; modal.style.borderRadius='16px';
          modal.style.boxShadow='0 4px 20px rgba(0,0,0,.2)'; modal.style.textAlign='center';
          modal.style.fontFamily='Arial, sans-serif'; modal.style.color='#0b2240'; modal.style.fontWeight='700';
          modal.innerHTML = '<h2>✅ Producto eliminado</h2><p>Actualizando listado...</p>';
          overlay.appendChild(modal); document.body.appendChild(overlay);
          setTimeout(()=>{ window.location.href='index.php'; }, 1800);
        });
      </script>";
      exit;
    } else {
      $mensaje = "Ocurrió un error al eliminar el producto.";
      $tipo = "error";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vendedor · Eliminar producto</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    :root{ --ink:#0b2240; --sidebar:#e6f3f3; --line:#e5e7eb; }
    body{background:#fff;}
    .wrap{display:flex; min-height:100vh;}
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
    .left-title{ color:#000; font-size:40px; font-weight:900; line-height:1.05; text-align:center; }
    .right-pane{flex:6; padding:48px 28px; display:flex; align-items:center; justify-content:center;}
    .card{
      width:min(720px,90%); border:1px solid var(--line); border-radius:16px; padding:24px;
      box-shadow:0 4px 16px rgba(0,0,0,.06);
    }
    .card h1{margin:0 0 14px; font-size:30px; color:#111; font-weight:900;}
    .summary{background:#f8fafc; border:1px dashed #cbd5e1; padding:14px; border-radius:12px; margin:16px 0;}
    .row{display:grid; grid-template-columns:160px 1fr; gap:10px; margin:6px 0; color:#111;}
    .label{font-weight:800; color:#334155;}
    .alert{padding:12px 14px; border-radius:10px; margin:10px 0 16px; font-weight:700;}
    .alert.error{background:#fee2e2; color:#991b1b; border:1px solid #fecaca;}
    .warn{background:#fff7ed; color:#7c2d12; border:1px solid #fed7aa; padding:12px 14px; border-radius:10px; font-weight:700; margin:14px 0;}
    .btns{display:flex; gap:10px; margin-top:16px;}
    .btn-primary{background:#0b2240; color:#fff; border:1px solid #0b2240; border-radius:12px; padding:10px 16px; font-weight:800;}
    .btn-muted{border:1px solid var(--line); background:#fff; color:#0b2240; border-radius:12px; padding:10px 16px; font-weight:800;}
  </style>
</head>
<body>

<div class="wrap">
  <!-- Panel izquierdo -->
  <aside class="left-pane">
    <div class="avatar" aria-hidden="true">
      <svg viewBox="0 0 64 64" width="140" height="140">
        <circle cx="32" cy="24" r="12" fill="#fff"/><path d="M8,60a24,18 0 1,1 48,0" fill="#fff"/>
      </svg>
    </div>
    <div class="left-title">GESTIÓN<br>DE PRODUCTOS</div>
  </aside>

  <!-- Contenido -->
  <main class="right-pane">
    <div class="card">
      <h1>Eliminar producto</h1>

      <?php if($mensaje): ?>
        <div class="alert error"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <?php if (productoTieneVentas($conn, $producto['idProducto'])): ?>
        <div class="warn">⚠️ Este producto está asociado a una o más ventas. No es posible eliminarlo.</div>
        <div class="btns">
          <a class="btn-muted" href="index.php">Volver al listado</a>
        </div>
      <?php else: ?>
        <p>Confirma que deseas eliminar el siguiente producto. Esta acción no se puede deshacer.</p>

        <div class="summary">
          <div class="row"><div class="label">ID</div><div>#<?= $producto['idProducto'] ?></div></div>
          <div class="row"><div class="label">Nombre</div><div><?= htmlspecialchars($producto['Nombre']) ?></div></div>
          <div class="row"><div class="label">Descripción</div><div><?= htmlspecialchars($producto['Descripcion']) ?></div></div>
          <div class="row"><div class="label">Precio</div><div>$<?= number_format($producto['Precio'],2) ?></div></div>
          <div class="row"><div class="label">Stock</div><div><?= (int)$producto['Stock'] ?></div></div>
          <div class="row"><div class="label">Categoría</div><div><?= htmlspecialchars($producto['Categoria'] ?? '') ?></div></div>
        </div>

        <form method="POST" action="">
          <div class="btns">
            <button type="submit" class="btn-primary">Sí, eliminar</button>
            <a class="btn-muted" href="index.php">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>

</body>
</html>
