<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav utility-page">
  <section class="utility-entity-hero">
    <a class="pm-kicker" href="<?=pm_e(pm_url('/utility'))?>">Pune Utility</a>
    <div class="utility-entity-title"><div><span class="utility-kind"><?=pm_e(strtoupper((string)($entity['kind']??'utility')))?></span><?php if(($entity['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'): require $viewRoot.'/components/sample-badge.php'; endif; ?><h1><?=pm_e(trim((string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu','',(string)($entity['name']??'Utility'))??'')))?></h1></div><button class="btn <?=$isFollowed?'red':''?>" type="button" data-utility-follow="<?=pm_e($entity['id']??'')?>"><span><?=$isFollowed?'Following':'Follow updates'?></span></button></div>
    <p><?=pm_e(($entity['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'?str_ireplace('DEMO','Sample',(string)($entity['authority']??'')):(string)($entity['authority']??''))?><?php if(!empty($entity['areas'])): ?> · <?=pm_e(implode(' · ',$entity['areas']))?><?php endif; ?></p>
    <?php if(!empty($entity['source_label'])): ?><div class="utility-source"><span>Primary source: <?=pm_e(($entity['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'?str_ireplace('DEMO','Sample',(string)($entity['source_label']??'')):(string)($entity['source_label']??''))?></span><?php if(!empty($entity['source_url'])): ?><a href="<?=pm_e($entity['source_url'])?>" target="_blank" rel="noopener noreferrer">Source site</a><?php endif; ?></div><?php endif; ?>
  </section>
  <section class="utility-feed">
    <?php if(!$updates): ?><div class="empty">No published updates for this utility entity yet.</div><?php endif; ?>
    <?php foreach($updates as $update): ?>
      <article class="utility-card severity-<?=pm_e($update['severity']??'info')?>">
        <div class="utility-card-head"><span class="utility-severity"><?=pm_e(strtoupper((string)($update['severity']??'info')))?></span><span class="status"><?=pm_e($update['status']??'active')?></span></div>
        <?php if(($update['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'): require $viewRoot.'/components/sample-badge.php'; endif; ?>
        <h2><?=pm_e(trim((string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu','',(string)($update['title']??'Utility update'))??'')))?></h2>
        <?php if(!empty($update['summary'])): ?><p><?=pm_e($update['summary'])?></p><?php endif; ?>
        <div class="utility-meta"><span><?=pm_icon('pin','sm')?> <?=pm_e($update['area']??'Pune')?></span><span>Published <?=pm_e($update['published_at']??'')?></span><?php if(($update['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'): ?><span>Illustrative · Unverified advisory</span><?php else: ?><span>Verified <?=pm_e($update['verified_at']??'')?></span><?php endif; ?></div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
