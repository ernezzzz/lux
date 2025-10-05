<?php
// checklogin.php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    // No hay sesión -> volver al login
    header("Location: login.html");
    exit;
}

// Opcional: variables para mostrar en todas las páginas
$usuarioId = $_SESSION['id_usuario'];
$usuarioNombre = $_SESSION['nombre'];
$usuarioRol = $_SESSION['id_rol'];
?>  