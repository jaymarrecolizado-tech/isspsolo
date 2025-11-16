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
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Registrants</h1>
    <div><a class="btn btn-outline-secondary btn-sm" href="/?r=admin_logout">Logout</a></div>
  </div>
  <form method="get" action="/">
    <input type="hidden" name="r" value="admin_registrants">
    <div class="row g-2">
      <div class="col-12 col-md-3"><input name="q" value="<?= htmlspecialchars($q??'', ENT_QUOTES) ?>" placeholder="Name" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="agency" value="<?= htmlspecialchars($agency??'', ENT_QUOTES) ?>" placeholder="Agency" class="form-control"></div>
      <div class="col-12 col-md-3"><input name="sector" value="<?= htmlspecialchars($sector??'', ENT_QUOTES) ?>" placeholder="Sector" class="form-control"></div>
      <div class="col-12 col-md-3"><button class="btn btn-primary w-100">Search</button></div>
    </div>
  </form>
  <div class="mt-3">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>#</th><th>Name</th><th>Agency</th><th>Sector</th><th>Email</th><th>UUID</th></tr>
        </thead>
        <tbody>
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