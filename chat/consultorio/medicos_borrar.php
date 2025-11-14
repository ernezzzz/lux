<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id) {
  mysqli_query($conn, "DELETE FROM medicos WHERE id_medico=$id") or die(mysqli_error($conn));
}
header('Location: medicos_listar.php'); exit;