<?php
session_start();
require_once '../../includes/connection.php';
require_once '../../includes/functions.php';

// =========================
//  Verificar sesión y tipo Cliente
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

if (!$infoUsuario || $infoUsuario['tipo'] !== 'Cliente') {
    header("Location: ../../modules/login.php");
    exit();
}

$idCliente = (int)$infoUsuario['idRelacionado'];
$cliente   = obtenerClientePorId($conn, $idCliente);
$nombre    = $cliente['Nombre'] ?? '';
$inicial   = strtoupper(substr($nombre, 0, 1));

// =========================
//  Mensajes (carrito, pago, etc.)
// =========================
$mensaje    = $_GET['msg']  ?? '';
$tipoMsg    = $_GET['type'] ?? '';

// Contador de artículos en carrito
$totalCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $totalCarrito += (int)$item['cantidad'];
    }
}

// =========================
//  Filtro de categoría y búsqueda
// =========================
$catId  = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$search = trim($_GET['q'] ?? '');

// CATEGORÍAS
$sqlCats = "SELECT c.idCategoria, c.Nombre,
                   COUNT(p.idProducto) AS totalProductos
            FROM categoria c
            LEFT JOIN producto p ON p.idCategoria = c.idCategoria
            GROUP BY c.idCategoria, c.Nombre
            ORDER BY c.Nombre";

$resCats = $conn->query($sqlCats);
$categorias = $resCats ? $resCats->fetch_all(MYSQLI_ASSOC) : [];

// PRODUCTOS (solo stock > 0)
$sqlProd = "SELECT p.idProducto,
                   p.Nombre,
                   p.Descripcion,
                   p.Precio,
                   p.Stock,
                   p.idCategoria,
                   c.Nombre AS Categoria
            FROM producto p
            INNER JOIN categoria c ON c.idCategoria = p.idCategoria
            WHERE p.Stock > 0";
$types  = '';
$params = [];

// Filtro por categoría
if ($catId > 0) {
    $sqlProd .= " AND p.idCategoria = ?";
    $types   .= 'i';
    $params[] = $catId;
}

// Filtro por texto
if ($search !== '') {
    $sqlProd .= " AND (p.Nombre LIKE ? OR p.Descripcion LIKE ?)";
    $types   .= 'ss';
    $like = '%'.$search.'%';
    $params[] = $like;
    $params[] = $like;
}

$sqlProd .= " ORDER BY p.idProducto DESC";

$stmtProd = $conn->prepare($sqlProd);
if ($types !== '') {
    $stmtProd->bind_param($types, ...$params);
}
$stmtProd->execute();
$resProd   = $stmtProd->get_result();
$productos = $resProd ? $resProd->fetch_all(MYSQLI_ASSOC) : [];

// Lógica extra de categoría única
if ($catId === 0 && $search !== '' && !empty($productos)) {
    $uniqueCats = array_unique(array_column($productos, 'idCategoria'));
    if (count($uniqueCats) === 1) {
        $catId = (int)$uniqueCats[0];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cliente · Catálogo de productos</title>

  <style>
    /* ======================  RESET  ====================== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      display: flex;
      height: 100vh;
      background: #0b1f6b;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
    }

    /* ======================  SIDEBAR  ====================== */
    .sidebar {
      width: 190px;
      padding: 18px 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .sidebar-card {
      width: 100%;
      height: 100%;
      border-radius: 32px;
      padding: 26px 20px;
      background: linear-gradient(180deg, #2141ff, #04145a);
      color: #eef3ff;
      display: flex;
      flex-direction: column;
    }

    .logo {
      font-size: 24px;
      font-weight: 900;
      letter-spacing: 0.8px;
      margin-bottom: 32px;
    }

    .logo span {
      display: block;
    }

    .side-menu {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-top: 8px;
    }

    .side-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px;
      border-radius: 999px;
      text-decoration: none;
      color: inherit;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.18s ease, transform 0.12s ease;
    }

    .side-item:hover {
      background: rgba(255,255,255,0.14);
      transform: translateX(2px);
    }

    .side-item--active {
      background: #eef3ff;
      color: #1f3bbf;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(0,0,0,0.28);
    }

    .side-item-icon {
      width: 28px;
      height: 28px;
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255,255,255,0.18);
    }

    .side-item--active .side-item-icon {
      background: #1f3bbf;
      color: #ffffff;
    }

    .side-item-icon svg {
      width: 18px;
      height: 18px;
    }

    .side-item-label {
      margin-top: 2px;
    }

    .side-footer {
      margin-top: auto;
      font-size: 11px;
      opacity: 0.75;
    }

    /* ======================  CONTENT AREA  ====================== */
    .content {
      flex: 1;
      padding: 26px 26px 26px 10px;
      background: #f3f5ff;
      border-top-left-radius: 32px;
      border-bottom-left-radius: 32px;
      box-shadow: -8px 0 18px rgba(0,0,0,0.35);
      overflow-y: auto;
    }

    /* ALERTAS */
    .alert {
      padding: 10px 14px;
      border-radius: 10px;
      margin-bottom: 12px;
      font-size: 14px;
      font-weight: 600;
    }
    .alert.exito {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #bbf7d0;
    }
    .alert.error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }

    /* ---------- BUSCADOR ---------- */
    .search-bar {
      background: white;
      padding: 12px 16px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 22px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .search-bar input {
      border: none;
      outline: none;
      flex: 1;
      font-size: 15px;
      background: transparent;
    }

    .search-bar button {
      border: none;
      background: #1f3bbf;
      color: #fff;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
    }

    .search-bar button:hover {
      background: #162a85;
    }

    /* ---------- CATEGORÍAS ---------- */
    .categories {
      display: flex;
      gap: 14px;
      margin-bottom: 22px;
      overflow-x: auto;
      padding-bottom: 6px;
    }

    .categories::-webkit-scrollbar {
      height: 6px;
    }
    .categories::-webkit-scrollbar-track {
      background: transparent;
    }
    .categories::-webkit-scrollbar-thumb {
      background: #c7d2fe;
      border-radius: 999px;
    }

    .category-card {
      border: none;
      background: #ffffff;
      border-radius: 18px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 150px;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06);
      transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.14s ease;
      text-decoration: none;
      color: inherit;
      flex-shrink: 0;
    }

    .category-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 16px rgba(15, 23, 42, 0.12);
      background: #e5ebff;
    }

    .category-card--active {
      background: #d7e3ff;
      box-shadow: 0 5px 18px rgba(15, 23, 42, 0.18);
    }

    .category-icon {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e0ebff;
      color: #1f3bbf;
      flex-shrink: 0;
    }

    .category-card--active .category-icon {
      background: #1f3bbf;
      color: #ffffff;
    }

    .category-icon svg {
      width: 20px;
      height: 20px;
    }

    .category-text {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .category-title {
      font-size: 14px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 2px;
    }

    .category-count {
      font-size: 11px;
      font-weight: 500;
      color: #6b7280;
    }

    /* ---------- PRODUCT GRID ---------- */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 14px;
    }

    .product-card {
      background: white;
      padding: 10px;
      border-radius: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);
      display: flex;
      flex-direction: column;
    }

    .product-card img {
      width: 100%;
      height: 110px;
      object-fit: contain;
      border-radius: 10px;
      background: #e5e7eb;
    }

    .product-card h3 {
      margin: 8px 0 2px;
      font-size: 15px;
      line-height: 1.2;
    }

    .product-card p {
      font-size: 12px;
      color: #6b7280;
      margin-bottom: 6px;
      min-height: 28px;
    }

    .price {
      font-size: 14px;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 4px;
    }

    .stock-label {
      font-size: 12px;
      color: #374151;
      margin-bottom: 6px;
    }

    .product-actions {
      display: flex;
      gap: 6px;
      margin-top: auto;
    }

    .btn-view {
      flex: 1;
      padding: 6px;
      background: #e5e7eb;
      color: #111827;
      border-radius: 8px;
      border: none;
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      text-decoration: none;
      cursor: pointer;
    }

    .btn-view:hover {
      background: #d1d5db;
    }

    .add-btn {
      flex: 1;
      padding: 6px;
      background: #1f3bbf;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
    }

    .add-btn:hover {
      background: #162a85;
    }

    @media (min-width: 1200px) {
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      }
    }

    .empty-state {
      margin-top: 20px;
      color: #6b7280;
      font-size: 14px;
    }

    .badge-cart {
      background: #1f3bbf;
      color: #fff;
      border-radius: 999px;
      padding: 2px 8px;
      font-size: 11px;
      margin-left: 6px;
    }
  </style>
</head>

<body>

  <!-- ====================== SIDEBAR ====================== -->
  <aside class="sidebar">
    <div class="sidebar-card">

      <div class="logo">
        <span>Super</span>
        <span>Limpio</span>
      </div>

      <nav class="side-menu">
        <!-- CATÁLOGO -->
        <a href="index.php" class="side-item side-item--active">
          <div class="side-item-icon">
            <!-- icono casa -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M240,208H224V136l2.34,2.34A8,8,0,0,0,237.66,127L139.31,28.68a16,16,0,0,0-22.62,0L18.34,127a8,8,0,0,0,11.32,11.31L32,136v72H16a8,8,0,0,0,0,16H240a8,8,0,0,0,0-16ZM48,120l80-80,80,80v88H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48Zm96,88H112V160h32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Catálogo</span>
        </a>

        <!-- CARRITO -->
        <a href="carrito.php" class="side-item">
          <div class="side-item-icon">
            <!-- icono carrito -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff" viewBox="0 0 256 256">
              <path d="M223.89,58.68A8,8,0,0,0,216,56H54.12L49.72,33.79A16,16,0,0,0,34,21.33H16A8,8,0,0,0,16,37H34l22.91,114.57A24,24,0,1,0,104,168h72a24,24,0,1,0,23.32-19.75L215.72,96H224a8,8,0,0,0,0-16H213.39l-3.2-16.32A8,8,0,0,0,223.89,58.68ZM96,192a8,8,0,1,1-8-8A8,8,0,0,1,96,192Zm96,0a8,8,0,1,1-8-8A8,8,0,0,1,192,192Z"></path>
            </svg>
          </div>
          <span class="side-item-label">
            Carrito
            <?php if ($totalCarrito > 0): ?>
              <span class="badge-cart"><?= $totalCarrito ?></span>
            <?php endif; ?>
          </span>
        </a>

        <!-- MI PERFIL -->
        <a href="perfil.php" class="side-item">
          <div class="side-item-icon">
            <!-- icono usuario -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M230.94,212.61a8,8,0,0,1-11.55.78C203.51,199,177,184,128,184s-75.52,15-91.39,29.39a8,8,0,1,1-10.78-11.78C44,184.14,74.64,168,128,168s84,16.14,102.17,33.61A8,8,0,0,1,230.94,212.61ZM128,152a56,56,0,1,0-56-56A56.06,56.06,0,0,0,128,152Zm0-96a40,40,0,1,1-40,40A40,40,0,0,1,128,56Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Mi perfil</span>
        </a>

        <!-- CERRAR SESIÓN -->
        <a href="../../modules/logout.php" class="side-item">
          <div class="side-item-icon">
            <!-- icono logout -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M141.66,133.66l-40,40a8,8,0,0,1-11.32-11.32L116.69,136H24a8,8,0,0,1,0-16h92.69L90.34,93.66a8,8,0,0,1,11.32-11.32l40,40A8,8,0,0,1,141.66,133.66ZM200,32H136a8,8,0,0,0,0,16h56V208H136a8,8,0,0,0,0,16h64a8,8,0,0,0,8-8V40A8,8,0,0,0,200,32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Cerrar sesión</span>
        </a>
      </nav>

      <div class="side-footer">
        Cliente · POS
      </div>

    </div>
  </aside>

  <!-- ====================== CONTENT ====================== -->
  <main class="content">

    <?php if ($mensaje): ?>
      <div class="alert <?= htmlspecialchars($tipoMsg) ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <!-- Buscador -->
    <form class="search-bar" method="get" action="index.php">
      <input
        type="text"
        name="q"
        placeholder="Buscar productos de limpieza..."
        value="<?php echo htmlspecialchars($search); ?>"
      >
      <?php if ($catId > 0): ?>
        <input type="hidden" name="cat" value="<?php echo $catId; ?>">
      <?php endif; ?>
      <button type="submit">Buscar</button>
    </form>

    <!-- CATEGORÍAS -->
    <div class="categories">

      <!-- TODOS -->
      <a href="index.php"
         class="category-card <?php echo ($catId === 0) ? 'category-card--active' : ''; ?>">
        <div class="category-icon">
           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M104,40H56A16,16,0,0,0,40,56v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V56A16,16,0,0,0,104,40Zm0,64H56V56h48v48Zm96-64H152a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V56A16,16,0,0,0,200,40Zm0,64H152V56h48v48Zm-96,32H56a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V152A16,16,0,0,0,104,136Zm0,64H56V152h48v48Zm96-64H152a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V152A16,16,0,0,0,200,136Zm0,64H152V152h48v48Z"></path></svg>
        </div>
        <div class="category-text">
          <div class="category-title">Todos</div>
          <div class="category-count">Ver todos los productos</div>
        </div>
      </a>

      <?php
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
        <a href="index.php?cat=<?php echo $cat['idCategoria']; ?><?php echo $search !== '' ? '&q='.urlencode($search) : ''; ?>"
           class="category-card <?php echo ($catId === (int)$cat['idCategoria']) ? 'category-card--active' : ''; ?>">
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

    <!-- GRID DE PRODUCTOS -->
    <div class="products-grid">
      <?php foreach ($productos as $p): ?>
        <div class="product-card">
          <img src="../../assets/img/clean-products.png" alt="Imagen producto">
          <h3><?php echo htmlspecialchars($p['Nombre']); ?></h3>
          <p><?php echo htmlspecialchars($p['Descripcion']); ?></p>
          <div class="price">$<?php echo number_format($p['Precio'], 2); ?></div>
          <div class="stock-label">
            Stock: <strong><?php echo (int)$p['Stock']; ?></strong>
          </div>

          <div class="product-actions">
            <a href="producto_ver.php?id=<?= $p['idProducto'] ?>" class="btn-view">Ver</a>

            <form method="post" action="carrito_agregar.php" style="margin:0; flex:1;">
              <input type="hidden" name="idProducto" value="<?= $p['idProducto'] ?>">
              <button class="add-btn" type="submit">Agregar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($productos)): ?>
      <div class="empty-state">
        No se encontraron productos para esta búsqueda / categoría.
      </div>
    <?php endif; ?>

  </main>
</body>
</html>
