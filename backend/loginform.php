<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Costa del Este</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet" />

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body style="font-family: 'Fredoka', sans-serif; background-color: #e1ebeb;">

  <!-- Contenedor del Login -->
  <div class="container d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px; border-radius: 1rem;">
      <h2 class="text-center mb-4" id="h2_servicios">Iniciar Sesión</h2>
      <form action="login.php" method="POST">
        <div class="mb-3">
          <label for="usuario" class="form-label label_servicios">Nombre</label>
          <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label label_servicios">Contraseña</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-success">Ingresar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>