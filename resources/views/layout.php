<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<meta name="app-base-path" content="<?=pm_e(pm_base_path())?>"><meta name="csrf-token" content="<?= pm_e(\PuneMirror\Core\Session::csrfToken()) ?>">
<title>Pune Mirror Now</title>
<link rel="stylesheet" href="<?=pm_e(pm_asset('resources/css/app.css','/assets/app.css'))?>">
</head>
<body>
<div class="app-shell"><?= $content ?></div>
<?php if (($activeNav ?? '') !== 'watch'): require $viewRoot . '/components/bottom-nav.php'; endif; ?>
<div id="toast" class="toast">Saved</div>
<script src="<?=pm_e(pm_asset('resources/js/app.js','/assets/app.js'))?>" defer></script>
</body>
</html>
