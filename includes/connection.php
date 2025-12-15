<?php

// =====================================================
// Descripción: Conexión a la base de datos MySQL
// Detecta automáticamente si está en LOCAL o HOSTINGER
// =====================================================

// Permitir forzar el uso de la base del host incluso en local.
// Cambia a false si quieres volver a usar la base local en XAMPP.
$forzarHost = true;

// Detectar el entorno basándose en el servidor
$esLocal = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
    $_SERVER['SERVER_ADDR'] === '::1' ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
);

// Si $forzarHost es true siempre tomará la config del host
$usarHost = $forzarHost || !$esLocal;

if ($usarHost) {
    // ==================== CONFIGURACIÓN HOSTINGER ====================
    $servername = "srv534.hstgr.io"; // Host MySQL remoto según panel
    $username   = "u534788190_superLimpio";
    $password   = "superLimpio1!";
    $database   = "u534788190_superLimpio";
    $port       = 3306;
} else {
    // ==================== CONFIGURACIÓN LOCAL (XAMPP) ====================
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $database   = "productos_limpieza";
}

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database, $port ?? ini_get("mysqli.default_port"));

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
