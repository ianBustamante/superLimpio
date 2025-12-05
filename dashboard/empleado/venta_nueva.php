<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// =========================
//  Verificar sesión y rol
// =========================
if (!isset($_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php");
  exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];

$sqlTipo = "SELECT tipo, idRelacionado FROM usuario WHERE idUsuario = ?";
$stmtTipo = $conn->prepare($sqlTipo);
$stmtTipo->bind_param("i", $idUsuario);
$stmtTipo->execute();
$infoUsuario = $stmtTipo->get_result()->fetch_assoc();

if (!$infoUsuario || $infoUsuario['tipo'] !== 'Empleado') {
  header("Location: ../../modules/login.php");
  exit();
}

$idEmpleado = (int)$infoUsuario['idRelacionado']; // FK a tabla Empleado

// Nombre e inicial para avatar
$nombre  = obtenerNombreUsuario($conn, $idUsuario);
$inicial = strtoupper(substr($nombre, 0, 1));

// =========================
//  Cliente de mostrador
// =========================
function obtenerIdClienteMostrador($conn) {
    $sql = "SELECT idCliente FROM cliente WHERE Nombre = 'Cliente de mostrador' LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['idCliente'];
    }
    return null;
}

// =========================
//  Carrito en sesión
// =========================
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = []; // [idProducto => cantidad]
}

$mensaje = "";
$tipoMensaje = ""; // "exito" | "error"

// =========================
//  Filtros de productos (GET)
// =========================
$q  = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

// =========================
//  Manejo de POST (acciones)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ---- Agregar producto al carrito ----
    if ($accion === 'agregar') {
        $idProd = (int)($_POST['idProducto'] ?? 0);
        $cant   = (int)($_POST['cantidad'] ?? 0);

        if ($idProd <= 0 || $cant <= 0) {
            $mensaje = "Producto o cantidad inválida.";
            $tipoMensaje = "error";
        } else {
            // Consultar stock del producto
            $sql = "SELECT Stock FROM producto WHERE idProducto = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $idProd);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();

            if (!$res) {
                $mensaje = "Producto no encontrado.";
                $tipoMensaje = "error";
            } else {
                $stockBD = (int)$res['Stock'];
                $enCarrito = (int)($_SESSION['carrito'][$idProd] ?? 0);
                $totalSolicitado = $enCarrito + $cant;

                if ($stockBD <= 0) {
                    $mensaje = "No hay stock disponible para este producto.";
                    $tipoMensaje = "error";
                } elseif ($totalSolicitado > $stockBD) {
                    $mensaje = "No puedes agregar $cant unidad(es). Solo hay $stockBD en stock (ya tienes $enCarrito en la venta).";
                    $tipoMensaje = "error";
                } else {
                    $_SESSION['carrito'][$idProd] = $totalSolicitado;
                    $mensaje = "Producto agregado al carrito.";
                    $tipoMensaje = "exito";
                }
            }
        }
    }

    // ---- Eliminar producto del carrito ----
    if ($accion === 'eliminar') {
        $idProd = (int)($_POST['idProducto'] ?? 0);
        if (isset($_SESSION['carrito'][$idProd])) {
            unset($_SESSION['carrito'][$idProd]);
            $mensaje = "Producto eliminado de la venta.";
            $tipoMensaje = "exito";
        }
    }

    // ---- Vaciar carrito ----
    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        $mensaje = "La venta en curso se ha vaciado.";
        $tipoMensaje = "exito";
    }

    // ---- Registrar venta ----
    if ($accion === 'finalizar') {
        if (empty($_SESSION['carrito'])) {
            $mensaje = "No hay productos en la venta.";
            $tipoMensaje = "error";
        } else {
            // Obtener cliente
$idCliente    = (int)($_POST['idCliente'] ?? 0);
$nombreRapido = trim($_POST['cliente_rapido'] ?? '');

// 1) Validar que haya ALGÚN tipo de cliente
if ($idCliente <= 0 && $nombreRapido === '') {
    $mensaje = "Selecciona un cliente registrado o escribe un nombre en 'cliente rápido'.";
    $tipoMensaje = "error";
} elseif ($idCliente <= 0 && $nombreRapido !== '') {
    // 2) Cliente rápido: usamos cliente de mostrador por debajo
    $idClienteMostrador = obtenerIdClienteMostrador($conn);
    if ($idClienteMostrador === null) {
        $mensaje = "No se encontró el cliente de mostrador. Crea uno en la tabla Cliente.";
        $tipoMensaje = "error";
    } else {
        $idCliente = $idClienteMostrador;
    }
}
// si $idCliente > 0 desde el combo, se usa tal cual


            if ($idCliente > 0 && $mensaje === "") {
                // Validar stock nuevamente y calcular total
                $totalVenta = 0;
                $detalles   = []; // para guardar datos antes de insertar

                foreach ($_SESSION['carrito'] as $idProd => $cant) {
                    $sql = "SELECT Precio, Stock, Nombre FROM producto WHERE idProducto = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $idProd);
                    $stmt->execute();
                    $prod = $stmt->get_result()->fetch_assoc();

                    if (!$prod) {
                        $mensaje = "Producto con ID $idProd no encontrado.";
                        $tipoMensaje = "error";
                        break;
                    }

                    $stockBD = (int)$prod['Stock'];
                    if ($cant > $stockBD) {
                        $mensaje = "Stock insuficiente para '{$prod['Nombre']}'. Disponible: $stockBD, solicitado: $cant.";
                        $tipoMensaje = "error";
                        break;
                    }

                    $precioUnit = (float)$prod['Precio'];
                    $subtotal   = $precioUnit * $cant;
                    $totalVenta += $subtotal;

                    $detalles[] = [
                        'idProducto'   => $idProd,
                        'cantidad'     => $cant,
                        'precioUnit'   => $precioUnit,
                        'subtotal'     => $subtotal
                    ];
                }

                // Si no hubo errores de stock...
                if ($mensaje === "") {
                    $conn->begin_transaction();

                    try {
                        // Insert en Venta
                        $sqlVenta = "INSERT INTO venta (Fecha, Total, idCliente, idEmpleado)
                                     VALUES (NOW(), ?, ?, ?)";
                        $stmtV = $conn->prepare($sqlVenta);
                        $stmtV->bind_param("dii", $totalVenta, $idCliente, $idEmpleado);
                        $stmtV->execute();
                        $idVenta = $stmtV->insert_id;

                        // Insert detalle + actualizar stock
                        foreach ($detalles as $d) {
                            $sqlDet = "INSERT INTO detalleventa (idVenta, idProducto, Cantidad, PrecioUnitario, subtotal)
                                       VALUES (?, ?, ?, ?, ?)";
                            $stmtD = $conn->prepare($sqlDet);
                            $stmtD->bind_param(
                                "iiidd",
                                $idVenta,
                                $d['idProducto'],
                                $d['cantidad'],
                                $d['precioUnit'],
                                $d['subtotal']
                            );
                            $stmtD->execute();

                            // Actualizamos stock
                            $sqlUpd = "UPDATE producto SET Stock = Stock - ? WHERE idProducto = ?";
                            $stmtU = $conn->prepare($sqlUpd);
                            $stmtU->bind_param("ii", $d['cantidad'], $d['idProducto']);
                            $stmtU->execute();
                        }

                        $conn->commit();
                        $_SESSION['carrito'] = []; // vaciar carrito
                        $mensaje = "Venta registrada correctamente.";
                        $tipoMensaje = "exito";

                    } catch (Exception $e) {
                        $conn->rollback();
                        $mensaje = "Error al registrar la venta.";
                        $tipoMensaje = "error";
                    }
                }
            }
        }
    }
}

// =========================
//  Cargar datos para la vista
// =========================

// Productos base
$productos = obtenerProductos($conn);

// Filtrado en memoria como en inventario
if ($q !== '' || $categoriaFiltro !== '') {
  $productos = array_values(array_filter($productos, function($p) use ($q, $categoriaFiltro) {

    if ($q !== '') {
      $textoOk =
        stripos($p['Nombre'],       $q) !== false ||
        stripos($p['Descripcion'],  $q) !== false ||
        stripos($p['Categoria'],    $q) !== false ||
        stripos((string)$p['idProducto'], $q) !== false;
      if (!$textoOk) {
        return false;
      }
    }

    if ($categoriaFiltro !== '') {
      if (strcasecmp($p['Categoria'], $categoriaFiltro) !== 0) {
        return false;
      }
    }

    return true;
  }));
}

// Categorías para filtros
$categorias = obtenerCategorias($conn);

// Clientes para combo
$sqlCli = "SELECT idCliente, Nombre, Apellido FROM cliente ORDER BY Nombre";
$resCli = $conn->query($sqlCli);
$clientes = $resCli ? $resCli->fetch_all(MYSQLI_ASSOC) : [];

// Armar detalle de carrito para mostrar
$carritoDetalle = [];
$totalCarrito = 0;

if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $idProd => $cant) {
        $sql = "SELECT Nombre, Precio FROM producto WHERE idProducto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idProd);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();

        if ($prod) {
            $precioUnit = (float)$prod['Precio'];
            $subtotal   = $precioUnit * $cant;
            $totalCarrito += $subtotal;

            $carritoDetalle[] = [
                'idProducto' => $idProd,
                'nombre'     => $prod['Nombre'],
                'cantidad'   => $cant,
                'precioUnit' => $precioUnit,
                'subtotal'   => $subtotal
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vendedor · Registrar venta</title>

  <!-- Tus CSS existentes (venta.css, etc.) -->
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
  <link rel="stylesheet" href="../../assets/css/dashboard-modern.css">
  <link rel="stylesheet" href="../../assets/css/avatar.css">
  <link rel="stylesheet" href="../../assets/css/venta.css">

  <!-- === MISMO LAYOUT AZUL QUE INVENTARIO === -->
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      display:flex;
      height:100vh;
      background:#0b1f6b;
      font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;
    }

    .sidebar {
      width:190px;
      padding:18px 8px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    .sidebar-card {
      width:100%;
      height:100%;
      border-radius:32px;
      padding:26px 20px;
      background:linear-gradient(180deg,#2141ff,#04145a);
      color:#eef3ff;
      display:flex;
      flex-direction:column;
    }

    .logo {
      font-size:24px;
      font-weight:900;
      letter-spacing:.8px;
      margin-bottom:32px;
    }
    .logo span{display:block;}

    .side-menu{
      display:flex;
      flex-direction:column;
      gap:16px;
      margin-top:8px;
    }

    .side-item{
      display:flex;
      align-items:center;
      gap:12px;
      padding:10px 12px;
      border-radius:999px;
      text-decoration:none;
      color:inherit;
      font-size:15px;
      font-weight:500;
      cursor:pointer;
      transition:background .18s ease,transform .12s ease;
    }

    .side-item:hover{
      background:rgba(255,255,255,.14);
      transform:translateX(2px);
    }

    .side-item--active{
      background:#eef3ff;
      color:#1f3bbf;
      font-weight:600;
      box-shadow:0 4px 12px rgba(0,0,0,.28);
    }

    .side-item-icon{
      width:28px;
      height:28px;
      border-radius:999px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
    }

    .side-item--active .side-item-icon{
      background:#1f3bbf;
      color:#fff;
    }

    .side-item-icon svg{width:18px;height:18px;}
    .side-item-label{margin-top:2px;}

    .side-footer{
      margin-top:auto;
      font-size:11px;
      opacity:.75;
    }

    .content{
      flex:1;
      padding:26px 26px 26px 10px;
      background:#f3f5ff;
      border-top-left-radius:32px;
      border-bottom-left-radius:32px;
      box-shadow:-8px 0 18px rgba(0,0,0,.35);
      overflow-y:auto;
      display:flex;
      flex-direction:column;
    }

    .header-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:20px;
    }
    .header-row h1{font-size:24px;margin-bottom:4px;color:#111827;}
    .header-row p{font-size:14px;color:#6b7280;}

    .avatar-circle{
      width:32px;
      height:32px;
      border-radius:999px;
      background:#1f3bbf;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:15px;
      font-weight:700;
    }
  </style>
</head>
<body>

  <!-- === SIDEBAR AZUL === -->
  <aside class="sidebar">
    <div class="sidebar-card">
      <div class="logo">
        <span>Super</span>
        <span>Limpio</span>
      </div>

      <nav class="side-menu">
        <!-- Inventario -->
        <a href="index.php" class="side-item">
          <div class="side-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M240,208H224V136l2.34,2.34A8,8,0,0,0,237.66,127L139.31,28.68a16,16,0,0,0-22.62,0L18.34,127a8,8,0,0,0,11.32,11.31L32,136v72H16a8,8,0,0,0,0,16H240a8,8,0,0,0,0-16ZM48,120l80-80,80,80v88H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48Zm96,88H112V160h32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Inventario</span>
        </a>

        <!-- Registrar venta (ACTIVO) -->
        <a href="venta_nueva.php" class="side-item side-item--active">
          <div class="side-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff" viewBox="0 0 256 256">
              <path d="M216,72H69.12L61.71,37.88A16,16,0,0,0,46.08,24H24a8,8,0,0,0,0,16H46.08l24.2,112.93A24,24,0,1,0,120,168h56a24,24,0,1,0,22.63-16.94L213,120l11.48-34.43A8,8,0,0,0,216,72ZM88,192a8,8,0,1,1-8-8A8,8,0,0,1,88,192Zm104,0a8,8,0,1,1-8-8A8,8,0,0,1,192,192Zm5-80H83.75L75.12,88H208.2Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Registrar venta</span>
        </a>

        <!-- Cerrar sesión -->
        <a href="../../modules/logout.php" class="side-item">
          <div class="side-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M141.66,133.66l-40,40a8,8,0,0,1-11.32-11.32L116.69,136H24a8,8,0,0,1,0-16h92.69L90.34,93.66a8,8,0,0,1,11.32-11.32l40,40A8,8,0,0,1,141.66,133.66ZM200,32H136a8,8,0,0,0,0,16h56V208H136a8,8,0,0,0,0,16h64a8,8,0,0,0,8-8V40A8,8,0,0,0,200,32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Cerrar sesión</span>
        </a>
      </nav>

      <div class="side-footer">
        Panel Vendedor · POS
      </div>
    </div>
  </aside>

  <!-- === CONTENIDO (reutilizamos tu layout de venta) === -->
  <main class="content">

    <!-- Header con avatar (usa $inicial definido en el PHP de arriba) -->
    <div class="header-row">
      <div>
        <h1>Registrar venta</h1>
        <p>Selecciona los productos y completa los datos del cliente.</p>
      </div>
      <div class="avatar-circle"><?php echo $inicial; ?></div>
    </div>

    <!-- Todo tu layout de venta intacto -->
    <div class="venta-layout">

      <!-- COLUMNA IZQUIERDA: PRODUCTOS -->
      <section class="venta-col venta-col-left" id="productos">
        <div class="venta-panel">
          <div class="venta-panel-header">
            <h2>Productos</h2>
            <p>Busca y agrega productos a la venta.</p>
          </div>

          <!-- Filtros -->
          <form method="get" class="venta-search-row">
            <input
              type="text"
              class="venta-search-input"
              name="q"
              placeholder="Buscar por nombre, descripción o código..."
              value="<?= htmlspecialchars($q) ?>"
            >

            <select name="categoria" class="venta-select" style="margin-top:8px;">
              <option value="">Todas las categorías</option>
              <?php foreach ($categorias as $cat): ?>
                <?php $nombreCat = $cat['Nombre']; ?>
                <option
                  value="<?= htmlspecialchars($nombreCat) ?>"
                  <?= ($categoriaFiltro === $nombreCat) ? 'selected' : '' ?>
                >
                  <?= htmlspecialchars($nombreCat) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:8px;">
              Aplicar filtros
            </button>
            <?php if ($q !== '' || $categoriaFiltro !== ''): ?>
              <a href="venta_nueva.php" class="btn btn-ghost btn-sm" style="margin-top:8px;">Limpiar</a>
            <?php endif; ?>
          </form>

          <div class="table-wrap">
            <table class="venta-table">
              <thead>
              <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Stock</th>
                <th style="width:120px;">Agregar</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($productos as $p): ?>
                <tr>
                  <td>#<?= $p['idProducto'] ?></td>
                  <td><?= htmlspecialchars($p['Nombre']) ?></td>
                  <td>$<?= number_format($p['Precio'], 2) ?></td>
                  <td><?= (int)$p['Stock'] ?></td>
                  <td>
                    <?php if ((int)$p['Stock'] > 0): ?>
                      <form method="post" class="venta-add-control" action="venta_nueva.php#productos">
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="idProducto" value="<?= $p['idProducto'] ?>">
                        <input type="number" min="1" max="<?= (int)$p['Stock'] ?>"
                               name="cantidad" value="1" class="venta-qty-input">
                        <button class="btn btn-primary btn-sm" type="submit">
                          Agregar
                        </button>
                      </form>
                    <?php else: ?>
                      <span style="font-size: 12px; color:#b91c1c;">Sin stock</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($productos)): ?>
                <tr>
                  <td colspan="5" style="text-align:center; padding:14px; color:#9ca3af;">
                    No hay productos que coincidan con los filtros.
                  </td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- COLUMNA DERECHA: CLIENTE + CARRITO -->
      <section class="venta-col venta-col-right" id="venta">

        <!-- PANEL CLIENTE + TOTAL -->
        <div class="venta-panel">
          <div class="venta-panel-header">
            <h2>Cliente y resumen</h2>
          </div>

          <?php if ($mensaje): ?>
            <div class="alert <?= $tipoMensaje; ?>" style="margin-bottom: 10px;">
              <?= htmlspecialchars($mensaje); ?>
            </div>
          <?php endif; ?>

          <form method="post" action="venta_nueva.php#venta">
            <input type="hidden" name="accion" value="finalizar">

            <div class="venta-cliente-box">
              <label class="venta-label">Cliente registrado</label>
              <select class="venta-select" name="idCliente">
                <option value="">Selecciona un cliente...</option>
                <?php foreach ($clientes as $c): ?>
                  <option value="<?= $c['idCliente'] ?>">
                    <?= htmlspecialchars($c['Nombre'].' '.$c['Apellido']) ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <div class="venta-divider">
                <span>o cliente rápido</span>
              </div>

              <label class="venta-label">Nombre (solo referencia visual)</label>
              <input type="text" class="venta-input" name="cliente_rapido"
                     placeholder="Nombre del cliente (no se guarda)">
            </div>

            <div class="venta-total-row" style="margin-top: 14px;">
              <span>Total actual</span>
              <strong>$<?= number_format($totalCarrito, 2) ?></strong>
            </div>

            <div class="venta-actions" style="justify-content: flex-end;">
              <button class="btn btn-primary" type="submit" <?= empty($carritoDetalle) ? 'disabled' : '' ?>>
                Registrar venta
              </button>
            </div>
          </form>
        </div>

        <!-- PANEL CARRITO -->
        <div class="venta-panel" style="margin-top: 14px;">
          <div class="venta-panel-header">
            <h2>Productos en la venta</h2>
          </div>

          <div class="table-wrap">
            <table class="venta-table">
              <thead>
              <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
              </thead>
              <tbody>
              <?php if (!empty($carritoDetalle)): ?>
                <?php foreach ($carritoDetalle as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['nombre']) ?></td>
                    <td><?= (int)$item['cantidad'] ?></td>
                    <td>$<?= number_format($item['precioUnit'], 2) ?></td>
                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                    <td>
                      <form method="post" action="venta_nueva.php#venta">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="idProducto" value="<?= $item['idProducto'] ?>">
                        <button type="submit" class="btn btn-link btn-link--danger" style="font-size: 12px;">
                          Quitar
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align:center; padding:12px; color:#9ca3af;">
                    No hay productos en la venta todavía.
                  </td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div class="venta-actions" style="justify-content: flex-start; margin-top:10px;">
            <form method="post" action="venta_nueva.php#venta">
              <input type="hidden" name="accion" value="vaciar">
              <button class="btn btn-ghost" type="submit" <?= empty($carritoDetalle) ? 'disabled' : '' ?>>
                Cancelar / Vaciar
              </button>
            </form>
          </div>
        </div>

      </section>

    </div><!-- /venta-layout -->

  </main>

<!-- JS para ocultar mensajes -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
      setTimeout(() => {
        alertBox.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        alertBox.style.opacity = '0';
        alertBox.style.transform = 'translateY(-4px)';
      }, 2500);
      setTimeout(() => { alertBox.remove(); }, 3200);
    }
  });
</script>

</body>
</html>
