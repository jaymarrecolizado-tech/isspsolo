<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    img.sig { max-height: 64px; border: 1px solid #ddd; background: #fff; }
  </style>
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Attendance</h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_registrants">Registrants</a>
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_logout">Logout</a>
    </div>
  </div>
  <form method="get" action="/">
    <input type="hidden" name="r" value="admin_attendance">
    <div class="row g-2">
      <div class="col-12 col-md-3"><input name="date" value="<?= htmlspecialchars($date??'', ENT_QUOTES) ?>" placeholder="YYYY-MM-DD" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="agency" value="<?= htmlspecialchars($agency??'', ENT_QUOTES) ?>" placeholder="Agency" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="name" value="<?= htmlspecialchars($name??'', ENT_QUOTES) ?>" placeholder="Name" class="form-control"></div>
      <div class="col-12 col-md-3"><button class="btn btn-primary w-100">Filter</button></div>
    </div>
  </form>
  <div class="mt-3">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>#</th><th>Date</th><th>Time</th><th>Name</th><th>Agency</th><th>UUID</th><th>Signature</th></tr>
        </thead>
        <tbody>
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
</body>
</html>