<?php $m=$story['media'][0]??null; $followed=in_array($story['id'],$userState['follow_story_ids']??[],true); $path=pm_story_path($story); ?>
<header class="page-head"><button class="iconbtn" type="button" data-back aria-label="Back"><?=pm_icon('arrow-left')?></button><h1>Developing</h1><span class="spacer"></span><button class="iconbtn" type="button" data-share-url="<?=pm_e($path)?>" data-share-title="<?=pm_e($story['headline'])?>" aria-label="Share story"><?=pm_icon('share')?></button></header>
<img class="article-hero" src="<?= pm_e(pm_media($m)) ?>" alt="<?=pm_e($story['headline']??'Developing Pune story')?>">
<section class="storycopy"><span class="flag">DEVELOPING</span><h1 class="h1"><?= pm_e($story['headline']) ?></h1><p class="dek"><?= pm_e($story['deck']??'') ?></p></section>
<div class="live-controls"><button class="btn red <?= $followed?'on':'' ?>" type="button" data-follow="<?= pm_e($story['id']) ?>"><?=pm_icon('follow','sm')?> <span><?= $followed?'Following':'Follow story' ?></span></button><button class="btn outline" type="button" data-jump-latest>Jump to latest</button></div>
<main id="developingTimeline" class="timeline-list">
  <?php foreach($story['updates']??[] as $u): ?><article class="live-card"><div class="live-time"><?= pm_e(date('H:i', strtotime($u['published_at']??'now'))) ?></div><h3><?= pm_e($u['headline']??'Update') ?></h3><p><?= pm_e($u['body']??'') ?></p></article><?php endforeach; ?>
</main>
