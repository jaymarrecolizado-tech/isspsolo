<?php
declare(strict_types=1);

$token = function_exists('csrf_token') ? csrf_token() : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Scan & Sign</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/app.css" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
  <meta name="csrf" content="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
  <style>
    #reader { width: 100%; max-width: 480px; margin: 0 auto; }
    #sigCanvas { border: 1px solid #ccc; width: 100%; height: 240px; touch-action: none; }
  </style>
  </head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container"><a class="navbar-brand" href="/">Event Registration</a>
    <div class="ms-auto"><a class="btn btn-outline-light btn-sm" href="/?r=scan">Scan</a> <a class="btn btn-outline-light btn-sm ms-2" href="/?r=admin_login">Admin</a></div>
  </div>
</nav>
<div class="container py-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/">Home</a></li>
      <li class="breadcrumb-item active" aria-current="page">Scan</li>
    </ol>
  </nav>
  <h1 class="h5 mb-3">Scan QR and Capture Signature</h1>
  <div id="reader" class="mb-3"></div>
  <div id="insecureHint" class="alert alert-warning" style="display:none">
    Camera access requires HTTPS or localhost. Use the file fallback below or open the app via HTTPS.
  </div>
  <div id="fileFallback" class="mb-3" style="display:none">
    <label class="form-label">Scan from image file</label>
    <input type="file" id="qrFile" accept="image/*" class="form-control">
    <div class="form-text">Upload a photo/screenshot of a QR code.</div>
  </div>
  <div class="position-fixed top-0 end-0 p-3" style="z-index:1055">
    <div id="saveToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">Attendance saved</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
  <div class="card mb-3" id="participantCard" style="display:none">
    <div class="card-body">
      <div id="pinfo" class="mb-2"></div>
      <canvas id="sigCanvas"></canvas>
      <div class="mt-2 d-flex gap-2">
        <button id="calibrateBtn" class="btn btn-outline-secondary">Calibrate</button>
        <button id="clearBtn" class="btn btn-outline-secondary">Clear</button>
        <button id="saveBtn" class="btn btn-primary">Save Attendance</button>
      </div>
      <div id="status" class="mt-2"></div>
    </div>
  </div>
  <a class="btn btn-secondary" href="/">Back</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf"]').getAttribute('content');
let currentUuid = null;
let sigPad;
let calibrated = false;
let qrScanner;

function startScan() {
  const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
  const hint = document.getElementById('insecureHint');
  const fallback = document.getElementById('fileFallback');
  const qrRegion = document.getElementById('reader');
  if (!isSecure) {
    hint.style.display = 'block';
    fallback.style.display = 'block';
    enableFileFallback();
    return;
  }
  qrScanner = new Html5Qrcode(qrRegion.id);
  Html5Qrcode.getCameras().then(devices => {
    const id = devices && devices.length ? devices[0].id : null;
    if (!id) { hint.style.display='block'; fallback.style.display='block'; enableFileFallback(); return; }
    qrScanner.start(id, { fps: 10, qrbox: 250 }, onScanSuccess).catch(() => {
      hint.style.display='block'; fallback.style.display='block'; enableFileFallback();
    });
  }).catch(() => {
    hint.style.display='block'; fallback.style.display='block'; enableFileFallback();
  });
}

function onScanSuccess(decodedText) {
  if (!decodedText) return;
  if (decodedText.startsWith('PART|')) {
    const uuid = decodedText.split('|')[1];
    if (qrScanner) qrScanner.stop().then(()=>{});
    fetch('?r=api_participant&uuid=' + encodeURIComponent(uuid))
      .then(r => r.json()).then(j => {
        if (j.participant) {
          currentUuid = j.participant.uuid;
          document.getElementById('participantCard').style.display = 'block';
          const p = j.participant;
          document.getElementById('pinfo').innerText = `${p.first_name} ${p.last_name} (${p.agency||''})`;
          const canvas = document.getElementById('sigCanvas');
          sigPad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255,255,255,1)',
            minWidth: Math.max((window.devicePixelRatio||1),1),
            maxWidth: Math.max((window.devicePixelRatio||1)*2,2)
          });
          calibrateCanvas(canvas);
        }
      });
  }
}

document.getElementById('calibrateBtn').addEventListener('click', () => {
  const canvas = document.getElementById('sigCanvas');
  calibrateCanvas(canvas);
});
document.getElementById('clearBtn').addEventListener('click', () => { if (sigPad) sigPad.clear(); });
document.getElementById('saveBtn').addEventListener('click', () => {
  if (!sigPad || sigPad.isEmpty() || !currentUuid) return;
  const data = sigPad.toDataURL('image/png');
  fetch('?r=attendance_submit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
    body: JSON.stringify({ uuid: currentUuid, signature: data })
  }).then(r => r.json()).then(j => {
    const el = document.getElementById('saveToast');
    el.className = 'toast align-items-center ' + (j.ok ? 'text-bg-success' : 'text-bg-danger') + ' border-0';
    el.querySelector('.toast-body').innerText = j.ok ? 'Attendance saved' : (j.error || 'Error');
    const t = new bootstrap.Toast(el);
    t.show();
    if (j.ok) {
      sigPad.clear();
      document.getElementById('participantCard').style.display = 'none';
      currentUuid = null;
      setTimeout(()=>{ startScan(); }, 300);
    }
  });
});

startScan();

function enableFileFallback(){
  const input = document.getElementById('qrFile');
  if (!input) return;
  input.addEventListener('change', async (e)=>{
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    try {
      const qr = new Html5Qrcode('reader');
      const text = await qr.scanFile(file, true);
      onScanSuccess(text);
      await qr.clear();
    } catch(err) {
      console.error(err);
      document.getElementById('status').textContent = 'Failed to read QR from file';
    }
  });
}

function calibrateCanvas(canvas){
  const ratio = Math.max(window.devicePixelRatio || 1, 1);
  const data = sigPad ? sigPad.toData() : null;
  const rect = canvas.getBoundingClientRect();
  canvas.width = Math.floor(rect.width * ratio);
  canvas.height = Math.floor(rect.height * ratio);
  const ctx = canvas.getContext('2d');
  ctx.scale(ratio, ratio);
  if (sigPad) {
    sigPad.clear();
    if (data && data.length) sigPad.fromData(data);
  }
  calibrated = true;
}

window.addEventListener('resize', () => {
  const canvas = document.getElementById('sigCanvas');
  if (canvas && sigPad) calibrateCanvas(canvas);
});
</script>
</body>
</html>