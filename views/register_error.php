<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registration Error</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="alert alert-danger mb-3"><?= htmlspecialchars($error ?? 'An error occurred', ENT_QUOTES) ?></div>
  <a class="btn btn-secondary" href="/">Back</a>
  <a class="btn btn-primary ms-2" href="/?r=register">Try Again</a>
  <div class="mt-3 text-muted">If the email already exists, use a different email or contact support.</div>
  </div>
</body>
</html>