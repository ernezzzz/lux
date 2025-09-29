<?php
session_start();
include("../backend/conexion.php");

// Validar sesión
if (!isset($_SESSION['id_usuario'])) {
    die("<div class='alert alert-danger'>⚠️ Debes iniciar sesión</div>");
}

// Validar que venga el producto
$id_producto = $_GET['id'] ?? null;
if (!$id_producto) {
    die("<div class='alert alert-danger'>❌ Producto no especificado.</div>");
}

// Obtener datos del producto
$stmt = $conn->prepare("SELECT p.*, n.nombre AS negocio 
                        FROM productos p 
                        LEFT JOIN negocios n ON p.id_negocio = n.id_negocio
                        WHERE p.id_producto = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();

if (!$producto) {
    die("<div class='alert alert-warning'>⚠️ Producto no encontrado.</div>");
}

// Obtener imágenes
$imagenes = $conn->query("SELECT * FROM productos_imagenes WHERE id_producto = $id_producto");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($producto['nombre']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .main-img { width:100%; height:400px; object-fit:cover; border-radius:10px; }
    .thumb { cursor:pointer; height:100px; object-fit:cover; border-radius:5px; }
  </style>
</head>
<body class="bg-light">
<div class="container mt-4">
  
  <a href="productos_listar.php" class="btn btn-secondary mb-3">⬅ Volver a productos</a>

  <div class="row">

    <div id="carouselProducto" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <?php 
    $imagenes = $conn->query("SELECT * FROM productos_imagenes WHERE id_producto = $id_producto");
    $active = "active";
    if ($imagenes->num_rows > 0) {
      while($img = $imagenes->fetch_assoc()) { ?>
        <div class="carousel-item <?= $active ?>">
          <img src="<?= $img['ruta'] ?>" class="d-block w-100" style="height:400px; object-fit:cover;">
        </div>
        <?php $active = ""; ?>
      <?php }
    } else { ?>
      <div class="carousel-item active">
        <img src="https://via.placeholder.com/600x400" class="d-block w-100">
      </div>
    <?php } ?>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselProducto" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselProducto" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>


    <!-- Info del producto -->
    <div class="col-md-6">
      <h2><?= htmlspecialchars($producto['nombre']) ?></h2>
      <p class="text-muted"><?= htmlspecialchars($producto['descripcion']) ?></p>
      <h4 class="text-success">$<?= number_format($producto['precio'],2) ?></h4>
      <p><b>Stock:</b> <?= $producto['stock'] ?></p>
      <p><b>Categoría:</b> <?= htmlspecialchars($producto['categoria']) ?></p>
      <p><b>Negocio:</b> <?= htmlspecialchars($producto['negocio']) ?></p>

      <button class="btn btn-primary">🛒 Agregar al carrito</button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
