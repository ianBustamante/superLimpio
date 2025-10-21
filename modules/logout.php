<?php
// modules/logout.php

// Incluir conexión y funciones
require_once '../includes/connection.php';
require_once '../includes/functions.php';

// Iniciar sesión para acceder a la variable de sesión
session_start();

if (isset($_SESSION['idUsuario'])) {
    $idUsuario = $_SESSION['idUsuario'];

    // Cerrar sesión en la base de datos
    cerrarSesion($conn, $idUsuario);

    // Destruir sesión en PHP
    session_unset();
    session_destroy();
}

// Redirigir al login
header("Location: login.php");
exit;
?>
