<?php
// consultorio/medicos_api.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  $q = isset($_POST['q']) ? trim($_POST['q']) : '';
  $esp = isset($_POST['especialidad']) ? trim($_POST['especialidad']) : '';
  $sort = isset($_POST['sort']) ? $_POST['sort'] : 'nombre_asc';
  $per_page = max(1, intval($_POST['per_page'] ?? 20));
  $page = max(1, intval($_POST['page'] ?? 1));
  $offset = ($page - 1) * $per_page;

  $where = [];
  $where[] = "m.activo = 1";

  if ($q !== '') {
    $esc = mysqli_real_escape_string($conn, $q);
    $where[] = "(m.nombre LIKE '%$esc%' OR m.apellido LIKE '%$esc%' OR COALESCE(m.especialidad,'') LIKE '%$esc%')";
  }
  if ($esp !== '') {
    $e = mysqli_real_escape_string($conn, $esp);
    $where[] = "m.especialidad = '$e'";
  }
  $where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  $order = "ORDER BY m.nombre ASC, m.apellido ASC";
  if ($sort === 'nombre_desc') $order = "ORDER BY m.nombre DESC, m.apellido DESC";

  $count_sql = "SELECT COUNT(*) AS total FROM medicos m $where_sql";
  $res_count = mysqli_query($conn, $count_sql);
  $total = 0; if ($res_count) $total = (int)mysqli_fetch_assoc($res_count)['total'];

  $sql = "SELECT m.id_medico, m.nombre, m.apellido, COALESCE(m.especialidad,'') AS especialidad,
           (SELECT COUNT(*) FROM turnos t WHERE t.id_medico = m.id_medico AND t.activo = 1) AS turnos_count
          FROM medicos m
          $where_sql
          $order
          LIMIT $per_page OFFSET $offset";
  $res = mysqli_query($conn, $sql);
  if ($res === false) throw new Exception(mysqli_error($conn));

  $rows = [];
  while($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'id_medico' => (int)$r['id_medico'],
      'nombre' => $r['nombre'],
      'apellido' => $r['apellido'],
      'especialidad' => $r['especialidad'],
      'turnos_count' => (int)$r['turnos_count']
    ];
  }

  $total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
  echo json_encode(['success'=>true,'total'=>$total,'per_page'=>$per_page,'page'=>$page,'total_pages'=>$total_pages,'data'=>$rows], JSON_UNESCAPED_UNICODE);
  exit;
} catch (Exception $ex) {
  http_response_code(500); echo json_encode(['success'=>false,'error'=>$ex->getMessage()]); exit;
}


