
<?php
include '../backend/checklogin.php'; // protege la página
include '../backend/header.php';     // muestra la barra superior

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/styles.css" />
  <title>Dashboard Librería</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #fff;
      font-family: 'Montserrat', sans-serif;
    }
    .btn-category {
      background: #eae6df;
      border: none;
      border-radius: 20px;
      padding: 15px 40px;
      font-size: 18px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .btn-category:hover {
      background: #d3cec7;
      transform: scale(1.05);
    }
  </style>
</head>
<body>

<!-- Header -->
<nav class="navbar navbar-dark bg-dark px-3">
  <a class="navbar-brand fw-bold text-white" href="#">GRUPO LUX</a>
  <div class="d-flex ms-auto">
    <a href="../index.php" class="btn btn-outline-light me-2">Volver</a>
    <a href="backend/loginform.php" class="btn btn-outline-light">Iniciar Sesión</a>
  </div>
</nav>

<div class="container text-center py-5">
  <h1 class="mb-5 text-danger fw-bold">PRODUCTOS</h1>

  <!-- Categorías 3x3 -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4 mb-5">
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Artística" class="btn-category w-75 py-3">Artística</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Comercial" class="btn-category w-75 py-3">Comercial</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Escolar" class="btn-category w-75 py-3">Escolar</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Regaleria" class="btn-category w-75 py-3">Regalería</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Tecnica" class="btn-category w-75 py-3">Técnica</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Facultad" class="btn-category w-75 py-3">Facultad</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Papeles" class="btn-category w-75 py-3">Papeles</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Paquetes" class="btn-category w-75 py-3">Paquetes</a>
    </div>
    <div class="col d-flex justify-content-center">
      <a href="productos_categoria.php?cat=Exclusivo" class="btn-category w-75 py-3">Exclusivo</a>
    </div>
  </div>

  <!-- Botones inferiores -->
  <div class="row justify-content-center g-4 mt-4">
    <div class="col-md-4 d-flex justify-content-center">
      <a href="ventas/ventas_listar.php" class="btn-category w-100 py-3">VISUALIZAR VENTAS</a>
    </div>
    <div class="col-md-4 d-flex justify-content-center">
      <a href="../productos_abm/productos_listar.php" class="btn-category w-100 py-3">INVENTARIO COMPLETO</a>
    </div>
  </div>
</div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
