<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav utility-page">
  <section class="utility-hero">
    <div><span class="pm-kicker">Pune Utility</span><h1>City status</h1><p>Verified traffic, transit, weather, air, civic, outage and emergency updates published by the PMNow desk or connected providers.</p></div>
    <a class="btn" href="<?=pm_e(pm_url('/profile'))?>">Tune My Pune</a>
  </section>
  <nav class="utility-filters" aria-label="Utility categories">
    <a class="chip <?=empty($selectedKind)?'active':''?>" href="<?=pm_e(pm_url('/utility'))?>">All</a>
    <?php foreach($kinds as $kind): ?><a class="chip <?=$selectedKind===$kind?'active':''?>" href="<?=pm_e(pm_url('/utility?kind='.rawurlencode($kind)))?>"><?=pm_e(ucfirst($kind))?></a><?php endforeach; ?>
  </nav>
  <?php if($selectedArea): ?><div class="utility-context">Showing updates relevant to <b><?=pm_e($selectedArea)?></b>. <a href="<?=pm_e(pm_url('/utility'))?>">Clear area</a></div><?php endif; ?>
  <section class="utility-feed" data-utility-feed>
    <?php if(!$updates): ?><div class="empty utility-empty"><b>No active utility advisories are published.</b><span>PMNow will not infer live conditions when a verified utility update is unavailable.</span></div><?php endif; ?>
    <?php foreach($updates as $update): $entity=$update['entity']??[]; $followed=in_array((string)($entity['id']??''),$followedEntityIds,true); ?>
      <article class="utility-card severity-<?=pm_e($update['severity']??'info')?>" data-utility-update-id="<?=pm_e($update['id']??'')?>">
        <div class="utility-card-head"><span class="utility-kind"><?=pm_e(strtoupper((string)($entity['kind']??'utility')))?></span><span class="utility-severity"><?=pm_e(strtoupper((string)($update['severity']??'info')))?></span></div>
        <a href="<?=pm_e(pm_url((string)($update['path']??'/utility')))?>"><h2><?=pm_e($update['title']??'Utility update')?></h2></a>
        <?php if(!empty($update['summary'])): ?><p><?=pm_e($update['summary'])?></p><?php endif; ?>
        <div class="utility-meta"><span><?=pm_icon('pin','sm')?> <?=pm_e($update['area']??'Pune')?></span><span><?=pm_e($entity['name']??'Pune utility')?></span><span><?=pm_e($update['status']??'active')?></span><?php if(!empty($update['freshness']['state'])): ?><span>Freshness: <?=pm_e($update['freshness']['state'])?></span><?php endif; ?></div>
        <div class="utility-source"><span>Verified <?=pm_e($update['verified_at']??$update['published_at']??'')?></span><?php if(!empty($update['source_label'])): ?><span>Source: <?=pm_e($update['source_label'])?></span><?php endif; ?></div>
        <div class="utility-actions"><a class="act" href="<?=pm_e(pm_url((string)($update['path']??'/utility')))?>">Details</a><button class="act <?=$followed?'on':''?>" type="button" data-utility-follow="<?=pm_e($entity['id']??'')?>"><span><?=$followed?'Following':'Follow'?></span></button></div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
