<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

// Recibe POST y hace insert/update
$id = isset($_POST['id_turno']) ? intval($_POST['id_turno']) : 0;
$id_paciente = isset($_POST['id_paciente']) ? intval($_POST['id_paciente']) : null;
$id_medico = isset($_POST['id_medico']) ? intval($_POST['id_medico']) : null;
$fecha_hora = $_POST['fecha_hora'] ?? null;
$estado = mysqli_real_escape_string($conn, $_POST['estado'] ?? 'pendiente');

if (!$id_paciente || !$id_medico || !$fecha_hora) {
  die('Faltan datos obligatorios.');
}

// separar fecha y hora para tus columnas (fecha DATE, hora TIMESTAMP)
$ts = strtotime($fecha_hora);
$fecha = date('Y-m-d', $ts);
$hora = date('Y-m-d H:i:s', $ts);

if ($id) {
  $sql = "UPDATE turnos SET id_paciente=$id_paciente, id_medico=$id_medico, fecha='$fecha', hora='$hora', estado='{$estado}' WHERE id_turno=$id";
  mysqli_query($conn, $sql) or die('Error update: '.mysqli_error($conn));
} else {
  $sql = "INSERT INTO turnos (id_paciente, id_medico, fecha, hora, estado) VALUES ($id_paciente, $id_medico, '$fecha', '$hora', '{$estado}')";
  mysqli_query($conn, $sql) or die('Error insert: '.mysqli_error($conn));
}

header('Location: turnos_listar.php');
exit;
