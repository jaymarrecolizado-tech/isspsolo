<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Export</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Export</h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_registrants">Registrants</a>
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_attendance">Attendance</a>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Registrants CSV</h2>
        <p class="text-muted">Google Sheets-compatible headers.</p>
        <a class="btn btn-primary" href="/?r=export_registrants_csv">Download</a>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Attendance CSV</h2>
        <p class="text-muted">Includes signature paths.</p>
        <a class="btn btn-primary" href="/?r=export_attendance_csv">Download</a>
      </div></div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card"><div class="card-body">
        <h2 class="h6">Sample CSV Template</h2>
        <p class="text-muted">Headers for registrants import.</p>
        <a class="btn btn-outline-secondary" href="/?r=sample_csv">Download Template</a>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>