<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Costa del Este</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet" />

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    body {
      font-family: 'Fredoka', sans-serif;
      background: linear-gradient(135deg, #d7eaea, #e9f5f5);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      width: 100%;
      max-width: 400px;
      border-radius: 1rem;
      border: none;
      background: #fff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      padding: 2rem;
    }

    .login-title {
      font-weight: 600;
      text-align: center;
      margin-bottom: 1.5rem;
      color: #1f3c3c;
    }

    .input-group-text {
      background-color: #f8f9fa;
      border-right: 0;
    }

    .form-control {
      border-left: 0;
    }

    .btn-custom {
      background: #1f7a7a;
      color: #fff;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
    }

    .btn-custom:hover {
      background: #145757;
      transform: translateY(-1px);
    }

    .small-text {
      font-size: 0.85rem;
      color: #6c757d;
      text-align: center;
      margin-top: 1rem;
    }
  </style>
</head>

<body>
  <!-- Contenedor del Login -->
  <div class="login-card">
    <h2 class="login-title"><i class="fa-solid fa-user-lock me-2"></i>Iniciar Sesión</h2>
    <form action="login.php" method="POST">
      <div class="mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
          <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingrese su nombre" required>
        </div>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password" placeholder="Ingrese su contraseña" required>
        </div>
      </div>

      <div class="d-grid mb-2">
        <button type="submit" class="btn btn-custom">Ingresar</button>
      </div>
    </form>
    <div class="small-text">
      <a href="#">¿Olvidaste tu contraseña?</a>
    </div>
  </div>

  <!-- Scripts Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
