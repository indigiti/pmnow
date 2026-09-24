<?php
$seo=$seo??[];
$title=(string)($seo['title']??'Pune Mirror Now');
$description=(string)($seo['description']??'Pune news and local updates from Pune Mirror Now.');
$canonical=(string)($seo['canonical']??pm_absolute_url('/'));
$image=(string)($seo['image']??pm_absolute_url('/media/home_rain.jpg'));
$robots=(string)($seo['robots']??'index,follow,max-image-preview:large');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<meta name="app-base-path" content="<?=pm_e(pm_base_path())?>">
<meta name="csrf-token" content="<?= pm_e(\PuneMirror\Core\Session::csrfToken()) ?>">
<meta name="push-vapid-key" content="<?=pm_e((string)pm_env('PUSH_VAPID_PUBLIC_KEY',''))?>">
<?php if(isset($story['id'])): ?><meta name="pm-story-id" content="<?=pm_e($story['id'])?>"><?php endif; ?>
<title><?=pm_e($title)?></title>
<meta name="description" content="<?=pm_e($description)?>">
<meta name="robots" content="<?=pm_e($robots)?>">
<link rel="canonical" href="<?=pm_e($canonical)?>">
<link rel="alternate" type="application/rss+xml" title="Pune Mirror Now RSS" href="<?=pm_e(pm_absolute_url('/rss.xml'))?>">
<meta property="og:site_name" content="Pune Mirror Now">
<meta property="og:title" content="<?=pm_e($title)?>">
<meta property="og:description" content="<?=pm_e($description)?>">
<meta property="og:type" content="<?=pm_e((string)($seo['type']??'website'))?>">
<meta property="og:url" content="<?=pm_e($canonical)?>">
<meta property="og:image" content="<?=pm_e($image)?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?=pm_e($title)?>">
<meta name="twitter:description" content="<?=pm_e($description)?>">
<meta name="twitter:image" content="<?=pm_e($image)?>">
<?php if(!empty($seo['json_ld'])): ?><script type="application/ld+json"><?=json_encode($seo['json_ld'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?></script><?php endif; ?>
<link rel="stylesheet" href="<?=pm_e(pm_asset('resources/css/app.css','/assets/app.css'))?>">
</head>
<body>
<div class="site-layout <?= ($activeNav ?? '') === 'watch' ? 'watch-layout' : '' ?>">
<?php if (($activeNav ?? '') !== 'watch'): ?>
  <aside class="desktop-rail" aria-label="Desktop navigation">
    <a class="desktop-brand" href="<?=pm_e(pm_url('/'))?>">
      <span class="desktop-brand-mark">PM</span>
      <span>Pune Mirror<small>NOW · LOCAL NEWS</small></span>
    </a>
    <nav class="desktop-nav">
      <a href="<?=pm_e(pm_url('/'))?>" <?=($activeNav??'')==='home'?'aria-current="page"':''?>><?=pm_icon('home')?> <span>Home</span></a>
      <a href="<?=pm_e(pm_url('/explore'))?>" <?=($activeNav??'')==='explore'?'aria-current="page"':''?>><?=pm_icon('search')?> <span>Explore</span></a>
      <a href="<?=pm_e(pm_url('/watch'))?>"><?=pm_icon('play')?> <span>Watch</span></a>
      <a href="<?=pm_e(pm_url('/notifications'))?>" <?=($activeNav??'')==='notifications'?'aria-current="page"':''?>><?=pm_icon('bell')?> <span>Alerts</span></a>
      <a href="<?=pm_e(pm_url('/profile'))?>" <?=($activeNav??'')==='profile'?'aria-current="page"':''?>><?=pm_icon('user')?> <span>My Pune</span></a>
    </nav>
    <div class="desktop-edition"><strong>Pune edition</strong><span>City, PCMC and neighbourhood reporting</span></div>
  </aside>
<?php endif; ?>
  <div class="app-shell"><?= $content ?></div>
<?php if (($activeNav ?? '') !== 'watch'): ?>
  <aside class="desktop-context" aria-label="Pune context">
    <div class="context-card">
      <div class="context-live">Pune Now</div>
      <h3>Local reporting, one feed</h3>
      <p>Traffic, civic, neighbourhood, developing and live coverage from the PMNow desk and connected sources.</p>
      <div class="context-links">
        <a href="<?=pm_e(pm_url('/category/traffic'))?>"><span>Traffic</span><?=pm_icon('arrow-right','sm')?></a>
        <a href="<?=pm_e(pm_url('/category/civic'))?>"><span>Civic</span><?=pm_icon('arrow-right','sm')?></a>
        <a href="<?=pm_e(pm_url('/category/events'))?>"><span>What’s on</span><?=pm_icon('arrow-right','sm')?></a>
      </div>
    </div>
    <div class="context-card">
      <h3>My Pune</h3>
      <p>Choose neighbourhoods and topics to shape your personal local-news stream.</p>
      <div class="context-links"><a href="<?=pm_e(pm_url('/profile'))?>"><span>Personalize My Pune</span><?=pm_icon('arrow-right','sm')?></a></div>
    </div>
  </aside>
<?php endif; ?>
</div>
<?php if (($activeNav ?? '') !== 'watch'): require $viewRoot . '/components/bottom-nav.php'; endif; ?>
<div id="toast" class="toast" role="status" aria-live="polite">Saved</div>
<script src="<?=pm_e(pm_asset('resources/js/app.js','/assets/app.js'))?>" defer></script>
</body>
</html>
