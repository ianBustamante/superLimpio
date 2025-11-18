<?php
// =================== PHP: CONEXIÓN Y CONSULTAS ===================
require_once 'includes/connection.php';
require_once 'includes/functions.php';

// 1) Filtro de categoría y búsqueda
$catId  = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$search = trim($_GET['q'] ?? '');

// 2) Cargar CATEGORÍAS con conteo de productos
$sqlCats = "SELECT c.idCategoria, c.Nombre,
                   COUNT(p.idProducto) AS totalProductos
            FROM Categoria c
            LEFT JOIN Producto p ON p.idCategoria = c.idCategoria
            GROUP BY c.idCategoria, c.Nombre
            ORDER BY c.Nombre";

$resCats = $conn->query($sqlCats);
$categorias = $resCats ? $resCats->fetch_all(MYSQLI_ASSOC) : [];

// 3) Cargar PRODUCTOS (filtrados por categoría y/o búsqueda)
$sqlProd = "SELECT p.idProducto,
                   p.Nombre,
                   p.Descripcion,
                   p.Precio,
                   p.idCategoria,          -- 👈 añadimos el idCategoria del producto
                   c.Nombre AS Categoria
            FROM Producto p
            INNER JOIN Categoria c ON c.idCategoria = p.idCategoria
            WHERE 1=1";
$types  = '';
$params = [];

// Filtro por categoría
if ($catId > 0) {
    $sqlProd .= " AND p.idCategoria = ?";
    $types   .= 'i';
    $params[] = $catId;
}

// Filtro por texto (nombre o descripción)
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
$resProd    = $stmtProd->get_result();
$productos  = $resProd ? $resProd->fetch_all(MYSQLI_ASSOC) : [];

/*
 * 🔵 LÓGICA EXTRA:
 * Si el usuario solo hizo búsqueda (q != '') SIN seleccionar categoría (cat=0),
 * y todos los productos encontrados son de UNA sola categoría,
 * marcamos esa categoría como activa para que se resalte la tarjeta correcta.
 */
if ($catId === 0 && $search !== '' && !empty($productos)) {
    // Obtenemos todos los idCategoria de los productos
    $uniqueCats = array_unique(array_column($productos, 'idCategoria'));

    // Si solo hay una categoría en los resultados, la usamos para el highlight
    if (count($uniqueCats) === 1) {
        $catId = (int)$uniqueCats[0];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Super Limpio – POS</title>

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
      background: #0b1f6b; /* fondo azul oscuro general */
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
    }

    /* ======================  SIDEBAR  ====================== */
    .sidebar {
      width: 190px; /* antes 260px – ahora aprox 3/4 */
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

    /* ---------- CATEGORÍAS (tarjetas con SCROLL) ---------- */
    .categories {
      display: flex;
      gap: 14px;
      margin-bottom: 22px;
      overflow-x: auto;      /* 👉 scroll horizontal */
      padding-bottom: 6px;   /* espacio para que no tape el scroll */
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
      flex-shrink: 0; /* 👉 para que no se encojan y se pueda hacer scroll */
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

    .category-card--active .category-title {
      color: #111827;
    }

    .category-count {
      font-size: 11px;
      font-weight: 500;
      color: #6b7280;
    }

    .category-card--active .category-count {
      color: #1f2937;
    }

      /* ---------- PRODUCT GRID ---------- */
    .products-grid {
      display: grid;
      /* tarjetas más compactas: permiten más columnas */
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 14px;
    }

    .product-card {
      background: white;
      padding: 10px;              /* antes 14px */
      border-radius: 14px;        /* un poco más pequeño */
      box-shadow: 0 1px 4px rgba(0,0,0,0.06);  /* sombra más ligera */
      display: flex;
      flex-direction: column;
    }

    .product-card img {
      width: 100%;
      height: 110px;              /* antes 140px */
      object-fit: contain;        /* para que no se vea tan recortada */
      border-radius: 10px;
      background: #e5e7eb;
    }

    .product-card h3 {
      margin: 8px 0 2px;          /* menos espacio vertical */
      font-size: 15px;            /* antes 17px */
      line-height: 1.2;
    }

    .product-card p {
      font-size: 12px;            /* antes 13px */
      color: #6b7280;
      margin-bottom: 6px;
      min-height: 28px;
    }

    .price {
      font-size: 14px;            /* antes 16px */
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 6px;
    }

    .add-btn {
      width: 100%;
      padding: 7px;               /* antes 9px */
      background: #1f3bbf;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 13px;            /* antes 14px */
      font-weight: 600;
      margin-top: auto;
    }

    .add-btn:hover {
      background: #162a85;
    }

    /* Opcional: en pantallas muy grandes, aún más columnas */
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

        <!-- ====== INICIO (ACTIVO) ====== -->
        <a href="index.php" class="side-item side-item--active">
          <div class="side-item-icon">
            <!-- SVG INICIO -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M240,208H224V136l2.34,2.34A8,8,0,0,0,237.66,127L139.31,28.68a16,16,0,0,0-22.62,0L18.34,127a8,8,0,0,0,11.32,11.31L32,136v72H16a8,8,0,0,0,0,16H240a8,8,0,0,0,0-16ZM48,120l80-80,80,80v88H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48Zm96,88H112V160h32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Inicio</span>
        </a>

        <!-- ====== LOGIN ====== -->
        <a href="modules/login.php" class="side-item">
          <div class="side-item-icon">
            <!-- SVG LOGIN -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M141.66,133.66l-40,40a8,8,0,0,1-11.32-11.32L116.69,136H24a8,8,0,0,1,0-16h92.69L90.34,93.66a8,8,0,0,1,11.32-11.32l40,40A8,8,0,0,1,141.66,133.66ZM200,32H136a8,8,0,0,0,0,16h56V208H136a8,8,0,0,0,0,16h64a8,8,0,0,0,8-8V40A8,8,0,0,0,200,32Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Login</span>
        </a>

        <!-- ====== REGISTRO ====== -->
        <a href="modules/registro.php" class="side-item">
          <div class="side-item-icon">
            <!-- SVG REGISTRO -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M227.32,73.37,182.63,28.69a16,16,0,0,0-22.63,0L36.69,152A15.86,15.86,0,0,0,32,163.31V208a16,16,0,0,0,16,16H216a8,8,0,0,0,0-16H115.32l112-112A16,16,0,0,0,227.32,73.37ZM92.69,208H48V163.31l88-88L180.69,120ZM192,108.69,147.32,64l24-24L216,84.69Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Registrar</span>
        </a>

        <!-- ====== ACERCA DE ====== -->
        <a href="about.php" class="side-item">
          <div class="side-item-icon">
            <!-- SVG ACERCA DE -->
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff"
              viewBox="0 0 256 256">
              <path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm16-40a8,8,0,0,1-8,8,16,16,0,0,1-16-16V128a8,8,0,0,1,0-16,16,16,0,0,1,16,16v40A8,8,0,0,1,144,176ZM112,84a12,12,0,1,1,12,12A12,12,0,0,1,112,84Z"></path>
            </svg>
          </div>
          <span class="side-item-label">Acerca de</span>
        </a>

      </nav>

      <div class="side-footer">
        POS · Super Limpio
      </div>

    </div>
  </aside>

  <!-- ====================== CONTENT ====================== -->
  <main class="content">

    <!-- 🔍 BARRA DE BÚSQUEDA (usa GET q y mantiene cat) -->
    <form class="search-bar" method="get" action="index.php">
      <div>
        <!-- 🔵 SVG ICONO LUPA AQUÍ (OPCIONAL) -->
      </div>
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

    <!-- 🟦 CATEGORÍAS EN TARJETAS (DINÁMICAS + SCROLL) -->
    <div class="categories">

      <!-- TODOS -->
      <a href="index.php"
         class="category-card <?php echo ($catId === 0) ? 'category-card--active' : ''; ?>">
        <div class="category-icon">
          <!-- SVG CATEGORÍA "TODOS" AQUÍ -->
           <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M104,40H56A16,16,0,0,0,40,56v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V56A16,16,0,0,0,104,40Zm0,64H56V56h48v48Zm96-64H152a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V56A16,16,0,0,0,200,40Zm0,64H152V56h48v48Zm-96,32H56a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V152A16,16,0,0,0,104,136Zm0,64H56V152h48v48Zm96-64H152a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h48a16,16,0,0,0,16-16V152A16,16,0,0,0,200,136Zm0,64H152V152h48v48Z"></path></svg>
        </div>
        <div class="category-text">
          <div class="category-title">Todos</div>
          <div class="category-count">Ver todos los productos</div>
        </div>
      </a>
      <?php
// Mapear nombre de categoría -> SVG
$categoryIcons = [
    'Hogar' => '
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M247.63,47.89a8,8,0,0,0-7.52-7.52c-51.76-3-93.32,12.74-111.18,42.22-11.8,19.49-11.78,43.16-.16,65.74a71.34,71.34,0,0,0-14.17,27L98.33,159c7.82-16.33,7.52-33.35-1-47.49-13.2-21.79-43.67-33.47-81.5-31.25a8,8,0,0,0-7.52,7.52c-2.23,37.83,9.46,68.3,31.25,81.5A45.82,45.82,0,0,0,63.44,176,54.58,54.58,0,0,0,87,170.33l25,25V224a8,8,0,0,0,16,0V194.51a55.61,55.61,0,0,1,12.27-35,73.91,73.91,0,0,0,33.31,8.4,60.9,60.9,0,0,0,31.83-8.86C234.89,141.21,250.67,99.65,247.63,47.89ZM47.81,155.6C32.47,146.31,23.79,124.32,24,96c28.32-.24,50.31,8.47,59.6,23.81,4.85,8,5.64,17.33,2.46,26.94L61.65,122.34a8,8,0,0,0-11.31,11.31l24.41,24.41C65.14,161.24,55.82,160.45,47.81,155.6Zm149.31-10.22c-13.4,8.11-29.15,8.73-45.15,2l53.69-53.7a8,8,0,0,0-11.31-11.31L140.65,136c-6.76-16-6.15-31.76,2-45.15,13.94-23,47-35.82,89.33-34.83C232.94,98.34,220.14,131.44,197.12,145.38Z"></path></svg>
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
    // 👇 Icono por defecto si no está en la lista
    '_default' => '
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M245.66,42.34l-32-32a8,8,0,0,0-11.32,11.32l1.48,1.47L148.65,64.51l-38.22,7.65a8.05,8.05,0,0,0-4.09,2.18L23,157.66a24,24,0,0,0,0,33.94L64.4,233a24,24,0,0,0,33.94,0l83.32-83.31a8,8,0,0,0,2.18-4.09l7.65-38.22,41.38-55.17,1.47,1.48a8,8,0,0,0,11.32-11.32ZM96,107.31,148.69,160,104,204.69,51.31,152ZM81.37,224a7.94,7.94,0,0,1-5.65-2.34L34.34,180.28a8,8,0,0,1,0-11.31L40,163.31,92.69,216,87,221.66A8,8,0,0,1,81.37,224ZM177.6,99.2a7.92,7.92,0,0,0-1.44,3.23l-7.53,37.63L160,148.69,107.31,96l8.63-8.63,37.63-7.53a7.92,7.92,0,0,0,3.23-1.44l58.45-43.84,6.19,6.19Z"></path></svg>
    ',
];
?>


      <!-- CATEGORÍAS DESDE LA BD -->
      <?php foreach ($categorias as $cat): ?>
  <a href="index.php?cat=<?php echo $cat['idCategoria']; ?><?php echo $search !== '' ? '&q='.urlencode($search) : ''; ?>"
     class="category-card <?php echo ($catId === (int)$cat['idCategoria']) ? 'category-card--active' : ''; ?>">
    <div class="category-icon">
      <?php
        // Obtenemos el nombre tal cual
        $nombreCat = $cat['Nombre'];

        // Si existe un SVG específico, lo usamos; si no, usamos el default
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

    <!-- 🛒 GRID DE PRODUCTOS -->
    <div class="products-grid">
      <?php foreach ($productos as $p): ?>
        <div class="product-card">
          <!-- Más adelante podemos usar una columna Imagen en la BD -->
          <img src="assets/img/clean-products.png" alt="Imagen producto">

          <h3><?php echo htmlspecialchars($p['Nombre']); ?></h3>
          <p><?php echo htmlspecialchars($p['Descripcion']); ?></p>
          <div class="price">$<?php echo number_format($p['Precio'], 2); ?></div>
          <button class="add-btn">Agregar</button>
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
