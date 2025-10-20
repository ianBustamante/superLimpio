<?php
// ==============================================
// index.php
// Página de bienvenida del Sistema de Productos de Limpieza
// ==============================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sistema de Productos de Limpieza</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    :root {
      --primary: #2b3a67;
      --accent: #4c8bf5;
      --bg: #f6f7fb;
      --text: #2e2e2e;
      --white: #ffffff;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      background-color: var(--white);
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      padding: 1.5rem 0;
      text-align: center;
    }

    header h1 {
      font-size: 1.9rem;
      color: var(--primary);
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
    }

    .container {
      max-width: 1100px;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 50px;
    }

    .text-section {
      flex: 1;
      min-width: 320px;
      padding-right: 20px;
    }

    .text-section h2 {
      font-size: 2.4rem;
      color: var(--primary);
      margin-bottom: 15px;
      font-weight: 600;
    }

    .text-section p {
      font-size: 1.1rem;
      line-height: 1.7;
      color: #555;
      margin-bottom: 30px;
    }

    .buttons {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-block;
      padding: 12px 28px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      letter-spacing: 0.3px;
    }

    .btn-primary {
      background-color: var(--accent);
      color: var(--white);
    }

    .btn-primary:hover {
      background-color: #3c76d8;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(76,139,245,0.2);
    }

    .btn-outline {
      background: none;
      border: 2px solid var(--accent);
      color: var(--accent);
    }

    .btn-outline:hover {
      background-color: var(--accent);
      color: var(--white);
      transform: translateY(-2px);
    }

    .image-section {
      flex: 1;
      min-width: 320px;
      text-align: center;
    }

    .image-section img {
      width: 90%;
      max-width: 460px;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.3s ease;
    }

    .image-section img:hover {
      transform: scale(1.03);
    }

    footer {
      background-color: var(--white);
      text-align: center;
      padding: 20px 0;
      color: #888;
      font-size: 0.9rem;
      border-top: 1px solid #e5e5e5;
    }

    @media (max-width: 900px) {
      main {
        padding-top: 40px;
      }
      .container {
        flex-direction: column-reverse;
        text-align: center;
      }
      .text-section {
        padding: 0;
      }
      .image-section img {
        max-width: 75%;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>Sistema de Productos de Limpieza</h1>
</header>

<main>
  <div class="container">
    <div class="text-section">
      <h2>Productos de limpieza</h2>
      <p>
        Administrar productos, clientes, empleados y ventas en un entorno moderno y seguro.
        Un sistema diseñado para mantener un negocio organizado.
      </p>
      <div class="buttons">
        <a href="modules/login.php" class="btn btn-primary">Iniciar sesión</a>
        <a href="modules/registro.php" class="btn btn-outline">Registrarme</a>
      </div>
    </div>

    <div class="image-section">
      <img src="assets/img/clean-products.png" alt="Productos de limpieza">
    </div>
  </div>
</main>

<footer>
  © <?= date('Y'); ?> Sistema de Productos de Limpieza. Todos los derechos reservados al equipo.
</footer>

</body>
</html>
