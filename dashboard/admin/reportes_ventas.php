<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// =========================
//  Verificar sesión y que sea ADMIN
// =========================
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];

if (!esAdmin($conn, $idUsuario)) {
    header("Location: ../../modules/login.php");
    exit();
}

// =========================
//  Filtros de periodo (mes / año)
// =========================
$anioActual = (int)date('Y');
$mesActual  = (int)date('n');

$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : $anioActual;
$mes  = isset($_GET['mes'])  ? (int)$_GET['mes']  : $mesActual;

if ($mes < 1 || $mes > 12)  $mes  = $mesActual;
if ($anio < 2000 || $anio > $anioActual + 1) $anio = $anioActual;

// =========================
//  Consultar ventas del periodo
// =========================
$sqlVentas = "SELECT v.idVenta,
                     v.Fecha,
                     v.Total,
                     c.Nombre AS Cliente,
                     e.Nombre AS Empleado
              FROM venta v
              INNER JOIN cliente  c ON c.idCliente  = v.idCliente
              INNER JOIN empleado e ON e.idEmpleado = v.idEmpleado
              WHERE YEAR(v.Fecha) = ? AND MONTH(v.Fecha) = ?
              ORDER BY v.Fecha ASC";

$stmtV = $conn->prepare($sqlVentas);
$stmtV->bind_param("ii", $anio, $mes);
$stmtV->execute();
$resVentas = $stmtV->get_result();
$ventas = $resVentas ? $resVentas->fetch_all(MYSQLI_ASSOC) : [];

// Totales
$totalVentas = 0;
foreach ($ventas as $v) {
    $totalVentas += (float)$v['Total'];
}
$cantidadVentas = count($ventas);

// =========================
//  Productos más vendidos
// =========================
$sqlTop = "SELECT p.Nombre,
                  SUM(d.Cantidad) AS totalCantidad,
                  SUM(d.subtotal) AS totalImporte
           FROM venta v
           INNER JOIN detalleventa d ON d.idVenta   = v.idVenta
           INNER JOIN producto     p ON p.idProducto = d.idProducto
           WHERE YEAR(v.Fecha) = ? AND MONTH(v.Fecha) = ?
           GROUP BY p.idProducto, p.Nombre
           ORDER BY totalCantidad DESC
           LIMIT 5";
$stmtT = $conn->prepare($sqlTop);
$stmtT->bind_param("ii", $anio, $mes);
$stmtT->execute();
$resTop = $stmtT->get_result();
$topProductos = $resTop ? $resTop->fetch_all(MYSQLI_ASSOC) : [];

$sinVentas = ($cantidadVentas === 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administrador · Reporte de ventas</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
  <link rel="stylesheet" href="../../assets/css/dashboard-modern.css">
  <style>
    .admin-body { background: #f3f4ff; }
    .report-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 18px 20px;
      box-shadow: 0 4px 12px rgba(15,23,42,0.12);
      margin-bottom: 18px;
    }
    .report-summary {
      display: flex;
      gap: 18px;
      flex-wrap: wrap;
    }
    .summary-item {
      flex: 1;
      min-width: 160px;
      background: #eef2ff;
      border-radius: 12px;
      padding: 10px 14px;
    }
    .summary-item span {
      font-size: 12px;
      color: #6b7280;
    }
    .summary-item strong {
      display: block;
      font-size: 18px;
      margin-top: 4px;
      color: #111827;
    }
    .table-wrap {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    th, td {
      padding: 8px 10px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
    }
    th {
      background: #eef2ff;
      font-weight: 600;
      color: #374151;
    }
    .filter-row {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }
    .filter-row select, .filter-row button {
      padding: 6px 10px;
      border-radius: 10px;
      border: 1px solid #d1d5db;
      font-size: 13px;
    }
    .filter-row button {
      border: none;
      background: #1f3bbf;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
    }
    .filter-row button:hover {
      background: #162a85;
    }
    .alert-info {
      padding: 8px 10px;
      border-radius: 8px;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 13px;
      margin-top: 6px;
    }
  </style>
</head>
<body class="admin-body">

<div class="admin-layout">

  <!-- SIDEBAR ADMIN -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      Super Limpio
      <span>Panel administrador</span>
    </div>

    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-item">
        <span class="label">Dashboard</span>
      </a>
      <a href="reportes_ventas.php" class="admin-nav-item is-active">
        <span class="label">Reporte de ventas</span>
      </a>
      <a href="../../modules/logout.php" class="admin-nav-item">
        <span class="label">Cerrar sesión</span>
      </a>
    </nav>
  </aside>

  <!-- CONTENIDO -->
  <main class="admin-main">
    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Reporte de ventas mensuales</h1>
        <p>Consulta el total de ventas y los productos más vendidos por periodo.</p>
      </div>
    </div>

    <!-- FILTROS -->
    <div class="report-card">
      <form method="get" class="filter-row">
        <div>
          <label for="mes" style="font-size:12px; color:#4b5563;">Mes</label><br>
          <select name="mes" id="mes">
            <?php
            $meses = ['1'=>'Enero','2'=>'Febrero','3'=>'Marzo','4'=>'Abril','5'=>'Mayo','6'=>'Junio','7'=>'Julio','8'=>'Agosto','9'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
            foreach ($meses as $num => $nom):
            ?>
              <option value="<?php echo $num; ?>" <?php echo ($mes == $num) ? 'selected' : ''; ?>>
                <?php echo $nom; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="anio" style="font-size:12px; color:#4b5563;">Año</label><br>
          <select name="anio" id="anio">
            <?php for ($y = $anioActual - 5; $y <= $anioActual + 1; $y++): ?>
              <option value="<?php echo $y; ?>" <?php echo ($anio == $y) ? 'selected' : ''; ?>>
                <?php echo $y; ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>

        <div style="align-self:flex-end;">
          <button type="submit">Aplicar</button>
        </div>
      </form>

      <div class="report-summary">
        <div class="summary-item">
          <span>Total de ventas</span>
          <strong>$<?php echo number_format($totalVentas, 2); ?></strong>
        </div>
        <div class="summary-item">
          <span>Número de ventas</span>
          <strong><?php echo $cantidadVentas; ?></strong>
        </div>
        <div class="summary-item">
          <span>Periodo</span>
          <strong><?php echo $meses[$mes]." ".$anio; ?></strong>
        </div>
      </div>

      <?php if ($sinVentas): ?>
        <div class="alert-info">
          No existen ventas registradas en el periodo seleccionado.
        </div>
      <?php endif; ?>
    </div>

    <!-- TOP PRODUCTOS -->
    <div class="report-card">
      <h2 style="margin-bottom: 8px;">Productos más vendidos</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cantidad vendida</th>
              <th>Importe total</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($topProductos)): ?>
            <?php foreach ($topProductos as $tp): ?>
              <tr>
                <td><?php echo htmlspecialchars($tp['Nombre']); ?></td>
                <td><?php echo (int)$tp['totalCantidad']; ?></td>
                <td>$<?php echo number_format($tp['totalImporte'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" style="text-align:center; color:#9ca3af; padding:8px;">
                No hay información de productos para este periodo.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- LISTADO DETALLADO DE VENTAS -->
    <div class="report-card">
      <h2 style="margin-bottom: 8px;">Detalle de ventas</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Empleado</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($ventas)): ?>
            <?php foreach ($ventas as $v): ?>
              <tr>
                <td>#<?php echo $v['idVenta']; ?></td>
                <td><?php echo $v['Fecha']; ?></td>
                <td><?php echo htmlspecialchars($v['Cliente']); ?></td>
                <td><?php echo htmlspecialchars($v['Empleado']); ?></td>
                <td>$<?php echo number_format($v['Total'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align:center; color:#9ca3af; padding:8px;">
                No hay ventas registradas en este periodo.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
</body>
</html>
