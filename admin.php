<?php
include 'backend/checklogin.php'; // protege la página
include 'backend/header.php';     // muestra la barra superior
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Administrador - Grupo Lux</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    /* ======== ESTILOS GLOBALES ======== */
    html, body {
      height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
      color: #0f172a;
    }

    main {
      flex: 1; /* Empuja el footer hacia abajo */
    }

    /* ======== NAVBAR ======== */
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

    /* ======== ICONOS ======== */
    .icons {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2rem;
      padding: 4rem 2rem;
      background: linear-gradient(to right, #f1f5f9, #ffffff);
    }

    .icon-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #fff;
  border-radius: 16px;
  padding: 2rem;
  width: 220px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  cursor: pointer;
  text-decoration: none;  /* quita subrayado */
  color: inherit;         /* hereda color */
}

.icon-btn:hover {
  background-color: #10b981;
  color: #fff;
  transform: translateY(-5px);
  box-shadow: 0 8px 18px rgba(16,185,129,0.3);
}

.icon-btn i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.icon-btn p {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
  word-wrap: break-word;     /* evita desbordes */
  text-align: center;
}


    .icon-btn a {
      text-decoration: none;
      color: inherit;
    }

    /* ======== FOOTER ======== */
    footer {
      background: #1e293b;
      color: #fff;
      text-align: center;
      padding: 1.5rem 0;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
      margin-top: auto;
    }

    /* ======== RESPONSIVE ======== */
    @media (max-width: 768px) {
      .icon-btn {
        width: 160px;
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-dark px-4">
    <a class="navbar-brand text-white" href="#">GRUPO LUX</a>
    <div class="ms-auto">
      <a href="index.php" class="btn btn-outline-light px-3">Cerrar Sesión</a>
    </div>
  </nav>

  <!-- CONTENIDO PRINCIPAL -->
  <main>
  <section class="icons">
  <a href="backend/loginform.php" class="icon-btn">
    <i>➕</i>
    <p>FARMACIA</p>
  </a>
  <a href="libreria/dashboard.php" class="icon-btn">
    <i>📖</i>
    <p>LIBRERÍA Y PAPELERÍA</p>
  </a>
  <a href="backend/loginform.php" class="icon-btn">
    <i>💻</i>
    <p>ELECTRODOMÉSTICOS Y TECNOLOGÍA</p>
  </a>
  <a href="backend/loginform.php" class="icon-btn">
    <i>🏥</i>
    <p>CONSULTORIO</p>
  </a>
</section>

  </main>

  <!-- FOOTER -->
  <footer>
    © <?php echo date("Y"); ?> Grupo Lux — Todos los derechos reservados.
  </footer>

</body>
</html>
