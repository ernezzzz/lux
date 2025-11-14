<?php
// consultorio/turnos_restaurar_ajax.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  $id_turno = isset($_POST['id_turno']) ? intval($_POST['id_turno']) : 0;
  $force = isset($_POST['force']) && ($_POST['force'] === '1' || $_POST['force'] === 1);

  if (!$id_turno) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'ID de turno inválido.']);
    exit;
  }

  // obtener turno y sus relaciones
  $stmt = mysqli_prepare($conn, "SELECT id_turno, id_paciente, id_medico, COALESCE(activo,1) AS activo FROM turnos WHERE id_turno = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $id_turno);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);
  if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>'Turno no encontrado.']);
    exit;
  }
  mysqli_stmt_bind_result($stmt, $tid, $id_paciente, $id_medico, $tactivo);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ($tactivo == 1) {
    echo json_encode(['success'=>true,'info'=>'Turno ya activo.']);
    exit;
  }

  // comprobar que paciente y médico (si existen) estén presentes y activos
  $problems = [];
  if ($id_paciente) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(activo,1) AS activo FROM usuarios WHERE id_usuario = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id_paciente);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
      $problems[] = 'Paciente no existe';
    } else {
      mysqli_stmt_bind_result($stmt, $pactivo);
      mysqli_stmt_fetch($stmt);
      if ($pactivo != 1) $problems[] = 'Paciente inactivo';
    }
    mysqli_stmt_close($stmt);
  }

  if ($id_medico) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(activo,1) AS activo FROM medicos WHERE id_medico = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id_medico);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
      $problems[] = 'Médico no existe';
    } else {
      mysqli_stmt_bind_result($stmt, $mactivo);
      mysqli_stmt_fetch($stmt);
      if ($mactivo != 1) $problems[] = 'Médico inactivo';
    }
    mysqli_stmt_close($stmt);
  }

  if (count($problems) > 0 && !$force) {
    // devolver detalle para que el front muestre mensaje y pida acción (force)
    http_response_code(409);
    echo json_encode([
      'success'=>false,
      'code'=>'MISSING_RELATION',
      'problems'=>$problems,
      'error'=>'No se puede restaurar: faltan relaciones activas (paciente/médico).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // proceder a restaurar
  $stmt = mysqli_prepare($conn, "UPDATE turnos SET activo = 1, deleted_at = NULL WHERE id_turno = ?");
  mysqli_stmt_bind_param($stmt, 'i', $id_turno);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Error al restaurar turno: '.$err]);
    exit;
  }
  $affected = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  echo json_encode([
    'success'=>true,
    'id_turno'=>$id_turno,
    'restored'=>(int)$affected,
    'message'=> $affected ? 'Turno restaurado.' : 'No se modificó el turno.'
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}
