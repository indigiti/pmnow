<?php
$media = $story['media'][0] ?? null;
$category = $story['categories'][0]['name'] ?? ucfirst($story['type'] ?? 'News');
$location = $story['locations'][0]['name'] ?? 'Pune';
$path = pm_story_path($story);
$sourceName = $story['source']['name'] ?? ($story['author'] ?? 'Pune Mirror');
$provider = strtoupper((string)($story['source']['provider'] ?? 'desk'));
$isSaved = in_array($story['id'], $userState['bookmark_story_ids'] ?? [], true);
$isFollowed = in_array($story['id'], $userState['follow_story_ids'] ?? [], true);
$variant = $cardVariant ?? 'standard';
$filterParts = [$story['type'] ?? '', $category, $location];
foreach (($story['categories'] ?? []) as $row) $filterParts[] = (string)($row['name'] ?? $row['slug'] ?? '');
foreach (($story['locations'] ?? []) as $row) $filterParts[] = (string)($row['name'] ?? $row['slug'] ?? '');
$filterTokens = strtolower(trim(implode(' ', array_filter($filterParts))));
?>
<article class="story <?=pm_e($variant)?>" data-story-id="<?= pm_e($story['id']) ?>" data-filter="<?=pm_e($filterTokens)?>">
  <a href="<?= pm_e($path) ?>" class="tapread"><img class="media" src="<?= pm_e(pm_media($media)) ?>" alt="<?=pm_e($story['headline']??'Pune news image')?>" loading="<?= $variant==='lead'?'eager':'lazy' ?>"></a>
  <div class="storycopy">
    <span class="flag <?= ($story['type']??'')==='article'?'outline':'' ?>"><?= pm_e(($story['type']??'')==='live'?'LIVE':$category) ?></span>
    <a href="<?= pm_e($path) ?>"><h2 class="h1"><?= pm_e($story['headline']) ?></h2></a>
    <p class="dek"><?= pm_e($story['deck'] ?? '') ?></p>
    <div class="meta"><span class="sourcepill"><?= pm_e($provider) ?> · <?= pm_e($sourceName) ?></span><span class="locpill"><?=pm_icon('pin','sm')?> <?= pm_e($location) ?></span><span><?= pm_e($story['display_time'] ?? 'Updated recently') ?></span></div>
    <div class="story-actions">
      <div class="engagement" aria-label="Story engagement"><span><?=pm_icon('heart','sm')?> <?= pm_e($story['likes'] ?? '2.4K') ?></span><span><?=pm_icon('comment','sm')?> <?= pm_e($story['comments'] ?? '143') ?></span></div>
      <div class="action-group">
        <button class="act" type="button" data-share-url="<?=pm_e($path)?>" data-share-title="<?=pm_e($story['headline'])?>" aria-label="Share story"><?=pm_icon('share','sm')?> <span>Share</span></button>
        <button class="act <?= $isSaved?'on':'' ?>" type="button" data-bookmark="<?= pm_e($story['id']) ?>" aria-label="<?= $isSaved?'Remove bookmark':'Save story' ?>"><?=pm_icon('bookmark','sm')?> <span><?= $isSaved?'Saved':'Save' ?></span></button>
        <?php if (in_array($story['type'] ?? '', ['live','developing'], true)): ?>
          <button class="act <?= $isFollowed?'on':'' ?>" type="button" data-follow="<?= pm_e($story['id']) ?>" data-follow-label="Follow"><?=pm_icon('follow','sm')?> <span><?= $isFollowed?'Following':'Follow' ?></span></button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</article>
