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
if (!$permisosProd['puede_registrar']) {
    header("Location: index.php?type=error&msg=" . urlencode("No tienes permiso para registrar productos."));
    exit();
}

$cats = obtenerCategorias($conn);
$mensaje = '';
$tipo = '';

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = $_POST['precio'] ?? '';
$stock  = $_POST['stock'] ?? '';
$idCat  = $_POST['idCategoria'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $precioF = is_numeric($precio) ? floatval($precio) : -1;
    $stockI  = is_numeric($stock)  ? intval($stock)  : -1;
    $idCatI  = intval($idCat);

    if ($nombre === '' || $precio === '' || $stock === '' || $idCat === '') {
        $mensaje = 'Todos los campos marcados son obligatorios.'; $tipo='error';
    } elseif ($precioF <= 0) {
        $mensaje = 'Precio inválido. Debe ser mayor a 0.'; $tipo='error';
    } elseif ($stockI <= 0) {
        $mensaje = 'Stock inválido. Debe ser mayor a 0.'; $tipo='error';
    } elseif ($idCatI <= 0) {
        $mensaje = 'Selecciona una categoría.'; $tipo='error';
    } elseif (productoExiste($conn, $nombre)) {
        $mensaje = 'Ya existe un producto con ese nombre.'; $tipo='error';
    } else {
        $ok = crearProducto($conn, $nombre, $descripcion, $precioF, $stockI, $idCatI);
        if ($ok) {
            header("Location: index.php?type=exito&msg=" . urlencode("Producto creado correctamente."));
            exit();
        } else {
            $mensaje = 'No fue posible crear el producto.'; $tipo='error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vendedor · Crear producto</title>
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
    .alert.exito{background:#ecfeff; color:#0ea5e9; border:1px solid #bae6fd;}
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
    <div class="left-title">NUEVO<br>PRODUCTO</div>
  </aside>

  <!-- Formulario -->
  <main class="right-pane">
    <div class="form-block">
      <h1 class="form-title">Crear producto</h1>

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
            <textarea name="descripcion" placeholder="Descripción opcional"><?= htmlspecialchars($descripcion) ?></textarea>
          </div>
        </div>

        <!-- Precio -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_precio.png" alt="Precio" width="35" height="35">
          </div>
          <div class="pill">
            <input type="number" step="0.01" name="precio" placeholder="Precio *" required value="<?= htmlspecialchars($precio) ?>">
          </div>
        </div>

        <!-- Stock -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_stock.png" alt="Stock" width="42" height="42">
          </div>
          <div class="pill">
            <input type="number" name="stock" placeholder="Stock *" required value="<?= htmlspecialchars($stock) ?>">
          </div>
        </div>

        <!-- Categoría -->
        <div class="row">
          <div class="icon-cell">
            <img src="../../assets/img/campo_categoria.png" alt="Categoría" width="46" height="46">
          </div>
          <div class="pill">
            <select name="idCategoria" required>
              <option value="">Selecciona una categoría</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= $c['idCategoria'] ?>" <?= ($idCat == $c['idCategoria']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['Nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="btn-area">
          <a href="index.php" class="btn-muted">Cancelar</a>
          <button type="submit" class="btn-primary">Guardar producto</button>
        </div>
      </form>
    </div>
  </main>
</div>

</body>
</html>
