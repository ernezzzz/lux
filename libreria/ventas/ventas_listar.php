<?php
include '../../backend/checklogin.php'; // protege la página
include '../../backend/header.php';     // muestra la barra superior
include("../../backend/conexion.php");

// --- Función para resolver imágenes ---
function resolveImageUrl($ruta) {
    if (empty($ruta)) return 'https://via.placeholder.com/80';
    if (preg_match('#^(https?:)?//#i', $ruta) || stripos($ruta, 'http') === 0) return $ruta;
    $ruta = str_replace('\\', '/', trim($ruta));
    $rutaSinUploads = preg_replace('#^uploads/#i', '', $ruta);
    if (stripos($rutaSinUploads, 'imagenes/') === 0) {
        $rutaSinUploads = preg_replace('#^imagenes/#i', '', $rutaSinUploads);
    }
    $carpetaFisica = __DIR__ . '/../../imagenes/';
    $nombreArchivo = ltrim($rutaSinUploads, '/');
    $rutaEnDisco = $carpetaFisica . $nombreArchivo;

    if (file_exists($rutaEnDisco)) {
        $parts = explode('/', $nombreArchivo);
        $parts[count($parts) - 1] = rawurlencode($parts[count($parts) - 1]);
        return '../../imagenes/' . implode('/', $parts);
    }

    $nombreAltern = str_replace(' ', '_', $nombreArchivo);
    if (file_exists($carpetaFisica . $nombreAltern)) {
        $parts = explode('/', $nombreAltern);
        $parts[count($parts) - 1] = rawurlencode($parts[count($parts) - 1]);
        return '../../imagenes/' . implode('/', $parts);
    }

    $decoded = urldecode($nombreArchivo);
    if ($decoded !== $nombreArchivo && file_exists($carpetaFisica . $decoded)) {
        $parts = explode('/', $decoded);
        $parts[count($parts) - 1] = rawurlencode($parts[count($parts) - 1]);
        return '../../imagenes/' . implode('/', $parts);
    }

    return 'https://via.placeholder.com/80';
}

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
      color: #333;
      display: flex;
      flex-direction: column;
    }
    main {
      flex: 1;
    }

    /* Navbar */
    .navbar {
      background: linear-gradient(90deg, #1e293b, #334155);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      min-height: 64px;
    }
    .navbar-brand {
      font-weight: 700;
      font-size: 1.4rem;
      color: #fff !important;
      letter-spacing: 1px;
    }

    /* Título */
    .dashboard-title {
      font-weight: 700;
      color: #0f172a;
      margin-top: 2rem;
      margin-bottom: 2rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      border-bottom: 3px solid #10b981;
      display: inline-block;
      padding-bottom: 0.5rem;
    }

    /* Tarjetas de ventas */
    .venta-card {
      border: none;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

    footer {
      text-align: center;
      padding: 1.5rem 0;
      background: #1e293b;
      color: #fff;
      font-size: 0.9rem;
      margin-top: auto;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-dark px-4">
    <a class="navbar-brand text-white" href="#">GRUPO LUX</a>
    <div class="ms-auto">
      <a href="../dashboard.php" class="btn btn-outline-light me-2 px-3">Volver</a>
    </div>
  </nav>

  <main>
    <div class="container py-4">
      <h1 class="dashboard-title">Administración de Ventas</h1>

      <!-- Filtro de fechas -->
      <form method="get" class="row g-3 mb-4" id="filtrosForm">
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
          <button type="button" class="btn btn-secondary w-100 mt-2" id="btnLimpiar">♻ Limpiar</button>
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
              $imgSrc = resolveImageUrl($prod['imagen']);
            ?>
              <div class="producto-item">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Producto">
                <div>
                  <h6><?= htmlspecialchars($prod['nombre']) ?></h6>
                  <small>Cantidad: <?= (int)$prod['cantidad'] ?> | Precio: $<?= number_format($prod['precio_unitario'], 2) ?></small><br>
                  <?php $subtotal = $prod['cantidad'] * $prod['precio_unitario']; ?>
                  <b>Subtotal: $<?= number_format($subtotal, 2) ?></b>
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
  </main>

  <footer>
    © <?= date("Y"); ?> Grupo Lux — Todos los derechos reservados.
  </footer>

  <script>
    document.getElementById('btnLimpiar').addEventListener('click', function() {
      // Limpia los campos del filtro
      document.querySelectorAll('#filtrosForm input').forEach(input => input.value = '');
      // Opcional: si tienes selects, también resetea aquí

      // Llama a la función AJAX para recargar la lista sin filtros
      filtrarProductos(1);
    });
  </script>
</body>
</html>
