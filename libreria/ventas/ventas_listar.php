<?php
include("../../backend/conexion.php");

// --- Filtros de fechas ---
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

$sql = "SELECT v.id_venta, v.fecha, v.total, v.solicitud,
               n.nombre AS negocio, 
               CONCAT(u.nombre, ' ', u.apellido) AS usuario
        FROM ventas v
        LEFT JOIN negocios n ON v.id_negocio = n.id_negocio
        LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
        WHERE 1=1";

if ($fecha_desde && $fecha_hasta) {
    $sql .= " AND v.fecha BETWEEN '$fecha_desde' AND '$fecha_hasta'";
} elseif ($fecha_desde) {
    $sql .= " AND v.fecha >= '$fecha_desde'";
} elseif ($fecha_hasta) {
    $sql .= " AND v.fecha <= '$fecha_hasta'";
}

$sql .= " ORDER BY v.fecha DESC";
$ventas = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administración de Ventas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .venta-card {
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      background: #fff;
    }
    .producto-item {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }
    .producto-item:last-child {
      border-bottom: none;
    }
    .producto-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
      margin-right: 15px;
    }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">

  <h2 class="mb-4">📊 Administración de Ventas</h2>

  <!-- Filtro de fechas -->
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

  <?php if ($ventas->num_rows > 0) { ?>
    <?php while ($venta = $ventas->fetch_assoc()) { ?>
      <div class="venta-card">
        <div class="d-flex justify-content-between mb-3">
          <div>
            <h5>Venta #<?= $venta['id_venta'] ?> - <?= htmlspecialchars($venta['usuario']) ?></h5>
            <small><b>Fecha:</b> <?= $venta['fecha'] ?> | <b>Negocio:</b> <?= htmlspecialchars($venta['negocio']) ?></small>
          </div>
          <div class="text-end">
            <h6 class="text-success">Total: $<?= number_format($venta['total'], 2) ?></h6>
            <span class="badge bg-info">Método: <?= htmlspecialchars($venta['solicitud']) ?></span>
          </div>
        </div>

        <!-- Productos de la venta -->
        <?php
$sqlDetalle = "SELECT d.cantidad, d.precio_unitario,
                              p.nombre,
                              (SELECT ruta FROM productos_imagenes WHERE id_producto = p.id_producto LIMIT 1) AS imagen
                       FROM ventas_detalle d
                       INNER JOIN productos p ON d.id_producto = p.id_producto
                       WHERE d.id_venta = ?";

        $stmt = $conn->prepare($sqlDetalle);
        $stmt->bind_param("i", $venta['id_venta']);
        $stmt->execute();
        $detalle = $stmt->get_result();
        ?>

        <?php while ($prod = $detalle->fetch_assoc()) { ?>
          <div class="producto-item">
            <img src="<?= $prod['imagen'] ?: 'https://via.placeholder.com/80' ?>" alt="Producto">
            <div>
              <h6><?= htmlspecialchars($prod['nombre']) ?></h6>
              <small>Cantidad: <?= $prod['cantidad'] ?> | Precio: $<?= number_format($prod['precio_unitario'],2) ?></small><br>
              <?php $subtotal = $prod['cantidad'] * $prod['precio_unitario']; ?>
<b>Subtotal: $<?= number_format($subtotal,2) ?></b>

            </div>
          </div>
        <?php } ?>
        
        <div class="mt-3 text-end">
          <a href="ventas_form.php?id=<?= $venta['id_venta'] ?>" class="btn btn-sm btn-warning">✏ Editar</a>
          <a href="ventas_borrar.php?id=<?= $venta['id_venta'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta venta?')">🗑 Eliminar</a>
        </div>
      </div>
    <?php } ?>
  <?php } else { ?>
    <div class="alert alert-info">⚠️ No hay ventas registradas en este rango de fechas.</div>
  <?php } ?>

</div>
</body>
</html>
