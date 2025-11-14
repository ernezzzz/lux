<?php
// consultorio/medico_restaurar_ajax.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  $id_medico = isset($_POST['id_medico']) ? intval($_POST['id_medico']) : 0;
  $restore_turnos = isset($_POST['restore_turnos']) && ($_POST['restore_turnos'] === '1' || $_POST['restore_turnos'] === 1);

  if (!$id_medico) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'ID de médico inválido.']);
    exit;
  }

  // comprobar existencia
  $stmt = mysqli_prepare($conn, "SELECT id_medico, COALESCE(activo,1) AS activo FROM medicos WHERE id_medico = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $id_medico);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);
  if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>'Médico no encontrado.']);
    exit;
  }
  mysqli_stmt_bind_result($stmt, $mid, $mactivo);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ($mactivo == 1 && !$restore_turnos) {
    // ya activo
    echo json_encode(['success'=>true,'info'=>'Médico ya activo.']);
    exit;
  }

  mysqli_begin_transaction($conn);
  $now = date('Y-m-d H:i:s');

  // restaurar médico
  $stmt = mysqli_prepare($conn, "UPDATE medicos SET activo = 1, deleted_at = NULL WHERE id_medico = ?");
  mysqli_stmt_bind_param($stmt, 'i', $id_medico);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_rollback($conn);
    throw new Exception('Error al restaurar médico: '.$err);
  }
  $affected_med = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  $restored_turnos = 0;
  if ($restore_turnos) {
    // restaurar turnos que estaban inactivos del medico
    $stmt = mysqli_prepare($conn, "UPDATE turnos SET activo = 1, deleted_at = NULL WHERE id_medico = ? AND COALESCE(activo,0) = 0");
    mysqli_stmt_bind_param($stmt, 'i', $id_medico);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok === false) {
      $err = mysqli_error($conn);
      mysqli_stmt_close($stmt);
      mysqli_rollback($conn);
      throw new Exception('Error al restaurar turnos: '.$err);
    }
    $restored_turnos = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
  }

  mysqli_commit($conn);

  echo json_encode([
    'success'=>true,
    'id_medico'=>$id_medico,
    'medico_restored'=>(int)$affected_med,
    'turnos_restored'=>(int)$restored_turnos,
    'message'=> ($affected_med ? 'Médico restaurado.' : 'No se modificó el médico.') . ($restore_turnos ? " Turnos restaurados: $restored_turnos." : '')
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  if (isset($conn)) @mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
