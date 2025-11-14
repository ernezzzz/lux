<?php
// historia_api.php  (POST -> JSON)
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
require_once __DIR__ . '/../../backend/checklogin.php';
require_once __DIR__ . '/../../backend/conexion.php';

try {
  $q = isset($_POST['q']) ? trim($_POST['q']) : '';
  $paciente = isset($_POST['paciente']) ? intval($_POST['paciente']) : 0;
  $sort = isset($_POST['sort']) ? $_POST['sort'] : 'fecha_desc';
  $per_page = max(1, intval($_POST['per_page'] ?? 20));
  $page = max(1, intval($_POST['page'] ?? 1));
  $offset = ($page - 1) * $per_page;

  $where = [];
  if ($q !== '') {
    $esc = mysqli_real_escape_string($conn, $q);
    $where[] = "(COALESCE(u.nombre,'') LIKE '%$esc%' OR COALESCE(u.apellido,'') LIKE '%$esc%' OR COALESCE(h.descripcion,'') LIKE '%$esc%')";
  }
  if ($paciente) $where[] = "h.id_paciente = ".(int)$paciente;

  $where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';
  $order_sql = ($sort === 'fecha_asc') ? "ORDER BY h.fecha ASC" : "ORDER BY h.fecha DESC";

  $count_sql = "SELECT COUNT(*) AS total FROM historia_clinica h LEFT JOIN usuarios u ON h.id_paciente = u.id_usuario $where_sql";
  $res_count = mysqli_query($conn, $count_sql);
  $total = 0; if ($res_count) $total = (int)mysqli_fetch_assoc($res_count)['total'];

  $sql = "SELECT h.id_historia, h.fecha, h.descripcion, h.id_paciente, COALESCE(CONCAT_WS(' ', u.nombre, u.apellido),'') AS paciente_nombre
          FROM historia_clinica h
          LEFT JOIN usuarios u ON h.id_paciente = u.id_usuario
          $where_sql
          $order_sql
          LIMIT $per_page OFFSET $offset";
  $res = mysqli_query($conn, $sql);
  if ($res === false) throw new Exception(mysqli_error($conn));

  $rows = [];
  while($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'id_historia' => (int)$r['id_historia'],
      'fecha' => $r['fecha'],
      'descripcion' => $r['descripcion'],
      'id_paciente' => (int)$r['id_paciente'],
      'paciente_nombre' => $r['paciente_nombre'],
    ];
  }

  $total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
  echo json_encode(['success'=>true,'total'=>$total,'per_page'=>$per_page,'page'=>$page,'total_pages'=>$total_pages,'data'=>$rows], JSON_UNESCAPED_UNICODE);
  exit;
} catch (Exception $ex) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE); exit;
}
