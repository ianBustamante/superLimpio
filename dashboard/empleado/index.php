<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// =========================
//  Verificar sesión y tipo Empleado (vendedor)
// =========================
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../../modules/login.php");
    exit();
}

$idUsuario = (int)$_SESSION['idUsuario'];

$sql = "SELECT tipo, idRelacionado FROM usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$infoUsuario = $stmt->get_result()->fetch_assoc();

if (!$infoUsuario || $infoUsuario['tipo'] !== 'Empleado') {
    header("Location: ../../modules/login.php");
    exit();
}

$idEmpleado = (int)$infoUsuario['idRelacionado'];
$nombreUsuario = obtenerNombreUsuario($conn, $idUsuario);
$inicial = strtoupper(substr($nombreUsuario !== '' ? $nombreUsuario : 'V', 0, 1));

// =========================
//  Filtros de búsqueda
// =========================
$q  = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

// Productos base (con categoría)
$productos = obtenerProductos($conn); // ya trae Nombre, Descripcion, Precio, Stock, Categoria, idCategoria

// Filtrado en memoria (por texto y categoría)
if ($q !== '' || $categoriaFiltro !== '') {
    $productos = array_values(array_filter($productos, function($p) use ($q, $categoriaFiltro) {

        if ($q !== '') {
            $textoOk =
                stripos($p['Nombre'],        $q) !== false ||
                stripos($p['Descripcion'],   $q) !== false ||
                stripos($p['Categoria'],     $q) !== false ||
                stripos((string)$p['idProducto'], $q) !== false;
            if (!$textoOk) return false;
        }

        if ($categoriaFiltro !== '') {
            if (strcasecmp($p['Categoria'], $categoriaFiltro) !== 0) {
                return false;
            }
        }

        return true;
    }));
}

// Categorías para tarjetas y select
$sqlCats = "SELECT c.idCategoria, c.Nombre,
                   COUNT(p.idProducto) AS totalProductos
            FROM categoria c
            LEFT JOIN producto p ON p.idCategoria = c.idCategoria
            GROUP BY c.idCategoria, c.Nombre
            ORDER BY c.Nombre";
$resCats = $conn->query($sqlCats);
$categorias = $resCats ? $resCats->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Vendedor · Inventario</title>

  <!-- MISMO ESTILO QUE EL CATÁLOGO -->
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

    .search-bar{
      background:white;
      padding:12px 16px;
      border-radius:14px;
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom:18px;
      box-shadow:0 2px 6px rgba(0,0,0,0.05);
    }
    .search-bar input[type="text"]{
      border:none;
      outline:none;
      flex:1;
      font-size:15px;
      background:transparent;
    }
    .search-bar select{
      border-radius:10px;
      border:1px solid #d1d5db;
      padding:6px 8px;
      font-size:14px;
      background:#fff;
    }
    .search-bar button{
      border:none;
      background:#1f3bbf;
      color:#fff;
      padding:8px 14px;
      border-radius:10px;
      font-size:14px;
      font-weight:600;
      cursor:pointer;
    }
    .search-bar button:hover{background:#162a85;}

    .categories{
      display:flex;
      gap:14px;
      margin-bottom:22px;
      overflow-x:auto;
      padding-bottom:6px;
    }
    .categories::-webkit-scrollbar{height:6px;}
    .categories::-webkit-scrollbar-track{background:transparent;}
    .categories::-webkit-scrollbar-thumb{
      background:#c7d2fe;
      border-radius:999px;
    }

    .category-card{
      border:none;
      background:#ffffff;
      border-radius:18px;
      padding:10px 14px;
      display:flex;
      align-items:center;
      gap:10px;
      min-width:150px;
      cursor:pointer;
      box-shadow:0 3px 10px rgba(15, 23, 42, 0.06);
      transition:transform .14s ease,box-shadow .14s ease,background .14s ease;
      text-decoration:none;
      color:inherit;
      flex-shrink:0;
    }
    .category-card:hover{
      transform:translateY(-2px);
      box-shadow:0 5px 16px rgba(15,23,42,.12);
      background:#e5ebff;
    }
    .category-card--active{
      background:#d7e3ff;
      box-shadow:0 5px 18px rgba(15,23,42,.18);
    }

    .category-icon{
      width:40px;
      height:40px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#e0ebff;
      color:#1f3bbf;
      flex-shrink:0;
    }
    .category-card--active .category-icon{
      background:#1f3bbf;
      color:#fff;
    }
    .category-icon svg{width:20px;height:20px;}

    .category-text{display:flex;flex-direction:column;align-items:flex-start;}
    .category-title{font-size:14px;font-weight:700;color:#111827;margin-bottom:2px;}
    .category-count{font-size:11px;font-weight:500;color:#6b7280;}

    .table-wrap{
      margin-top:10px;
      background:#ffffff;
      border-radius:14px;
      box-shadow:0 2px 8px rgba(15,23,42,.06);
      overflow:hidden;
    }
    table{
      width:100%;
      border-collapse:collapse;
      font-size:13px;
    }
    thead{
      background:#e5e7eb;
    }
    th,td{
      padding:8px 10px;
      text-align:left;
    }
    tbody tr:nth-child(even){
      background:#f9fafb;
    }

    .badge-stock{
      display:inline-flex;
      align-items:center;
      padding:2px 8px;
      border-radius:999px;
      font-size:12px;
    }
    .badge-ok{
      background:#dcfce7;
      color:#166534;
    }
    .badge-low{
      background:#fef9c3;
      color:#92400e;
    }
    .badge-zero{
      background:#fee2e2;
      color:#b91c1c;
    }

    .empty-state{
      margin-top:16px;
      color:#6b7280;
      font-size:14px;
    }

    .btn-link {
      font-size: 12px;
      color: #1f3bbf;
      text-decoration: none;
      font-weight: 600;
    }
    .btn-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-card">
      <div class="logo">
        <span>Super</span>
        <span>Limpio</span>
      </div>

      <nav class="side-menu">
        <a href="index.php" class="side-item side-item--active">
          <div class="side-item-icon">
            <!-- casa -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M240,208H224V136l2.34,2.34A8,8,0,0,0,237.66,127L139.31,28.68a16,16,0,0,0-22.62,0L18.34,127a8,8,0,0,0,11.32,11.31L32,136v72H16a8,8,0,0,0,0,16H240a8,8,0,0,0,0-16ZM48,120l80-80,80,80v88H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48Zm96,88H112V160h32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Inventario</span>
        </a>

        <a href="venta_nueva.php" class="side-item">
          <div class="side-item-icon">
            <!-- carrito simple -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff" viewBox="0 0 256 256"><path d="M216,72H69.12L61.71,37.88A16,16,0,0,0,46.08,24H24a8,8,0,0,0,0,16H46.08l24.2,112.93A24,24,0,1,0,120,168h56a24,24,0,1,0,22.63-16.94L213,120l11.48-34.43A8,8,0,0,0,216,72ZM88,192a8,8,0,1,1-8-8A8,8,0,0,1,88,192Zm104,0a8,8,0,1,1-8-8A8,8,0,0,1,192,192Zm5-80H83.75L75.12,88H208.2Z"></path></svg>
          </div>
          <span class="side-item-label">Registrar venta</span>
        </a>

        <a href="../../modules/logout.php" class="side-item">
          <div class="side-item-icon">
            <!-- logout -->
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

  <!-- CONTENIDO -->
  <main class="content">

    <div class="header-row">
      <div>
        <h1>Inventario</h1>
        <p>Consulta los productos disponibles y su stock.</p>
      </div>
      <div class="avatar-circle"><?php echo $inicial; ?></div>
    </div>

    <!-- Buscador y filtros -->
    <form class="search-bar" method="get" action="index.php">
      <input
        type="text"
        name="q"
        placeholder="Buscar productos por nombre, descripción o código..."
        value="<?php echo htmlspecialchars($q); ?>"
      >

      <select name="categoria">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias as $cat): ?>
          <?php $nombreCat = $cat['Nombre']; ?>
          <option
            value="<?php echo htmlspecialchars($nombreCat); ?>"
            <?php echo ($categoriaFiltro === $nombreCat) ? 'selected' : ''; ?>
          >
            <?php echo htmlspecialchars($nombreCat); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Buscar</button>
    </form>

    <!-- Tarjetas de categorías con iconos -->
    <div class="categories">

      <!-- TODOS -->
      <a href="index.php"
         class="category-card <?php echo ($categoriaFiltro === '' && $q === '') ? 'category-card--active' : ''; ?>">
        <div class="category-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M104,40H56A16,16,0,0,0,40,56v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V56A16,16,0,0,0,104,40Zm0,64H56V56h48v48Zm96-64H152a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V56A16,16,0,0,0,200,40Zm0,64H152V56h48v48Zm-96,32H56a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V152A16,16,0,0,0,104,136Zm0,64H56V152h48v48Zm96-64H152a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V152A16,16,0,0,0,200,136Zm0,64H152V152h48v48Z"></path></svg>
        </div>
        <div class="category-text">
          <div class="category-title">Todos</div>
          <div class="category-count">Ver todos los productos</div>
        </div>
      </a>

      <?php
      // Array de iconos (INCLUYENDO '_default')
      $categoryIcons = [
        'Hogar' => '
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M247.63,73.37,182.63,28.69a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H216a8,8,0,0,0,0-16H115.32l112-112A16,16,0,0,0,227.32,73.37ZM92.69,208H48V163.31l88-88L180.69,120ZM192,108.69,147.32,64l24-24L216,84.69Z"></path></svg>
        ',
        'Detergentes' => '
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Zm0,176H48V48H208V208ZM128,64a64,64,0,1,0,64,64A64.07,64.07,0,0,0,128,64Zm0,112a48,48,0,1,1,48-48A48.05,48.05,0,0,1,128,176ZM200,68a12,12,0,1,1-12-12A12,12,0,0,1,200,68Zm-74.34,49.66-16,16a8,8,0,0,1-11.32-11.32l16-16a8,8,0,0,1,11.32,11.32Zm32-3.32a8,8,0,0,1,0,11.32l-32,32a8,8,0,0,1-11.32-11.32l32-32A8,8,0,0,1,157.66,114.34Z"></path></svg>
        ',
        'Desinfectantes' => '
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M200,80a8,8,0,0,0,8-8,56.06,56.06,0,0,0-56-56H80A16,16,0,0,0,64,32V80a24,24,0,0,1-24,24,8,8,0,0,0,0,16A40,40,0,0,0,80,80h32v24.62a23.87,23.87,0,0,1-9,18.74L87,136.15a39.79,39.79,0,0,0-15,31.23V224a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V211.47A270.88,270.88,0,0,0,174,80ZM80,32h72a40.08,40.08,0,0,1,39.2,32H80ZM192,211.47V224H88V167.38a23.87,23.87,0,0,1,9-18.74l16-12.79a39.79,39.79,0,0,0,15-31.23V80h27.52A254.86,254.86,0,0,1,192,211.47Z"></path></svg>
        ',
        'Cocina' => '
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Zm0,176H48V48H208V208ZM72,76A12,12,0,1,1,84,88,12,12,0,0,1,72,76Zm44,0a12,12,0,1,1,12,12A12,12,0,0,1,116,76Zm44,0a12,12,0,1,1,12,12A12,12,0,0,1,160,76Zm24,28H72a8,8,0,0,0-8,8v72a8,8,0,0,0,8,8H184a8,8,0,0,0,8-8V112A8,8,0,0,0,184,104Zm-8,72H80V120h96Z"></path></svg>
        ',
        'Baño' => '
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M120,64a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h16A8,8,0,0,1,120,64Zm52.32,133.14,3.52,24.6A16,16,0,0,1,160,240H96a16,16,0,0,1-15.84-18.26l3.52-24.6A96.09,96.09,0,0,1,32,112a8,8,0,0,1,8-8H56V40A16,16,0,0,1,72,24H184a16,16,0,0,1,16,16v64h16a8,8,0,0,1,8,8A96.09,96.09,0,0,1,172.32,197.14ZM72,104H184V40H72Zm85.07,99.5a96.15,96.15,0,0,1-58.14,0L96,224h64ZM207.6,120H48.4a80,80,0,0,0,159.2,0Z"></path></svg>
        ',
        '_default' => '
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M245.66,42.34l-32-32a8,8,0,0,0-11.32,11.32l1.48,1.47L148.65,64.51l-38.22,7.65a8.05,8.05,0,0,0-4.09,2.18L23,157.66a24,24,0,0,0,0,33.94L64.4,233a24,24,0,0,0,33.94,0l83.32-83.31a8,8,0,0,0,2.18-4.09l7.65-38.22,41.38-55.17,1.47,1.48a8,8,0,0,0,11.32-11.32Z"></path></svg>
        ',
      ];
      ?>

      <?php foreach ($categorias as $cat): ?>
        <a href="index.php?categoria=<?php echo urlencode($cat['Nombre']); ?>"
           class="category-card <?php echo ($categoriaFiltro === $cat['Nombre']) ? 'category-card--active' : ''; ?>">
          <div class="category-icon">
            <?php
              $nombreCat = $cat['Nombre'];
              echo $categoryIcons[$nombreCat] ?? $categoryIcons['_default'];
            ?>
          </div>
          <div class="category-text">
            <div class="category-title">
              <?php echo htmlspecialchars($cat['Nombre']); ?>
            </div>
            <div class="category-count">
              <?php echo (int)$cat['totalProductos']; ?> productos
            </div>
          </div>
        </a>
      <?php endforeach; ?>

    </div>

    <!-- Tabla de inventario -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Código</th>
            <th>Producto</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($productos)): ?>
          <?php foreach ($productos as $p): ?>
            <?php
              $stock = (int)$p['Stock'];
              if     ($stock === 0)       { $badgeClass = 'badge-zero'; $label='Sin stock'; }
              elseif ($stock <= 10)       { $badgeClass = 'badge-low';  $label="Bajo ($stock)"; }
              else                        { $badgeClass = 'badge-ok';   $label=$stock; }
            ?>
            <tr>
              <td>#<?php echo $p['idProducto']; ?></td>
              <td>
                <a href="producto_ver.php?id=<?= $p['idProducto'] ?>" class="btn-link">
                  <?php echo htmlspecialchars($p['Nombre']); ?>
                </a>
              </td>
              <td><?php echo htmlspecialchars($p['Categoria']); ?></td>
              <td>$<?php echo number_format($p['Precio'], 2); ?></td>
              <td>
                <span class="badge-stock <?php echo $badgeClass; ?>">
                  <?php echo $label; ?>
                </span>
              </td>
              <td>
                <a href="producto_ver.php?id=<?= $p['idProducto'] ?>" class="btn-link">Ver</a>
                &nbsp;|&nbsp;
                <a href="producto_editar.php?id=<?= $p['idProducto'] ?>" class="btn-link">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center; padding:12px; color:#9ca3af;">
              No hay productos que coincidan con los filtros.
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (empty($productos)): ?>
      <div class="empty-state">
        Ajusta la búsqueda o categoría para ver resultados.
      </div>
    <?php endif; ?>

  </main>
</body>
</html>
