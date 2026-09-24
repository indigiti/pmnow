<?php $m=$story['media'][0]??null; $saved=in_array($story['id'],$userState['bookmark_story_ids']??[],true); $path=pm_story_path($story); ?>
<header class="page-head"><button class="iconbtn" type="button" data-back aria-label="Back"><?=pm_icon('arrow-left')?></button><h1>Story</h1><span class="spacer"></span><button class="iconbtn <?= $saved?'on':'' ?>" type="button" data-bookmark="<?= pm_e($story['id']) ?>" aria-label="<?= $saved?'Remove bookmark':'Save story' ?>"><?=pm_icon('bookmark')?></button></header>
<img class="article-hero" src="<?= pm_e(pm_media($m)) ?>" alt="<?=pm_e($story['headline']??'Pune news image')?>">
<main class="article-wrap">
  <span class="flag outline"><?= pm_e($story['categories'][0]['name']??'Pune') ?></span>
  <h1><?= pm_e($story['headline']) ?></h1>
  <p class="lead"><?= pm_e($story['deck']??'') ?></p>
  <div class="meta"><span>By <?= pm_e($story['author']??'Pune Mirror Desk') ?></span><span><?=pm_icon('pin','sm')?> <?= pm_e($story['locations'][0]['name']??'Pune') ?></span><span><?=pm_e($story['display_time']??'Updated recently')?></span></div>
  <div class="article-actions">
    <button class="act" type="button" data-share-url="<?=pm_e($path)?>" data-share-title="<?=pm_e($story['headline'])?>"><?=pm_icon('share','sm')?> <span>Share</span></button>
    <button class="act <?= $saved?'on':'' ?>" type="button" data-bookmark="<?= pm_e($story['id']) ?>"><?=pm_icon('bookmark','sm')?> <span><?= $saved?'Saved':'Save' ?></span></button>
  </div>
  <div class="article-text"><?php foreach (preg_split('/\n\n+/', $story['body']??'') as $p): ?><p><?= pm_e($p) ?></p><?php endforeach; ?></div>
</main>
