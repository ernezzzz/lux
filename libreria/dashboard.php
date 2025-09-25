<?php
// dashboard.php
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
    .dashboard-container {
      text-align: center;
      padding: 50px 20px;
    }
    h1 {
      font-weight: bold;
      margin-bottom: 30px;
      color: red;
    }
    .btn-category {
      background: #eae6df;
      border: none;
      border-radius: 20px;
      padding: 15px 40px;
      margin: 10px;
      font-size: 18px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    .btn-category:hover {
      background: #d3cec7;
      transform: scale(1.05);
    }
    .btn-danger-custom {
      background: #ff3333;
      color: #fff;
      font-weight: bold;
      border-radius: 20px;
      padding: 15px 30px;
      margin: 15px;
      border: none;
      transition: transform 0.2s;
    }
    .btn-danger-custom:hover {
      transform: scale(1.05);
      background: #e60000;
    }
    .btn-success-custom {
      background: #00b300;
      color: #fff;
      font-weight: bold;
      border-radius: 20px;
      padding: 15px 30px;
      margin: 15px;
      border: none;
      transition: transform 0.2s;
    }
    .btn-success-custom:hover {
      transform: scale(1.05);
      background: #008000;
    }
  </style>
</head>
<body>

<!-- Header -->
  <header>
    <h1>GRUPO LUX</h1>
    <button class="btn-login"><a href="../index.php">Volver</button></a>
    <button class="btn-login"><a href="backend/loginform.php">Iniciar Sesión</button></a>
  </header>

  <div class="container-fluid dashboard-container">
    <h1>PRODUCTOS</h1>

    <!-- Categorías -->
    <div class="row justify-content-center">
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Artística')">Artística</button>
      </div>
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Comercial')">Comercial</button>
      </div>
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Escolar')">Escolar</button>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Regalería')">Regalería</button>
      </div>
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Técnica')">Técnica</button>
      </div>
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Facultad')">Facultad</button>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Papeles')">Papeles</button>
      </div>
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Paquetes')">Paquetes</button>
      </div>
      <div class="col-md-3">
        <button class="btn-category w-100" onclick="mostrarCategoria('Exclusivo')">Exclusivo</button>
      </div>
    </div>

    <!-- Botones inferiores -->
    <div class="mt-5">
      <button class="btn-danger-custom" onclick="visualizarVentas()">VISUALIZAR VENTAS</button>
      <button class="btn-success-custom" onclick="inventarioCompleto()">INVENTARIO COMPLETO</button>
    </div>

    <!-- Contenido dinámico -->
    <div id="contenido" class="mt-4"></div>
  </div>

  <script>
    function mostrarCategoria(categoria) {
      document.getElementById("contenido").innerHTML = 
        `<div class="alert alert-info">Cargando productos de <b>${categoria}</b>...</div>`;
      // aquí podrías hacer fetch a un PHP (ej: productos_categoria.php?cat=Artística)
    }

    function mostrarCategoria(categoria) {
  // cargar productos dinámicamente
  fetch("productos_categoria.php?cat=" + encodeURIComponent(categoria))
    .then(res => res.text())
    .then(data => {
      document.getElementById("contenido").innerHTML = data;
    })
    .catch(err => {
      document.getElementById("contenido").innerHTML = "<div class='alert alert-danger'>Error al cargar productos.</div>";
    });
}

    function visualizarVentas() {
      document.getElementById("contenido").innerHTML = 
        `<div class="alert alert-danger">Mostrando las ventas registradas...</div>`;
      // aquí podrías llamar con fetch a ventas.php
    }

    function inventarioCompleto() {
      document.getElementById("contenido").innerHTML = 
        `<div class="alert alert-success">Mostrando inventario completo...</div>`;
      // aquí podrías llamar con fetch a productos_listar.php
    }
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
