<?php
// historia_guardar_ajax.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/../../backend/checklogin.php';
require_once __DIR__ . '/../../backend/conexion.php';

try {
  $id = isset($_POST['id_historia']) ? intval($_POST['id_historia']) : 0;
  $id_paciente = isset($_POST['id_paciente']) ? intval($_POST['id_paciente']) : 0;
  $fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : null;
  $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

  if (!$id_paciente) throw new Exception('Seleccione un paciente.');
  if (!$fecha) throw new Exception('Seleccione una fecha.');

  $desc_esc = mysqli_real_escape_string($conn, $descripcion);

  if ($id) {
    $sql = "UPDATE historia_clinica SET id_paciente={$id_paciente}, fecha='{$fecha}', descripcion='{$desc_esc}' WHERE id_historia={$id}";
    if (!mysqli_query($conn, $sql)) throw new Exception('Error al actualizar: ' . mysqli_error($conn));
  } else {
    $sql = "INSERT INTO historia_clinica (id_paciente, fecha, descripcion) VALUES ({$id_paciente}, '{$fecha}', '{$desc_esc}')";
    if (!mysqli_query($conn, $sql)) throw new Exception('Error al insertar: ' . mysqli_error($conn));
    $id = mysqli_insert_id($conn);
  }

  echo json_encode(['success' => true, 'id' => (int)$id], JSON_UNESCAPED_UNICODE);
  exit;
} catch (Exception $ex) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
