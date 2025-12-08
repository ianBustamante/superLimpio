<?php

// Descripción: Conexión a la base de datos MySQL


$servername = "localhost";
$username   = "u534788190_superLimpio";
$password   = "superLimpio1!";
$database   = "u534788190_superLimpio";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer codificación
$conn->set_charset("utf8");
?>
