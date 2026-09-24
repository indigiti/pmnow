<div class="scroll withnav">
<header class="page-head"><a class="iconbtn" href="<?=pm_e(pm_url('/'))?>" aria-label="Back to Home"><?=pm_icon('arrow-left')?></a><h1>Alerts</h1><div class="spacer"></div></header>
<section class="sect"><div class="secttitle"><div><span class="pm-kicker">Distribution</span><h2>Your updates</h2></div><a class="link" href="<?=pm_e(pm_url('/profile'))?>">Alert settings</a></div><div class="alert-summary"><span><?= count($notifications) ?> recent</span><span>Breaking · neighbourhoods · topics · briefings</span></div>
<?php if (!$notifications): ?><div class="empty">Follow a developing or live story to receive updates here.</div><?php endif; ?>
<div class="notification-list">
<?php foreach ($notifications as $n): ?><article class="notification-row <?= empty($n['read_at'])?'unread':'' ?>" data-notification-id="<?= pm_e($n['id']) ?>">
<div class="notification-dot"></div><div><small><?= pm_e(strtoupper(str_replace('_',' ',$n['type']??'update'))) ?></small><h3><?= pm_e($n['title']??'Update') ?></h3><p><?= pm_e($n['body']??'') ?></p><time><?= pm_e($n['created_at']??'') ?></time></div>
</article><?php endforeach; ?>
</div></section></div>
