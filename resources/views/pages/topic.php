<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav">
  <section class="topic-hero">
    <span class="pm-kicker"><?=pm_e($topicKind==='area'?'Neighbourhood':'Topic')?></span>
    <h1><?=pm_e($topic['name']??'Pune')?></h1>
    <p><?=pm_e($topicKind==='area'?'Latest verified reporting from this Pune neighbourhood.':'Latest Pune Mirror Now stories in this topic.')?></p>
  </section>
  <section class="feed">
    <?php if(!$stories): ?><div class="empty">No published stories here yet.</div><?php endif; ?>
    <?php foreach($stories as $i=>$story): $cardVariant=$i===0?'lead':($i<3?'standard':'compact'); require $viewRoot . '/components/story-card.php'; endforeach; unset($cardVariant); ?>
  </section>
</main>
