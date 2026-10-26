<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error <?= e($code ?? 500) ?> — Veen Africa</title>
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
  <div class="error-page">
    <div>
      <div class="error-code"><?= e($code ?? 500) ?></div>
      <h2 style="margin: 12px 0;"><?= $code === 404 ? 'Page or Resource Not Found' : ($code === 403 ? 'Access Denied' : 'System Error') ?></h2>
      <p class="muted" style="max-width: 480px; margin: 0 auto 24px;">
        <?= e($message ?: 'The requested resource could not be found or you do not have permission.') ?>
      </p>
      <div style="display: flex; gap: 10px; justify-content: center;">
        <a href="<?= route('dashboard') ?>" class="btn btn-primary">Return to Dashboard</a>
        <a href="<?= route('login') ?>" class="btn btn-ghost">Sign In</a>
      </div>
    </div>
  </div>
</body>
</html>
