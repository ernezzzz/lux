<?php
session_start();
include("../backend/conexion.php");

// Verificar que haya sesión
if (!isset($_SESSION['id_usuario'])) {
    die("<div class='alert alert-danger'>⚠️ Debes iniciar sesión</div>");
}

// Verificar rol permitido
if ($_SESSION['id_rol'] != 1 && $_SESSION['id_rol'] != 2) {
    die("<div class='alert alert-danger'>⛔ No tienes permisos para acceder a esta página</div>");
}

// Tomamos id_negocio automáticamente
$id_negocio = $_SESSION['id_negocio'];

// --- Filtros ---
$precio_min = $_GET['precio_min'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$stock = $_GET['stock'] ?? '';
$nombre = $_GET['nombre'] ?? '';

// --- Consulta base ---
$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.categoria, n.nombre AS negocio
        FROM productos p
        LEFT JOIN negocios n ON p.id_negocio = n.id_negocio
        WHERE p.id_negocio = ?";

$params = [$id_negocio];
$types = "i";

// --- Filtro precio mínimo ---
if ($precio_min !== '') {
    $sql .= " AND p.precio >= ?";
    $params[] = $precio_min;
    $types .= "d"; // número decimal
}

// --- Filtro precio máximo ---
if ($precio_max !== '') {
    $sql .= " AND p.precio <= ?";
    $params[] = $precio_max;
    $types .= "d";
}

// --- Filtro categoría ---
if ($categoria !== '') {
    $sql .= " AND p.categoria LIKE ?";
    $params[] = "%$categoria%";
    $types .= "s";
}

// --- Filtro stock ---
if ($stock !== '') {
    $sql .= " AND p.stock >= ?";
    $params[] = $stock;
    $types .= "i";
}

// --- Filtro nombre ---
if ($nombre !== '') {
    $sql .= " AND p.nombre LIKE ?";
    $params[] = "%$nombre%";
    $types .= "s";
}

// --- Ejecutar consulta ---
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Productos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">

  <h2 class="text-center mb-4">📦 Productos - Negocio <?= htmlspecialchars($id_negocio) ?></h2>

  <!-- Filtros -->
  <form method="get" class="row g-3 mb-4">

    <div class="col-md-2">
      <label class="form-label">Precio mínimo:</label>
      <input type="number" step="0.01" name="precio_min" class="form-control" value="<?= htmlspecialchars($precio_min) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Precio máximo:</label>
      <input type="number" step="0.01" name="precio_max" class="form-control" value="<?= htmlspecialchars($precio_max) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Categoría:</label>
      <input type="text" name="categoria" class="form-control" value="<?= htmlspecialchars($categoria) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Stock mínimo:</label>
      <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($stock) ?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Nombre:</label>
      <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($nombre) ?>">
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">🔎 Filtrar</button>
    </div>

    <div class="col-md-12 d-flex justify-content-end">
      <a href="productos_listar.php" class="btn btn-secondary">♻ Limpiar</a>
    </div>
  </form>

  <a href="productos_form.php" class="btn btn-success mb-3">➕ Agregar Producto</a>

  <!-- Resultados -->
  <table class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Negocio</th>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Precio</th>
        <th>Stock</th>
        <th>Categoría</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($result->num_rows > 0) { ?>
      <?php while($row = $result->fetch_assoc()) { ?>
        <tr>
          <td><?= $row['id_producto'] ?></td>
          <td><?= $row['negocio'] ?></td>
          <td><?= $row['nombre'] ?></td>
          <td><?= $row['descripcion'] ?></td>
          <td><?= $row['precio'] ?></td>
          <td><?= $row['stock'] ?></td>
          <td><?= $row['categoria'] ?></td>
          <td>
            <a href="productos_form.php?id=<?= $row['id_producto'] ?>" class="btn btn-sm btn-warning">✏️ Editar</a>
            <a href="productos_borrar.php?id=<?= $row['id_producto'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro de eliminar?')">🗑 Eliminar</a>
          </td>
        </tr>
      <?php } ?>
    <?php } else { ?>
      <tr>
        <td colspan="8" class="text-center">⚠️ No se encontraron productos con esos filtros</td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
</div>
</body>
</html>
