<?php $m=$story['media'][0]??null; $followed=in_array($story['id'],$userState['follow_story_ids']??[],true); ?>
<header class="page-head"><button class="iconbtn" data-back>←</button><h1>Live Updates</h1><span class="spacer"></span><span class="flag">● LIVE</span></header>
<img class="article-hero" src="<?= pm_e(pm_media($m)) ?>" alt="">
<section class="storycopy"><span class="flag">LIVE</span><h1 class="h2"><?= pm_e($story['headline']) ?></h1><p class="dek"><?= pm_e($story['deck']??'') ?></p></section>
<?php if ($live): ?>
<div class="live-controls"><button class="btn red" data-follow="<?= pm_e($story['id']) ?>"><?= $followed?'Following':'Follow live' ?></button><button class="btn outline" data-live-demo="<?= pm_e($live['id']) ?>">+ Demo update</button></div>
<main id="liveTimeline" data-live-id="<?= pm_e($live['id']) ?>" data-last="<?= pm_e($live['updates'][0]['published_at']??'') ?>" style="padding:4px 10px 110px">
  <?php foreach($live['updates']??[] as $u): ?><article class="live-card"><div class="live-time"><?= pm_e(date('H:i', strtotime($u['published_at']??'now'))) ?> · <?= pm_e($u['type']??'Update') ?></div><h3><?= pm_e($u['headline']??'Update') ?></h3><p><?= pm_e($u['body']??'') ?></p></article><?php endforeach; ?>
</main>
<?php else: ?><div class="empty">No active live timeline for this story.</div><?php endif; ?>
