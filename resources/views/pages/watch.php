<div class="watch-top"><b>PUNE MIRROR <span style="color:#ed1c24">NOW</span></b><a href="<?=pm_e(pm_url('/'))?>">✕</a></div>
<main class="reels">
<?php foreach($reels as $story): $m=$story['media'][0]??null; $saved=in_array($story['id'],$userState['bookmark_story_ids']??[],true); ?>
<section class="reel" data-reel><img src="<?= pm_e(pm_media($m)) ?>" alt=""><div class="reel-copy"><span class="flag">PUNE LIVE</span><a href="<?= pm_e(pm_story_path($story)) ?>"><h2><?= pm_e($story['headline']) ?></h2></a><p><?= pm_e($story['deck']??'') ?></p><small>⌖ <?= pm_e($story['locations'][0]['name']??'Pune') ?> · <?= pm_e($story['author']??'Pune Mirror Desk') ?></small></div><div class="reel-actions"><button data-toast="Liked">♡</button><button data-toast="Comments ready for M4">◌</button><button data-toast="Share link copied">↗</button><button class="<?= $saved?'on':'' ?>" data-bookmark="<?= pm_e($story['id']) ?>">☆</button></div></section>
<?php endforeach; ?>
</main>
<nav class="bottomnav" style="background:#080808e8;border-color:#222;color:#fff"><a class="navitem" href="<?=pm_e(pm_url('/'))?>">⌂<span>Home</span></a><a class="navitem" href="<?=pm_e(pm_url('/explore'))?>">⌕<span>Explore</span></a><span class="navitem active">▶<span>Watch</span></span><a class="navitem" href="<?=pm_e(pm_url('/profile'))?>">◎<span>Profile</span></a></nav>
