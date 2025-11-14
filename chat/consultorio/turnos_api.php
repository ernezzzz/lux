<?php
// consultorio/turnos_api.php
header('Content-Type: application/json; charset=utf-8');
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

try {
  // parámetros POST
  $q = isset($_POST['q']) ? trim($_POST['q']) : '';
  $estado = isset($_POST['estado']) && $_POST['estado'] !== '' ? trim($_POST['estado']) : '';
  $medico = isset($_POST['medico']) && $_POST['medico'] !== '' ? intval($_POST['medico']) : 0;
  $sort = isset($_POST['sort']) ? $_POST['sort'] : 'fecha_desc';
  $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 15;
  $page = isset($_POST['page']) ? max(1,intval($_POST['page'])) : 1;
  $activo_param = isset($_POST['activo']) ? trim($_POST['activo']) : null; // '0' or '1' or null -> default 1

  if ($per_page <= 0) $per_page = 15;
  if ($per_page > 1000) $per_page = 1000;

  // determinar filtro activo: por defecto activo = 1
  if ($activo_param === '0' || $activo_param === 0 || $activo_param === 'false') {
    $activo_filter = 0;
  } elseif ($activo_param === '1' || $activo_param === 1 || $activo_param === 'true') {
    $activo_filter = 1;
  } else {
    $activo_filter = 1;
  }

  // construir WHERE dinámico
  $where = [];
  $params = [];
  $types = '';

  // filtro por activo
  $where[] = "t.activo = ?";
  $params[] = $activo_filter;
  $types .= 'i';

  // estado
  if ($estado !== '') {
    $where[] = "t.estado = ?";
    $params[] = $estado;
    $types .= 's';
  }

  // medico
  if ($medico > 0) {
    $where[] = "t.id_medico = ?";
    $params[] = $medico;
    $types .= 'i';
  }

  // q: buscar por paciente o médico (nombre/apellido) o id_turno
  if ($q !== '') {
    // buscaremos por CONCAT(nombre, ' ', apellido) tanto en usuarios como en medicos, y por id_turno si es numérico
    $where[] = " ( CONCAT_WS(' ', u.nombre, u.apellido) LIKE ? OR CONCAT_WS(' ', m.nombre, m.apellido) LIKE ? OR t.id_turno = ? OR t.fecha LIKE ? ) ";
    $like = '%' . $q . '%';
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
    // si q es numérico poner id, sino -1 para que no coincida
    $params[] = is_numeric($q) ? intval($q) : -1; $types .= 'i';
    $params[] = $like; $types .= 's';
  }

  $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

  // ordenar
  $order_sql = "ORDER BY t.fecha DESC, t.hora DESC";
  switch ($sort) {
    case 'fecha_asc': $order_sql = "ORDER BY t.fecha ASC, t.hora ASC"; break;
    case 'paciente_asc': $order_sql = "ORDER BY u.nombre ASC, u.apellido ASC, t.fecha DESC"; break;
    case 'paciente_desc': $order_sql = "ORDER BY u.nombre DESC, u.apellido DESC, t.fecha DESC"; break;
    case 'fecha_desc':
    default:
      $order_sql = "ORDER BY t.fecha DESC, t.hora DESC";
  }

  // count total
  $count_sql = "SELECT COUNT(*) FROM turnos t
    LEFT JOIN usuarios u ON u.id_usuario = t.id_paciente
    LEFT JOIN medicos m ON m.id_medico = t.id_medico
    $where_sql";
  $stmt = mysqli_prepare($conn, $count_sql);
  if ($stmt === false) throw new Exception('Error preparar COUNT: ' . mysqli_error($conn));

  if (count($params) > 0) {
    // bind dinamico
    $bind_names = [];
    $bind_names[] = $types;
    for ($i=0;$i<count($params);$i++){
      // bind_param needs references
      $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
  }
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $total);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);
  $total = intval($total);

  $total_pages = max(1, (int)ceil($total / $per_page));
  if ($page > $total_pages) $page = $total_pages;
  $offset = ($page - 1) * $per_page;

  // seleccionar datos
  $select_sql = "SELECT
      t.id_turno,
      t.fecha,
      t.hora,
      t.estado,
      t.id_paciente,
      CONCAT_WS(' ', u.nombre, u.apellido) AS paciente_nombre,
      t.id_medico,
      CONCAT_WS(' ', m.nombre, m.apellido) AS medico_nombre
    FROM turnos t
    LEFT JOIN usuarios u ON u.id_usuario = t.id_paciente
    LEFT JOIN medicos m ON m.id_medico = t.id_medico
    $where_sql
    $order_sql
    LIMIT ? OFFSET ?";

  $stmt = mysqli_prepare($conn, $select_sql);
  if ($stmt === false) throw new Exception('Error preparar SELECT: ' . mysqli_error($conn));

  // bind params + limit/offset
  $bind_params = $params; // copia
  $bind_types = $types;
  $bind_types .= 'ii'; // for LIMIT and OFFSET
  $bind_params[] = $per_page;
  $bind_params[] = $offset;

  // create bind array with references
  $bind_names = [];
  $bind_names[] = $bind_types;
  for ($i=0;$i<count($bind_params);$i++){
    $bind_names[] = &$bind_params[$i];
  }
  call_user_func_array([$stmt, 'bind_param'], $bind_names);

  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $rows = [];
  while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'id_turno' => (int)$r['id_turno'],
      'fecha' => $r['fecha'],
      'hora' => $r['hora'],
      'estado' => $r['estado'],
      'id_paciente' => $r['id_paciente'] !== null ? (int)$r['id_paciente'] : null,
      'paciente_nombre' => $r['paciente_nombre'] ?? null,
      'id_medico' => $r['id_medico'] !== null ? (int)$r['id_medico'] : null,
      'medico_nombre' => $r['medico_nombre'] ?? null
    ];
  }
  mysqli_stmt_close($stmt);

  echo json_encode([
    'success' => true,
    'data' => $rows,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages
  ], JSON_UNESCAPED_UNICODE);

} catch (Exception $ex) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}


