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

// --- Helpers locales (no duplican funciones globales) ---
function obtenerProductoPorIdLocal($conn, $id) {
  $sql = "SELECT p.idProducto, p.Nombre, p.Descripcion, p.Precio, p.Stock, p.idCategoria
          FROM producto p
          WHERE p.idProducto = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  return $stmt->get_result()->fetch_assoc();
}

function nombreProductoDuplicado($conn, $nombre, $idProducto) {
  $sql = "SELECT 1 FROM producto WHERE Nombre = ? AND idProducto <> ? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $nombre, $idProducto);
  $stmt->execute();
  return ($stmt->get_result()->num_rows > 0);
}

// --- Cargar producto por id ---
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: index.php?type=error&msg=" . urlencode("Producto no válido.")); exit();
}

$prod = obtenerProductoPorIdLocal($conn, $id);
if (!$prod) {
  header("Location: index.php?type=error&msg=" . urlencode("Producto no encontrado.")); exit();
}

$cats = obtenerCategorias($conn);
$mensaje = ''; $tipo = '';

// Mantener valores si hay error, si no, precargar con los del producto
$nombre      = trim($_POST['nombre']      ?? $prod['Nombre']);
$descripcion = trim($_POST['descripcion'] ?? $prod['Descripcion']);
$precio      = $_POST['precio']           ?? $prod['Precio'];
$stock       = $_POST['stock']            ?? $prod['Stock'];
$idCat       = $_POST['idCategoria']      ?? $prod['idCategoria'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $precioF = is_numeric($precio) ? floatval($precio) : -1;
  $stockI  = is_numeric($stock)  ? intval($stock)  : -1;
  $idCatI  = intval($idCat);

  if ($nombre === '' || $precio === '' || $stock === '' || $idCat === '') {
    $mensaje = 'Todos los campos marcados son obligatorios.'; $tipo='error';
  } elseif ($precioF <= 0) {
    $mensaje = 'Precio inválido. Debe ser mayor a 0.'; $tipo='error';
  } elseif ($stockI < 0) {
    // aquí sí dejamos que pueda ser 0 (sin stock)
    $mensaje = 'Stock inválido. Debe ser 0 o mayor.'; $tipo='error';
  } elseif ($idCatI <= 0) {
    $mensaje = 'Selecciona una categoría.'; $tipo='error';
  } elseif (nombreProductoDuplicado($conn, $nombre, $id)) {
    $mensaje = 'Ya existe otro producto con ese nombre. Evita duplicados.'; $tipo='error';
  } else {
    $sqlU = "UPDATE producto
             SET Nombre = ?, Descripcion = ?, Precio = ?, Stock = ?, idCategoria = ?
             WHERE idProducto = ?";
    $stmtU = $conn->prepare($sqlU);
    $stmtU->bind_param("ssdiii", $nombre, $descripcion, $precioF, $stockI, $idCatI, $id);
    $ok = $stmtU->execute();

    if ($ok) {
      // Auditoría
      registrarEvento($conn, $_SESSION['idUsuario'], 'Producto - Editar (vendedor)', 'Exitoso', "Producto #$id actualizado por vendedor.");

      // Modal + redirección a inventario del empleado
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
          modal.innerHTML = '<h2>✅ Producto actualizado</h2>';
          overlay.appendChild(modal); document.body.appendChild(overlay);
          setTimeout(()=>{ window.location.href='index.php'; }, 2000);
        });
      </script>";
      exit;
    } else {
      $mensaje = 'No fue posible actualizar el producto.'; $tipo='error';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vendedor · Edición de producto</title>
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
    .left-title{ color:#000; font-size:36px; font-weight:900; line-height:1.05; text-align:center; }
    .right-pane{flex:6; padding:48px 28px; display:flex; align-items:center; justify-content:center;}
    .form-block{width:min(720px,90%);}
    .form-title{ margin:0 0 28px; text-align:center; font-weight:900; font-size:40px; color:#000; letter-spacing:.6px; }

    .row{display:grid; grid-template-columns:56px 1fr; align-items:center; gap:18px; margin:18px 0;}
    .icon-cell{display:flex; align-items:center; justify-content:center;}
    .pill{ background:var(--pill); border-radius:28px; padding:12px 18px; display:flex; align-items:center; border:1px solid var(--line); width:100%; }
    .pill input, .pill select, .pill textarea{ width:100%; border:none; outline:none; background:transparent; font-size:18px; color:#111; }
    .pill textarea{ resize:vertical; min-height:52px; padding-top:4px; }
    .btn-area{display:flex; justify-content:center; margin-top:12px; gap:10px;}
    .btn-primary{background:#0b2240; color:#fff; border:1px solid #0b2240; border-radius:14px; padding:10px 18px; font-weight:800; text-decoration:none;}
    .btn-muted{border:1px solid var(--line); background:#fff; color:var(--ink); border-radius:14px; padding:10px 16px; font-weight:800; text-decoration:none;}
    .alert{padding:12px 14px; border-radius:10px; margin-bottom:14px; font-weight:700;}
    .alert.error{background:#fee2e2; color:#991b1b; border:1px solid #fecaca;}
  </style>
</head>
<body>

<div class="wrap-reg">
  <!-- Panel izquierdo -->
  <aside class="left-pane">
    <div class="avatar" aria-hidden="true">
      <!-- Avatar simple -->
      <svg viewBox="0 0 64 64" width="140" height="140">
        <circle cx="32" cy="24" r="12" fill="#fff"/><path d="M8,60a24,18 0 1,1 48,0" fill="#fff"/>
      </svg>
    </div>
    <div class="left-title">EDICIÓN<br>DE PRODUCTO</div>
  </aside>

  <!-- Formulario -->
  <main class="right-pane">
    <div class="form-block">
      <h1 class="form-title">Editar producto #<?= $prod['idProducto'] ?></h1>

      <?php if($mensaje): ?>
        <div class="alert <?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <!-- Nombre -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_nombre.jpg" alt="Nombre" width="50" height="50">
          </div>
          <div class="pill">
            <input type="text" name="nombre" placeholder="Nombre *" required value="<?= htmlspecialchars($nombre) ?>">
          </div>
        </div>

        <!-- Descripción -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_descripcion.png" alt="Descripción" width="45" height="45">
          </div>
          <div class="pill">
            <textarea name="descripcion" placeholder="Descripción"><?= htmlspecialchars($descripcion) ?></textarea>
          </div>
        </div>

        <!-- Precio -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_precio.png" alt="Precio" width="35" height="35">
          </div>
          <div class="pill">
            <input type="number" name="precio" min="0.01" step="0.01" placeholder="Precio *" required value="<?= htmlspecialchars($precio) ?>">
          </div>
        </div>

        <!-- Stock -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_stock.png" alt="Stock" width="32" height="32">
          </div>
          <div class="pill">
            <input type="number" name="stock" min="0" step="1" placeholder="Stock *" required value="<?= htmlspecialchars($stock) ?>">
          </div>
        </div>

        <!-- Categoría -->
        <div class="row">
          <div class="icon-cell">
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
          <button class="btn-primary" type="submit">Actualizar</button>
          <a class="btn-muted" href="index.php">Cancelar</a>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
