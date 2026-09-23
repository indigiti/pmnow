<?php
$media = $story['media'][0] ?? null;
$category = $story['categories'][0]['name'] ?? ucfirst($story['type'] ?? 'News');
$location = $story['locations'][0]['name'] ?? 'Pune';
$path = pm_story_path($story);
$sourceName = $story['source']['name'] ?? ($story['author'] ?? 'Pune Mirror');
$provider = strtoupper((string)($story['source']['provider'] ?? 'desk'));
$isSaved = in_array($story['id'], $userState['bookmark_story_ids'] ?? [], true);
$isFollowed = in_array($story['id'], $userState['follow_story_ids'] ?? [], true);
?>
<article class="story hero" data-story-id="<?= pm_e($story['id']) ?>">
  <a href="<?= pm_e($path) ?>" class="tapread"><img class="media" src="<?= pm_e(pm_media($media)) ?>" alt=""></a>
  <div class="storycopy">
    <span class="flag <?= ($story['type']??'')==='article'?'outline':'' ?>"><?= pm_e($story['type']==='live'?'LIVE':$category) ?></span>
    <a href="<?= pm_e($path) ?>"><h2 class="h1"><?= pm_e($story['headline']) ?></h2></a>
    <p class="dek"><?= pm_e($story['deck'] ?? '') ?></p>
    <div class="meta"><span class="sourcepill"><?= pm_e($provider) ?> · <?= pm_e($sourceName) ?></span><span class="locpill">⌖ <?= pm_e($location) ?></span><span><?= pm_e($story['display_time'] ?? 'Updated recently') ?></span></div>
    <div class="actions">
      <button class="act" type="button" data-toast="Liked">♡ <span><?= pm_e($story['likes'] ?? '2.4K') ?></span></button>
      <button class="act" type="button" data-toast="Comments open in M4">◌ <span><?= pm_e($story['comments'] ?? '143') ?></span></button>
      <button class="act" type="button" data-toast="Share link copied">↗ Share</button>
      <button class="act <?= $isSaved?'on':'' ?>" type="button" data-bookmark="<?= pm_e($story['id']) ?>"><?= $isSaved?'★':'☆' ?> Save</button>
      <?php if (in_array($story['type'] ?? '', ['live','developing'], true)): ?>
        <button class="act <?= $isFollowed?'on':'' ?>" type="button" data-follow="<?= pm_e($story['id']) ?>">◉ Follow</button>
      <?php endif; ?>
    </div>
  </div>
</article>
