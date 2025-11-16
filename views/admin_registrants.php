<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrants</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/app.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container"><a class="navbar-brand" href="/">Event Registration</a>
    <div class="ms-auto btn-group"><a class="btn btn-outline-light btn-sm" href="/?r=admin_registrants">Registrants</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_attendance">Attendance</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_import">Import</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_export">Export</a><a class="btn btn-outline-light btn-sm" href="/?r=admin_report">Report</a></div>
  </div>
</nav>
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Registrants</h1>
    <div><a class="btn btn-outline-secondary btn-sm" href="/?r=admin_logout">Logout</a></div>
  </div>
  <form method="get" action="/">
    <input type="hidden" name="r" value="admin_registrants">
    <div class="row g-2">
      <div class="col-12 col-md-3"><input name="q" value="<?= htmlspecialchars($q??'', ENT_QUOTES) ?>" placeholder="Name" class="form-control"></div>
      <div class="col-12 col-md-3">
        <div class="input-group">
          <input name="agency" list="agencyList" value="<?= htmlspecialchars($agency??'', ENT_QUOTES) ?>" placeholder="Agency" class="form-control">
          <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#agencyExpand">Expand</button>
        </div>
      </div>
      <div class="col-12 col-md-3">
        <div class="input-group">
          <input name="sector" list="sectorList" value="<?= htmlspecialchars($sector??'', ENT_QUOTES) ?>" placeholder="Sector" class="form-control">
          <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#sectorExpand">Expand</button>
        </div>
      </div>
      <div class="col-12 col-md-3"><button class="btn btn-primary w-100">Search</button></div>
    </div>
  </form>
  <datalist id="agencyList">
    <?php foreach (($agenciesList??[]) as $a): ?><option value="<?= htmlspecialchars($a['agency'], ENT_QUOTES) ?>"></option><?php endforeach; ?>
  </datalist>
  <datalist id="sectorList">
    <?php foreach (($sectorsList??[]) as $s): ?><option value="<?= htmlspecialchars($s['sector'], ENT_QUOTES) ?>"></option><?php endforeach; ?>
  </datalist>
  <div class="collapse mt-2" id="agencyExpand">
    <select class="form-select scroll-select" size="6">
      <?php foreach (($agenciesList??[]) as $a): ?><option><?= htmlspecialchars($a['agency'], ENT_QUOTES) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="collapse mt-2" id="sectorExpand">
    <select class="form-select scroll-select" size="6">
      <?php foreach (($sectorsList??[]) as $s): ?><option><?= htmlspecialchars($s['sector'], ENT_QUOTES) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="mt-3">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>#</th><th>Name</th><th>Agency</th><th>Sector</th><th>Email</th><th>UUID</th></tr>
        </thead>
        <tbody>
        <?php if (!($rows??[])) : ?>
          <tr><td colspan="6" class="text-center text-muted">No registrants found</td></tr>
        <?php endif; ?>
        <?php foreach (($rows??[]) as $i=>$r): ?>
          <tr>
            <td><?= ($i+1) + (($page-1)*20) ?></td>
            <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['agency']??'', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['sector']??'', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($r['email']??'', ENT_QUOTES) ?></td>
            <td><code><?= htmlspecialchars($r['uuid'], ENT_QUOTES) ?></code></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-2">
    <a class="btn btn-outline-secondary btn-sm" href="/?r=export_registrants_csv&q=<?= urlencode($q??'') ?>&agency=<?= urlencode($agency??'') ?>&sector=<?= urlencode($sector??'') ?>">Quick Export Current View</a>
  </div>
  <nav>
    <ul class="pagination">
      <?php for ($p=1; $p<=($pages??1); $p++): ?>
        <li class="page-item <?= $p==($page??1)?'active':'' ?>"><a class="page-link" href="/?r=admin_registrants&page=<?= $p ?>&q=<?= urlencode($q??'') ?>&agency=<?= urlencode($agency??'') ?>&sector=<?= urlencode($sector??'') ?>"><?= $p ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
</body>
</html>