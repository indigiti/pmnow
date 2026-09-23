<?php $m=$story['media'][0]??null; $followed=in_array($story['id'],$userState['follow_story_ids']??[],true); ?>
<header class="page-head"><button class="iconbtn" data-back>←</button><h1>Developing Story</h1><span class="spacer"></span><button class="iconbtn" data-toast="Share link copied">↗</button></header>
<img class="article-hero" src="<?= pm_e(pm_media($m)) ?>" alt="">
<section class="storycopy"><span class="flag">DEVELOPING</span><h1 class="h1"><?= pm_e($story['headline']) ?></h1><p class="dek"><?= pm_e($story['deck']??'') ?></p></section>
<div class="live-controls"><button class="btn red <?= $followed?'on':'' ?>" data-follow="<?= pm_e($story['id']) ?>"><?= $followed?'Following':'Follow story' ?></button><button class="btn outline" data-jump-latest>Jump to latest</button></div>
<main id="developingTimeline" style="padding:4px 10px 110px">
  <?php foreach($story['updates']??[] as $u): ?><article class="live-card"><div class="live-time"><?= pm_e(date('H:i', strtotime($u['published_at']??'now'))) ?></div><h3><?= pm_e($u['headline']??'Update') ?></h3><p><?= pm_e($u['body']??'') ?></p></article><?php endforeach; ?>
</main>
