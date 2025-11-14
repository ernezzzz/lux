<?php
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$paciente = isset($_GET['paciente']) ? intval($_GET['paciente']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'fecha_desc';
$per_page = max(1, intval($_GET['per_page'] ?? 20));
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = [];
if ($q !== '') {
  $esc = mysqli_real_escape_string($conn, $q);
  $where[] = "(COALESCE(u.nombre,'') LIKE '%$esc%' OR COALESCE(u.apellido,'') LIKE '%$esc%' OR COALESCE(h.descripcion,'') LIKE '%$esc%')";
}
if ($paciente) $where[] = "h.id_paciente = ".(int)$paciente;

$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';
$order_sql = "ORDER BY h.fecha DESC";
if ($sort === 'fecha_asc') $order_sql = "ORDER BY h.fecha ASC";

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
?>
<table class="table table-striped">
  <thead><tr><th>ID</th><th>Fecha</th><th>Paciente</th><th>Descripción</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php if ($res && mysqli_num_rows($res)): while($r = mysqli_fetch_assoc($res)): ?>
    <tr>
      <td><?= (int)$r['id_historia'] ?></td>
      <td><?= htmlspecialchars($r['fecha']) ?></td>
      <td><?= htmlspecialchars($r['paciente_nombre']) ?></td>
      <td><?= nl2br(htmlspecialchars($r['descripcion'])) ?></td>
      <td>
        <a href="historia_form.php?id=<?= (int)$r['id_historia'] ?>" class="btn btn-sm btn-primary">Editar</a>
        <a href="historia_borrar.php?id=<?= (int)$r['id_historia'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Borrar historia?')">Borrar</a>
      </td>
    </tr>
  <?php endwhile; else: ?>
    <tr><td colspan="5" class="text-center">No se encontraron registros.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<?php
$total_pages = max(1, (int)ceil($total / $per_page));
if ($total_pages > 1){
  $current = $page;
  echo '<nav><ul class="pagination">';
  if ($current > 1) echo '<li class="page-item"><a class="page-link js-page-link-hist" href="#" data-page="'.($current-1).'">«</a></li>';
  $start = max(1, $current-3); $end = min($total_pages, $current+3);
  for($p=$start;$p<=$end;$p++){
    $cls = $p==$current ? ' active' : '';
    echo '<li class="page-item'.$cls.'"><a class="page-link js-page-link-hist" href="#" data-page="'.$p.'">'.$p.'</a></li>';
  }
  if ($current < $total_pages) echo '<li class="page-item"><a class="page-link js-page-link-hist" href="#" data-page="'.($current+1).'">»</a></li>';
  echo '</ul></nav>';
}
?>
