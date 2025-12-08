<?php

// =====================================================
// Descripción: Conexión a la base de datos MySQL
// Detecta automáticamente si está en LOCAL o HOSTINGER
// =====================================================

// Detectar el entorno basándose en el servidor
$esLocal = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
    $_SERVER['SERVER_ADDR'] === '::1' ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
);

if ($esLocal) {
    // ==================== CONFIGURACIÓN LOCAL (XAMPP) ====================
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $database   = "productos_limpieza";
} else {
    // ==================== CONFIGURACIÓN HOSTINGER ====================
    $servername = "localhost";
    $username   = "u534788190_superLimpio";
    $password   = "superLimpio1!";
    $database   = "u534788190_superLimpio";
}

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    $entorno = $esLocal ? 'LOCAL' : 'HOSTINGER';
    die("Error de conexión [$entorno]: " . $conn->connect_error);
}

// Establecer codificación
$conn->set_charset("utf8");

// (Opcional) Descomentar la siguiente línea para debug:
// echo "<!-- Conectado a: " . ($esLocal ? 'LOCAL (XAMPP)' : 'HOSTINGER') . " -->";
?>
