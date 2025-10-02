<?php
include("../backend/conexion.php");

$categoria = $_GET['cat'] ?? '';

if (!$categoria) {
    die("<div class='alert alert-warning'>⚠️ No se especificó categoría</div>");
}

$stmt = $conn->prepare("SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, n.nombre AS negocio 
                       FROM productos p
                       LEFT JOIN negocios n ON p.id_negocio = n.id_negocio
                       WHERE p.categoria = ?");
$stmt->bind_param("s", $categoria);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Productos - <?= htmlspecialchars($categoria) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <h1 class="text-center text-danger">Productos - <?= htmlspecialchars($categoria) ?></h1>
    <div class="text-center mb-4">
      <a href="dashboard.php" class="btn btn-secondary">⬅ Volver al Dashboard</a>
      <a href="../productos_abm/productos_form.php" class="btn btn-success">➕ Nuevo Producto</a>
      <a href="../productos_abm/productos_listar.php" class="btn btn-primary">📋 Inventario Completo</a>
    </div>

    <?php if ($result->num_rows > 0) { ?>
      <table class="table table-striped table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Negocio</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
          <tr>
            <td><?= $row['id_producto'] ?></td>
            <td><?= htmlspecialchars($row['negocio']) ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['descripcion']) ?></td>
            <td>$<?= number_format($row['precio'],2) ?></td>
            <td><?= $row['stock'] ?></td>
            <td class="text-center">
              <a href="../productos_abm/productos_form.php?id=<?= $row['id_producto'] ?>" 
                 class="btn btn-sm btn-warning">✏ Editar</a>
              <a href="../productos_abm/productos_borrar.php?id=<?= $row['id_producto'] ?>" 
                 class="btn btn-sm btn-danger" 
                 onclick="return confirm('¿Seguro que deseas eliminar este producto?')">🗑 Eliminar</a>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    <?php } else { ?>
      <div class="alert alert-info">⚠️ No hay productos en la categoría <b><?= htmlspecialchars($categoria) ?></b>.</div>
    <?php } ?>
  </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>


