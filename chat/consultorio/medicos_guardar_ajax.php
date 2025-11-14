<?php
// medicos_guardar_ajax.php
header('Content-Type: application/json; charset=utf-8');
// Evitar salida HTML/errores en la respuesta
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/../../backend/checklogin.php';
require_once __DIR__ . '/../../backend/conexion.php';

try {
  $id = isset($_POST['id_medico']) ? intval($_POST['id_medico']) : 0;
  $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
  $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
  $especialidad = isset($_POST['especialidad']) ? trim($_POST['especialidad']) : '';

  if ($nombre === '') throw new Exception('Falta el nombre del médico.');

  $nombre_esc = mysqli_real_escape_string($conn, $nombre);
  $apellido_esc = mysqli_real_escape_string($conn, $apellido);
  $esp_esc = mysqli_real_escape_string($conn, $especialidad);

  if ($id) {
    $sql = "UPDATE medicos SET nombre='{$nombre_esc}', apellido='{$apellido_esc}', especialidad='{$esp_esc}' WHERE id_medico={$id}";
    if (!mysqli_query($conn, $sql)) throw new Exception('Error al actualizar: ' . mysqli_error($conn));
  } else {
    $sql = "INSERT INTO medicos (nombre, apellido, especialidad) VALUES ('{$nombre_esc}','{$apellido_esc}','{$esp_esc}')";
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

