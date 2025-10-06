<?php
include '../backend/checklogin.php'; // protege la página
include '../backend/header.php';     // muestra la barra superior
include("../backend/conexion.php");

// Verificar sesión y permisos
if (!isset($_SESSION['id_usuario'])) {
    die("<div class='alert alert-danger'>⚠️ Debes iniciar sesión</div>");
}
$id_negocio = $_SESSION['id_negocio'];
$rol = $_SESSION['id_rol'] ?? null; // <-- Agrega esta línea

// --- Filtros y query ---
$precio_min = $_GET['precio_min'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';
$categoria  = $_GET['categoria'] ?? '';
$stock      = $_GET['stock'] ?? '';
$nombre     = $_GET['nombre'] ?? '';
$orden      = $_GET['orden'] ?? 'alfabetico';
$page       = $_GET['page'] ?? 1;
$limit      = 20;
$offset     = ($page - 1) * $limit;

$sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.categoria,
        (SELECT ruta FROM productos_imagenes WHERE id_producto = p.id_producto LIMIT 1) AS imagen
        FROM productos p
        WHERE p.id_negocio = ?";
$params = [$id_negocio];
$types = "i";

if ($precio_min !== '') { $sql .= " AND p.precio >= ?"; $params[] = $precio_min; $types.="d"; }
if ($precio_max !== '') { $sql .= " AND p.precio <= ?"; $params[] = $precio_max; $types.="d"; }
if ($categoria !== '')  { $sql .= " AND p.categoria LIKE ?"; $params[] = "%$categoria%"; $types.="s"; }
if ($stock !== '')      { $sql .= " AND p.stock >= ?"; $params[] = $stock; $types.="i"; }
if ($nombre !== '')     { $sql .= " AND p.nombre LIKE ?"; $params[] = "%$nombre%"; $types.="s"; }

switch ($orden) {
    case "mayor_precio": $sql .= " ORDER BY p.precio DESC"; break;
    case "menor_precio": $sql .= " ORDER BY p.precio ASC"; break;
    default: $sql .= " ORDER BY p.nombre ASC"; break;
}
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
      color: #333;
    }

    /* ===== Navbar ===== */
    .navbar {
      background: linear-gradient(90deg, #1e293b, #334155);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      padding-top: 0.75rem !important;
      padding-bottom: 0.75rem !important;
      padding-left: 2rem !important;
      padding-right: 2rem !important;
      min-height: 64px;
    }
    .navbar-brand {
      font-weight: 700;
      font-size: 1.4rem;
      letter-spacing: 1px;
      color: #fff !important;
      margin-right: auto !important; /* siempre a la izquierda */
    }
    .navbar .btn {
      border-radius: 10px;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 0.4rem 1rem !important;
      font-size: 0.95rem;
      line-height: 1.2;
    }
    .navbar .btn:hover {
      transform: translateY(-2px);
      background-color: #10b981;
      color: #fff;
    }

    /* ===== Productos ===== */
    .sidebar { background:#f6f5f2; padding:20px; border-radius:10px; }
    .product-card { border:1px solid #ddd; border-radius:10px; overflow:hidden; text-align:center; background:#fff; }
    .product-card img { width:100%; height:150px; object-fit:cover; transition:0.3s; }
    .product-card img:hover { transform:scale(1.05); }
    .pagination a { margin:0 5px; }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark px-4">
  <a class="navbar-brand text-white" href="#">GRUPO LUX</a>
  <div class="ms-auto">
    <a href="../libreria/dashboard.php" class="btn btn-outline-light me-2 px-3">Volver</a>
  </div>
</nav>

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
        <?php if ($rol != 4) { ?>
        <div>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Agregar Producto</button>
        </div>
        <?php } ?>
      </div>

      <div class="row g-3">
        <?php if ($result->num_rows > 0) { ?>
          <?php while($row = $result->fetch_assoc()) { ?>
            <div class="col-md-3">
              <div class="product-card p-2">
                <img src="<?= $row['imagen'] ?: 'https://via.placeholder.com/150' ?>" alt="Producto">
                <div class="p-2">
                  <h6><?= htmlspecialchars($row['nombre']) ?></h6>
                  <p class="text-muted mb-1">$<?= number_format($row['precio'],2) ?></p>
                  <?php if ($rol != 4) { ?>
                  <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-warning" 
                            onclick="editarProducto(<?= $row['id_producto'] ?>, '<?= htmlspecialchars($row['nombre']) ?>', '<?= htmlspecialchars($row['descripcion']) ?>', <?= $row['precio'] ?>, <?= $row['stock'] ?>)">
                      ✏ Editar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="eliminarProducto(<?= $row['id_producto'] ?>)">🗑 Eliminar</button>
                  </div>
                  <?php } ?>
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

<?php if ($rol != 4) { ?>
<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formAgregar">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Agregar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required>
          <textarea name="descripcion" class="form-control mb-2" placeholder="Descripción"></textarea>
          <input type="number" name="precio" step="0.01" class="form-control mb-2" placeholder="Precio" required>
          <input type="number" name="stock" class="form-control mb-2" placeholder="Stock" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php } ?>

<?php if ($rol != 4) { ?>
<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formEditar">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Editar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_producto" id="edit_id">
          <input type="text" name="nombre" id="edit_nombre" class="form-control mb-2" required>
          <textarea name="descripcion" id="edit_descripcion" class="form-control mb-2"></textarea>
          <input type="number" name="precio" id="edit_precio" step="0.01" class="form-control mb-2" required>
          <input type="number" name="stock" id="edit_stock" class="form-control mb-2" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarProducto(id, nombre, descripcion, precio, stock) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_nombre').value = nombre;
  document.getElementById('edit_descripcion').value = descripcion;
  document.getElementById('edit_precio').value = precio;
  document.getElementById('edit_stock').value = stock;
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function eliminarProducto(id) {
  if (confirm('¿Seguro que deseas eliminar este producto?')) {
    fetch('productos_borrar.php?id=' + id)
      .then(res => res.text())
      .then(() => location.reload());
  }
}

// Guardar nuevo producto
document.getElementById("formAgregar").addEventListener("submit", function(e) {
  e.preventDefault();
  let formData = new FormData(this);
  fetch("productos_guardar.php", { method: "POST", body: formData })
    .then(r => r.json())
    .then(data => {
      alert(data.message);
      if (data.success) location.reload();
    });
});

// Editar producto
document.getElementById("formEditar").addEventListener("submit", function(e) {
  e.preventDefault();
  let formData = new FormData(this);
  fetch("productos_actualizar.php", { method: "POST", body: formData })
    .then(r => r.json())
    .then(data => {
      alert(data.message);
      if (data.success) location.reload();
    });
});
</script>
</body>
</html>
