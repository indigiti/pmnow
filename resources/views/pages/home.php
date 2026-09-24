<?php require $viewRoot . '/components/header.php'; ?>
<div class="channels" aria-label="News channels">
  <?php foreach (['For You','Pune','PCMC','Traffic','Crime','Civic','Food','Sports'] as $i=>$channel): ?>
    <button class="chip <?= $i===0?'active':'' ?>" type="button" data-channel="<?= pm_e(strtolower(str_replace(' ','-',$channel))) ?>"><?= pm_e($channel) ?></button>
  <?php endforeach; ?>
</div>
<main id="feed" class="scroll withnav feed">
  <?php foreach ($stories as $i=>$story): $cardVariant=$i===0?'lead':($i<3?'standard':'compact'); require $viewRoot . '/components/story-card.php'; endforeach; unset($cardVariant); ?>
</main>
