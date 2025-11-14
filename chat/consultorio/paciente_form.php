<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nuevo Paciente</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<main class="container py-4">
  <h2>Nuevo Paciente</h2>
  <form action="paciente_guardar.php" method="post" id="formPaciente">
    <div class="mb-3">
      <label class="form-label">Nombre completo</label>
      <input type="text" name="nombre" class="form-control" required>
    </div>
    <!-- Agregar más campos si tu tabla usuarios los requiere (apellido, email, dni, etc.) -->
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <button class="btn btn-secondary" type="button" onclick="window.close()">Cancelar</button>
    </div>
  </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
