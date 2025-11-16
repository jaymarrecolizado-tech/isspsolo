<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Import History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5">Import History</h1>
    <div class="btn-group">
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_import">Import</a>
      <a class="btn btn-outline-secondary btn-sm" href="/?r=admin_registrants">Registrants</a>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-striped">
      <thead>
        <tr><th>#</th><th>File</th><th>Action</th><th>Strategy</th><th>Admin</th><th>Timestamp</th></tr>
      </thead>
      <tbody>
        <?php foreach (($rows??[]) as $i=>$r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['file_name']??'', ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($r['action']??'', ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($r['duplicate_strategy']??'', ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)$r['admin_id'], ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars($r['created_at']??'', ENT_QUOTES) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>