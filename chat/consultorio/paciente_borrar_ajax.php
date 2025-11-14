<?php
// consultorio/paciente_borrar_ajax.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  // recibir id via POST (nombre exacto: id_usuario)
  $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
  if (!$id_usuario) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'ID de usuario inválido.']);
    exit;
  }

  // obtener paciente y active flag
  $stmt = mysqli_prepare($conn, "SELECT id_usuario, activo FROM usuarios WHERE id_usuario = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);
  if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    http_response_code(404);
    echo json_encode(['success'=>false, 'error'=>'Paciente no encontrado.']);
    exit;
  }
  mysqli_stmt_bind_result($stmt, $uid, $uactivo);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ($uactivo == 0) {
    echo json_encode(['success'=>true, 'info'=>'Paciente ya inactivo.']);
    exit;
  }

  // contar turnos PENDIENTE/CONFIRMADO activos
  $stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS cnt
      FROM turnos
     WHERE id_paciente = ? AND activo = 1 AND estado IN ('pendiente','confirmado')
  ");
  mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $cntPendConf);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ($cntPendConf > 0) {
    http_response_code(409); // conflicto
    echo json_encode(['success'=>false, 'error'=>'No se puede borrar el paciente porque tiene registros relacionados.']);
    exit;
  }

  // soft delete dentro de transacción
  mysqli_begin_transaction($conn);
  $now = date('Y-m-d H:i:s');

  // marcar turnos finalizado/cancelado como inactivos
  $stmt = mysqli_prepare($conn, "
    UPDATE turnos
       SET activo = 0, deleted_at = ?
     WHERE id_paciente = ? AND activo = 1 AND estado IN ('finalizado','cancelado')
  ");
  mysqli_stmt_bind_param($stmt, 'si', $now, $id_usuario);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) { mysqli_stmt_close($stmt); mysqli_rollback($conn); throw new Exception('Error al marcar turnos: '.mysqli_error($conn)); }
  $affected_turnos = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  // marcar paciente inactivo
  $stmt = mysqli_prepare($conn, "UPDATE usuarios SET activo = 0, deleted_at = ? WHERE id_usuario = ?");
  mysqli_stmt_bind_param($stmt, 'si', $now, $id_usuario);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) { mysqli_stmt_close($stmt); mysqli_rollback($conn); throw new Exception('Error al marcar paciente: '.mysqli_error($conn)); }
  $affected_user = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  mysqli_commit($conn);

  echo json_encode(['success'=>true, 'id_usuario'=>$id_usuario, 'turnos_soft_deleted'=>(int)$affected_turnos, 'user_soft_deleted'=>(int)$affected_user], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  if (isset($conn)) @mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['success'=>false, 'error'=>$ex->getMessage()]);
  exit;
}

