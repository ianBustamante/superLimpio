<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// Solo empleados (vendedor)
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];
$sql = "SELECT tipo FROM usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info || $info['tipo'] !== 'Empleado') {
    header("Location: ../../modules/login.php");
    exit();
}

$permisosProd = obtenerPermisosProductos($conn, $idUsuario);
if (!$permisosProd['puede_consultar']) {
    header("Location: index.php?type=error&msg=" . urlencode("No tienes permiso para consultar productos."));
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Usamos la función global
$prod = obtenerProductoPorId($conn, $id);
if (!$prod) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vendedor · Detalle de producto</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    body {
      background:#0b1f6b;
      font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:100vh;
      padding:16px;
    }
    .card {
      background:#f3f5ff;
      border-radius:24px;
      padding:24px 28px;
      max-width:520px;
      width:100%;
      box-shadow:0 12px 30px rgba(0,0,0,.35);
    }
    .card h1 {
      font-size:22px;
      margin-bottom:8px;
      color:#111827;
    }
    .card p.subtitle {
      font-size:13px;
      color:#6b7280;
      margin-bottom:18px;
    }
    .tag {
      display:inline-flex;
      padding:4px 10px;
      border-radius:999px;
      font-size:12px;
      background:#e0ebff;
      color:#1f3bbf;
      font-weight:600;
      margin-bottom:12px;
    }
    .row { margin-bottom:10px; font-size:14px; color:#111827; }
    .row span.label { display:block; font-size:12px; color:#6b7280; margin-bottom:2px; }
    .badge-stock{
      display:inline-flex;
      align-items:center;
      padding:2px 8px;
      border-radius:999px;
      font-size:12px;
    }
    .badge-ok{background:#dcfce7;color:#166534;}
    .badge-low{background:#fef9c3;color:#92400e;}
    .badge-zero{background:#fee2e2;color:#b91c1c;}
    .actions {
      display:flex;
      justify-content:flex-end;
      gap:10px;
      margin-top:16px;
    }
    .btn {
      border-radius:999px;
      padding:8px 16px;
      border:none;
      font-size:13px;
      font-weight:600;
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }
    .btn-secondary {
      background:#e5e7eb;
      color:#111827;
    }
    .btn-primary {
      background:#1f3bbf;
      color:#fff;
    }
  </style>
</head>
<body>

<div class="card">
  <h1><?= htmlspecialchars($prod['Nombre']) ?></h1>
  <p class="subtitle">Detalle del producto en inventario.</p>

  <span class="tag">
    <?= htmlspecialchars($prod['Categoria'] ?? '') ?>
  </span>

  <div class="row">
    <span class="label">Descripción</span>
    <?= nl2br(htmlspecialchars($prod['Descripcion'] ?? 'Sin descripción')) ?>
  </div>

  <div class="row">
    <span class="label">Precio</span>
    $<?= number_format($prod['Precio'], 2) ?>
  </div>

  <div class="row">
    <span class="label">Stock</span>
    <?php
      $stock = (int)$prod['Stock'];
      if ($stock === 0)      { $c='badge-zero'; $txt='Sin stock'; }
      elseif ($stock <= 10)  { $c='badge-low';  $txt="Bajo ($stock)"; }
      else                   { $c='badge-ok';   $txt=$stock; }
    ?>
    <span class="badge-stock <?= $c ?>"><?= $txt ?></span>
  </div>

  <div class="actions">
    <a href="index.php" class="btn btn-secondary">Volver al inventario</a>
    <a href="producto_editar.php?id=<?= $prod['idProducto'] ?>" class="btn btn-primary">Editar</a>
  </div>
</div>

</body>
</html>
