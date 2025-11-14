<?php
include '../../backend/checklogin.php';
include '../../backend/conexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$esp = isset($_GET['especialidad']) ? trim($_GET['especialidad']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nombre_asc';
$per_page = max(1, intval($_GET['per_page'] ?? 20));
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = [];
if ($q !== '') {
  $esc = mysqli_real_escape_string($conn, $q);
  $where[] = "(nombre LIKE '%$esc%' OR apellido LIKE '%$esc%')";
}
if ($esp !== '') {
  $e = mysqli_real_escape_string($conn, $esp);
  $where[] = "especialidad = '$e'";
}
$where_sql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$order = "ORDER BY nombre ASC, apellido ASC";
if ($sort === 'nombre_desc') $order = "ORDER BY nombre DESC, apellido DESC";

$count_sql = "SELECT COUNT(*) AS total FROM medicos $where_sql";
$res_count = mysqli_query($conn, $count_sql);
$total = 0; if ($res_count) { $total = (int)mysqli_fetch_assoc($res_count)['total']; }

$sql = "SELECT id_medico, nombre, apellido, especialidad FROM medicos $where_sql $order LIMIT $per_page OFFSET $offset";
$res = mysqli_query($conn, $sql);
?>
<table class="table table-striped">
  <thead><tr><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Acciones</th></tr></thead>
  <tbody>
  <?php if ($res && mysqli_num_rows($res)): while($r = mysqli_fetch_assoc($res)): ?>
    <tr>
      <td><?= (int)$r['id_medico'] ?></td>
      <td><?= htmlspecialchars(trim($r['nombre'].' '.$r['apellido'])) ?></td>
      <td><?= htmlspecialchars($r['especialidad']) ?></td>
      <td>
        <a href="medicos_form.php?id=<?= (int)$r['id_medico'] ?>" class="btn btn-sm btn-primary">Editar</a>
        <a href="medicos_borrar.php?id=<?= (int)$r['id_medico'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Borrar médico?')">Borrar</a>
      </td>
    </tr>
  <?php endwhile; else: ?>
    <tr><td colspan="4" class="text-center">No se encontraron médicos.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<?php
$total_pages = max(1, (int)ceil($total / $per_page));
if ($total_pages > 1){
  $current = $page;
  echo '<nav><ul class="pagination">';
  if ($current > 1) echo '<li class="page-item"><a class="page-link js-page-link-med" href="#" data-page="'.($current-1).'">«</a></li>';
  $start = max(1, $current-3); $end = min($total_pages, $current+3);
  for($p=$start;$p<=$end;$p++){
    $cls = $p==$current ? ' active' : '';
    echo '<li class="page-item'.$cls.'"><a class="page-link js-page-link-med" href="#" data-page="'.$p.'">'.$p.'</a></li>';
  }
  if ($current < $total_pages) echo '<li class="page-item"><a class="page-link js-page-link-med" href="#" data-page="'.($current+1).'">»</a></li>';
  echo '</ul></nav>';
}
?>
