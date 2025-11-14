<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
require_once __DIR__ . '/../../backend/checklogin.php';
require_once __DIR__ . '/../../backend/conexion.php';

try {
  $id = isset($_POST['id_turno']) ? intval($_POST['id_turno']) : 0;
  $id_paciente = isset($_POST['id_paciente']) ? intval($_POST['id_paciente']) : null;
  $id_medico = isset($_POST['id_medico']) ? intval($_POST['id_medico']) : null;
  $fecha_hora = $_POST['fecha_hora'] ?? null;
  $estado = mysqli_real_escape_string($conn, $_POST['estado'] ?? 'pendiente');

  if (!$id_paciente || !$id_medico || !$fecha_hora) {
    throw new Exception('Faltan datos obligatorios.');
  }

  // parsear fecha/hora y validar que no sea pasada
  $ts = strtotime($fecha_hora);
  if ($ts === false) throw new Exception('Formato de fecha/hora inválido.');
  // permitir pequeña tolerancia (30s)
  if ($ts < time() - 30) throw new Exception('No se puede crear/editar un turno en el pasado.');

  $fecha = date('Y-m-d', $ts);
  $hora = date('Y-m-d H:i:s', $ts);

  if ($id) {
    $sql = "UPDATE turnos SET id_paciente=$id_paciente, id_medico=$id_medico, fecha='$fecha', hora='$hora', estado='{$estado}' WHERE id_turno=$id";
    if (!mysqli_query($conn, $sql)) throw new Exception('Error update: '.mysqli_error($conn));
  } else {
    $sql = "INSERT INTO turnos (id_paciente, id_medico, fecha, hora, estado) VALUES ($id_paciente, $id_medico, '$fecha', '$hora', '{$estado}')";
    if (!mysqli_query($conn, $sql)) throw new Exception('Error insert: '.mysqli_error($conn));
  }

  echo json_encode(['success' => true]);
  exit;
} catch (Exception $ex) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
  exit;
}


