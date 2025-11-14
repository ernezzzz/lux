<?php
// pacientes_listar.php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pacientes - Consultorio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .nowrap { white-space: nowrap; }
    .pagination { margin: 0; }
    /* pequeñas ayudas UI */
    .small-note { font-size: 0.9rem; color: #666; }
  </style>
</head>
<body>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Pacientes</h2>
    <div>
      <a href="turnos_form.php" class="btn btn-success">Nuevo Turno</a>
      <button id="btnAddPacienteTop" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#modalNuevoPaciente">Agregar Paciente</button>
      <button id="btnShowDeletedPac" class="btn btn-outline-secondary ms-2">Ver registros eliminados</button>
      <a href="dashboard.php" class="btn btn-secondary ms-2">Volver</a>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-md-4"><input id="qPac" class="form-control" placeholder="Buscar por nombre o email..."></div>
    <div class="col-auto"><select id="sortPac" class="form-select"><option value="nombre_asc">A → Z</option><option value="nombre_desc">Z → A</option></select></div>
    <div class="col-auto"><select id="perPagePac" class="form-select"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option></select></div>
    <div class="col-auto"><button id="clearPac" class="btn btn-outline-secondary">Limpiar</button></div>
  </div>

  <div id="resultadoPac" class="table-responsive"></div>
</main>

<!-- Modal para crear/editar paciente -->
<div class="modal fade" id="modalNuevoPaciente" tabindex="-1" aria-labelledby="modalNuevoPacienteLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formNuevoPaciente" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevoPacienteLabel">Agregar paciente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div id="nuevoPacienteAlert" class="alert d-none" role="alert"></div>

          <input type="hidden" name="id_usuario" id="pacienteId" value="">

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
            <div class="form-text small-note">Opcional pero recomendado. Si ya existe un paciente con el mismo email no se permitirá duplicados.</div>
          </div>

          <!-- Campos ocultos: id_rol y id_negocio -->
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

<!-- Modal para ver Turnos del paciente -->
<div class="modal fade" id="modalTurnosPac" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Turnos</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="modalTurnosBody">Cargando...</div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<!-- Modal para ver Historias del paciente -->
<div class="modal fade" id="modalHistPac" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Historias Clínicas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="modalHistBody">Cargando...</div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<!-- Modal: Deleted Pacientes -->
<div class="modal fade" id="modalDeletedPac" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Pacientes eliminados</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="deletedPacAlert" class="alert d-none"></div>
        <div id="deletedPacContainer">Cargando...</div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ---------- Utilidades ---------- */
function debounce(fn, delay){ let t; return function(...a){ clearTimeout(t); t = setTimeout(()=> fn.apply(this,a), delay); }; }
function escapeHtml(s){ if (s === null || s === undefined) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }
function showAlert(el, type, msg){ el.classList.remove('d-none','alert-success','alert-danger','alert-info'); el.classList.add('alert-'+type); el.innerHTML = msg; }
function clearAlert(el){ el.classList.add('d-none'); el.innerHTML=''; }

/* ---------- Broadcast entre pestañas para refrescar listados ---------- */
const bcSupported = typeof BroadcastChannel !== 'undefined';
const channelName = 'lux_pacientes';
if (bcSupported) {
  const bc = new BroadcastChannel(channelName);
  bc.onmessage = (ev) => {
    try { fetchPac(1); } catch(e){ console.error(e); }
  };
} else {
  window.addEventListener('storage', function(e){
    if (e.key === channelName && e.newValue) {
      try { fetchPac(1); } catch(err){ console.error(err); }
      localStorage.removeItem(channelName);
    }
  });
}

/* ---------- Elementos y estado ---------- */
const el = {
  q: document.getElementById('qPac'),
  sort: document.getElementById('sortPac'),
  per: document.getElementById('perPagePac'),
  resultado: document.getElementById('resultadoPac'),
  clear: document.getElementById('clearPac'),
  btnAddTop: document.getElementById('btnAddPacienteTop'),
  btnShowDeleted: document.getElementById('btnShowDeletedPac')
};
const state = { page: 1 };

/* ---------- Construir body POST ---------- */
function buildBody(page=1){
  const body = new URLSearchParams();
  if (el.q && el.q.value.trim()) body.set('q', el.q.value.trim());
  if (el.sort && el.sort.value) body.set('sort', el.sort.value);
  body.set('per_page', el.per.value || '20');
  body.set('page', page);
  return body.toString();
}

/* ---------- Fetch y render de pacientes (POST JSON) ---------- */
async function fetchPac(page=1){
  state.page = page;
  el.resultado.innerHTML = '<div class="p-4 text-center">Cargando...</div>';
  try {
    const res = await fetch('pacientes_api.php', {
      method:'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      credentials:'same-origin',
      body: buildBody(page)
    });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ el.resultado.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Mirá DevTools.</div>'; console.error('pacientes_api raw:', text); return; }
    if (!js.success) { el.resultado.innerHTML = '<div class="alert alert-danger">Error: ' + (js.error||'Error servidor') + '</div>'; return; }
    renderTable(js);
  } catch(err) {
    el.resultado.innerHTML = '<div class="alert alert-danger">Error: ' + escapeHtml(err.message || err) + '</div>';
  }
}

/* ---------- Render tabla ---------- */
function renderTable(payload){
  const rows = payload.data || [];
  const total = payload.total || 0;
  const page = payload.page || 1;
  const total_pages = payload.total_pages || 1;

  let html = '<table class="table table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Turnos</th><th>Historias</th><th>Acciones</th></tr></thead><tbody>';
  if (rows.length === 0) {
    html += '<tr><td colspan="6" class="text-center">No se encontraron pacientes.</td></tr>';
  } else {
    for (const r of rows) {
      const id = r.id_usuario;
      const nombre = escapeHtml((r.nombre||'') + ' ' + (r.apellido||''));
      const email = escapeHtml(r.email || '');
      const turnos = Number(r.turnos_count || 0);
      const historias = Number(r.historias_count || 0);

      html += `<tr>
        <td class="nowrap">${id}</td>
        <td>${nombre}</td>
        <td>${email}</td>
        <td class="nowrap">${turnos}</td>
        <td class="nowrap">${historias}</td>
        <td class="nowrap">
          <button class="btn btn-sm btn-success js-edit-paciente" data-id="${id}" data-nombre="${escapeHtml(r.nombre || '')}" data-apellido="${escapeHtml(r.apellido || '')}" data-email="${escapeHtml(r.email || '')}">Editar</button>
          <button class="btn btn-sm btn-danger js-delete-paciente ms-1" data-id="${id}">Eliminar</button>
          <button class="btn btn-sm btn-info js-ver-turnos ms-1" data-id="${id}">Turnos</button>
          <button class="btn btn-sm btn-primary js-ver-hist ms-1" data-id="${id}">Historias</button>
        </td>
      </tr>`;
    }
  }
  html += '</tbody></table>';

  // pagination simple
  html += '<div class="d-flex justify-content-between align-items-center"><div>Mostrando ' + rows.length + ' de ' + total + ' resultados</div>';
  html += '<nav><ul class="pagination mb-0">';
  if (page > 1) html += `<li class="page-item"><a class="page-link" href="#" data-page="${page-1}">«</a></li>`; else html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
  const start = Math.max(1, page-2); const end = Math.min(total_pages, page+2);
  for (let p=start;p<=end;p++) html += `<li class="page-item${p===page?' active':''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
  if (page < total_pages) html += `<li class="page-item"><a class="page-link" href="#" data-page="${page+1}">»</a></li>`; else html += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
  html += '</ul></nav></div>';

  el.resultado.innerHTML = html;

  // pagination handlers
  document.querySelectorAll('#resultadoPac .page-link[data-page]').forEach(a=>{ a.addEventListener('click', (ev)=>{ ev.preventDefault(); fetchPac(parseInt(a.dataset.page)||1); }); });

  // attach action buttons
  document.querySelectorAll('.js-ver-turnos').forEach(b=> b.addEventListener('click', ()=> openTurnosModal(b.dataset.id)));
  document.querySelectorAll('.js-ver-hist').forEach(b=> b.addEventListener('click', ()=> openHistModal(b.dataset.id)));
  document.querySelectorAll('.js-delete-paciente').forEach(b=> b.addEventListener('click', onDeletePaciente));
  document.querySelectorAll('.js-edit-paciente').forEach(b=> b.addEventListener('click', onEditPaciente));
}

/* ---------- Modales: Turnos / Historias ---------- */
async function openTurnosModal(id){
  const body = new URLSearchParams(); body.set('paciente', id); body.set('per_page', '100');
  const modalBody = document.getElementById('modalTurnosBody');
  modalBody.innerHTML = 'Cargando...';
  const modal = new bootstrap.Modal(document.getElementById('modalTurnosPac'));
  modal.show();

  try {
    const res = await fetch('turnos_por_paciente.php', {
      method:'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, credentials:'same-origin', body: body.toString()
    });
    const text = await res.text(); const js = JSON.parse(text);
    if (!js.success) { modalBody.innerHTML = '<div class="alert alert-danger">'+(js.error||'Error')+'</div>'; return; }
    if (!js.data.length) { modalBody.innerHTML = '<div class="p-3">No tiene turnos registrados.</div>'; return; }
    let html = '<ul class="list-group">';
    for (const t of js.data) {
      html += `<li class="list-group-item"><strong>${escapeHtml(t.fecha || '')} ${escapeHtml(t.hora || '')}</strong> — ${escapeHtml(t.medico_nombre || '')} — Estado: ${escapeHtml(t.estado || '')}</li>`;
    }
    html += '</ul>';
    modalBody.innerHTML = html;
  } catch(err) {
    modalBody.innerHTML = '<div class="alert alert-danger">Error: '+escapeHtml(err.message || err)+'</div>';
  }
}

async function openHistModal(id){
  const body = new URLSearchParams(); body.set('paciente', id); body.set('per_page', '100');
  const modalBody = document.getElementById('modalHistBody');
  modalBody.innerHTML = 'Cargando...';
  const modal = new bootstrap.Modal(document.getElementById('modalHistPac'));
  modal.show();

  try {
    const res = await fetch('historia_por_paciente.php', {
      method:'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, credentials:'same-origin', body: body.toString()
    });
    const text = await res.text(); const js = JSON.parse(text);
    if (!js.success) { modalBody.innerHTML = '<div class="alert alert-danger">'+(js.error||'Error')+'</div>'; return; }
    if (!js.data.length) { modalBody.innerHTML = '<div class="p-3">No hay historias registradas.</div>'; return; }
    let html = '<div class="accordion" id="accHist">';
    js.data.forEach((h,i)=>{
      const idh = 'hist' + h.id_historia;
      html += `<div class="accordion-item">
        <h2 class="accordion-header" id="h${idh}">
          <button class="accordion-button ${i? 'collapsed':''}" type="button" data-bs-toggle="collapse" data-bs-target="#${idh}">${escapeHtml(h.fecha || '')} — ${escapeHtml(h.paciente_nombre || '')}</button>
        </h2>
        <div id="${idh}" class="accordion-collapse collapse ${i? '':'show'}" data-bs-parent="#accHist">
          <div class="accordion-body">${escapeHtml(h.descripcion || '').replace(/\n/g,'<br>')}</div>
        </div>
      </div>`;
    });
    html += '</div>';
    modalBody.innerHTML = html;
  } catch(err) {
    modalBody.innerHTML = '<div class="alert alert-danger">Error: '+escapeHtml(err.message || err)+'</div>';
  }
}

/* ---------- Crear / Editar paciente (misma modal) ---------- */
const formNuevoPaciente = document.getElementById('formNuevoPaciente');
const modalNuevoPacienteEl = document.getElementById('modalNuevoPaciente');
const modalNuevoPaciente = new bootstrap.Modal(modalNuevoPacienteEl);
const nuevoPacienteAlert = document.getElementById('nuevoPacienteAlert');
const pacienteIdEl = document.getElementById('pacienteId');

function openCreateModal(){
  pacienteIdEl.value = '';
  document.getElementById('pacienteNombre').value = '';
  document.getElementById('pacienteApellido').value = '';
  document.getElementById('pacienteEmail').value = '';
  document.getElementById('modalNuevoPacienteLabel').innerText = 'Agregar paciente';
  clearAlert(nuevoPacienteAlert);
  modalNuevoPaciente.show();
}

function onEditPaciente(ev){
  const btn = ev.currentTarget;
  const id = btn.dataset.id;
  // rellenar campos desde data-attrs generados en renderTable
  document.getElementById('modalNuevoPacienteLabel').innerText = 'Editar paciente';
  pacienteIdEl.value = id || '';
  document.getElementById('pacienteNombre').value = btn.dataset.nombre || '';
  document.getElementById('pacienteApellido').value = btn.dataset.apellido || '';
  document.getElementById('pacienteEmail').value = btn.dataset.email || '';
  clearAlert(nuevoPacienteAlert);
  modalNuevoPaciente.show();
}

formNuevoPaciente.addEventListener('submit', async function(e){
  e.preventDefault();
  const btn = document.getElementById('btnGuardarPacienteModal');
  btn.disabled = true;
  clearAlert(nuevoPacienteAlert);

  // validación mínima
  const nombre = document.getElementById('pacienteNombre').value.trim();
  const apellido = document.getElementById('pacienteApellido').value.trim();
  const email = document.getElementById('pacienteEmail').value.trim();
  if (!nombre || !apellido) {
    showAlert(nuevoPacienteAlert, 'danger', 'Nombre y apellido son obligatorios.');
    btn.disabled = false;
    return;
  }
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showAlert(nuevoPacienteAlert, 'danger', 'Email con formato inválido.');
    btn.disabled = false;
    return;
  }

  // preparar FormData
  const fd = new FormData(formNuevoPaciente);

  try {
    const res = await fetch('paciente_guardar_ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(err){ throw new Error('Respuesta inválida del servidor. Mirá DevTools → Network.'); }

    if (!res.ok || !js.success) {
      showAlert(nuevoPacienteAlert, 'danger', js.error || 'Error al guardar paciente');
      btn.disabled = false;
      return;
    }

    // broadcasting a otras pestañas
    try {
      const pacObj = { id: js.id || parseInt(pacienteIdEl.value || 0), nombre_completo: js.nombre_completo || (nombre + ' ' + apellido), email: js.email || email || '' };
      if (bcSupported) {
        const bc = new BroadcastChannel(channelName);
        bc.postMessage(pacObj);
        bc.close();
      } else {
        localStorage.setItem(channelName, JSON.stringify(pacObj));
        setTimeout(()=> localStorage.removeItem(channelName), 600);
      }
    } catch(bcErr){ console.warn('Broadcast error', bcErr); }

    // mostrar warning si el servidor lo devolvió (por ejemplo: nombre duplicado pero email distinto)
    if (js.warning) {
      showAlert(nuevoPacienteAlert, 'info', js.warning);
      setTimeout(()=> {
        modalNuevoPaciente.hide();
        formNuevoPaciente.reset();
        fetchPac(1);
        clearAlert(nuevoPacienteAlert);
      }, 1400);
    } else {
      modalNuevoPaciente.hide();
      formNuevoPaciente.reset();
      fetchPac(1);
    }
  } catch(err) {
    console.error(err);
    showAlert(nuevoPacienteAlert, 'danger', err.message || 'Error de red');
  } finally {
    btn.disabled = false;
    // limpiar backdrops por si quedó alguno
    setTimeout(()=> {
      document.querySelectorAll('.modal-backdrop').forEach(n=>n.remove());
      document.body.classList.remove('modal-open');
    }, 300);
  }
});

/* ---------- Borrar paciente (soft-delete) ---------- */
async function onDeletePaciente(ev){
  const btn = ev.currentTarget;
  const id = btn.dataset.id;
  if (!id) { alert('ID de paciente no encontrado.'); return; }
  if (!confirm('¿Confirmás borrar este paciente? (se marcará como inactivo)')) return;

  btn.disabled = true;
  try {
    const body = new URLSearchParams(); body.set('id_usuario', id);
    const res = await fetch('paciente_borrar_ajax.php', {
      method:'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, credentials:'same-origin', body: body.toString()
    });

    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(err){ throw new Error('Respuesta inválida del servidor'); }

    if (!res.ok) {
      // conflicto 409 u otro error
      alert(js.error || 'No se pudo borrar el paciente.');
      btn.disabled = false;
      return;
    }

    if (js.success) {
      // refrescar y notificar
      fetchPac(1);
    } else {
      alert(js.error || 'No se pudo borrar el paciente.');
      btn.disabled = false;
    }
  } catch(err) {
    console.error(err);
    alert('Error: ' + (err.message || err));
    btn.disabled = false;
  }
}

/* ---------- Deleted pacientes modal & restore ---------- */
const modalDeletedPacEl = document.getElementById('modalDeletedPac');
const modalDeletedPac = new bootstrap.Modal(modalDeletedPacEl);
const deletedPacContainer = document.getElementById('deletedPacContainer');
const deletedPacAlert = document.getElementById('deletedPacAlert');

el.btnShowDeleted.addEventListener('click', async function(){
  deletedPacContainer.innerHTML = 'Cargando...';
  deletedPacAlert.classList.add('d-none');
  modalDeletedPac.show();

  try {
    const body = new URLSearchParams();
    body.set('per_page','1000');
    body.set('page','1');
    body.set('activo','0');
    const res = await fetch('pacientes_api.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){
      deletedPacContainer.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Mirá DevTools → Network.</div>';
      console.error('pacientes_api raw:', text);
      return;
    }
    if (!js.success) {
      deletedPacContainer.innerHTML = '<div class="alert alert-danger">' + escapeHtml(js.error || 'Error servidor') + '</div>';
      return;
    }
    renderDeletedPacientes(js.data || []);
  } catch(err) {
    deletedPacContainer.innerHTML = '<div class="alert alert-danger">Error de red: ' + escapeHtml(err.message || err) + '</div>';
  }
});

function renderDeletedPacientes(rows){
  if (!rows || !rows.length) {
    deletedPacContainer.innerHTML = '<div class="p-3">No hay pacientes eliminados.</div>';
    return;
  }
  let html = '<table class="table table-sm table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Acciones</th></tr></thead><tbody>';
  for (const r of rows) {
    html += `<tr id="deleted_pac_row_${r.id_usuario}">
      <td class="nowrap">${r.id_usuario}</td>
      <td>${escapeHtml((r.nombre||'') + ' ' + (r.apellido||''))}</td>
      <td>${escapeHtml(r.email || '')}</td>
      <td class="nowrap"><button class="btn btn-sm btn-success" data-id="${r.id_usuario}" onclick="restorePaciente(this)">Restaurar</button></td>
    </tr>`;
  }
  html += '</tbody></table>';
  deletedPacContainer.innerHTML = html;
}

async function restorePaciente(btnEl) {
  const id = parseInt(btnEl.dataset.id || 0);
  if (!id) return alert('ID inválido');
  btnEl.disabled = true;
  deletedPacAlert.classList.add('d-none');

  try {
    const body = new URLSearchParams(); body.set('id_usuario', id);
    const res = await fetch('paciente_restaurar_ajax.php', { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ deletedPacAlert.classList.remove('d-none'); deletedPacAlert.classList.add('alert-danger'); deletedPacAlert.innerText = 'Respuesta inválida del servidor.'; console.error('paciente_restaurar raw:', text); btnEl.disabled = false; return; }
    if (res.ok && js.success) {
      const tr = document.getElementById('deleted_pac_row_' + id);
      if (tr) tr.remove();
      // refrescar la lista visible
      fetchPac(1);
      deletedPacAlert.classList.remove('d-none'); deletedPacAlert.classList.add('alert-success'); deletedPacAlert.innerText = js.message || 'Paciente restaurado.';
    } else {
      deletedPacAlert.classList.remove('d-none'); deletedPacAlert.classList.add('alert-danger'); deletedPacAlert.innerText = js.error || 'No se pudo restaurar el paciente';
    }
  } catch(err) {
    deletedPacAlert.classList.remove('d-none'); deletedPacAlert.classList.add('alert-danger'); deletedPacAlert.innerText = 'Error de red: ' + (err.message || err);
  } finally {
    btnEl.disabled = false;
  }
}

/* ---------- Binds y carga inicial ---------- */
const deb = debounce(()=> fetchPac(1), 300);
if (el.q) el.q.addEventListener('input', deb);
if (el.sort) el.sort.addEventListener('change', ()=> fetchPac(1));
if (el.per) el.per.addEventListener('change', ()=> fetchPac(1));
if (el.clear) el.clear.addEventListener('click', ()=> { el.q.value=''; el.sort.value='nombre_asc'; el.per.value='20'; fetchPac(1); });

if (el.btnAddTop) {
  el.btnAddTop.addEventListener('click', ()=> {
    // limpiar modal antes de mostrar
    clearAlert(nuevoPacienteAlert);
    formNuevoPaciente.reset();
    pacienteIdEl.value = '';
    document.getElementById('modalNuevoPacienteLabel').innerText = 'Agregar paciente';
  });
}

// inicial
fetchPac(1);
</script>
</body>
</html>


