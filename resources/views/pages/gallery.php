<?php $saved=in_array($story['id'],$userState['bookmark_story_ids']??[],true); $path=pm_story_path($story); ?>
<header class="detailbar"><button class="iconbtn" type="button" data-back aria-label="Back"><?=pm_icon('arrow-left')?></button><div class="title">Photo story</div><button class="iconbtn <?= $saved?'on':'' ?>" type="button" data-bookmark="<?= pm_e($story['id']) ?>" aria-label="<?= $saved?'Remove bookmark':'Save gallery' ?>"><?=pm_icon('bookmark')?></button></header>
<div class="galleryviewport" data-gallery>
  <div class="gallerytrack">
  <?php foreach ($story['media'] as $media): ?><div class="gslide"><img src="<?= pm_e(pm_media($media)) ?>" alt="<?=pm_e($story['headline']??'Pune photo story')?>"><span class="gshade"></span></div><?php endforeach; ?>
  </div>
  <span class="gcounter"><b data-gallery-index>1</b> / <?= count($story['media']) ?></span>
  <div class="gdots" aria-hidden="true"><?php foreach($story['media'] as $i=>$x): ?><span class="gdot <?= $i===0?'active':'' ?>"></span><?php endforeach; ?></div>
  <span class="swipehint">Swipe for more →</span>
</div>
<main class="gallerybody">
  <span class="pm-kicker">Photo story</span>
  <h1><?= pm_e($story['headline']) ?></h1><p class="dek"><?= pm_e($story['deck']??'') ?></p>
  <div class="byline"><div class="person"><div class="avatar">PM</div><div><b><?= pm_e($story['author']??'Pune Mirror Desk') ?></b><br><small><?=pm_icon('pin','sm')?> <?= pm_e($story['locations'][0]['name']??'Pune') ?></small></div></div><small><?=pm_e($story['display_time']??'Updated recently')?></small></div>
  <div class="galleryactions"><span class="act"><?=pm_icon('heart','sm')?><span><?=pm_e($story['likes']??'—')?></span></span><span class="act"><?=pm_icon('comment','sm')?><span><?=pm_e($story['comments']??'—')?></span></span><button class="act" type="button" data-share-url="<?=pm_e($path)?>" data-share-title="<?=pm_e($story['headline'])?>"><?=pm_icon('share','sm')?><span>Share</span></button><button class="act <?= $saved?'on':'' ?>" type="button" data-bookmark="<?= pm_e($story['id']) ?>"><?=pm_icon('bookmark','sm')?><span><?= $saved?'Saved':'Save' ?></span></button></div>
  <div class="bodycopy"><p><?= pm_e($story['body']??'') ?></p></div>
</main>
