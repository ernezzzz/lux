<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administración</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css"> <!-- Asegurate que esta ruta sea correcta -->

  <style>
    body#body_admin {
      font-family: 'Poppins';
      background: #e1ebeb;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem;
    }

    h1#titulo_admin {
      color: #032A2B;
      font-weight: 600;
      margin-bottom: 2rem;
      animation: aparecer 1s ease-out both;
    }

    .btn-admin {
      font-size: 1.2rem;
      padding: 1rem 2rem;
      margin: 1rem;
      border-radius: 0.75rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-admin:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    @keyframes aparecer {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body id="body_admin">

  <h1 id="titulo_admin" class="text-center">Administración</h1>

  <div class="d-flex flex-column align-items-center">
    <a href="../index.php" class="btn btn-outline-success btn-admin">Librería y Papelería</a>
    <a href="../index.php" class="btn btn-outline-success btn-admin">Farmacia</a>
    <a href="../index.php" class="btn btn-outline-success btn-admin">Electrodomésticos y Tecnología</a>
    <a href="../index.php" class="btn btn-outline-success btn-admin">Consultorio Médico</a>
    <a href="../index.php" class="btn btn-success btn-admin">Pagina principal</a>
  </div>

</body>
</html>