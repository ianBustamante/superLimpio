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
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/admin.css">
  <link rel="stylesheet" href="../../assets/css/dashboard-modern.css">
  <link rel="stylesheet" href="../../assets/css/avatar.css">
  <link rel="stylesheet" href="../../assets/css/venta.css">
</head>
<body class="admin-body">

<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      Super Limpio
      <span>Panel de vendedor</span>
    </div>

    <nav class="admin-nav">
      <a href="index.php" class="admin-nav-item">
        <span class="label">Inventario</span>
      </a>
      <a href="venta_nueva.php" class="admin-nav-item is-active">
        <span class="label">Registrar venta</span>
      </a>
      <a href="../../modules/logout.php" class="admin-nav-item">
        <span class="label">Cerrar sesión</span>
      </a>
    </nav>
  </aside>

  <!-- CONTENIDO -->
  <main class="admin-main">

    <!-- Header con avatar -->
    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Registrar venta</h1>
        <p>Selecciona los productos y completa los datos del cliente.</p>
      </div>
      <div class="header-icons">
        <div class="avatar-circle"><?php echo $inicial; ?></div>
      </div>
    </div>

    <!-- LAYOUT DE VENTA -->
    <div class="venta-layout">

      <!-- COLUMNA IZQUIERDA: PRODUCTOS -->
      <section class="venta-col venta-col-left" id="productos">
        <div class="venta-panel">
          <div class="venta-panel-header">
            <h2>Productos</h2>
            <p>Busca y agrega productos a la venta.</p>
          </div>

          <!-- Filtros como en Inventario -->
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
                      <!-- Al enviar, hacemos POST y volvemos a #productos para no subir arriba -->
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

        <!-- PANEL CLIENTE + TOTAL + REGISTRAR -->
        <div class="venta-panel">
          <div class="venta-panel-header">
            <h2>Cliente y resumen</h2>
          </div>

          <!-- Mensaje aquí, cerca de la acción, no arriba de la página -->
          <?php if ($mensaje): ?>
            <div class="alert <?= $tipoMensaje; ?>" style="margin-bottom: 10px;">
              <?= htmlspecialchars($mensaje); ?>
            </div>
          <?php endif; ?>

          <!-- Form para REGISTRAR VENTA -->
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
              <input type="text" class="venta-input" name="cliente_rapido" placeholder="Nombre del cliente (no se guarda)">
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

        <!-- PANEL CARRITO / DETALLE -->
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
</div>

<!-- JS sencillo para que los mensajes desaparezcan -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
      setTimeout(() => {
        alertBox.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        alertBox.style.opacity = '0';
        alertBox.style.transform = 'translateY(-4px)';
      }, 2500);
      setTimeout(() => {
        alertBox.remove();
      }, 3200);
    }
  });
</script>

</body>
</html>
