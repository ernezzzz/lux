<?php
include '../backend/checklogin.php';
include '../backend/header.php';
include("../backend/conexion.php");

$categoria = $_GET['cat'] ?? '';

if (!$categoria) {
    die("<div class='alert alert-warning'>⚠️ No se especificó categoría</div>");
}

// Supongamos que el rol está en $_SESSION['id_rol']
$rol = $_SESSION['id_rol'] ?? null;

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
      color: #333;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* Navbar estilo LUX */
    .navbar {
      background: linear-gradient(90deg, #1e293b, #334155);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      padding: 0.75rem 2rem;
      min-height: 64px;
    }
    .navbar-brand {
      font-weight: 700;
      font-size: 1.4rem;
      letter-spacing: 1px;
      color: #fff !important;
    }
    .navbar .btn {
      border-radius: 10px;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 0.4rem 1rem;
      font-size: 0.95rem;
    }
    .navbar .btn:hover {
      transform: translateY(-2px);
      background-color: #10b981;
      color: #fff;
    }

    .content {
      flex: 1;
    }

    .btn-group .btn {
      margin: 0 5px;
    }

    h1 {
      font-weight: 700;
      color: #1e293b;
    }

    table th, table td {
      vertical-align: middle !important;
    }

    footer {
      background-color: #1e293b;
      color: white;
      text-align: center;
      padding: 15px;
      margin-top: auto;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-dark">
    <a class="navbar-brand" href="#">GRUPO LUX</a>
  </nav>

  <!-- Contenido -->
  <div class="container mt-5 content">
    <h1 class="text-center mb-4">🛒 Productos - <?= htmlspecialchars($categoria) ?></h1>

    <div class="text-center mb-4 btn-group">
      <a href="dashboard.php" class="btn btn-secondary">⬅ Volver</a>
      <?php if ($rol != 4) { ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoProducto">
          ➕ Nuevo Producto
        </button>
        <a href="../productos_abm/productos_listar.php" class="btn btn-primary">📋 Inventario Completo</a>
      <?php } ?>
    </div>

    <div id="mensajeExito"></div>

    <div id="tabla-productos">
      <?php if ($result->num_rows > 0) { ?>
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
              <tr>
                <th>ID</th>
                <th>Negocio</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Stock</th>
                <?php if ($rol != 4) { ?><th>Acciones</th><?php } ?>
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
                <?php if ($rol != 4) { ?>
                <td class="text-center">
                  <a href="../productos_abm/productos_form.php?id=<?= $row['id_producto'] ?>" 
                     class="btn btn-sm btn-warning">✏ Editar</a>
                  <a href="../productos_abm/productos_borrar.php?id=<?= $row['id_producto'] ?>" 
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('¿Seguro que deseas eliminar este producto?')">🗑 Eliminar</a>
                </td>
                <?php } ?>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } else { ?>
        <div class="alert alert-info text-center">
          ⚠️ No hay productos en la categoría <strong><?= htmlspecialchars($categoria) ?></strong>.
        </div>
      <?php } ?>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    &copy; <?= date("Y") ?> Grupo Lux - Todos los derechos reservados
  </footer>

  <!-- Modal para nuevo producto -->
  <div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-labelledby="modalNuevoProductoLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formNuevoProducto" method="post" action="../productos_abm/productos_guardar.php" enctype="multipart/form-data" class="form-producto" style="box-shadow:none;max-width:100%;padding:1.5rem;">
          <div class="modal-header">
            <h5 class="modal-title" id="modalNuevoProductoLabel">Agregar Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <label>Negocio:</label>
            <select name="id_negocio" required class="form-select mb-2">
              <option value="">Seleccione...</option>
              <?php
              $negocios = $conn->query("SELECT * FROM negocios");
              while($n = $negocios->fetch_assoc()) { ?>
                <option value="<?= $n['id_negocio'] ?>"><?= $n['nombre'] ?></option>
              <?php } ?>
            </select>

            <label>Nombre:</label>
            <input type="text" name="nombre" class="form-control mb-2" required>

            <label>Descripción:</label>
            <textarea name="descripcion" class="form-control mb-2"></textarea>

            <label>Precio:</label>
            <input type="text" name="precio" class="form-control mb-2" required>

            <label>Stock:</label>
            <input type="number" name="stock" class="form-control mb-2" required>

            <label>Categoría:</label>
            <input type="text" name="categoria" class="form-control mb-2">

            <label>Imágenes:</label>
            <input type="file" name="imagenes[]" multiple class="form-control mb-2">
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
document.getElementById('formNuevoProducto').addEventListener('submit', function(e) {
  e.preventDefault();
  var form = this;
  var data = new FormData(form);

  fetch(form.action, {
    method: 'POST',
    body: data
  })
  .then(response => response.json())
  .then(res => {
    if (res.success) {
      // Cierra el modal
      var modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoProducto'));
      modal.hide();
      // Muestra mensaje de éxito
      document.getElementById('mensajeExito').innerHTML = '<div class="alert alert-success text-center mt-3">✅ Producto guardado correctamente.</div>';
      // Limpia el formulario
      form.reset();
      // Opcional: recarga la tabla de productos (requiere AJAX extra)
      setTimeout(() => { document.getElementById('mensajeExito').innerHTML = ''; }, 3000);
    } else {
      alert(res.error || 'Ocurrió un error al guardar el producto.');
    }
  })
  .catch(() => {
    alert('Ocurrió un error al guardar el producto.');
  });
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
