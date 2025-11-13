<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['idUsuario']) || !esAdmin($conn, $_SESSION['idUsuario'])) {
  header("Location: ../../modules/login.php"); exit();
}

$q = trim($_GET['q'] ?? '');
$productos = obtenerProductos($conn);
if ($q !== '') {
  $productos = array_values(array_filter($productos, function($p) use($q){
    return stripos($p['Nombre'],$q)!==false || stripos($p['Descripcion'],$q)!==false || stripos($p['Categoria'],$q)!==false;
  }));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Administrador · Gestión de productos</title>
  <link rel="stylesheet" href="../../assets/css/style.css" />
  <style>
    :root{
      --ink:#0b2240;
      --sidebar:#e6f3f3;      /* azul verdoso muy suave (como tu wireframe) */
      --pill:#f4f5ef;         /* pastilla cabecera tabla */
      --line:#e5e7eb;
    }

    /* ====== LAYOUT “5/6 – 1/6” ====== */
    .admin-slim{display:flex; min-height:100vh; background:#fff;}
    .sidebar-slim{
      flex:1; max-width:240px; min-width:200px;
      background:var(--sidebar); border-right:3px solid #0b2240;
      padding:24px 18px;
    }
    .sidebar-title{
      font-weight:900; color:var(--ink); letter-spacing:.3px;
      font-size:26px; line-height:1.1; margin:0 0 22px;
    }
    .side-links a{
      display:block; color:var(--ink); text-decoration:none;
      font-weight:800; margin:12px 0; padding-left:10px;
    }
    .side-links a:hover{ text-decoration:underline; }

    .main-wide{ flex:5; padding:28px 28px 40px; }
    .main-wide h1{
      margin:0 0 18px; text-align:center; color:#000;
      letter-spacing:1px; font-size:42px; font-weight:900;
    }

    /* ====== ACCIONES DE LISTA ====== */
    .actions{display:flex; gap:10px; align-items:center; margin:2px 0 14px;}
    .actions .btn{border:1px solid var(--line); padding:9px 14px; border-radius:10px; background:#fff; font-weight:700; color:var(--ink);}
    .actions .btn-primary{background:#0b2240; border-color:#0b2240; color:#fff;}
    .actions .input{border:1px solid var(--line); border-radius:10px; padding:9px 12px; min-width:280px;}

    /* ====== TABLA ====== */
    .table-wrap{overflow-x:auto;}
    table.pretty{width:100%; border-collapse:separate; border-spacing:0; color:#111;}
    table.pretty thead th{
      background:var(--pill); color:#0b2240; font-weight:900;
      padding:16px 14px; font-size:20px; border-top:1px solid var(--line);
    }
    table.pretty thead th:first-child{border-top-left-radius:18px;}
    table.pretty thead th:last-child{border-top-right-radius:18px;}
    table.pretty tbody td{
      padding:12px 14px; border-bottom:1px solid var(--line); vertical-align:top;
    }
    .badge{display:inline-block; padding:4px 10px; border-radius:999px; background:#eef2ff; color:#1e3a8a; font-weight:800; font-size:.85rem;}
    .btn-link{font-weight:800; color:#0b2240; text-decoration:none; margin-left:6px;}
    .btn-link:hover{text-decoration:underline;}
  </style>
</head>
<body>
<div class="admin-slim">

  <!-- Sidebar (1/6) -->
  <aside class="sidebar-slim">
    <div class="sidebar-title">GESTIÓN DE<br>PRODUCTOS</div>
    <nav class="side-links">
      <a href="producto_crear.php">&gt; REGISTRO</a>
    </nav>
  </aside>

  <!-- Main (5/6) -->
  <main class="main-wide">
    <h1>BIENVENIDO</h1>

    <div class="actions">
      
      <form method="get" style="margin-left:auto; display:flex; gap:8px;">
        <input class="input" type="text" name="q" placeholder="Buscar nombre / descripción / categoría" value="<?= htmlspecialchars($q) ?>">
        <button class="btn" type="submit">Buscar</button>
      </form>
    </div>

    <div class="table-wrap">
  <table class="pretty">
    <!-- controla anchos por columna -->
    <colgroup>
      <col style="width:7%">
      <col style="width:24%">
      <col style="width:33%">
      <col style="width:12%">
      <col style="width:10%">
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
    <th>Acciones</th> <!-- Nueva columna -->
  </tr>
</thead>

<tbody>
  <?php foreach($productos as $p): ?>
    <tr>
      <td class="c-center"><?= $p['idProducto'] ?></td>
      <td class="c-left"><strong><?= htmlspecialchars($p['Nombre']) ?></strong></td>
      <td class="c-left"><?= htmlspecialchars($p['Descripcion']) ?></td>
      <td class="c-right">$<?= number_format($p['Precio'],2) ?></td>
      <td class="c-right"><?= (int)$p['Stock'] ?></td>
      <td class="c-center"><span class="badge"><?= htmlspecialchars($p['Categoria']) ?></span></td>
      <td class="c-center">
        <a href="edicion_producto.php?id=<?= $p['idProducto'] ?>" class="btn-link">Editar</a> |
        <a href="producto_eliminar.php?id=<?= $p['idProducto'] ?>" class="btn-link" style="color:#b91c1c;">Eliminar</a>
      </td>
    </tr>
  <?php endforeach; ?>

  <?php if (empty($productos)): ?>
    <tr><td colspan="7" style="padding:20px; color:#6b7280;">No hay productos registrados.</td></tr>
  <?php endif; ?>
</tbody>

  </table>
</div>

  </main>

</div>
</body>
</html>

