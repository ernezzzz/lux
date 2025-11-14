<?php
// historia_por_paciente.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
require_once __DIR__ . '/../../backend/checklogin.php';
require_once __DIR__ . '/../../backend/conexion.php';

try {
  $pac = isset($_POST['paciente']) ? intval($_POST['paciente']) : 0;
  $per_page = max(1, intval($_POST['per_page'] ?? 100));
  if (!$pac) throw new Exception('Paciente inválido.');

  $sql = "SELECT h.id_historia, h.fecha, h.descripcion, COALESCE(CONCAT_WS(' ', u.nombre, u.apellido),'') AS paciente_nombre
          FROM historia_clinica h
          LEFT JOIN usuarios u ON h.id_paciente = u.id_usuario
          WHERE h.id_paciente = $pac
          ORDER BY h.fecha DESC
          LIMIT $per_page";
  $res = mysqli_query($conn, $sql);
  if ($res === false) throw new Exception(mysqli_error($conn));
  $rows = [];
  while($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'id_historia' => (int)$r['id_historia'],
      'fecha' => $r['fecha'],
      'descripcion' => $r['descripcion'],
      'paciente_nombre' => $r['paciente_nombre'],
    ];
  }
  echo json_encode(['success'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE); exit;
} catch (Exception $ex) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>$ex->getMessage()]); exit;
}
