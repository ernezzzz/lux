<?php
include("../../backend/conexion.php");

// --- Helpers ---------------------------------------------------
/**
 * Resuelve una ruta guardada en BD a una URL válida para el navegador.
 * Si la ruta es absoluta, la retorna.
 * Si es solo el nombre del archivo, busca en la carpeta 'imagenes/'.
 */
function resolveImageUrl($ruta) {
    if (!$ruta) return 'https://via.placeholder.com/80';
    // Si es URL absoluta
    if (preg_match('#^(https?:)?//#i', $ruta) || stripos($ruta,'http') === 0) {
        return $ruta;
    }
    // Siempre busca en la carpeta 'imagenes'
    return 'imagenes/' . ltrim($ruta, '/\\');


    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

    // Candidate filesystem paths to try (incluye carpeta 'imagenes')
    $candidates = [
        $docRoot . '/imagenes/' . ltrim($ruta, '/\\'),           // <-- carpeta de imágenes principal
        $docRoot . '/' . ltrim($ruta, '/\\'),
        $docRoot . '/lux/' . ltrim($ruta, '/\\'),
        $docRoot . '/uploads/' . ltrim($ruta, '/\\'),
        $docRoot . '/libreria/' . ltrim($ruta, '/\\'),
    ];

    foreach ($candidates as $path) {
        if (file_exists($path)) {
            // convertir filesystem path a web path
            $webPath = str_replace('\\', '/', str_replace($docRoot, '', $path));
            if ($webPath === '') {
                $webPath = '/';
            }
            if ($webPath[0] !== '/') $webPath = '/' . $webPath;
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $scheme . '://' . $_SERVER['HTTP_HOST'] . $webPath;
        }
    }

    // Si no encontramos el archivo físico, devolvemos una ruta relativa basada en la carpeta 'imagenes'
    return 'imagenes/' . ltrim($ruta, '/\\');
}
// ---------------------------------------------------------------

// --- Filtros de fechas ---
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

$sql = "SELECT v.id_venta, v.fecha, v.total, v.metodo_pago, v.solicitud,
               n.nombre AS negocio,
               CONCAT(u.nombre, ' ', u.apellido) AS usuario
        FROM ventas v
        LEFT JOIN negocios n ON v.id_negocio = n.id_negocio
        LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
        WHERE 1=1";

if ($fecha_desde && $fecha_hasta) {
    $sql .= " AND v.fecha BETWEEN ? AND ?";
    $params = [$fecha_desde, $fecha_hasta];
    $types = "ss";
} elseif ($fecha_desde) {
    $sql .= " AND v.fecha >= ?";
    $params = [$fecha_desde];
    $types = "s";
} elseif ($fecha_hasta) {
    $sql .= " AND v.fecha <= ?";
    $params = [$fecha_hasta];
    $types = "s";
} else {
    $params = [];
    $types = "";
}

$sql .= " ORDER BY v.fecha DESC";
$stmtMain = $conn->prepare($sql);
if ($types !== "") {
    $stmtMain->bind_param($types, ...$params);
}
$stmtMain->execute();
$ventas = $stmtMain->get_result();
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
    .producto-item:last-child { border-bottom: none; }
    .producto-item img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 15px; }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">

  <h2 class="mb-4">📊 Administración de Ventas</h2>

  <!-- Filtro de fechas -->
  <form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
      <label for="fecha_desde" class="form-label">Fecha desde:</label>
      <input type="date" id="fecha_desde" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fecha_desde) ?>">
    </div>
    <div class="col-md-3">
      <label for="fecha_hasta" class="form-label">Fecha hasta:</label>
      <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fecha_hasta) ?>">
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
            <span class="badge bg-info">Método: <?= htmlspecialchars($venta['metodo_pago']) ?></span>
            <span class="badge bg-secondary">Solicitud: <?= htmlspecialchars($venta['solicitud']) ?></span>
          </div>
        </div>

        <!-- Productos de la venta (traemos imagen principal vía subquery) -->
        <?php
        $sqlDetalle = "SELECT d.cantidad, d.precio_unitario, p.nombre,
                              (SELECT ruta FROM productos_imagenes WHERE id_producto = p.id_producto ORDER BY id_imagen ASC LIMIT 1) AS imagen
                       FROM ventas_detalle d
                       INNER JOIN productos p ON d.id_producto = p.id_producto
                       WHERE d.id_venta = ?";
        $stmt = $conn->prepare($sqlDetalle);
        $stmt->bind_param("i", $venta['id_venta']);
        $stmt->execute();
        $detalle = $stmt->get_result();
        ?>

        <?php while ($prod = $detalle->fetch_assoc()) {
            // resolvemos URL pública para la imagen (intenta varias ubicaciones)
            $imgSrc = resolveImageUrl($prod['imagen']);
        ?>
          <!-- Ruta guardada en BD: "<?= htmlspecialchars($prod['imagen']) ?>" -->
          <div class="producto-item">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Producto">
            <div>
              <h6><?= htmlspecialchars($prod['nombre']) ?></h6>
              <small>Cantidad: <?= (int)$prod['cantidad'] ?> | Precio: $<?= number_format($prod['precio_unitario'],2) ?></small><br>
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