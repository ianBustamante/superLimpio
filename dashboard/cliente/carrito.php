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

$mensaje = '';
$tipoMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualizar cantidades
    if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar' && !empty($_SESSION['carrito'])) {
        foreach ($_POST['cantidades'] as $idProd => $cant) {
            $idProd = (int)$idProd;
            $cant   = (int)$cant;
            if (!isset($_SESSION['carrito'][$idProd])) continue;

            if ($cant <= 0) {
                unset($_SESSION['carrito'][$idProd]);
                continue;
            }

            // Verificar stock actual
            $sqlS = "SELECT Stock FROM producto WHERE idProducto = ?";
            $stmtS = $conn->prepare($sqlS);
            $stmtS->bind_param("i", $idProd);
            $stmtS->execute();
            $resS = $stmtS->get_result()->fetch_assoc();
            $stockActual = $resS ? (int)$resS['Stock'] : 0;

            if ($stockActual <= 0) {
                unset($_SESSION['carrito'][$idProd]);
                continue;
            }

            if ($cant > $stockActual) {
                $cant = $stockActual;
            }

            $_SESSION['carrito'][$idProd]['cantidad'] = $cant;
            $_SESSION['carrito'][$idProd]['stock']    = $stockActual;
        }
        $mensaje = "Carrito actualizado.";
        $tipoMsg = "exito";
    }

    // Vaciar carrito
    if (isset($_POST['accion']) && $_POST['accion'] === 'vaciar') {
        unset($_SESSION['carrito']);
        $mensaje = "Carrito vaciado.";
        $tipoMsg = "exito";
    }
}

$carrito = $_SESSION['carrito'] ?? [];
$total   = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito de compra</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      min-height:100vh;
      background:#0b1f6b;
      font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:20px;
    }
    .wrap{
      background:#f3f5ff;
      border-radius:24px;
      padding:22px 26px;
      max-width:840px;
      width:100%;
      box-shadow:0 16px 40px rgba(0,0,0,.45);
    }
    h1{
      font-size:22px;
      color:#111827;
      margin-bottom:4px;
    }
    .subtitle{
      font-size:13px;
      color:#6b7280;
      margin-bottom:14px;
    }
    .alert{
      padding:10px 12px;
      border-radius:10px;
      margin-bottom:12px;
      font-size:14px;
      font-weight:600;
    }
    .alert.exito{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
    .alert.error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}

    table{
      width:100%;
      border-collapse:collapse;
      font-size:13px;
      margin-bottom:14px;
    }
    th,td{
      padding:8px 6px;
      text-align:left;
    }
    thead{
      background:#e5e7eb;
    }
    tbody tr:nth-child(even){
      background:#edf2ff;
    }
    .qty-input{
      width:56px;
      padding:4px;
      border-radius:8px;
      border:1px solid #d1d5db;
      text-align:center;
    }
    .text-right{text-align:right;}
    .text-center{text-align:center;}
    .actions-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-top:8px;
      gap:10px;
      flex-wrap:wrap;
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
    .btn-danger{
      background:#fee2e2;
      color:#b91c1c;
      border:1px solid #fecaca;
    }
    .btn-primary{
      background:#1f3bbf;
      color:#fff;
    }
    .btn-secondary:hover{background:#d1d5db;}
    .btn-danger:hover{background:#fecaca;}
    .btn-primary:hover{background:#162a85;}
    .total{
      font-size:15px;
      font-weight:800;
      color:#111827;
    }
    .empty{
      font-size:14px;
      color:#6b7280;
      margin-top:10px;
    }
  </style>
</head>
<body>

<div class="wrap">
  <h1>Carrito de compra</h1>
  <div class="subtitle">Revisa tus productos antes de pagar.</div>

  <?php if ($mensaje): ?>
    <div class="alert <?= htmlspecialchars($tipoMsg) ?>">
      <?= htmlspecialchars($mensaje) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($carrito)): ?>
    <form method="post">
      <input type="hidden" name="accion" value="actualizar">
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th class="text-center">Precio</th>
            <th class="text-center">Cantidad</th>
            <th class="text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($carrito as $id => $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['nombre']) ?></td>
            <td class="text-center">$<?= number_format($item['precio'], 2) ?></td>
            <td class="text-center">
              <input
                type="number"
                class="qty-input"
                name="cantidades[<?= $id ?>]"
                min="1"
                max="<?= (int)$item['stock'] ?>"
                value="<?= (int)$item['cantidad'] ?>"
              >
            </td>
            <td class="text-right">
              $<?= number_format($item['precio'] * $item['cantidad'], 2) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="actions-row">
        <div>
          <span class="total">
            Total: $<?= number_format($total, 2) ?>
          </span>
        </div>
        <div>
          <button type="submit" class="btn btn-secondary">Actualizar cantidades</button>
        </div>
      </div>
    </form>

    <div class="actions-row" style="margin-top:10px;">
      <form method="post" onsubmit="return confirm('¿Vaciar carrito completo?');">
        <input type="hidden" name="accion" value="vaciar">
        <button type="submit" class="btn btn-danger">Vaciar carrito</button>
      </form>

      <div style="display:flex;gap:8px;">
        <a href="index.php" class="btn btn-secondary">Seguir comprando</a>
        <a href="pago.php" class="btn btn-primary">Ir a pagar</a>
      </div>
    </div>
  <?php else: ?>
    <div class="empty">
      Tu carrito está vacío. <a href="index.php">Ir al catálogo</a>.
    </div>
  <?php endif; ?>

</div>

</body>
</html>
