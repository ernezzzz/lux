<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

// cargar opciones (medicos) para filtro
$meds_res = mysqli_query($conn, "SELECT id_medico, nombre, apellido FROM medicos ORDER BY nombre, apellido");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Turnos - Consultorio (AJAX JSON)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .filter-row .form-control, .filter-row .form-select { min-width: 160px; }
    .pagination { margin: 0; }
    td.nowrap { white-space: nowrap; }
  </style>
</head>
<body>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Turnos</h2>
    <div>
      <a href="turnos_form.php" id="linkNuevoTurno" class="btn btn-success">Nuevo Turno</a>
      <button id="btnShowDeletedTurnos" class="btn btn-outline-secondary ms-2">Ver registros eliminados</button>
      <a href="dashboard.php" class="btn btn-secondary ms-2">Volver</a>
    </div>
  </div>

  <!-- Controls -->
  <div class="row g-2 filter-row mb-3">
    <div class="col-md-4"><input id="q" class="form-control" placeholder="Buscar por paciente o médico..."></div>

    <div class="col-auto">
      <select id="filter_estado" class="form-select">
        <option value="">Todos los estados</option>
        <option value="pendiente">Pendiente</option>
        <option value="confirmado">Confirmado</option>
        <option value="cancelado">Cancelado</option>
        <option value="finalizado">Finalizado</option>
      </select>
    </div>

    <div class="col-auto">
      <select id="filter_medico" class="form-select">
        <option value="">Todos los médicos</option>
        <?php while($m = mysqli_fetch_assoc($meds_res)):
          $mid = (int)$m['id_medico'];
          $mname = htmlspecialchars(trim($m['nombre'].' '.$m['apellido']));
        ?>
          <option value="<?= $mid ?>"><?= $mname ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-auto">
      <select id="sort_by" class="form-select">
        <option value="fecha_desc">Fecha (más reciente)</option>
        <option value="fecha_asc">Fecha (más antigua)</option>
        <option value="paciente_asc">Paciente A→Z</option>
        <option value="paciente_desc">Paciente Z→A</option>
      </select>
    </div>

    <div class="col-auto">
      <select id="per_page" class="form-select">
        <option value="15" selected>15 / pág</option>
        <option value="30">30 / pág</option>
        <option value="60">60 / pág</option>
      </select>
    </div>

    <div class="col-auto">
      <button id="clearFilters" class="btn btn-outline-secondary">Limpiar</button>
    </div>
  </div>

  <!-- Contenedor resultados -->
  <div id="resultado" class="table-responsive"></div>
</main>

<!-- Modal: Deleted Turnos -->
<div class="modal fade" id="modalDeletedTurnos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Turnos eliminados</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="deletedTurnosAlert" class="alert d-none" role="alert"></div>
        <div id="deletedTurnosContainer">Cargando...</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Helpers */
function debounce(fn, delay){ let t; return function(...args){ clearTimeout(t); t = setTimeout(()=> fn.apply(this,args), delay); }; }
function escapeHtml(s){ if (!s) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }
function showInline(el, type, msg){ el.classList.remove('d-none','alert-success','alert-danger','alert-info'); el.classList.add('alert-'+type); el.innerText = msg; }

/* Elements */
const el = {
  q: document.getElementById('q'),
  estado: document.getElementById('filter_estado'),
  medico: document.getElementById('filter_medico'),
  sort: document.getElementById('sort_by'),
  per: document.getElementById('per_page'),
  resultado: document.getElementById('resultado'),
  clear: document.getElementById('clearFilters'),
  linkNuevo: document.getElementById('linkNuevoTurno'),
  btnShowDeleted: document.getElementById('btnShowDeletedTurnos')
};

let state = { page: 1 };

/* Build POST body as URLSearchParams */
function buildBody(page=1){
  const body = new URLSearchParams();
  if (el.q.value.trim()) body.set('q', el.q.value.trim());
  if (el.estado.value) body.set('estado', el.estado.value);
  if (el.medico.value) body.set('medico', el.medico.value);
  if (el.sort.value) body.set('sort', el.sort.value);
  body.set('per_page', el.per.value || '15');
  body.set('page', page);
  return body.toString();
}

/* Fetch + render */
async function fetchData(page=1){
  state.page = page;
  el.resultado.innerHTML = '<div class="p-4 text-center">Cargando...</div>';
  try {
    const res = await fetch('turnos_api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: buildBody(page)
    });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); }
    catch(e){
      el.resultado.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Abrí DevTools → Network y mirá la respuesta.</div>';
      console.error('Respuesta bruta turnos_api.php:', text);
      return;
    }
    if (!js.success) {
      el.resultado.innerHTML = '<div class="alert alert-danger">Error: ' + (js.error || 'Error servidor') + '</div>';
      return;
    }
    renderTable(js);
  } catch (err) {
    el.resultado.innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
  }
}

/* Render table + pagination */
function renderTable(payload){
  const rows = payload.data || [];
  const total = payload.total || 0;
  const per = payload.per_page || 15;
  const page = payload.page || 1;
  const total_pages = payload.total_pages || 1;

  let html = '<table class="table table-striped"><thead><tr><th>ID</th><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Médico</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
  if (rows.length === 0) {
    html += '<tr><td colspan="7" class="text-center">No se encontraron turnos.</td></tr>';
  } else {
    for (const r of rows) {
      const id = r.id_turno;
      const paciente = escapeHtml(r.paciente_nombre || '—');
      const medico = escapeHtml(r.medico_nombre || '—');
      const fecha = escapeHtml(r.fecha || '');
      const hora = escapeHtml(r.hora || '');
      const estado = escapeHtml(r.estado || '');
      const id_paciente = r.id_paciente ? parseInt(r.id_paciente) : 0;
      html += `<tr>
        <td class="nowrap">${id}</td>
        <td class="nowrap">${fecha}</td>
        <td class="nowrap">${hora}</td>
        <td>${paciente}</td>
        <td>${medico}</td>
        <td>${estado}</td>
        <td class="nowrap">
          <a href="turnos_form.php?id=${id}" class="btn btn-sm btn-primary js-go-form">Editar</a>
          <a href="turnos_borrar.php?id=${id}" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar turno #${id}?')">Borrar</a>
          <a href="historia_listar.php?paciente=${id_paciente}" class="btn btn-sm btn-info">Historia</a>
        </td>
      </tr>`;
    }
  }
  html += '</tbody></table>';

  // footer: info + pagination
  html += '<div class="d-flex justify-content-between align-items-center">';
  html += `<div>Mostrando ${rows.length} de ${total} resultados</div>`;

  const maxButtons = 7;
  let start = Math.max(1, page - Math.floor(maxButtons/2));
  let end = start + maxButtons - 1;
  if (end > total_pages) { end = total_pages; start = Math.max(1, end - maxButtons + 1); }

  html += '<nav aria-label="Paginación"><ul class="pagination mb-0">';
  html += page > 1 ? `<li class="page-item"><a class="page-link" href="#" data-page="${page-1}">«</a></li>` : `<li class="page-item disabled"><span class="page-link">«</span></li>`;

  if (start > 1) { html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`; if (start > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`; }

  for (let p = start; p <= end; p++) { html += `<li class="page-item${p===page?' active':''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`; }

  if (end < total_pages) { if (end < total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`; html += `<li class="page-item"><a class="page-link" href="#" data-page="${total_pages}">${total_pages}</a></li>`; }

  html += page < total_pages ? `<li class="page-item"><a class="page-link" href="#" data-page="${page+1}">»</a></li>` : `<li class="page-item disabled"><span class="page-link">»</span></li>`;
  html += '</ul></nav></div>';

  el.resultado.innerHTML = html;

  // attach page handlers
  document.querySelectorAll('#resultado .page-link[data-page]').forEach(a=>{
    a.addEventListener('click', (ev)=>{
      ev.preventDefault();
      const p = parseInt(a.dataset.page) || 1;
      fetchData(p);
    });
  });

  // attach form links to replace history
  document.querySelectorAll('.js-go-form').forEach(a=>{
    if (a.dataset.attached === '1') return;
    a.dataset.attached = '1';
    a.addEventListener('click', function(ev){
      ev.preventDefault();
      location.replace(this.getAttribute('href'));
    });
  });
}

/* events */
const debFetch = debounce(()=> fetchData(1), 300);
el.q.addEventListener('input', debFetch);
el.estado.addEventListener('change', ()=> fetchData(1));
el.medico.addEventListener('change', ()=> fetchData(1));
el.sort.addEventListener('change', ()=> fetchData(1));
el.per.addEventListener('change', ()=> fetchData(1));
el.clear.addEventListener('click', ()=> { el.q.value=''; el.estado.value=''; el.medico.value=''; el.sort.value='fecha_desc'; el.per.value='15'; fetchData(1); });

// Make sure the top "Nuevo Turno" link replaces history as well
if (el.linkNuevo) {
  el.linkNuevo.addEventListener('click', function(e){ e.preventDefault(); location.replace(this.getAttribute('href')); });
}

/* ---------- Deleted modal logic ---------- */
const modalDeletedTurnosEl = document.getElementById('modalDeletedTurnos');
const modalDeletedTurnos = new bootstrap.Modal(modalDeletedTurnosEl);
const deletedTurnosContainer = document.getElementById('deletedTurnosContainer');
const deletedTurnosAlert = document.getElementById('deletedTurnosAlert');

document.getElementById('btnShowDeletedTurnos').addEventListener('click', async function(){
  deletedTurnosContainer.innerHTML = 'Cargando...';
  deletedTurnosAlert.classList.add('d-none');
  modalDeletedTurnos.show();
  // fetch deleted turnos (activo = 0). Mostramos los últimos 200 por defecto
  try {
    const body = new URLSearchParams();
    body.set('per_page','200');
    body.set('page','1');
    body.set('activo','0'); // parámetro que nuestras APIs deben soportar
    const res = await fetch('turnos_api.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){
      deletedTurnosContainer.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Mirá DevTools → Network.</div>';
      console.error('turnos_api raw:', text);
      return;
    }
    if (!js.success) {
      deletedTurnosContainer.innerHTML = '<div class="alert alert-danger">' + escapeHtml(js.error || 'Error servidor') + '</div>';
      return;
    }
    renderDeletedTurnos(js.data || []);
  } catch(err) {
    deletedTurnosContainer.innerHTML = '<div class="alert alert-danger">Error de red: ' + escapeHtml(err.message || err) + '</div>';
  }
});

function renderDeletedTurnos(rows){
  if (!rows || !rows.length) {
    deletedTurnosContainer.innerHTML = '<div class="p-3">No hay turnos eliminados.</div>';
    return;
  }
  let html = '<table class="table table-sm table-striped"><thead><tr><th>ID</th><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Médico</th><th>Estado</th><th>Acción</th></tr></thead><tbody>';
  for (const r of rows) {
    html += `<tr id="deleted_turno_row_${r.id_turno}">
      <td class="nowrap">${r.id_turno}</td>
      <td class="nowrap">${escapeHtml(r.fecha || '')}</td>
      <td class="nowrap">${escapeHtml(r.hora || '')}</td>
      <td>${escapeHtml(r.paciente_nombre || '')}</td>
      <td>${escapeHtml(r.medico_nombre || '')}</td>
      <td>${escapeHtml(r.estado || '')}</td>
      <td class="nowrap"><button class="btn btn-sm btn-success" data-id="${r.id_turno}" onclick="restoreTurno(this)">Restaurar</button></td>
    </tr>`;
  }
  html += '</tbody></table>';
  deletedTurnosContainer.innerHTML = html;
}

async function restoreTurno(btnOrEl) {
  const id = (btnOrEl.dataset) ? parseInt(btnOrEl.dataset.id) : 0;
  if (!id) return alert('ID inválido');
  const btn = btnOrEl;
  btn.disabled = true;
  try {
    const body = new URLSearchParams(); body.set('id_turno', id);
    const res = await fetch('turnos_restaurar_ajax.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ alert('Respuesta inválida del servidor. Mirá DevTools → Network.'); console.error('turnos_restaurar raw:', text); btn.disabled=false; return; }
    if (res.ok && js.success) {
      // remover fila del modal y refrescar lista principal
      const tr = document.getElementById('deleted_turno_row_' + id);
      if (tr) tr.remove();
      fetchData(state.page);
      showInline(deletedTurnosAlert, 'success', js.message || 'Turno restaurado.');
    } else {
      showInline(deletedTurnosAlert, 'danger', js.error || 'No se pudo restaurar el turno.');
    }
  } catch(err) {
    showInline(deletedTurnosAlert, 'danger', 'Error de red: ' + (err.message || err));
  } finally {
    btn.disabled = false;
  }
}

/* inicial */
fetchData(1);
</script>
</body>
</html>


