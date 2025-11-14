<?php
// consultorio/paciente_restaurar_ajax.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
  if (!$id_usuario) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'ID inválido.']);
    exit;
  }

  // verificar existencia
  $stmt = mysqli_prepare($conn, "SELECT id_usuario, activo FROM usuarios WHERE id_usuario = ? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);
  if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    http_response_code(404);
    echo json_encode(['success'=>false,'error'=>'Paciente no encontrado.']);
    exit;
  }
  mysqli_stmt_bind_result($stmt, $uid, $uactivo);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ($uactivo == 1) {
    echo json_encode(['success'=>true,'info'=>'Paciente ya activo.']);
    exit;
  }

  mysqli_begin_transaction($conn);

  // reactivar usuario
  $stmt = mysqli_prepare($conn, "UPDATE usuarios SET activo = 1, deleted_at = NULL WHERE id_usuario = ?");
  mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_rollback($conn);
    throw new Exception('Error al restaurar paciente: ' . $err);
  }
  $affected_user = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  // restaurar turnos inactivos del paciente (los que estaban soft-deleted)
  $stmt = mysqli_prepare($conn, "UPDATE turnos SET activo = 1, deleted_at = NULL WHERE id_paciente = ? AND activo = 0");
  mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_rollback($conn);
    throw new Exception('Error al restaurar turnos: ' . $err);
  }
  $affected_turnos = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  mysqli_commit($conn);

  echo json_encode([
    'success'=>true,
    'id_usuario'=>$id_usuario,
    'user_restored'=>(int)$affected_user,
    'turnos_restored'=>(int)$affected_turnos
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  if (isset($conn)) @mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()]);
  exit;
}
