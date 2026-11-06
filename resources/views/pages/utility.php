<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav utility-page">
  <section class="utility-hero">
    <div><span class="pm-kicker">Pune Utility</span><h1>City status</h1><p>Source-labelled traffic, transit, weather, air, civic, outage and emergency updates. Sample advisories are clearly marked.</p></div>
    <a class="btn" href="<?=pm_e(pm_url('/profile'))?>">Tune My Pune</a>
  </section>
  <nav class="utility-filters" aria-label="Utility categories">
    <a class="chip <?=empty($selectedKind)?'active':''?>" href="<?=pm_e(pm_url('/utility'))?>">All</a>
    <?php foreach($kinds as $kind): ?><a class="chip <?=$selectedKind===$kind?'active':''?>" href="<?=pm_e(pm_url('/utility?kind='.rawurlencode($kind)))?>"><?=pm_e(ucfirst($kind))?></a><?php endforeach; ?>
  </nav>
  <?php if($selectedArea): ?><div class="utility-context">Showing updates relevant to <b><?=pm_e($selectedArea)?></b>. <a href="<?=pm_e(pm_url('/utility'))?>">Clear area</a></div><?php endif; ?>
  <section class="utility-feed" data-utility-feed>
    <?php if(!$updates): ?><div class="empty utility-empty"><b>No active utility advisories are published.</b><span>PMNow will not infer live conditions when a verified utility update is unavailable.</span></div><?php endif; ?>
    <?php foreach($updates as $update): $entity=$update['entity']??[]; $followed=in_array((string)($entity['id']??''),$followedEntityIds,true);
      $isSampleUtility=($update['demo_fixture_suite']??'')==='pmnow-safe-demo-v1';
      $sampleUtilityTitle=trim((string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu','',(string)($update['title']??'Utility update'))??''));
      $sampleUtilityEntity=trim((string)(preg_replace('/^\\s*\\[DEMO\\]\\s*/iu','',(string)($entity['name']??'Pune utility'))??''));
      ?>
      <article class="utility-card severity-<?=pm_e($update['severity']??'info')?>" data-utility-update-id="<?=pm_e($update['id']??'')?>">
        <div class="utility-card-head"><span class="utility-kind"><?=pm_e(strtoupper((string)($entity['kind']??'utility')))?></span><span class="utility-severity"><?=pm_e(strtoupper((string)($update['severity']??'info')))?></span></div>
        <?php if($isSampleUtility): require $viewRoot.'/components/sample-badge.php'; endif; ?>
        <a href="<?=pm_e(pm_url((string)($update['path']??'/utility')))?>"><h2><?=pm_e($sampleUtilityTitle)?></h2></a>
        <?php if(!empty($update['summary'])): ?><p><?=pm_e($update['summary'])?></p><?php endif; ?>
        <div class="utility-meta"><span><?=pm_icon('pin','sm')?> <?=pm_e($update['area']??'Pune')?></span><span><?=pm_e($sampleUtilityEntity)?></span><span><?=pm_e($update['status']??'active')?></span></div>
        <div class="utility-source"><?php if(($update['demo_fixture_suite']??'')==='pmnow-safe-demo-v1'): ?><span>Illustrative · Unverified advisory</span><?php else: ?><span>Verified <?=pm_e($update['verified_at']??$update['published_at']??'')?></span><?php endif; ?><?php if(!empty($update['source_label'])): ?><span>Source: <?=pm_e($isSampleUtility?str_ireplace('DEMO','Sample',(string)$update['source_label']):$update['source_label'])?></span><?php endif; ?></div>
        <div class="utility-actions"><a class="act" href="<?=pm_e(pm_url((string)($update['path']??'/utility')))?>">Details</a><button class="act <?=$followed?'on':''?>" type="button" data-utility-follow="<?=pm_e($entity['id']??'')?>"><span><?=$followed?'Following':'Follow'?></span></button></div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
