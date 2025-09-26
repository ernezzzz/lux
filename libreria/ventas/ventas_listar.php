<?php
include("../../backend/conexion.php");

// Valores iniciales del filtro
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

$sql = "SELECT v.id_venta, v.fecha, v.total, v.solicitud,
               n.nombre AS negocio, 
               CONCAT(u.nombre, ' ', u.apellido) AS usuario
        FROM ventas v
        LEFT JOIN negocios n ON v.id_negocio = n.id_negocio
        LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
        WHERE 1=1";

// Agrego condiciones según el filtro
if ($fecha_desde && $fecha_hasta) {
    $sql .= " AND v.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";
} elseif ($fecha_desde) {
    $sql .= " AND v.fecha >= '$fecha_desde'";
} elseif ($fecha_hasta) {
    $sql .= " AND v.fecha <= '$fecha_hasta'";
}

$sql .= " ORDER BY v.fecha DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Ventas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

  <h2 class="text-center mb-4">📊 Listado de Ventas</h2>

  <!-- Formulario de filtro -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
      <label for="fecha_desde" class="form-label">Fecha desde:</label>
      <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="<?= $fecha_desde ?>">
    </div>
    <div class="col-md-3">
      <label for="fecha_hasta" class="form-label">Fecha hasta:</label>
      <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?= $fecha_hasta ?>">
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">🔎 Filtrar</button>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <a href="ventas_listar.php" class="btn btn-secondary w-100">♻ Limpiar</a>
    </div>
  </form>

  <div class="mb-3">
    <a href="ventas_form.php" class="btn btn-success">➕ Nueva Venta</a>
  </div>

  <?php if ($result->num_rows > 0) { ?>
    <table class="table table-striped table-bordered">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Negocio</th>
          <th>Usuario</th>
          <th>Fecha</th>
          <th>Total</th>
          <th>Solicitud</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
          <td><?= $row['id_venta'] ?></td>
          <td><?= $row['negocio'] ?></td>
          <td><?= $row['usuario'] ?></td>
          <td><?= $row['fecha'] ?></td>
          <td><?= $row['total'] ?></td>
          <td><?= $row['solicitud'] ?></td>
          <td>
            <a href="ventas_form.php?id=<?= $row['id_venta'] ?>" class="btn btn-sm btn-warning">✏ Editar</a>
            <a href="ventas_borrar.php?id=<?= $row['id_venta'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta venta?')">🗑 Eliminar</a>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  <?php } else { ?>
    <div class="alert alert-info">No hay ventas registradas en este rango de fechas.</div>
  <?php } ?>

</div>
</body>
</html>
