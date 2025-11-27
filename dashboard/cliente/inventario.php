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

// =========================
//  Filtros de inventario
// =========================
$q  = trim($_GET['q'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

// Datos base
$productos   = obtenerProductos($conn);      // de functions.php
$categorias  = obtenerCategorias($conn);     // de functions.php

// Aplicar filtros en memoria (igual que en admin)
if ($q !== '' || $categoriaFiltro !== '') {
  $productos = array_values(array_filter($productos, function($p) use ($q, $categoriaFiltro) {

    // Filtro de texto (nombre / descripción / categoría)
    if ($q !== '') {
      $textoOk =
        stripos($p['Nombre'],       $q) !== false ||
        stripos($p['Descripcion'],  $q) !== false ||
        stripos($p['Categoria'],    $q) !== false ||
        stripos((string)$p['idProducto'], $q) !== false; // 👈 buscar por ID/código
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

    <!-- Header superior -->
    <div class="admin-header-row">
      <div class="admin-header-left">
        <h1>Inventario</h1>
        <p>Consulta de productos disponibles para la venta.</p>
        <div class="admin-header-meta">
          <?= $totalProductos ?> producto<?= $totalProductos === 1 ? '' : 's' ?> encontrado<?= $totalProductos === 1 ? '' : 's' ?>.
        </div>
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
            placeholder="Nombre, descri
