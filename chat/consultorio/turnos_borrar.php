<?php
// consultorio/turnos_borrar.php
// Soft-delete de un turno (marcar activo = 0, guardar deleted_at)

include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
  header('Location: turnos_listar.php');
  exit;
}

try {
  // comprobar existencia
  $stmt = mysqli_prepare($conn, "SELECT id_turno, COALESCE(activo,1) AS activo FROM turnos WHERE id_turno = ? LIMIT 1");
  if ($stmt === false) throw new Exception('Error preparando consulta: ' . mysqli_error($conn));
  mysqli_stmt_bind_param($stmt, 'i', $id);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);

  if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    // no existe
    header('Location: turnos_listar.php?error=' . urlencode('Turno no encontrado'));
    exit;
  }

  mysqli_stmt_bind_result($stmt, $tid, $tactivo);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  if ((int)$tactivo === 0) {
    // ya inactivo
    header('Location: turnos_listar.php?info=' . urlencode('El turno ya está eliminado'));
    exit;
  }

  // realizar soft-delete en transacción
  mysqli_begin_transaction($conn);

  $now = date('Y-m-d H:i:s');
  $upd = mysqli_prepare($conn, "UPDATE turnos SET activo = 0, deleted_at = ? WHERE id_turno = ? AND activo = 1");
  if ($upd === false) {
    throw new Exception('Error preparando actualización: ' . mysqli_error($conn));
  }
  mysqli_stmt_bind_param($upd, 'si', $now, $id);
  $ok = mysqli_stmt_execute($upd);
  if ($ok === false) {
    $err = mysqli_error($conn);
    mysqli_stmt_close($upd);
    mysqli_rollback($conn);
    throw new Exception('Error al actualizar turno: ' . $err);
  }
  $affected = mysqli_stmt_affected_rows($upd);
  mysqli_stmt_close($upd);

  mysqli_commit($conn);

  if ($affected > 0) {
    header('Location: turnos_listar.php?ok=' . urlencode('Turno eliminado correctamente'));
    exit;
  } else {
    // ninguna fila afectada (posible condición race)
    header('Location: turnos_listar.php?error=' . urlencode('No se pudo eliminar el turno (ya modificado)'));
    exit;
  }

} catch (Exception $ex) {
  if (isset($conn)) {
    @mysqli_rollback($conn);
  }
  // registra error si querés (log), y redirige con mensaje
  $msg = 'Error al eliminar turno: ' . $ex->getMessage();
  header('Location: turnos_listar.php?error=' . urlencode($msg));
  exit;
}
