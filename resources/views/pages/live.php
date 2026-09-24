<?php $m=$story['media'][0]??null; $followed=in_array($story['id'],$userState['follow_story_ids']??[],true); ?>
<header class="page-head"><button class="iconbtn" type="button" data-back aria-label="Back"><?=pm_icon('arrow-left')?></button><h1>Live updates</h1><span class="spacer"></span><span class="flag">● LIVE</span></header>
<img class="article-hero" src="<?= pm_e(pm_media($m)) ?>" alt="<?=pm_e($story['headline']??'Live Pune story')?>">
<section class="storycopy"><span class="pm-kicker">Live · Pune</span><h1 class="h2"><?= pm_e($story['headline']) ?></h1><p class="dek"><?= pm_e($story['deck']??'') ?></p></section>
<?php if ($live): ?>
<div class="live-controls"><button class="btn red <?= $followed?'on':'' ?>" type="button" data-follow="<?= pm_e($story['id']) ?>"><?=pm_icon('follow','sm')?> <span><?= $followed?'Following':'Follow live' ?></span></button><?php if ((string)pm_env('APP_ENV','local') !== 'production'): ?><button class="btn outline" type="button" data-live-demo="<?= pm_e($live['id']) ?>">Demo update</button><?php endif; ?></div>
<main id="liveTimeline" class="timeline-list" data-live-id="<?= pm_e($live['id']) ?>" data-last="<?= pm_e($live['updates'][0]['published_at']??'') ?>">
  <?php foreach($live['updates']??[] as $u): ?><article class="live-card" data-update-id="<?=pm_e($u['id']??'')?>"><div class="live-time"><?= pm_e(date('H:i', strtotime($u['published_at']??'now'))) ?> · <?= pm_e($u['type']??'Update') ?></div><h3><?= pm_e($u['headline']??'Update') ?></h3><p><?= pm_e($u['body']??'') ?></p></article><?php endforeach; ?>
</main>
<?php else: ?><div class="empty">No active live timeline for this story.</div><?php endif; ?>
