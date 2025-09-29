<?php
session_start();
include("../backend/conexion.php");

// Verificar sesión y permisos
if (!isset($_SESSION['id_usuario'])) {
    die("<div class='alert alert-danger'>⚠️ Debes iniciar sesión</div>");
}
if ($_SESSION['id_rol'] != 1 && $_SESSION['id_rol'] != 2) {
    die("<div class='alert alert-danger'>⛔ No tienes permisos para acceder a esta página</div>");
}

// Tomamos negocio desde sesión
$id_negocio = $_SESSION['id_negocio'];

// --- Parámetros GET ---
$precio_min = $_GET['precio_min'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$stock      = $_GET['stock'] ?? '';
$nombre     = $_GET['nombre'] ?? '';
$orden      = $_GET['orden'] ?? 'alfabetico';
$page       = $_GET['page'] ?? 1;
$limit      = 20;
$offset     = ($page - 1) * $limit;

// --- Consulta base ---
$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.categoria,
        (SELECT ruta FROM productos_imagenes WHERE id_producto = p.id_producto LIMIT 1) AS imagen
        FROM productos p
        WHERE p.id_negocio = ?";
$params = [$id_negocio];
$types = "i";

// --- Filtros ---
if ($precio_min !== '') {
    $sql .= " AND p.precio >= ?";
    $params[] = $precio_min; $types .= "d";
}
if ($precio_max !== '') {
    $sql .= " AND p.precio <= ?";
    $params[] = $precio_max; $types .= "d";
}
if ($categoria !== '') {
    $sql .= " AND p.categoria LIKE ?";
    $params[] = "%$categoria%"; $types .= "s";
}
if ($stock !== '') {
    $sql .= " AND p.stock >= ?";
    $params[] = $stock; $types .= "i";
}
if ($nombre !== '') {
    $sql .= " AND p.nombre LIKE ?";
    $params[] = "%$nombre%"; $types .= "s";
}

// --- Orden ---
switch ($orden) {
    case "mayor_precio": $sql .= " ORDER BY p.precio DESC"; break;
    case "menor_precio": $sql .= " ORDER BY p.precio ASC"; break;
    default: $sql .= " ORDER BY p.nombre ASC"; break;
}

// --- Paginación ---
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- Contar total para paginación ---
$sqlCount = "SELECT COUNT(*) as total FROM productos p WHERE p.id_negocio = ?";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("i", $id_negocio);
$stmtCount->execute();
$totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Productos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .sidebar { background:#f6f5f2; padding:20px; border-radius:10px; }
    .product-card { border:1px solid #ddd; border-radius:10px; overflow:hidden; text-align:center; background:#fff; }
    .product-card img { width:100%; height:150px; object-fit:cover; transition:0.3s; }
    .product-card img:hover { transform:scale(1.05); }
    .pagination a { margin:0 5px; }
  </style>
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
  <div class="row">
    
    <!-- Filtros -->
    <div class="col-md-3">
      <div class="sidebar">
        <h5><b>FILTROS:</b></h5>
        <form method="get">
          <label>Precio:</label>
          <div class="d-flex gap-2 mb-2">
            <input type="number" step="0.01" name="precio_min" class="form-control" placeholder="Desde" value="<?= htmlspecialchars($precio_min) ?>">
            <input type="number" step="0.01" name="precio_max" class="form-control" placeholder="Hasta" value="<?= htmlspecialchars($precio_max) ?>">
          </div>

          <label>Categoría:</label>
          <input type="text" name="categoria" class="form-control mb-2" value="<?= htmlspecialchars($categoria) ?>">

          <label>Stock mínimo:</label>
          <input type="number" name="stock" class="form-control mb-2" value="<?= htmlspecialchars($stock) ?>">

          <label>Nombre:</label>
          <input type="text" name="nombre" class="form-control mb-2" value="<?= htmlspecialchars($nombre) ?>">

          <button type="submit" class="btn btn-primary w-100">🔎 Filtrar</button>
          <a href="productos_listar.php" class="btn btn-secondary w-100 mt-2">♻ Limpiar</a>
        </form>
      </div>
    </div>

    <!-- Productos -->
    <div class="col-md-9">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <label><b>Ordenar por:</b></label>
          <select onchange="location.href='?orden='+this.value" class="form-select d-inline-block w-auto ms-2">
            <option value="alfabetico" <?= $orden=="alfabetico"?"selected":"" ?>>Alfabético</option>
            <option value="mayor_precio" <?= $orden=="mayor_precio"?"selected":"" ?>>Mayor precio</option>
            <option value="menor_precio" <?= $orden=="menor_precio"?"selected":"" ?>>Menor precio</option>
          </select>
        </div>
        <h4 class="text-danger"><?= htmlspecialchars($categoria ?: "Productos") ?></h4>
      </div>

      <div class="row g-3">
        <?php if ($result->num_rows > 0) { ?>
          <?php while($row = $result->fetch_assoc()) { ?>
            <div class="col-md-3">
              <div class="product-card">
                <a href="producto_detalle.php?id=<?= $row['id_producto'] ?>">
                  <img src="<?= $row['imagen'] ?: 'https://via.placeholder.com/150' ?>" alt="Producto">
                </a>
                <div class="p-2">
                  <a href="producto_detalle.php?id=<?= $row['id_producto'] ?>" class="text-decoration-none text-dark">
                    <h6><?= htmlspecialchars($row['nombre']) ?></h6>
                  </a>
                  <p class="text-muted">$<?= number_format($row['precio'],2) ?></p>
                </div>
              </div>
            </div>
          <?php } ?>
        <?php } else { ?>
          <div class="alert alert-warning">⚠️ No se encontraron productos.</div>
        <?php } ?>
      </div>

      <!-- Paginación -->
      <div class="d-flex justify-content-center mt-4">
        <nav>
          <ul class="pagination">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
              <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&orden=<?= $orden ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>
</body>
</html>


