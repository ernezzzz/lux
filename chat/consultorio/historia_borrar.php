<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id) {
  mysqli_query($conn, "DELETE FROM historia_clinica WHERE id_historia=$id") or die(mysqli_error($conn));
}
header('Location: historia_listar.php');
exit;
