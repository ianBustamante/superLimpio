<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// Verificar cliente
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];
$sql = "SELECT tipo, idRelacionado FROM usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info || $info['tipo'] !== 'Cliente') {
    header("Location: ../../modules/login.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Usa tu función global (ajústala si tiene otro nombre)
$prod = obtenerProductoPorId($conn, $id);
if (!$prod) {
    header("Location: index.php?msg=" . urlencode("Producto no encontrado.") . "&type=error");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle de producto</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      min-height:100vh;
      background:#0b1f6b;
      display:flex;
      align-items:center;
      justify-content:center;
      font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
      padding:16px;
    }
    .card{
      background:#f3f5ff;
      border-radius:24px;
      padding:24px 28px;
      max-width:640px;
      width:100%;
      box-shadow:0 16px 40px rgba(0,0,0,.45);
      display:grid;
      grid-template-columns:220px 1fr;
      gap:20px;
    }
    .img-wrap{
      background:#e5e7eb;
      border-radius:18px;
      padding:10px;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .img-wrap img{
      width:100%;
      height:auto;
      object-fit:contain;
    }
    .title{
      font-size:20px;
      font-weight:800;
      color:#111827;
      margin-bottom:4px;
    }
    .category{
      display:inline-flex;
      padding:3px 9px;
      border-radius:999px;
      font-size:11px;
      background:#e0ebff;
      color:#1f3bbf;
      font-weight:600;
      margin-bottom:8px;
    }
    .desc{
      font-size:13px;
      color:#4b5563;
      margin-bottom:10px;
    }
    .price{
      font-size:17px;
      font-weight:800;
      color:#111827;
      margin-bottom:6px;
    }
    .stock{
      font-size:13px;
      color:#374151;
      margin-bottom:14px;
    }
    .actions{
      display:flex;
      gap:8px;
    }
    .btn{
      border-radius:999px;
      padding:8px 14px;
      font-size:13px;
      font-weight:600;
      border:none;
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }
    .btn-secondary{
      background:#e5e7eb;
      color:#111827;
    }
    .btn-primary{
      background:#1f3bbf;
      color:#fff;
    }
    .btn-secondary:hover{background:#d1d5db;}
    .btn-primary:hover{background:#162a85;}
    @media(max-width:720px){
      .card{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>

<div class="card">
  <div class="img-wrap">
    <img src="../../assets/img/clean-products.png" alt="Producto">
  </div>
  <div>
    <div class="title"><?= htmlspecialchars($prod['Nombre']) ?></div>
    <div class="category"><?= htmlspecialchars($prod['Categoria'] ?? '') ?></div>
    <div class="desc">
      <?= nl2br(htmlspecialchars($prod['Descripcion'] ?? 'Sin descripción')) ?>
    </div>
    <div class="price">$<?= number_format($prod['Precio'], 2) ?></div>
    <div class="stock">
      Stock disponible: <strong><?= (int)$prod['Stock'] ?></strong>
    </div>

    <div class="actions">
      <a href="index.php" class="btn btn-secondary">Volver al catálogo</a>
      <form method="post" action="carrito_agregar.php">
        <input type="hidden" name="idProducto" value="<?= $prod['idProducto'] ?>">
        <button type="submit" class="btn btn-primary">Agregar al carrito</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
