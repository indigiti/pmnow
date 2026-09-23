<?php $m=$story['media'][0]??null; $saved=in_array($story['id'],$userState['bookmark_story_ids']??[],true); ?>
<header class="page-head"><button class="iconbtn" data-back>←</button><h1>Story</h1><span class="spacer"></span><button class="iconbtn" data-bookmark="<?= pm_e($story['id']) ?>"><?= $saved?'★':'☆' ?></button></header>
<img class="article-hero" src="<?= pm_e(pm_media($m)) ?>" alt="">
<main class="article-wrap">
  <span class="flag outline"><?= pm_e($story['categories'][0]['name']??'Pune') ?></span>
  <h1><?= pm_e($story['headline']) ?></h1>
  <p class="lead"><?= pm_e($story['deck']??'') ?></p>
  <div class="meta"><span>By <?= pm_e($story['author']??'Pune Mirror Desk') ?></span><span>•</span><span>⌖ <?= pm_e($story['locations'][0]['name']??'Pune') ?></span></div>
  <div class="article-text"><?php foreach (preg_split('/\n\n+/', $story['body']??'') as $p): ?><p><?= pm_e($p) ?></p><?php endforeach; ?></div>
</main>
