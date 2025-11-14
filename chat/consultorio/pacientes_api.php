<?php
// consultorio/pacientes_api.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  $q = isset($_POST['q']) ? trim($_POST['q']) : '';
  $sort = isset($_POST['sort']) ? $_POST['sort'] : 'nombre_asc';
  $per_page = max(1, intval($_POST['per_page'] ?? 20));
  $page = max(1, intval($_POST['page'] ?? 1));
  $offset = ($page - 1) * $per_page;

  $where = [];
  // only patients and active
  $where[] = "u.id_rol = (SELECT id_rol FROM rol WHERE rol = 'paciente' LIMIT 1)";
  $where[] = "u.activo = 1";

  if ($q !== '') {
    $esc = mysqli_real_escape_string($conn, $q);
    $where[] = "(CONCAT_WS(' ', u.nombre, u.apellido) LIKE '%$esc%' OR COALESCE(u.email,'') LIKE '%$esc%')";
  }

  $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  // order
  $order_sql = "ORDER BY u.nombre ASC, u.apellido ASC";
  if ($sort === 'nombre_desc') $order_sql = "ORDER BY u.nombre DESC, u.apellido DESC";

  // total
  $count_sql = "SELECT COUNT(*) AS total FROM usuarios u $where_sql";
  $res_count = mysqli_query($conn, $count_sql);
  $total = 0;
  if ($res_count) $total = (int) mysqli_fetch_assoc($res_count)['total'];

  // select rows with subqueries for counts
  $sql = "SELECT u.id_usuario, u.nombre, u.apellido, COALESCE(u.email,'') AS email,
             (SELECT COUNT(*) FROM turnos t WHERE t.id_paciente = u.id_usuario AND t.activo = 1) AS turnos_count,
             (SELECT COUNT(*) FROM historia_clinica h WHERE h.id_paciente = u.id_usuario) AS historias_count
          FROM usuarios u
          $where_sql
          $order_sql
          LIMIT $per_page OFFSET $offset";
  $res = mysqli_query($conn, $sql);
  if ($res === false) throw new Exception(mysqli_error($conn));

  $rows = [];
  while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'id_usuario' => (int)$r['id_usuario'],
      'nombre' => $r['nombre'],
      'apellido' => $r['apellido'],
      'email' => $r['email'],
      'turnos_count' => (int)$r['turnos_count'],
      'historias_count' => (int)$r['historias_count']
    ];
  }

  $total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
  echo json_encode([
    'success'=>true,
    'total'=>$total,
    'per_page'=>$per_page,
    'page'=>$page,
    'total_pages'=>$total_pages,
    'data'=>$rows
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Exception $ex) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()]);
  exit;
}

