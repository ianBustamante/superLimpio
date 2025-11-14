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
$sqlProd = "SELECT p.idProducto, p.Nombre, p.Descripcion, p.Precio,
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
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 18px;
    }

    .product-card {
      background: white;
      padding: 14px;
      border-radius: 18px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      display: flex;
      flex-direction: column;
    }

    .product-card img {
      width: 100%;
      height: 140px;
      object-fit: cover;
      border-radius: 12px;
      background: #e5e7eb;
    }

    .product-card h3 {
      margin: 10px 0 4px;
      font-size: 17px;
    }

    .product-card p {
      font-size: 13px;
      color: #6b7280;
      margin-bottom: 8px;
      min-height: 32px;
    }

    .price {
      font-size: 16px;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 8px;
    }

    .add-btn {
      width: 100%;
      padding: 9px;
      background: #1f3bbf;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      margin-top: auto;
    }

    .add-btn:hover {
      background: #162a85;
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
        </div>
        <div class="category-text">
          <div class="category-title">Todos</div>
          <div class="category-count">Ver todos los productos</div>
        </div>
      </a>

      <!-- CATEGORÍAS DESDE LA BD -->
      <?php foreach ($categorias as $cat): ?>
        <a href="index.php?cat=<?php echo $cat['idCategoria']; ?><?php echo $search !== '' ? '&q='.urlencode($search) : ''; ?>"
           class="category-card <?php echo ($catId === (int)$cat['idCategoria']) ? 'category-card--active' : ''; ?>">
          <div class="category-icon">
            <!-- SVG CATEGORÍA "<?php echo htmlspecialchars($cat['Nombre']); ?>" AQUÍ -->
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
          <img src="https://via.placeholder.com/300x150" alt="Imagen producto">

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
