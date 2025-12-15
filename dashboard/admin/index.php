<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php");
  exit();
}

$permisosProd = obtenerPermisosProductos($conn, $_SESSION['idUsuario']);

// Parámetros de filtro
$q  = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

// Datos base
$productos   = $permisosProd['puede_consultar'] ? obtenerProductos($conn) : [];
$categorias  = obtenerCategorias($conn); // para el select de filtro

// Aplicar filtros en memoria
if ($permisosProd['puede_consultar'] && ($q !== '' || $categoriaFiltro !== '')) {
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
$mensaje = $_GET['msg'] ?? '';
$tipoMensaje = $_GET['type'] ?? '';
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
  <!-- SIDEBAR -->
<aside class="admin-sidebar">
  <div class="admin-logo">
    Super Limpio
    <span>Panel de administración</span>
  </div>

  <nav class="admin-nav">
    <!-- Productos -->
    <a href="index.php"
       class="admin-nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'is-active' : '' ?>">
      <span class="label">Productos</span>
    </a>

    <!-- Usuarios -->
    <a href="usuarios.php"
       class="admin-nav-item <?= basename($_SERVER['PHP_SELF']) === 'usuarios.php' || strpos(basename($_SERVER['PHP_SELF']), 'usuario_') === 0 ? 'is-active' : '' ?>">
      <span class="label">Usuarios</span>
    </a>

    <!-- Roles y permisos -->
    <a href="roles_permisos.php"
       class="admin-nav-item <?= basename($_SERVER['PHP_SELF']) === 'roles_permisos.php' ? 'is-active' : '' ?>">
      <span class="label">Roles y permisos</span>
    </a>

    <!-- Reportes de ventas -->
    <a href="reportes_ventas.php"
       class="admin-nav-item <?= basename($_SERVER['PHP_SELF']) === 'reportes_ventas.php' ? 'is-active' : '' ?>">
      <span class="label">Reportes de ventas</span>
    </a>

    <!-- Cerrar sesión -->
    <a href="../../modules/logout.php" class="admin-nav-item">
      <span class="label">Cerrar sesión</span>
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
        <?php if ($permisosProd['puede_registrar']): ?>
          <a href="producto_crear.php" class="btn btn-primary">
            + Nuevo producto
          </a>
        <?php else: ?>
          <span class="btn btn-ghost" style="opacity:0.6; cursor:not-allowed;" title="Sin permiso para registrar">+ Nuevo producto</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Filtros: búsqueda + categoría -->
    <?php if ($mensaje): ?>
      <div class="alert <?= htmlspecialchars($tipoMensaje) ?>" style="margin-bottom:12px;">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <?php if (!$permisosProd['puede_consultar']): ?>
      <div class="alert error" style="margin-bottom:12px;">
        No tienes permiso para consultar productos.
      </div>
    <?php endif; ?>

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
          <?php if (!$permisosProd['puede_consultar']): ?>
            <tr>
              <td colspan="7" style="padding: 18px; text-align:center; color:#9ca3af;">
                No tienes permiso para ver la lista de productos.
              </td>
            </tr>
          <?php else: ?>
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
                <?php if ($permisosProd['puede_modificar']): ?>
                  <a
                    href="edicion_producto.php?id=<?= $p['idProducto'] ?>"
                    class="btn-link"
                  >Editar</a>
                <?php else: ?>
                  <span style="color:#9ca3af;">Editar</span>
                <?php endif; ?>
                &nbsp;|&nbsp;
                <?php if ($permisosProd['puede_eliminar']): ?>
                  <a
                    href="producto_eliminar.php?id=<?= $p['idProducto'] ?>"
                    class="btn-link btn-link--danger"
                  >Eliminar</a>
                <?php else: ?>
                  <span style="color:#9ca3af;">Eliminar</span>
                <?php endif; ?>
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
