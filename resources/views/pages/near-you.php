<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav">
  <section class="near-you-hero">
    <span class="pm-kicker">My Pune</span>
    <h1>Near You</h1>
    <?php if($areas): ?>
      <p>Local stories from the neighbourhoods you chose in My Pune.</p>
      <div class="near-you-areas"><?php foreach($areas as $area): ?><a class="chip active" href="<?=pm_e(pm_url('/area/'.pm_slug((string)$area)))?>"><?=pm_e($area)?></a><?php endforeach; ?></div>
    <?php else: ?>
      <p>Choose neighbourhoods in My Pune to build a local feed around the places you care about.</p>
      <div class="near-you-areas"><a class="chip active" href="<?=pm_e(pm_url('/profile'))?>">Choose neighbourhoods</a></div>
    <?php endif; ?>
  </section>
  <section id="nearYouFeed" class="feed">
    <?php if(!$stories): ?><div class="empty"><?= $areas?'No recent stories match your selected neighbourhoods yet.':'Your Near You feed will appear here after you choose neighbourhoods.' ?></div><?php endif; ?>
    <?php foreach($stories as $i=>$story): $cardVariant=$i===0?'lead':($i<3?'standard':'compact'); require $viewRoot . '/components/story-card.php'; endforeach; unset($cardVariant); ?>
  </section>
</main>
