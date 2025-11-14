<?php
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$medico = isset($_GET['medico']) ? intval($_GET['medico']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'fecha_desc';
$per_page = max(1, intval($_GET['per_page'] ?? 20));
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = [];
if ($q !== '') {
  $esc = mysqli_real_escape_string($conn, $q);
  // buscar en paciente nombre/apellido, medico nombre/apellido, y motivo si existe en turnos
  $where[] = "(CONCAT_WS(' ', u.nombre, u.apellido) LIKE '%$esc%' OR CONCAT_WS(' ', m.nombre, m.apellido) LIKE '%$esc%' OR COALESCE(t.motivo,'') LIKE '%$esc%')";
}
if ($estado !== '') {
  $e = mysqli_real_escape_string($conn, $estado);
  $where[] = "t.estado = '$e'";
}
if ($medico) {
  $mid = (int)$medico;
  $where[] = "t.id_medico = $mid";
}
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

// ordenar
$order_sql = "ORDER BY t.fecha DESC, t.hora DESC";
if ($sort === 'fecha_asc') $order_sql = "ORDER BY t.fecha ASC, t.hora ASC";
if ($sort === 'paciente_asc') $order_sql = "ORDER BY paciente_nombre ASC";
if ($sort === 'paciente_desc') $order_sql = "ORDER BY paciente_nombre DESC";

// contar total
$count_sql = "SELECT COUNT(*) AS total
  FROM turnos t
  LEFT JOIN usuarios u ON t.id_paciente = u.id_usuario
  LEFT JOIN medicos m ON t.id_medico = m.id_medico
  $where_sql";
$count_res = mysqli_query($conn, $count_sql);
$total = 0;
if ($count_res) {
  $row = mysqli_fetch_assoc($count_res);
  $total = (int)$row['total'];
}

// obtener filas
$sql = "SELECT t.id_turno, t.id_paciente, t.fecha, t.hora, t.estado,
               COALESCE(CONCAT_WS(' ', u.nombre, u.apellido),'') AS paciente_nombre,
               COALESCE(CONCAT_WS(' ', m.nombre, m.apellido),'') AS medico_nombre,
               COALESCE(t.motivo,'') AS motivo
        FROM turnos t
        LEFT JOIN usuarios u ON t.id_paciente = u.id_usuario
        LEFT JOIN medicos m ON t.id_medico = m.id_medico
        $where_sql
        $order_sql
        LIMIT $per_page OFFSET $offset";
$res = mysqli_query($conn, $sql);

// renderizar tabla (rows)
?>
<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Fecha</th>
      <th>Hora</th>
      <th>Paciente</th>
      <th>Médico</th>
      <th>Motivo</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($res && mysqli_num_rows($res)): 
        while($r = mysqli_fetch_assoc($res)):
          $id = (int)$r['id_turno'];
          $paciente = htmlspecialchars($r['paciente_nombre']);
          $medico = htmlspecialchars($r['medico_nombre']);
          $fecha = htmlspecialchars($r['fecha']);
          $hora = $r['hora'] ? htmlspecialchars(date('H:i', strtotime($r['hora']))) : '';
          $motivo = htmlspecialchars($r['motivo']);
          $estadoRow = htmlspecialchars($r['estado']);
    ?>
    <tr>
      <td><?= $id ?></td>
      <td><?= $fecha ?></td>
      <td><?= $hora ?></td>
      <td><?= $paciente ?></td>
      <td><?= $medico ?></td>
      <td><?= $motivo ?></td>
      <td><?= $estadoRow ?></td>
      <td>
        <a href="turnos_form.php?id=<?= $id ?>" class="btn btn-sm btn-primary">Editar</a>
        <a href="turnos_borrar.php?id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar turno #<?= $id ?>?')">Borrar</a>
        <a href="historia_listar.php?paciente=<?= (int)$r['id_paciente'] ?>" class="btn btn-sm btn-info">Historia</a>
      </td>
    </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="8" class="text-center">No se encontraron turnos.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php
// paginación simple
$total_pages = max(1, (int)ceil($total / $per_page));
if ($total_pages > 1):
  $current = $page;
  echo '<nav><ul class="pagination">';
  $start = max(1, $current - 3);
  $end = min($total_pages, $current + 3);
  if ($current > 1) {
    echo '<li class="page-item"><a class="page-link js-page-link" href="#" data-page="'.($current-1).'">«</a></li>';
  }
  for($p=$start;$p<=$end;$p++){
    $cls = $p==$current ? ' active' : '';
    echo '<li class="page-item'.$cls.'"><a class="page-link js-page-link" href="#" data-page="'.$p.'">'.$p.'</a></li>';
  }
  if ($current < $total_pages) {
    echo '<li class="page-item"><a class="page-link js-page-link" href="#" data-page="'.($current+1).'">»</a></li>';
  }
  echo '</ul></nav>';
endif;
?>
