<?php
$adminPath=(string)(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'');
$nav=[
  ['/admin','home','Dashboard'],
  ['/admin/inbox','grid','Editorial Inbox'],
  ['/admin/sources','external','Sources'],
  ['/admin/jobs','follow','Jobs & Scheduler'],
  ['/admin/system','heart','System Health'],
  ['/admin/errors','bell','Error Center'],
  ['/admin/notifications','bell','Notifications'],
];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="app-base-path" content="<?=pm_e(pm_base_path())?>"><meta name="csrf-token" content="<?=pm_e(\PuneMirror\Core\Session::csrfToken())?>"><meta name="theme-color" content="#15171b"><title>Pune Mirror Newsroom</title><link rel="stylesheet" href="<?=pm_e(pm_asset('resources/css/app.css','/assets/app.css'))?>"></head>
<body class="admin-body"><div class="admin-shell"><aside class="admin-sidebar"><a class="admin-brand" href="<?=pm_e(pm_url('/admin'))?>"><span>PUNE</span> MIRROR <small>NEWSROOM</small></a><nav>
<?php foreach($nav as [$path,$icon,$label]): $url=pm_url($path); $active=$adminPath===$url || ($path!=='/admin' && str_starts_with($adminPath,$url)); ?><a href="<?=pm_e($url)?>" <?=$active?'aria-current="page"':''?>><?=pm_icon($icon,'sm')?> <span><?=pm_e($label)?></span></a><?php endforeach; ?>
<a href="<?=pm_e(pm_url('/'))?>" target="_blank" rel="noopener"><?=pm_icon('external','sm')?> <span>Open Public App</span></a></nav>
<div class="admin-user"><strong><?=pm_e($adminUser['name']??$adminUser['email']??'Editor')?></strong><small><?=pm_e($adminUser['role']??'')?></small><button data-admin-logout>Sign out</button></div></aside><main class="admin-main"><?=$content?></main></div><script src="<?=pm_e(pm_asset('resources/js/admin.js','/assets/admin.js'))?>" defer></script></body></html>
