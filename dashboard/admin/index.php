<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php");
  exit();
}

// Parámetros de filtro
$q  = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

// Datos base
$productos   = obtenerProductos($conn);
$categorias  = obtenerCategorias($conn); // para el select de filtro

// Aplicar filtros en memoria
if ($q !== '' || $categoriaFiltro !== '') {
  $productos = array_values(array_filter($productos, function($p) use ($q, $categoriaFiltro) {

    // Filtro de texto
    if ($q !== '') {
      $textoOk =
        stripos($p['Nombre'],       $q) !== false ||
        stripos($p['Descripcion'],  $q) !== false ||
        stripos($p['Categoria'],    $q) !== false;
      if (!$textoOk) {
        return false;
      }
    }

    // Filtro por categoría (comparación por nombre de categoría)
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
  <title>Administrador · Productos</title>
  <link rel="stylesheet" href="../../assets/css/style.css" />
  <link rel="stylesheet" href="../../assets/css/admin.css" />
</head>
<body class="admin-body">

<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="admin-logo">
      Super Limpio
      <span>Panel de administración</span>
    </div>

    <nav class="admin-nav">
  <!-- Productos activo -->
  <a href="index.php" class="admin-nav-item is-active">
    <span class="label">Productos</span>
  </a>

  <!-- Usuarios (placeholder, sin 'Próximamente') -->
  <a href="#!" class="admin-nav-item">
    <span class="label">Usuarios</span>
  </a>
</nav>

  </aside>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="admin-main">

    <!-- Header superior -->
    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Productos</h1>
        <p>Gestión del catálogo de productos de limpieza.</p>
        <div class="admin-header-meta">
          <?= $totalProductos ?> producto<?= $totalProductos === 1 ? '' : 's' ?> encontrado<?= $totalProductos === 1 ? '' : 's' ?>.
        </div>
      </div>

      <div>
        <a href="producto_crear.php" class="btn btn-primary">
          + Nuevo producto
        </a>
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
            placeholder="Nombre, descripción o categoría"
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
          <a href="index.php" class="btn btn-ghost">Limpiar</a>
        <?php endif; ?>
      </form>
    </section>

    <!-- Lista de productos -->
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
            <col style="width:14%">
          </colgroup>

          <thead>
          <tr>
            <th>ID</th>
            <th>Nombre del producto</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Categoría</th>
            <th>Acciones</th>
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
              <td class="c-center">
                <a
                  href="edicion_producto.php?id=<?= $p['idProducto'] ?>"
                  class="btn-link"
                >Editar</a>
                &nbsp;|&nbsp;
                <a
                  href="producto_eliminar.php?id=<?= $p['idProducto'] ?>"
                  class="btn-link btn-link--danger"
                >Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($productos)): ?>
            <tr>
              <td colspan="7" style="padding: 18px; text-align:center; color:#9ca3af;">
                No se encontraron productos con los filtros actuales.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="admin-table-footer">
        <span>Mostrando <?= $totalProductos ?> elemento<?= $totalProductos === 1 ? '' : 's' ?>.</span>
        <!-- Aquí podrías poner paginación más adelante -->
      </div>
    </section>

  </main>
</div>

</body>
</html>
