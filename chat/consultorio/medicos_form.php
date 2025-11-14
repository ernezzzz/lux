<?php
// medicos_form.php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$med = ['id_medico'=>'','nombre'=>'','apellido'=>'','especialidad'=>''];
if ($id) {
  $q = mysqli_query($conn, "SELECT * FROM medicos WHERE id_medico=$id LIMIT 1");
  if ($q && mysqli_num_rows($q)) $med = mysqli_fetch_assoc($q);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $id ? 'Editar' : 'Nuevo' ?> Médico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
  <h2><?= $id ? 'Editar' : 'Nuevo' ?> Médico</h2>
  <form id="medicoForm" class="mt-3" method="post" action="#">
    <input type="hidden" name="id_medico" value="<?= htmlspecialchars($med['id_medico']) ?>">
    <div class="mb-3">
      <label class="form-label">Nombre</label>
      <input name="nombre" id="nombre" class="form-control" required value="<?= htmlspecialchars($med['nombre']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Apellido</label>
      <input name="apellido" id="apellido" class="form-control" value="<?= htmlspecialchars($med['apellido']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Especialidad</label>
      <input name="especialidad" id="especialidad" class="form-control" value="<?= htmlspecialchars($med['especialidad']) ?>">
    </div>
    <div id="medicoAlert" class="alert d-none" role="alert"></div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit" id="btnGuardar">Guardar</button>
      <a href="medicos_listar.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</main>
<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('medicoForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('btnGuardar');
  btn.disabled = true;
  const alertEl = document.getElementById('medicoAlert');
  alertEl.classList.add('d-none'); alertEl.classList.remove('alert-danger','alert-success');
  const f = new FormData(this);

  try {
    const res = await fetch('medicos_guardar_ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: f
    });
    // debug: if response not json, show raw text
    const text = await res.text();
    try {
      const js = JSON.parse(text);
      if (js.success) {
        // Reemplaza historial y vuelve a la lista
        location.replace('medicos_listar.php');
      } else {
        alertEl.classList.remove('d-none'); alertEl.classList.add('alert-danger'); alertEl.innerText = js.error || 'Error al guardar.';
        btn.disabled = false;
      }
    } catch(parseErr) {
      // respuesta no JSON
      alertEl.classList.remove('d-none'); alertEl.classList.add('alert-danger');
      alertEl.innerText = 'Respuesta inválida del servidor. Revisá Network (DevTools).';
      console.error('Respuesta bruta medicos_guardar_ajax.php:', text);
      btn.disabled = false;
    }
  } catch(err) {
    alertEl.classList.remove('d-none'); alertEl.classList.add('alert-danger'); alertEl.innerText = 'Error de red: ' + err;
    btn.disabled = false;
  }
});
</script>
</body>
</html>
