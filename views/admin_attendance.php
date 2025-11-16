<?php
declare(strict_types=1);
$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/app.css" rel="stylesheet">
  <style>
    img.sig { max-height: 64px; border: 1px solid #ddd; background: #fff; }
  </style>
</head>
<body class="bg-light">
<meta name="csrf" content="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container"><a class="navbar-brand" href="/">Event Registration</a>
    <div class="ms-auto btn-group"><a class="btn btn-outline-light btn-sm" href="/?r=admin_registrants">Registrants</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_attendance">Attendance</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_import">Import</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_export">Export</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_report">Report</a></div>
  </div>
</nav>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Attendance</h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_registrants">Registrants</a>
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_logout">Logout</a>
    </div>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/">Home</a></li>
      <li class="breadcrumb-item"><a href="/?r=admin_registrants">Admin</a></li>
      <li class="breadcrumb-item active" aria-current="page">Attendance</li>
    </ol>
  </nav>
  <form method="get" action="/">
    <input type="hidden" name="r" value="admin_attendance">
    <div class="row g-2">
      <div class="col-12 col-md-3"><input name="date" value="<?= htmlspecialchars($date??'', ENT_QUOTES) ?>" placeholder="YYYY-MM-DD" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="agency" list="agencyList" value="<?= htmlspecialchars($agency??'', ENT_QUOTES) ?>" placeholder="Agency" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="name" value="<?= htmlspecialchars($name??'', ENT_QUOTES) ?>" placeholder="Name" class="form-control"></div>
      <div class="col-12 col-md-3">
        <button id="filterBtn" class="btn btn-primary w-100" type="submit">
          <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display:none"></span>
          Filter
        </button>
      </div>
    </div>
  </form>
  <datalist id="agencyList">
    <?php // we reuse registrants agencies if not passed explicitly ?>
    <?php if (isset($rows) && is_array($rows)) { $agSeen=[]; foreach ($rows as $r) { if (!empty($r['agency'])) { $agSeen[$r['agency']]=true; } } foreach (array_keys($agSeen) as $a): ?>
      <option value="<?= htmlspecialchars($a, ENT_QUOTES) ?>"></option>
    <?php endforeach; } ?>
  </datalist>
  <div class="mt-3">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>#</th><th>Date</th><th>Time</th><th>Name</th><th>Agency</th><th>UUID</th><th>Signature</th></tr>
        </thead>
        <tbody>
        <?php if (!($rows??[])) : ?>
          <tr><td colspan="7" class="text-center text-muted">No attendance records found</td></tr>
        <?php endif; ?>
        <?php foreach (($rows??[]) as $i=>$r): ?>
          <tr>
            <td><?= ($i+1) + (($page-1)*20) ?></td>
            <td><?= htmlspecialchars($r['attendance_date'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['time_in'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['agency']??'', ENT_QUOTES) ?></td>
            <td><code><?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?></code></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="/signature.php?aid=<?= (int)$r['id'] ?>" target="_blank">Download</a>
              <img class="sig ms-2" src="/signature.php?aid=<?= (int)$r['id'] ?>" alt="sig">
              <button class="btn btn-sm btn-outline-secondary ms-2" data-aid="<?= (int)$r['id'] ?>" data-uuid="<?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?>" data-bs-toggle="modal" data-bs-target="#sigModal">Replace</button>
              <button class="btn btn-sm btn-outline-success ms-1" data-new="true" data-uuid="<?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?>" data-bs-toggle="modal" data-bs-target="#sigModal">New</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <nav>
    <ul class="pagination">
      <?php for ($p=1; $p<=($pages??1); $p++): ?>
        <li class="page-item <?= $p==($page??1)?'active':'' ?>"><a class="page-link" href="/?r=admin_attendance&page=<?= $p ?>&date=<?= urlencode($date??'') ?>&agency=<?= urlencode($agency??'') ?>&name=<?= urlencode($name??'') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
<div class="modal fade" id="sigModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Signature</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <canvas id="sigCanvas" style="width:100%;height:240px;border:1px solid #ddd;touch-action:none"></canvas>
        <div class="mt-2 d-flex gap-2">
          <input type="date" id="sigDate" class="form-control" style="max-width:180px" value="<?= date('Y-m-d') ?>">
          <button class="btn btn-outline-secondary" id="sigClear">Clear</button>
          <button class="btn btn-primary" id="sigSave">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="position-fixed top-0 end-0 p-3" style="z-index:1055">
  <div id="sigToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">Saved</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf"]').getAttribute('content');
  let sigPad; let currentAid=null; let currentUuid=null; let isNew=false;
  const modal = document.getElementById('sigModal');
  modal.addEventListener('shown.bs.modal', (e) => {
    const btn = e.relatedTarget;
    const canvas = document.getElementById('sigCanvas');
    calibrateCanvas(canvas);
    const ratio = Math.max(window.devicePixelRatio||1,1);
    sigPad = new SignaturePad(canvas, { backgroundColor:'rgba(255,255,255,1)', minWidth: ratio, maxWidth: Math.max(2, ratio*2) });
    currentAid = btn.getAttribute('data-aid');
    currentUuid = btn.getAttribute('data-uuid');
    isNew = btn.hasAttribute('data-new');
  });
  document.getElementById('sigClear').addEventListener('click', ()=>{ if(sigPad) sigPad.clear(); });
  document.getElementById('sigSave').addEventListener('click', ()=>{
    if (!sigPad || sigPad.isEmpty()) return;
    const data = sigPad.toDataURL('image/png');
    if (!isNew) {
      fetch('/?r=admin_signature_replace', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ aid: parseInt(currentAid||'0'), signature: data }) })
        .then(r=>r.json()).then(j=>{ showToast(j.ok, j.error); if (j.ok) refreshThumb(); });
    } else {
      const dInput = document.getElementById('sigDate');
      let d = dInput.value || '<?= date('Y-m-d') ?>';
      if (d && !/^\d{4}-\d{2}-\d{2}$/.test(d)) {
        const asDate = dInput.valueAsDate;
        if (asDate && !isNaN(asDate.getTime())) {
          d = new Date(asDate.getTime() - asDate.getTimezoneOffset()*60000).toISOString().slice(0,10);
        } else {
          const m = d.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
          if (m) d = `${m[3]}-${m[2]}-${m[1]}`;
        }
      }
      fetch('/?r=admin_signature_new', { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ uuid: currentUuid, date: d, signature: data }) })
        .then(r=>r.json()).then(j=>{ showToast(j.ok, j.error); if (j.ok) window.location.reload(); });
    }
  });
  function showToast(ok, msg){ const el=document.getElementById('sigToast'); el.className='toast align-items-center '+(ok?'text-bg-success':'text-bg-danger')+' border-0'; el.querySelector('.toast-body').textContent = ok ? 'Saved' : (msg||'Error'); new bootstrap.Toast(el).show(); }
  function refreshThumb(){ const img=document.querySelector('img[src^="/signature.php?aid='+currentAid+'"]'); if(img){ img.src='/signature.php?aid='+currentAid+'&t='+(Date.now()); } }
  function calibrateCanvas(canvas){ const ratio=Math.max(window.devicePixelRatio||1,1); const data=sigPad?sigPad.toData():null; const rect=canvas.getBoundingClientRect(); canvas.width=Math.floor(rect.width*ratio); canvas.height=Math.floor(rect.height*ratio); const ctx=canvas.getContext('2d'); ctx.scale(ratio, ratio); if(sigPad){ sigPad.clear(); if(data&&data.length) sigPad.fromData(data); } }
  window.addEventListener('resize', ()=>{ const canvas=document.getElementById('sigCanvas'); if(canvas && sigPad){ calibrateCanvas(canvas); } });
})();
</script>
<script>
(function(){
  const form = document.querySelector('form[action="/"]');
  const btn = document.getElementById('filterBtn');
  const spin = btn?.querySelector('.spinner-border');
  if (form && btn && spin) {
    form.addEventListener('submit', ()=>{ spin.style.display='inline-block'; btn.setAttribute('disabled','disabled'); });
  }
})();
</script>
</body>
</html>