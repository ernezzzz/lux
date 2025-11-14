<?php
include '../../backend/checklogin.php';
include '../../backend/header.php';
include '../../backend/conexion.php';

// cargar pacientes para filtro
$pac_res = mysqli_query($conn, "SELECT id_usuario, nombre, apellido FROM usuarios WHERE id_rol = (SELECT id_rol FROM rol WHERE rol = 'paciente' LIMIT 1) ORDER BY nombre");
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Historias Clínicas - Consultorio (AJAX)</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<main class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Historias Clínicas</h2>
    <div><a href="historia_form.php" id="linkNuevaHist" class="btn btn-success">Nueva Historia</a><a href="dashboard.php" class="btn btn-secondary ms-2">Volver</a></div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-md-4"><input id="qHist" class="form-control" placeholder="Buscar por paciente o descripción..."></div>
    <div class="col-auto">
      <select id="filter_pac" class="form-select">
        <option value="">Todos los pacientes</option>
        <?php while($p = mysqli_fetch_assoc($pac_res)): ?>
          <option value="<?= (int)$p['id_usuario'] ?>"><?= htmlspecialchars(trim($p['nombre'].' '.$p['apellido'])) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-auto"><select id="sortHist" class="form-select"><option value="fecha_desc">Fecha (reciente)</option><option value="fecha_asc">Fecha (antigua)</option></select></div>
    <div class="col-auto"><select id="perPageHist" class="form-select"><option value="10">10</option><option value="20" selected>20</option><option value="50">50</option></select></div>
    <div class="col-auto"><button id="clearHist" class="btn btn-outline-secondary">Limpiar</button></div>
  </div>

  <div id="resultadoHist" class="table-responsive"></div>
</main>

<footer class="text-center py-3">© <?= date('Y') ?> Grupo Lux — Consultorio</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function debounce(fn, delay){ let t; return function(...a){ clearTimeout(t); t = setTimeout(()=> fn.apply(this,a), delay); }; }
function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

const elH = {
  q: document.getElementById('qHist'),
  pac: document.getElementById('filter_pac'),
  sort: document.getElementById('sortHist'),
  per: document.getElementById('perPageHist'),
  resultado: document.getElementById('resultadoHist'),
  clear: document.getElementById('clearHist'),
  linkNueva: document.getElementById('linkNuevaHist')
};

function buildHistBody(page=1){
  const body = new URLSearchParams();
  if (elH.q.value.trim()) body.set('q', elH.q.value.trim());
  if (elH.pac.value) body.set('paciente', elH.pac.value);
  if (elH.sort.value) body.set('sort', elH.sort.value);
  body.set('per_page', elH.per.value || '20');
  body.set('page', page);
  return body.toString();
}

async function fetchHist(page=1){
  elH.resultado.innerHTML = '<div class="p-4 text-center">Cargando...</div>';
  try{
    const res = await fetch('historia_api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: buildHistBody(page)
    });
    const text = await res.text();
    let js;
    try { js = JSON.parse(text); } catch(e){ elH.resultado.innerHTML = '<div class="alert alert-danger">Respuesta inválida del servidor. Revisá Network.</div>'; console.error('historia_api raw:', text); return; }
    if (!js.success) { elH.resultado.innerHTML = '<div class="alert alert-danger">Error: '+(js.error||'Error servidor')+'</div>'; return; }
    renderHistTable(js);
  }catch(e){
    elH.resultado.innerHTML = '<div class="alert alert-danger">Error al cargar: '+e.message+'</div>';
  }
}

function renderHistTable(payload){
  const rows = payload.data || [];
  const total = payload.total || 0;
  const page = payload.page || 1;
  const per = payload.per_page || 20;
  const total_pages = payload.total_pages || 1;

  let html = '<table class="table table-striped"><thead><tr><th>ID</th><th>Fecha</th><th>Paciente</th><th>Descripción</th><th>Acciones</th></tr></thead><tbody>';
  if (rows.length === 0) {
    html += '<tr><td colspan="5" class="text-center">No se encontraron registros.</td></tr>';
  } else {
    for (const r of rows) {
      html += `<tr>
        <td class="nowrap">${r.id_historia}</td>
        <td class="nowrap">${escapeHtml(r.fecha)}</td>
        <td>${escapeHtml(r.paciente_nombre)}</td>
        <td>${escapeHtml((r.descripcion || '').slice(0,200))}${(r.descripcion && r.descripcion.length>200)?'…':''}</td>
        <td class="nowrap">
          <a href="historia_form.php?id=${r.id_historia}" class="btn btn-sm btn-primary js-go-form">Editar</a>
          <a href="historia_borrar.php?id=${r.id_historia}" class="btn btn-sm btn-danger" onclick="return confirm('¿Borrar historia?')">Borrar</a>
        </td>
      </tr>`;
    }
  }
  html += '</tbody></table>';

  // pagination
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

  elH.resultado.innerHTML = html;

  // attach page handlers
  document.querySelectorAll('#resultadoHist .page-link[data-page]').forEach(a=>{
    a.addEventListener('click', (e)=>{ e.preventDefault(); fetchHist(parseInt(a.dataset.page)||1); });
  });

  // attach form links to replace history
  document.querySelectorAll('.js-go-form').forEach(a=>{
    if (a.dataset.attached === '1') return;
    a.dataset.attached = '1';
    a.addEventListener('click', function(ev){ ev.preventDefault(); location.replace(this.getAttribute('href')); });
  });
}

const debH = debounce(()=> fetchHist(1), 300);
elH.q.addEventListener('input', debH);
elH.pac.addEventListener('change', ()=> fetchHist(1));
elH.sort.addEventListener('change', ()=> fetchHist(1));
elH.per.addEventListener('change', ()=> fetchHist(1));
elH.clear.addEventListener('click', ()=> { elH.q.value=''; elH.pac.value=''; elH.sort.value='fecha_desc'; elH.per.value='20'; fetchHist(1); });

// ensure new link replaces history
if (elH.linkNueva) elH.linkNueva.addEventListener('click', function(e){ e.preventDefault(); location.replace(this.getAttribute('href')); });

// inicial
fetchHist(1);
</script>
</body>
</html>
