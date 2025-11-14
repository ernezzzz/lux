<?php
// turnos_por_paciente.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
require_once __DIR__ . '/../../backend/checklogin.php';
require_once __DIR__ . '/../../backend/conexion.php';

try {
  $pac = isset($_POST['paciente']) ? intval($_POST['paciente']) : 0;
  $per_page = max(1, intval($_POST['per_page'] ?? 100));
  if (!$pac) throw new Exception('Paciente inválido.');

  $sql = "SELECT t.id_turno, t.fecha, t.hora, t.estado, COALESCE(CONCAT_WS(' ', m.nombre, m.apellido),'') AS medico_nombre
          FROM turnos t
          LEFT JOIN medicos m ON t.id_medico = m.id_medico
          WHERE t.id_paciente = $pac
          ORDER BY t.fecha DESC, t.hora DESC
          LIMIT $per_page";
  $res = mysqli_query($conn, $sql);
  if ($res === false) throw new Exception(mysqli_error($conn));
  $rows = [];
  while($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'id_turno' => (int)$r['id_turno'],
      'fecha' => $r['fecha'],
      'hora' => $r['hora'] ? date('H:i', strtotime($r['hora'])) : '',
      'estado' => $r['estado'],
      'medico_name' => $r['medico_nombre'],
      'medico_nombre' => $r['medico_nombre'],
    ];
  }
  echo json_encode(['success'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE); exit;
} catch (Exception $ex) {
  http_response_code(400); echo json_encode(['success'=>false,'error'=>$ex->getMessage()]); exit;
}
