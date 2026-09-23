<?php $saved=in_array($story['id'],$userState['bookmark_story_ids']??[],true); ?>
<header class="detailbar"><button class="iconbtn" data-back>←</button><div class="title">Photo story</div><button class="iconbtn" data-bookmark="<?= pm_e($story['id']) ?>"><?= $saved?'★':'☆' ?></button></header>
<div class="galleryviewport" data-gallery>
  <div class="gallerytrack">
  <?php foreach ($story['media'] as $media): ?><div class="gslide"><img src="<?= pm_e(pm_media($media)) ?>" alt=""><span class="gshade"></span></div><?php endforeach; ?>
  </div>
  <span class="gcounter"><b data-gallery-index>1</b> / <?= count($story['media']) ?></span>
  <div class="gdots"><?php foreach($story['media'] as $i=>$x): ?><span class="gdot <?= $i===0?'active':'' ?>"></span><?php endforeach; ?></div>
  <span class="swipehint">Swipe for more →</span>
</div>
<main class="gallerybody">
  <h1><?= pm_e($story['headline']) ?></h1><p class="dek"><?= pm_e($story['deck']??'') ?></p>
  <div class="byline"><div class="person"><div class="avatar">PM</div><div><b><?= pm_e($story['author']??'Pune Mirror Desk') ?></b><br><small>⌖ <?= pm_e($story['locations'][0]['name']??'Pune') ?></small></div></div><small>Updated today</small></div>
  <div class="galleryactions"><button class="act" data-toast="Liked">♡<span>Like</span></button><button class="act" data-toast="Comments ready for M4">◌<span>Comment</span></button><button class="act" data-toast="Share link copied">↗<span>Share</span></button><button class="act <?= $saved?'on':'' ?>" data-bookmark="<?= pm_e($story['id']) ?>">☆<span>Save</span></button></div>
  <div class="bodycopy"><p><?= pm_e($story['body']??'') ?></p></div>
</main>
