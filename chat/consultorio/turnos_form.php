<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$turno = ['id_turno'=>'','id_paciente'=>'','id_medico'=>'','fecha'=>'','hora'=>'','estado'=>'pendiente'];

if ($id) {
  $q = mysqli_query($conn, "SELECT * FROM turnos WHERE id_turno = $id LIMIT 1");
  if ($q && mysqli_num_rows($q)) $turno = mysqli_fetch_assoc($q);
}

// Medicos
$meds = mysqli_query($conn, "SELECT id_medico, nombre, apellido, especialidad FROM medicos ORDER BY nombre, apellido");

// Pacientes (rol = 'paciente') - ahora traemos email
$pacientes = mysqli_query($conn, "SELECT id_usuario, nombre, apellido, email FROM usuarios WHERE id_rol = (SELECT id_rol FROM rol WHERE rol = 'paciente' LIMIT 1) ORDER BY nombre, apellido");

// preselección si viene id_paciente por GET (nota: parámetro se llama paciente_id)
$preselect_patient = isset($_GET['paciente_id']) ? intval($_GET['paciente_id']) : ($turno['id_paciente'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $id ? 'Editar' : 'Nuevo' ?> Turno</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
  <h2><?= $id ? 'Editar' : 'Nuevo' ?> Turno</h2>

  <?php
  $fecha_hora_val = '';
  if (!empty($turno['fecha']) && !empty($turno['hora'])) {
    $hora_dt = strtotime($turno['hora']);
    if ($hora_dt) {
      $fecha_hora_val = date('Y-m-d\TH:i', $hora_dt);
    } elseif ($turno['fecha']) {
      $fecha_hora_val = date('Y-m-d\TH:i', strtotime($turno['fecha']));
    }
  }
  ?>

  <form action="#" method="post" class="mt-3" id="turnoForm">
    <input type="hidden" name="id_turno" value="<?= htmlspecialchars($turno['id_turno']) ?>">

    <div class="mb-3">
      <label class="form-label">Paciente</label>
      <div class="input-group">
        <select name="id_paciente" class="form-select" id="selectPaciente" required>
          <option value="">-- Seleccione paciente --</option>
          <?php mysqli_data_seek($pacientes, 0); while($p = mysqli_fetch_assoc($pacientes)):
            $id_usr = (int)$p['id_usuario'];
            $nombre_full = htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')));
            $email = isset($p['email']) && $p['email'] ? ' — ' . htmlspecialchars($p['email']) : '';
          ?>
            <option value="<?= $id_usr ?>" <?= ($id_usr==($preselect_patient ?: $turno['id_paciente'])) ? 'selected' : '' ?>><?= $nombre_full . $email ?></option>
          <?php endwhile; ?>
        </select>
        <button type="button" class="btn btn-outline-primary" id="btnAddPaciente" data-bs-toggle="modal" data-bs-target="#modalNuevoPaciente">
          Agregar paciente
        </button>
      </div>
      <div class="form-text">Si el paciente no está en la lista, podés agregarlo aquí mismo.</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Médico</label>
      <select name="id_medico" class="form-select" required>
        <option value="">-- Seleccione médico --</option>
        <?php mysqli_data_seek($meds, 0); while($m = mysqli_fetch_assoc($meds)): ?>
          <option value="<?= $m['id_medico'] ?>" <?= ($m['id_medico']==$turno['id_medico']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre'].' '.$m['apellido'].' ('.$m['especialidad'].')') ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Fecha y hora</label>
      <input type="datetime-local" name="fecha_hora" class="form-control" value="<?= $fecha_hora_val ?>" required>
      <div class="form-text">Seleccione fecha y hora (local).</div>
    </div>

    <div class="mb-3">
      <label class="form-label">Estado</label>
      <select name="estado" class="form-select">
        <?php foreach(['pendiente','confirmado','cancelado','finalizado'] as $e): ?>
          <option value="<?= $e ?>" <?= ($turno['estado']==$e) ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit" id="btnGuardarTurno">Guardar</button>
      <a href="turnos_listar.php" class="btn btn-secondary" id="btnCancelar">Cancelar</a>
    </div>
  </form>
</main>

<!-- Modal Bootstrap: Nuevo Paciente (actualizado con email) -->
<div class="modal fade" id="modalNuevoPaciente" tabindex="-1" aria-labelledby="modalNuevoPacienteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formNuevoPaciente">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevoPacienteLabel">Agregar paciente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div id="nuevoPacienteAlert" class="alert d-none" role="alert"></div>

          <div class="mb-3">
            <label for="pacienteNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="pacienteNombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="pacienteApellido" class="form-label">Apellido</label>
            <input type="text" class="form-control" id="pacienteApellido" name="apellido" required>
          </div>
          <div class="mb-3">
            <label for="pacienteEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="pacienteEmail" name="email" placeholder="ejemplo@dominio.com">
            <div class="form-text">Opcional pero recomendado.</div>
          </div>

          <!-- Campos ocultos: id_rol y id_negocio se envían desde el servidor -->
          <input type="hidden" name="id_rol" value="6">
          <input type="hidden" name="id_negocio" value="1">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarPacienteModal">Guardar paciente</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ---------- Broadcast / Fallback (localStorage) ----------
const bcSupported = typeof BroadcastChannel !== 'undefined';
const channelName = 'lux_pacientes';
let bc = null;
if (bcSupported) {
  bc = new BroadcastChannel(channelName);
  bc.onmessage = (ev) => {
    try { handlePacienteAdded(ev.data); } catch(e){ console.error(e); }
  };
} else {
  // fallback: listen storage events
  window.addEventListener('storage', function(e){
    if (e.key === channelName && e.newValue) {
      try { handlePacienteAdded(JSON.parse(e.newValue)); } catch(err){ console.error(err); }
      // cleanup (optional)
      localStorage.removeItem(channelName);
    }
  });
}

// helper to broadcast a patient object { id, nombre_completo, email }
function broadcastPaciente(pac) {
  try {
    if (bcSupported && bc) {
      bc.postMessage(pac);
    } else {
      // fallback: write to localStorage (triggers storage event in other tabs)
      localStorage.setItem(channelName, JSON.stringify(pac));
      // Opcional: remove quickly to avoid buildup (other tabs will read it then)
      setTimeout(()=> localStorage.removeItem(channelName), 500);
    }
  } catch(e){ console.error('Error broadcasting paciente', e); }
}

// Handle message: update selectPaciente if exists
function handlePacienteAdded(data) {
  if (!data || !data.id) return;
  const sel = document.getElementById('selectPaciente');
  if (!sel) return;
  // if option already exists, do nothing
  if (sel.querySelector('option[value="'+data.id+'"]')) return;
  const opt = document.createElement('option');
  opt.value = data.id;
  opt.text = data.nombre_completo + (data.email ? ' — ' + data.email : '');
  // Insert at end; you can sort if you prefer
  sel.appendChild(opt);
  // optionally select it if this tab was the origin? We leave selection to originator
}

// ---------- Utilidades ----------
function showAlert(el, type, msg){
  el.classList.remove('d-none','alert-success','alert-danger','alert-info');
  el.classList.add('alert-'+type);
  el.innerText = msg;
}

// ---------- Modal: crear paciente (AJAX) - versión robusta ----------
document.getElementById('formNuevoPaciente').addEventListener('submit', function(e){
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('btnGuardarPacienteModal');
  const alertEl = document.getElementById('nuevoPacienteAlert');
  alertEl.classList.add('d-none');
  btn.disabled = true;

  const formData = new FormData(form);

  // helper: cerrar modal y limpiar backdrops/cuerpo si algo quedó pegado
  function closeModalAndCleanup() {
    try {
      const modalEl = document.getElementById('modalNuevoPaciente');
      // hide instance if exists
      const inst = bootstrap.Modal.getInstance(modalEl);
      if (inst) {
        inst.hide();
      } else {
        // create a temporary instance and hide (safe)
        try { (new bootstrap.Modal(modalEl)).hide(); } catch(e2){ /* ignore */ }
      }
    } catch (err) {
      console.warn('Error al intentar ocultar modal (no crítico):', err);
    }
    // forzar limpieza de backdrop y clase modal-open por si Bootstrap falló
    document.querySelectorAll('.modal-backdrop').forEach(n => n.remove());
    document.body.classList.remove('modal-open');
  }

  fetch('paciente_guardar_ajax.php', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
  })
  .then(async r => {
    const text = await r.text();
    // intentar parsear JSON; si falla, lanzar con texto bruto para debug
    try {
      return JSON.parse(text);
    } catch (errParse) {
      throw new Error('Respuesta inválida del servidor. Mirá DevTools → Network (paciente_guardar_ajax.php). Respuesta: ' + text.slice(0,500));
    }
  })
  .then(js => {
    btn.disabled = false;
    if (js.success) {
      // broadcast para otras pestañas
      try {
        const pacObj = { id: js.id, nombre_completo: js.nombre_completo, email: js.email || '' };
        if (typeof BroadcastChannel !== 'undefined') {
          const bc2 = new BroadcastChannel('lux_pacientes');
          bc2.postMessage(pacObj);
          bc2.close();
        } else {
          localStorage.setItem('lux_pacientes', JSON.stringify(pacObj));
          setTimeout(()=> localStorage.removeItem('lux_pacientes'), 600);
        }
      } catch(bcErr){ console.warn('No se pudo broadcast:', bcErr); }

      // insertar opción en el select del origen y seleccionarla
      const sel = document.getElementById('selectPaciente');
      if (sel) {
        // si no existe option, crearla; si existe, seleccionarla
        let opt = sel.querySelector('option[value="'+js.id+'"]');
        const label = js.nombre_completo + (js.email ? ' — ' + js.email : '');
        if (!opt) {
          opt = document.createElement('option');
          opt.value = js.id;
          opt.text = label;
          sel.appendChild(opt);
        } else {
          opt.text = label; // actualizar texto por si
        }
        sel.value = js.id;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }

      // Si hay warning lo mostramos un momento antes de cerrar; si no, cerramos ya
      if (js.warning) {
        showAlert(alertEl, 'info', js.warning);
        // cerramos luego de mostrar un ratito y limpiamos form
        setTimeout(()=> {
          closeModalAndCleanup();
          form.reset();
          alertEl.classList.add('d-none');
        }, 1400);
      } else {
        closeModalAndCleanup();
        form.reset();
      }
    } else {
      // error enviado por servidor -> mostrar en alerta dentro del modal
      showAlert(alertEl, 'danger', js.error || 'Error al crear paciente');
    }
  })
  .catch(err => {
    btn.disabled = false;
    // Mostrar error y además intentar limpiar backdrop si la UI quedó pegada
    showAlert(alertEl, 'danger', 'Error: ' + (err.message || err));
    console.error('Error en paciente_guardar_ajax:', err);
    // En casos raros donde un error impidió que bootstrap limpie el backdrop, quitamos manualmente
    setTimeout(() => {
      // si aún hay .modal-backdrop y el modal no está visible, limpiamos
      const anyBackdrop = document.querySelector('.modal-backdrop');
      const modalEl = document.getElementById('modalNuevoPaciente');
      const isHidden = modalEl && window.getComputedStyle(modalEl).display === 'none';
      if (anyBackdrop && isHidden) {
        document.querySelectorAll('.modal-backdrop').forEach(n => n.remove());
        document.body.classList.remove('modal-open');
      }
    }, 300);
  });
});

// ---------- datetime-local: evitar fechas pasadas (cliente) ----------
(function setMinDatetime() {
  const input = document.querySelector('input[name="fecha_hora"]');
  if (!input) return;
  // calcular ahora en formato local compatible: yyyy-mm-ddThh:mm
  const now = new Date();
  // round down seconds
  now.setSeconds(0,0);
  const pad = (n)=> String(n).padStart(2,'0');
  const minstr = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
  input.min = minstr;
})();

// Antes de enviar el formulario validar que la fecha no sea pasada
document.getElementById('turnoForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = this;
  const btnGuardar = document.getElementById('btnGuardarTurno');
  btnGuardar.disabled = true;

  try {
    const fechaEl = form.querySelector('input[name="fecha_hora"]');
    if (!fechaEl) throw new Error('Campo fecha/hora no encontrado.');
    const val = fechaEl.value;
    if (!val) throw new Error('Seleccioná fecha y hora.');
    const selTs = Date.parse(val);
    if (isNaN(selTs)) throw new Error('Formato fecha inválido.');
    const now = Date.now();
    if (selTs < now - 30000) { // tolerancia 30s
      alert('No podés seleccionar una fecha/hora pasada. Elegí una fecha actual o futura.');
      btnGuardar.disabled = false;
      return;
    }

    // preparar FormData y enviar
    const f = new FormData(form);
    const res = await fetch('turnos_guardar_ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: f
    });

    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(err) {
      console.error('Respuesta bruta turnos_guardar_ajax.php:', text);
      alert('Respuesta inválida del servidor. Mirá DevTools → Network para más detalles.');
      btnGuardar.disabled = false;
      return;
    }

    if (js.success) {
      // sustituir historial con la lista (evita volver al form)
      location.replace('turnos_listar.php');
    } else {
      alert(js.error || 'Error al guardar turno');
      btnGuardar.disabled = false;
    }
  } catch(err) {
    alert('Error: ' + (err.message || err));
    btnGuardar.disabled = false;
    console.error(err);
  }
});
</script>

</body>
</html>


