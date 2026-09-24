<?php require $viewRoot . '/components/header.php'; ?>
<div class="channels" aria-label="News channels">
  <?php foreach (['For You','Pune','PCMC','Traffic','Crime','Civic','Food','Sports'] as $i=>$channel): ?>
    <button class="chip <?= $i===0?'active':'' ?>" type="button" data-channel="<?= pm_e(strtolower(str_replace(' ','-',$channel))) ?>"><?= pm_e($channel) ?></button>
  <?php endforeach; ?>
</div>
<?php $homePrefs=array_values(array_filter(array_merge($userState['user']['preferences']['areas']??[],$userState['user']['preferences']['channels']??[]))); ?>
<div class="personalization-strip"><span><b>For You</b><?= $homePrefs?' · '.pm_e(implode(' · ',array_slice($homePrefs,0,3))):' · Pune' ?></span><div class="personalization-links"><?php if(!empty($userState['user']['preferences']['areas'])): ?><a href="<?=pm_e(pm_url('/near-you'))?>">Near You</a><?php endif; ?><a href="<?=pm_e(pm_url('/profile'))?>">Tune My Pune</a></div></div>
<main id="feed" class="scroll withnav feed">
  <?php foreach ($stories as $i=>$story): $cardVariant=$i===0?'lead':($i<3?'standard':'compact'); require $viewRoot . '/components/story-card.php'; endforeach; unset($cardVariant); ?>
</main>
