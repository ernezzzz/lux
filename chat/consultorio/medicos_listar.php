<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

// cargar especialidades existentes (distintas)
$sp_res = mysqli_query($conn, "SELECT DISTINCT especialidad FROM medicos WHERE COALESCE(especialidad,'') <> '' ORDER BY especialidad");
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Médicos - Consultorio (AJAX)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.pagination{margin:0} td.nowrap{white-space:nowrap}</style>
</head>
<body>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Médicos</h2>
    <div>
      <a href="medicos_form.php" class="btn btn-success" id="linkNuevoMedico">Nuevo Médico</a>
      <button id="btnShowDeletedMedicos" class="btn btn-outline-secondary ms-2">Ver registros eliminados</button>
      <a href="dashboard.php" class="btn btn-secondary ms-2">Volver</a>
    </div>
  </div>

  <!-- alerts -->
  <div id="alertsMed"></div>

  <div class="row g-2 mb-3">
    <div class="col-md-4"><input id="qMed" class="form-control" placeholder="Buscar por nombre o apellido..."></div>
    <div class="col-auto"><select id="filter_esp" class="form-select"><option value="">Todas las especialidades</option><?php while($s = mysqli_fetch_assoc($sp_res)){ echo '<option value="'.htmlspecialchars($s['especialidad']).'">'.htmlspecialchars($s['especialidad']).'</option>'; } ?></select></div>
    <div class="col-auto"><select id="sortMed" class="form-select"><option value="nombre_asc">A → Z</option><option value="nombre_desc">Z → A</option></select></div>
    <div class="col-auto"><select id="perPageMed" class="form-select"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option></select></div>
    <div class="col-auto"><button id="clearMed" class="btn btn-outline-secondary">Limpiar</button></div>
  </div>

  <div id="resultadoMed" class="table-responsive"></div>
</main>

<!-- Modal: cuando existen turnos pendientes (ofrece forzar o reasignar) -->
<div class="modal fade" id="modalDeleteMed" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formModalDeleteMed">
        <div class="modal-header">
          <h5 class="modal-title">Eliminar médico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="modalDeleteAlert" class="alert d-none" role="alert"></div>
          <p id="modalDeleteMsg">Procesando...</p>

          <div id="reassignBlock" class="d-none">
            <label class="form-label">Seleccionar médico para reemplazar (reasignar turnos pendientes/confirmados)</label>
            <select id="reassignSelect" class="form-select"></select>
            <div class="form-text">Los turnos pendientes/confirmados serán transferidos al médico seleccionado.</div>
          </div>

        </div>
        <div class="modal-footer">
          <input type="hidden" id="modal_med_id" value="">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="btnForceDelete" class="btn btn-danger">Forzar borrado y desactivar turnos</button>
          <button type="button" id="btnShowReassign" class="btn btn-primary">Reasignar y eliminar</button>
          <button type="submit" id="btnDoReassign" class="btn btn-success d-none">Confirmar reasignación</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Deleted Medicos / restore -->
<div class="modal fade" id="modalDeletedMedicos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Médicos eliminados</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="deletedMedAlert" class="alert d-none"></div>
        <div id="deletedMedContainer">Cargando...</div>
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
/* utilidades */
function debounce(fn, delay){ let t; return function(...a){ clearTimeout(t); t = setTimeout(()=> fn.apply(this,a), delay); }; }
function escapeHtml(s){ if(s===null || s===undefined) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }
function makeAlert(type, msg, timeout=4000){
  const container = document.getElementById('alertsMed');
  const id = 'alert-'+Date.now();
  const el = document.createElement('div');
  el.id = id;
  el.className = 'alert alert-' + type + ' alert-dismissible fade show';
  el.role = 'alert';
  el.innerHTML = escapeHtml(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
  container.appendChild(el);
  if (timeout>0) setTimeout(()=> { try{ bootstrap.Alert.getOrCreateInstance(document.getElementById(id)).close(); }catch(e){} }, timeout);
  return el;
}

/* elementos */
const elMed = {
  q: document.getElementById('qMed'),
  esp: document.getElementById('filter_esp'),
  sort: document.getElementById('sortMed'),
  per: document.getElementById('perPageMed'),
  resultado: document.getElementById('resultadoMed'),
  clear: document.getElementById('clearMed'),
  linkNuevo: document.getElementById('linkNuevoMedico'),
  btnShowDeleted: document.getElementById('btnShowDeletedMedicos')
};

let currentPage = 1;

// Construir body para medicos_api.php (POST)
function buildMedBody(page=1){
  const body = new URLSearchParams();
  if (elMed.q.value.trim()) body.set('q', elMed.q.value.trim());
  if (elMed.esp.value) body.set('especialidad', elMed.esp.value);
  if (elMed.sort.value) body.set('sort', elMed.sort.value);
  body.set('per_page', elMed.per.value || '20');
  body.set('page', page);
  return body.toString();
}

async function fetchMed(page=1){
  currentPage = page;
  elMed.resultado.innerHTML = '<div class="p-4 text-center">Cargando...</div>';
  try{
    const res = await fetch('medicos_api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: buildMedBody(page)
    });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){
      elMed.resultado.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Mirá DevTools → Network.</div>';
      console.error('medicos_api raw:', text);
      return;
    }
    if (!js.success) { elMed.resultado.innerHTML = '<div class="alert alert-danger">Error: '+escapeHtml(js.error||'Error servidor')+'</div>'; return; }
    renderMedTable(js);
  }catch(e){
    elMed.resultado.innerHTML = '<div class="alert alert-danger">Error al cargar: '+escapeHtml(e.message)+'</div>';
  }
}

function renderMedTable(payload){
  const rows = payload.data || [];
  const total = payload.total || 0;
  const page = payload.page || 1;
  const total_pages = payload.total_pages || 1;

  let html = '<table class="table table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Acciones</th></tr></thead><tbody>';
  if (rows.length === 0) {
    html += '<tr><td colspan="4" class="text-center">No se encontraron médicos.</td></tr>';
  } else {
    for (const r of rows) {
      const id = r.id_medico;
      const nombre = escapeHtml((r.nombre || '') + ' ' + (r.apellido || ''));
      const esp = escapeHtml(r.especialidad || '');
      html += `<tr>
        <td class="nowrap">${id}</td>
        <td>${nombre}</td>
        <td>${esp}</td>
        <td class="nowrap">
          <a href="medicos_form.php?id=${id}" class="btn btn-sm btn-primary js-go-form">Editar</a>
          <button class="btn btn-sm btn-danger js-delete" data-id="${id}">Borrar</button>
        </td>
      </tr>`;
    }
  }
  html += '</tbody></table>';

  // pagination compacta
  html += '<div class="d-flex justify-content-between align-items-center">';
  html += `<div>Mostrando ${rows.length} de ${total} resultados</div>`;

  const maxButtons = 7;
  let start = Math.max(1, page - Math.floor(maxButtons/2));
  let end = start + maxButtons - 1;
  if (end > total_pages) { end = total_pages; start = Math.max(1, end - maxButtons + 1); }

  html += '<nav aria-label="Paginación"><ul class="pagination mb-0">';
  html += page > 1 ? `<li class="page-item"><a class="page-link" href="#" data-page="${page-1}">«</a></li>` : `<li class="page-item disabled"><span class="page-link">«</span></li>`;

  if (start > 1) { html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`; if (start > 2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`; }
  for (let p=start; p<=end; p++){ html += `<li class="page-item${p===page?' active':''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`; }
  if (end < total_pages) { if (end < total_pages-1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`; html += `<li class="page-item"><a class="page-link" href="#" data-page="${total_pages}">${total_pages}</a></li>`; }
  html += page < total_pages ? `<li class="page-item"><a class="page-link" href="#" data-page="${page+1}">»</a></li>` : `<li class="page-item disabled"><span class="page-link">»</span></li>`;
  html += '</ul></nav></div>';

  elMed.resultado.innerHTML = html;

  // paginación
  document.querySelectorAll('#resultadoMed .page-link[data-page]').forEach(a=>{
    a.addEventListener('click', (e)=>{ e.preventDefault(); const p = parseInt(a.dataset.page)||1; fetchMed(p); });
  });

  // editar: location.replace para evitar volver al form en el historial
  document.querySelectorAll('.js-go-form').forEach(a=>{
    if (a.dataset.attached === '1') return; a.dataset.attached = '1';
    a.addEventListener('click', function(ev){ ev.preventDefault(); location.replace(this.getAttribute('href')); });
  });

  // borrar: apertura primer paso
  document.querySelectorAll('.js-delete').forEach(btn=>{
    if (btn.dataset.attached === '1') return; btn.dataset.attached='1';
    btn.addEventListener('click', function(ev){
      ev.preventDefault();
      const id = parseInt(this.dataset.id || 0);
      if (!id) { makeAlert('danger','ID inválido'); return; }
      // llamada simple: preguntamos al servidor por el caso
      askDelete(id);
    });
  });
}

/* ---------- Deleted medicos modal & restore ---------- */
const modalDeletedMedicosEl = document.getElementById('modalDeletedMedicos');
const modalDeletedMedicos = new bootstrap.Modal(modalDeletedMedicosEl);
const deletedMedContainer = document.getElementById('deletedMedContainer');
const deletedMedAlert = document.getElementById('deletedMedAlert');

elMed.btnShowDeleted.addEventListener('click', async function(){
  deletedMedContainer.innerHTML = 'Cargando...';
  deletedMedAlert.classList.add('d-none');
  modalDeletedMedicos.show();

  try {
    const body = new URLSearchParams();
    body.set('per_page','1000');
    body.set('page','1');
    body.set('activo','0'); // pedimos solo eliminados
    const res = await fetch('medicos_api.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){
      deletedMedContainer.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Mirá DevTools → Network.</div>';
      console.error('medicos_api raw:', text);
      return;
    }
    if (!js.success) {
      deletedMedContainer.innerHTML = '<div class="alert alert-danger">' + escapeHtml(js.error || 'Error servidor') + '</div>';
      return;
    }
    renderDeletedMedicos(js.data || []);
  } catch(err) {
    deletedMedContainer.innerHTML = '<div class="alert alert-danger">Error de red: ' + escapeHtml(err.message || err) + '</div>';
  }
});

function renderDeletedMedicos(rows){
  if (!rows || !rows.length) {
    deletedMedContainer.innerHTML = '<div class="p-3">No hay médicos eliminados.</div>';
    return;
  }
  let html = '<table class="table table-sm table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Especialidad</th><th>Acciones</th></tr></thead><tbody>';
  for (const r of rows) {
    html += `<tr id="deleted_med_row_${r.id_medico}">
      <td class="nowrap">${r.id_medico}</td>
      <td>${escapeHtml((r.nombre||'') + ' ' + (r.apellido||''))}</td>
      <td>${escapeHtml(r.especialidad || '')}</td>
      <td class="nowrap">
        <button class="btn btn-sm btn-success" data-id="${r.id_medico}" onclick="restoreMedico(this)">Restaurar</button>
      </td>
    </tr>`;
  }
  html += '</tbody></table>';
  deletedMedContainer.innerHTML = html;
}

async function restoreMedico(btnEl) {
  const id = parseInt(btnEl.dataset.id || 0);
  if (!id) return alert('ID inválido');
  btnEl.disabled = true;
  deletedMedAlert.classList.add('d-none');

  try {
    const body = new URLSearchParams(); body.set('id_medico', id);
    // si querés restaurar también turnos inactivos, podés setear restore_turnos=1
    body.set('restore_turnos','1');
    const res = await fetch('medico_restaurar_ajax.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ deletedMedAlert.classList.remove('d-none'); deletedMedAlert.classList.add('alert-danger'); deletedMedAlert.innerText = 'Respuesta inválida del servidor.'; console.error('medico_restaurar raw:', text); btnEl.disabled = false; return; }
    if (res.ok && js.success) {
      const tr = document.getElementById('deleted_med_row_' + id);
      if (tr) tr.remove();
      makeAlert('success', js.message || 'Médico restaurado.');
      fetchMed(currentPage);
    } else {
      deletedMedAlert.classList.remove('d-none');
      deletedMedAlert.classList.add('alert-danger');
      deletedMedAlert.innerText = js.error || 'No se pudo restaurar el médico';
    }
  } catch(err) {
    deletedMedAlert.classList.remove('d-none');
    deletedMedAlert.classList.add('alert-danger');
    deletedMedAlert.innerText = 'Error de red: ' + (err.message || err);
  } finally {
    btnEl.disabled = false;
  }
}

/* ---------- resto del script (borrado / reasignación) ---------- */

/** Primera llamada: intenta borrar (sin force ni reassign). Si el servidor responde HAS_PENDING, mostramos modal con opciones */
async function askDelete(id) {
  try {
    const body = new URLSearchParams(); body.set('id_medico', id);
    const res = await fetch('medico_borrar_ajax.php', {
      method:'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, credentials:'same-origin', body: body.toString()
    });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e) {
      console.error('raw response:', text);
      makeAlert('danger','Respuesta inválida del servidor. Mirá DevTools → Network.');
      return;
    }

    if (res.ok && js.success) {
      makeAlert('success', js.message || 'Médico eliminado correctamente.');
      // refresh
      fetchMed(currentPage);
      return;
    }

    // si no success y code == HAS_PENDING -> abrir modal con opciones
    if (js.code === 'HAS_PENDING' || js.code === 'HAS_RELATED_PENDING') {
      modal_med_id.value = id;
      clearModal();
      modalDeleteMsg.innerText = `El médico tiene ${js.pending_count} turno(s) pendiente(s) o confirmado(s). ¿Qué querés hacer?`;
      modalBs.show();
      await loadMedicosOptions(id);
      return;
    }

    // otro error
    makeAlert('danger', js.error || 'Error al procesar la solicitud', 7000);
  } catch(err) {
    console.error(err);
    makeAlert('danger', 'Error de red al intentar eliminar médico.');
  }
}

/* cargadores y handlers del modalDelete (se mantiene tu lógica) */
const modalEl = document.getElementById('modalDeleteMed');
const modalBs = new bootstrap.Modal(modalEl);
const modalMsg = document.getElementById('modalDeleteMsg');
const modalAlert = document.getElementById('modalDeleteAlert');
const reassignBlock = document.getElementById('reassignBlock');
const reassignSelect = document.getElementById('reassignSelect');
const modal_med_id = document.getElementById('modal_med_id');
const btnForceDelete = document.getElementById('btnForceDelete');
const btnShowReassign = document.getElementById('btnShowReassign');
const btnDoReassign = document.getElementById('btnDoReassign');

function clearModal() {
  modalAlert.classList.add('d-none');
  modalAlert.innerText = '';
  reassignBlock.classList.add('d-none');
  reassignSelect.innerHTML = '';
  btnDoReassign.classList.add('d-none');
  btnShowReassign.classList.remove('d-none');
  btnForceDelete.disabled = false;
  btnShowReassign.disabled = false;
  btnDoReassign.disabled = false;
}

// Cargar lista medicos para reasignar
async function loadMedicosOptions(excludeId) {
  reassignSelect.innerHTML = '<option value="">Cargando médicos...</option>';
  try {
    const body = new URLSearchParams(); body.set('per_page','1000'); body.set('page','1'); body.set('sort','nombre_asc');
    const res = await fetch('medicos_api.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e) { reassignSelect.innerHTML = '<option value="">Error cargando</option>'; return; }
    reassignSelect.innerHTML = '<option value="">-- Seleccione médico --</option>';
    if (Array.isArray(js.data)) {
      for (const m of js.data) {
        if (m.id_medico === excludeId) continue;
        const opt = document.createElement('option');
        opt.value = m.id_medico;
        opt.text = (m.nombre || '') + ' ' + (m.apellido || '') + (m.especialidad ? ' ('+m.especialidad+')' : '');
        reassignSelect.appendChild(opt);
      }
    }
  } catch(err) {
    reassignSelect.innerHTML = '<option value="">Error de red</option>';
  }
}

/* Botón: mostrar bloque reasignar */
btnShowReassign.addEventListener('click', function(){
  reassignBlock.classList.remove('d-none');
  btnShowReassign.classList.add('d-none');
  btnDoReassign.classList.remove('d-none');
});

/* Botón: forzar borrado (marca todos los turnos activos como inactivos y borra) */
btnForceDelete.addEventListener('click', async function(){
  const id = parseInt(modal_med_id.value || 0);
  if (!id) return;
  if (!confirm('Forzar borrado: esto marcará todos los turnos del médico como inactivos y lo eliminará. ¿Continuar?')) return;
  btnForceDelete.disabled = true;
  try {
    const body = new URLSearchParams(); body.set('id_medico', id); body.set('force', '1');
    const res = await fetch('medico_borrar_ajax.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ makeAlert('danger','Respuesta inválida del servidor. Mirá DevTools → Network.'); modalBs.hide(); return; }
    if (res.ok && js.success) {
      makeAlert('success', js.message || 'Operación finalizada.');
      modalBs.hide();
      fetchMed(currentPage);
    } else {
      makeAlert('danger', js.error || 'Error al forzar borrado.', 7000);
    }
  } catch(err) {
    makeAlert('danger', 'Error de red al forzar borrado.');
  } finally {
    btnForceDelete.disabled = false;
  }
});

/* Submit: realizar la reasignación al médico seleccionado */
document.getElementById('formModalDeleteMed').addEventListener('submit', async function(e){
  e.preventDefault();
  const id = parseInt(modal_med_id.value || 0);
  const to = parseInt(reassignSelect.value || 0);
  if (!id || !to) {
    modalAlert.classList.remove('d-none');
    modalAlert.classList.add('alert-danger');
    modalAlert.innerText = 'Seleccioná un médico válido para reasignar.';
    return;
  }
  btnDoReassign.disabled = true;
  try {
    const body = new URLSearchParams(); body.set('id_medico', id); body.set('reassign_to', to);
    const res = await fetch('medico_borrar_ajax.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ modalAlert.classList.remove('d-none'); modalAlert.classList.add('alert-danger'); modalAlert.innerText = 'Respuesta inválida del servidor. Mirá DevTools → Network.'; return; }
    if (res.ok && js.success) {
      makeAlert('success', js.message || 'Turnos reasignados y médico eliminado.');
      modalBs.hide();
      fetchMed(currentPage);
    } else {
      modalAlert.classList.remove('d-none');
      modalAlert.classList.add('alert-danger');
      modalAlert.innerText = js.error || 'Error al reasignar.';
    }
  } catch(err) {
    modalAlert.classList.remove('d-none');
    modalAlert.classList.add('alert-danger');
    modalAlert.innerText = 'Error de red al reasignar.';
  } finally {
    btnDoReassign.disabled = false;
  }
});

// Cuando cerramos modal limpiar
modalEl.addEventListener('hidden.bs.modal', function(){ clearModal(); });

/* Debounce y binds */
const debMed = debounce(()=> fetchMed(1), 300);
elMed.q.addEventListener('input', debMed);
elMed.esp.addEventListener('change', ()=> fetchMed(1));
elMed.sort.addEventListener('change', ()=> fetchMed(1));
elMed.per.addEventListener('change', ()=> fetchMed(1));
elMed.clear.addEventListener('click', ()=> { elMed.q.value=''; elMed.esp.value=''; elMed.sort.value='nombre_asc'; elMed.per.value='20'; fetchMed(1); });

// ensure top New link replaces history
if (elMed.linkNuevo) elMed.linkNuevo.addEventListener('click', function(e){ e.preventDefault(); location.replace(this.getAttribute('href')); });

// inicial
fetchMed(1);
</script>
</body>
</html>


