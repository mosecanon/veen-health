<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Sign In') ?> — Veen Health</title>
  <link rel="icon" type="image/x-icon" href="<?= asset('img/favicon.ico') ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('img/favicon-16x16.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('img/apple-touch-icon.png') ?>">
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
  <?= $this->slot('content') ?>
  <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
