<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// 1) Verificar que haya sesión
if (!isset($_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php");
  exit();
}

// 2) Verificar que el usuario sea EMPLEADO (vendedor)
$idUsuario = (int)$_SESSION['idUsuario'];

$sqlTipo = "SELECT tipo FROM usuario WHERE idUsuario = ?";
$stmtTipo = $conn->prepare($sqlTipo);
$stmtTipo->bind_param("i", $idUsuario);
$stmtTipo->execute();
$infoUsuario = $stmtTipo->get_result()->fetch_assoc();

if (!$infoUsuario || $infoUsuario['tipo'] !== 'Empleado') {
  // Si no es empleado, lo regresamos al login (luego puedes redirigir mejor)
  header("Location: ../../modules/login.php");
  exit();
}

// Nombre e inicial para el avatar
$nombre  = obtenerNombreUsuario($conn, $idUsuario);
$inicial = strtoupper(substr($nombre, 0, 1));

// Filtros
$q  = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

// Datos base
$productos   = obtenerProductos($conn);      // de functions.php
$categorias  = obtenerCategorias($conn);     // de functions.php

// Aplicar filtros en memoria (igual que en admin)
if ($q !== '' || $categoriaFiltro !== '') {
  $productos = array_values(array_filter($productos, function($p) use ($q, $categoriaFiltro) {

    // Filtro de texto (nombre / descripción / categoría / ID)
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

    // Filtro por categoría (por nombre)
    if ($categoriaFiltro !== '') {
      if (strcasecmp($p['Categoria'], $categoriaFiltro) !== 0) {
        return false;
      }
    }

    return true;
  }));
}

$totalProductos = count($productos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Vendedor · Inventario</title>
  <link rel="stylesheet" href="../../assets/css/style.css" />
  <link rel="stylesheet" href="../../assets/css/admin.css" />
  <link rel="stylesheet" href="../../assets/css/dashboard-modern.css">
  <link rel="stylesheet" href="../../assets/css/avatar.css"><!-- tu css de avatar -->
</head>
<body class="admin-body"><!-- reutilizamos mismo layout -->

<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      Super Limpio
      <span>Panel de vendedor</span>
    </div>

    <nav class="admin-nav">
      <!-- Inventario activo -->
      <a href="inventario.php" class="admin-nav-item is-active">
        <span class="label">Inventario</span>
      </a>

      <!-- Registrar venta (lo crearemos después) -->
      <a href="venta_nueva.php" class="admin-nav-item">
        <span class="label">Registrar venta</span>
      </a>

      <a href="../../modules/logout.php" class="admin-nav-item">
        <span class="label">Cerrar sesión</span>
      </a>
    </nav>
  </aside>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="admin-main">

    <!-- Header superior con avatar -->
    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Inventario</h1>
        <p>Consulta de productos disponibles para la venta.</p>
        <div class="admin-header-meta">
          <?= $totalProductos ?> producto<?= $totalProductos === 1 ? '' : 's' ?> encontrado<?= $totalProductos === 1 ? '' : 's' ?>.
        </div>
      </div>

      <div class="header-icons">
        
        <div class="avatar-circle"><?php echo $inicial; ?></div>
      </div>
    </div>

    <!-- Filtros: búsqueda + categoría -->
    <section class="admin-filters">
      <form method="get" class="admin-filters-form">
        <div class="field-group">
          <span class="field-label">Búsqueda</span>
          <input
            class="field-input"
            type="text"
            name="q"
            placeholder="Nombre, descripción o código (ID)"
            value="<?= htmlspecialchars($q) ?>"
          >
        </div>

        <div class="field-group">
          <span class="field-label">Categoría</span>
          <select name="categoria" class="field-select">
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
        </div>

        <button class="btn btn-primary" type="submit">Aplicar filtros</button>

        <?php if ($q !== '' || $categoriaFiltro !== ''): ?>
          <a href="inventario.php" class="btn btn-ghost">Limpiar</a>
        <?php endif; ?>
      </form>
    </section>

    <!-- Lista de productos (solo lectura) -->
    <section class="admin-card">
      <div class="table-wrap">
        <table class="admin-table">
          <colgroup>
            <col style="width:7%">
            <col style="width:24%">
            <col style="width:33%">
            <col style="width:10%">
            <col style="width:8%">
            <col style="width:12%">
          </colgroup>

          <thead>
          <tr>
            <th>ID</th>
            <th>Nombre del producto</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Categoría</th>
          </tr>
          </thead>

          <tbody>
          <?php foreach ($productos as $p): ?>
            <tr>
              <td class="c-center">#<?= $p['idProducto'] ?></td>
              <td class="c-left">
                <strong><?= htmlspecialchars($p['Nombre']) ?></strong>
              </td>
              <td class="c-left"><?= htmlspecialchars($p['Descripcion']) ?></td>
              <td class="c-right">$<?= number_format($p['Precio'], 2) ?></td>
              <td class="c-right"><?= (int)$p['Stock'] ?></td>
              <td class="c-center">
                <span class="badge"><?= htmlspecialchars($p['Categoria']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($productos)): ?>
            <tr>
              <td colspan="6" style="padding: 18px; text-align:center; color:#9ca3af;">
                No se encontraron productos en el inventario con los filtros actuales.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="admin-table-footer">
        <span>Mostrando <?= $totalProductos ?> elemento<?= $totalProductos === 1 ? '' : 's' ?>.</span>
      </div>
    </section>

  </main>
</div>

</body>
</html>
