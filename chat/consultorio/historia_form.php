<?php
// historia_form.php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hist = ['id_historia'=>'','id_paciente'=>'','fecha'=>date('Y-m-d'),'descripcion'=>''];
if ($id) {
  $q = mysqli_query($conn, "SELECT * FROM historia_clinica WHERE id_historia=$id LIMIT 1");
  if ($q && mysqli_num_rows($q)) $hist = mysqli_fetch_assoc($q);
}

// Lista pacientes
$pacientes = mysqli_query($conn, "SELECT id_usuario, nombre, apellido FROM usuarios WHERE id_rol = (SELECT id_rol FROM rol WHERE rol = 'paciente' LIMIT 1) ORDER BY nombre");
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id ? 'Editar' : 'Nueva' ?> Historia Clínica</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<main class="container py-4">
  <h2><?= $id ? 'Editar' : 'Nueva' ?> Historia Clínica</h2>

  <form id="historiaForm" class="mt-3" method="post" action="#">
    <input type="hidden" name="id_historia" value="<?= htmlspecialchars($hist['id_historia']) ?>">
    <div class="mb-3">
      <label class="form-label">Paciente</label>
      <select name="id_paciente" id="id_paciente" class="form-select" required>
        <option value="">-- Seleccione paciente --</option>
        <?php while($p = mysqli_fetch_assoc($pacientes)): ?>
          <option value="<?= (int)$p['id_usuario'] ?>" <?= ($p['id_usuario']==$hist['id_paciente']) ? 'selected' : '' ?>><?= htmlspecialchars(trim($p['nombre'].' '.$p['apellido'])) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Fecha</label>
      <input type="date" name="fecha" id="fecha" class="form-control" value="<?= htmlspecialchars($hist['fecha']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Descripción / Antecedentes</label>
      <textarea name="descripcion" id="descripcion" class="form-control" rows="6"><?= htmlspecialchars($hist['descripcion']) ?></textarea>
    </div>

    <div id="historiaAlert" class="alert d-none" role="alert"></div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit" id="btnGuardarHist">Guardar</button>
      <a href="historia_listar.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</main>
<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('historiaForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('btnGuardarHist');
  btn.disabled = true;
  const alertEl = document.getElementById('historiaAlert');
  alertEl.classList.add('d-none'); alertEl.classList.remove('alert-danger','alert-success');

  const f = new FormData(this);
  try {
    const res = await fetch('historia_guardar_ajax.php', { method:'POST', credentials:'same-origin', body: f });
    const text = await res.text();
    try {
      const js = JSON.parse(text);
      if (js.success) {
        location.replace('historia_listar.php');
      } else {
        alertEl.classList.remove('d-none'); alertEl.classList.add('alert-danger'); alertEl.innerText = js.error || 'Error al guardar';
        btn.disabled = false;
      }
    } catch (parseErr) {
      alertEl.classList.remove('d-none'); alertEl.classList.add('alert-danger');
      alertEl.innerText = 'Respuesta inválida del servidor. Revisá Network (DevTools).';
      console.error('Respuesta bruta historia_guardar_ajax.php:', text);
      btn.disabled = false;
    }
  } catch (err) {
    alertEl.classList.remove('d-none'); alertEl.classList.add('alert-danger'); alertEl.innerText = 'Error de red: ' + err;
    btn.disabled = false;
  }
});
</script>
</body>
</html>

