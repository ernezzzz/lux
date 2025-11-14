<?php
// consultorio/medico_borrar_ajax.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  // Parámetros
  $id_medico = isset($_POST['id_medico']) ? intval($_POST['id_medico']) : 0;
  $force = isset($_POST['force']) && ($_POST['force'] == '1' || $_POST['force'] === 1);
  $reassign_to = isset($_POST['reassign_to']) ? intval($_POST['reassign_to']) : 0;

  if (!$id_medico) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'ID de médico inválido.']);
    exit;
  }

  // Verificar existencia del médico y estado activo (COALESCE por si no hay columna activo)
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

  if ($mactivo == 0 && !$force && $reassign_to==0) {
    // ya inactivo, respondemos éxito idempotente
    echo json_encode(['success'=>true,'info'=>'Médico ya inactivo.']);
    exit;
  }

  // Contar turnos pendientes/confirmados activos
  $stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS cnt FROM turnos
      WHERE id_medico = ?
        AND COALESCE(activo,1) = 1
        AND estado IN ('pendiente','confirmado')"
  );
  mysqli_stmt_bind_param($stmt, 'i', $id_medico);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $cntPendConf);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  $cntPendConf = (int)$cntPendConf;

  // Si tiene pendientes/confirmados y no se pidió force ni reassign, devolvemos información para que el frontend ofrezca opciones
  if ($cntPendConf > 0 && !$force && $reassign_to == 0) {
    echo json_encode([
      'success' => false,
      'code' => 'HAS_PENDING',
      'pending_count' => $cntPendConf,
      'error' => 'El médico tiene turnos pendientes o confirmados.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Si reassign_to fue enviado: validar
  if ($reassign_to > 0) {
    if ($reassign_to === $id_medico) {
      http_response_code(400);
      echo json_encode(['success'=>false,'error'=>'El médico de reemplazo no puede ser el mismo.']);
      exit;
    }
    // comprobar que reassign_to existe y está activo
    $stmt = mysqli_prepare($conn, "SELECT id_medico, COALESCE(activo,1) AS activo FROM medicos WHERE id_medico = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $reassign_to);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) === 0) {
      mysqli_stmt_close($stmt);
      http_response_code(404);
      echo json_encode(['success'=>false,'error'=>'Médico de reemplazo no encontrado.']);
      exit;
    }
    mysqli_stmt_bind_result($stmt, $mid2, $mactivo2);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    if ($mactivo2 == 0) {
      http_response_code(400);
      echo json_encode(['success'=>false,'error'=>'El médico de reemplazo está inactivo.']);
      exit;
    }
  }

  // Empezar transacción
  mysqli_begin_transaction($conn);
  $now = date('Y-m-d H:i:s');

  $reassigned = 0;
  $soft_deleted_turnos = 0;

  // Si reassign_to pedido: reasignar los turnos pendientes/confirmados a reassign_to
  if ($reassign_to > 0) {
    $stmt = mysqli_prepare($conn,
      "UPDATE turnos SET id_medico = ? WHERE id_medico = ? AND COALESCE(activo,1) = 1 AND estado IN ('pendiente','confirmado')"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $reassign_to, $id_medico);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok === false) {
      $err = mysqli_error($conn);
      mysqli_stmt_close($stmt);
      mysqli_rollback($conn);
      throw new Exception('Error al reasignar turnos: ' . $err);
    }
    $reassigned = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    // Además, soft-delete (marcar inactivos) los demás turnos que puedan quedar (finalizado/cancelado)
    $stmt = mysqli_prepare($conn,
      "UPDATE turnos SET activo = 0, deleted_at = ? WHERE id_medico = ? AND COALESCE(activo,1) = 1 AND estado IN ('finalizado','cancelado')"
    );
    mysqli_stmt_bind_param($stmt, 'si', $now, $id_medico);
    $ok = mysqli_stmt_execute($stmt);
    if ($ok === false) { $err = mysqli_error($conn); mysqli_stmt_close($stmt); mysqli_rollback($conn); throw new Exception('Error al marcar turnos: '.$err); }
    $soft_deleted_turnos += mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

  } else {
    // No reasignación: si force => marcar todos los turnos activos del médico como inactivos
    // si no force (llegamos aquí porque no había pendientes OR ya manejamos el caso de pendings) entonces marcamos finalizados/cancelados como inactivos
    if ($force) {
      $stmt = mysqli_prepare($conn,
        "UPDATE turnos SET activo = 0, deleted_at = ? WHERE id_medico = ? AND COALESCE(activo,1) = 1"
      );
      mysqli_stmt_bind_param($stmt, 'si', $now, $id_medico);
      $ok = mysqli_stmt_execute($stmt);
      if ($ok === false) { $err = mysqli_error($conn); mysqli_stmt_close($stmt); mysqli_rollback($conn); throw new Exception('Error al forzar borrado de turnos: '.$err); }
      $soft_deleted_turnos = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
    } else {
      // soft-delete únicamente finalizado/cancelado (comportamiento anterior)
      $stmt = mysqli_prepare($conn,
        "UPDATE turnos SET activo = 0, deleted_at = ? WHERE id_medico = ? AND COALESCE(activo,1) = 1 AND estado IN ('finalizado','cancelado')"
      );
      mysqli_stmt_bind_param($stmt, 'si', $now, $id_medico);
      $ok = mysqli_stmt_execute($stmt);
      if ($ok === false) { $err = mysqli_error($conn); mysqli_stmt_close($stmt); mysqli_rollback($conn); throw new Exception('Error al marcar turnos: '.$err); }
      $soft_deleted_turnos = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
    }
  }

  // Soft-delete del médico
  $stmt = mysqli_prepare($conn, "UPDATE medicos SET activo = 0, deleted_at = ? WHERE id_medico = ?");
  mysqli_stmt_bind_param($stmt, 'si', $now, $id_medico);
  $ok = mysqli_stmt_execute($stmt);
  if ($ok === false) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_rollback($conn);
    throw new Exception('Error al marcar médico: ' . $err);
  }
  $affected_med = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  mysqli_commit($conn);

  // Respuesta con detalle
  echo json_encode([
    'success' => true,
    'id_medico' => $id_medico,
    'reassigned' => (int)$reassigned,
    'turnos_soft_deleted' => (int)$soft_deleted_turnos,
    'medico_soft_deleted' => (int)$affected_med,
    'message' => ($reassigned>0 ? "Turnos reasignados: $reassigned. " : '') . "Médico marcado como inactivo."
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  if (isset($conn)) @mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}


