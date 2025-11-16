<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration Complete</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4 text-center">
  <h1 class="h4 mb-3">Registration Complete</h1>
  <p class="mb-3">Save your QR code for onsite scanning.</p>
  <div class="mb-3">
    <img src="/qrcode.php?uuid=<?= htmlspecialchars($participant['uuid'], ENT_QUOTES) ?>" alt="QR" class="img-fluid" style="max-width:300px">
  </div>
  <div class="mb-3">
    <a class="btn btn-outline-primary" href="/qrcode.php?uuid=<?= htmlspecialchars($participant['uuid'], ENT_QUOTES) ?>" download>Download QR</a>
  </div>
  <div class="mb-3">
    <a class="btn btn-secondary" href="/">Back</a>
  </div>
</div>
</body>
</html>