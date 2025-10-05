<?php
include '../backend/checklogin.php'; // protege la página
include '../backend/header.php';     // muestra la barra superior
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Librería - Grupo Lux</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    /* ======== Estructura global ======== */
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
      flex: 1; /* Empuja el footer hacia abajo */
    }

    /* ======== Navbar ======== */
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

    /* ======== Títulos ======== */
    .dashboard-title {
      font-weight: 700;
      color: #0f172a;
      margin-top: 3rem;
      margin-bottom: 3rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      border-bottom: 3px solid #10b981;
      display: inline-block;
      padding-bottom: 0.5rem;
    }

    /* ======== Categorías ======== */
    .btn-category {
      background: #ffffff;
      border: none;
      border-radius: 16px;
      padding: 2rem 1rem;
      font-size: 1.1rem;
      font-weight: 600;
      text-transform: uppercase;
      color: #0f172a;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      transition: all 0.3s ease;
      display: flex;
      justify-content: center;
      align-items: center;
      text-decoration: none;
    }

    .btn-category:hover {
      background: #10b981;
      color: #fff;
      transform: translateY(-4px);
      box-shadow: 0 8px 18px rgba(16,185,129,0.3);
    }

    /* ======== Contenedor ======== */
    .container {
      max-width: 1100px;
    }

    /* ======== Footer ======== */
    footer {
      text-align: center;
      padding: 1.5rem 0;
      background: #1e293b;
      color: #fff;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
      margin-top: auto; /* asegura que quede abajo */
    }
  </style>
</head>

<body>
  <!-- Header -->
  <nav class="navbar navbar-dark px-4">
    <a class="navbar-brand text-white" href="#">GRUPO LUX</a>
    <div class="ms-auto">
      <a href="../admin.php" class="btn btn-outline-light me-2 px-3">Volver</a>
    </div>
  </nav>

  <!-- Contenido principal -->
  <main>
    <div class="container text-center py-5">
      <!-- Sección productos -->
      <h1 class="dashboard-title">Productos</h1>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4 mb-5">
        <div class="col">
          <a href="productos_categoria.php?cat=Artística" class="btn-category">Artística</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Comercial" class="btn-category">Comercial</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Escolar" class="btn-category">Escolar</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Regaleria" class="btn-category">Regalería</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Tecnica" class="btn-category">Técnica</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Facultad" class="btn-category">Facultad</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Papeles" class="btn-category">Papeles</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Paquetes" class="btn-category">Paquetes</a>
        </div>
        <div class="col">
          <a href="productos_categoria.php?cat=Exclusivo" class="btn-category">Exclusivo</a>
        </div>
      </div>

      <!-- Sección administración -->
      <h1 class="dashboard-title">Administración</h1>
      <div class="row justify-content-center g-4 mt-4">
        <div class="col-md-4">
          <a href="ventas/ventas_listar.php" class="btn-category">Visualizar Ventas</a>
        </div>
        <div class="col-md-4">
          <a href="../productos_abm/productos_listar.php" class="btn-category">Inventario Completo</a>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    © <?php echo date("Y"); ?> Grupo Lux — Todos los derechos reservados.
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
