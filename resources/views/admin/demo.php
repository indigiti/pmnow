<?php
$counts=(array)($demoStatus['counts']??[]);
$modules=[
 ['stories','News stories',18],['contents','Editorial drafts',7],
 ['utility-updates','Utility advisories',9],['users','Reader profiles',2],
];
?>
<header class="admin-top">
 <div><p>Quality assurance · Nonproduction</p><h1>Demo Data</h1></div>
 <a class="admin-secondary" href="<?=pm_e(pm_url('/admin'))?>">Dashboard</a>
</header>
<section class="demo-hero">
 <div>
  <span class="demo-eyebrow">PMNOW TEST SANDBOX · <?=pm_e(strtoupper((string)($demoStatus['environment']??'')))?></span>
  <h2>Test PMNow using realistic synthetic content.</h2>
  <p>Test Pune news, reader scenarios, editorial drafts, and city utilities — all identified as DEMO, never real-world reporting.</p>
 </div>
 <div class="demo-total"><strong data-demo-total><?=number_format((int)($demoStatus['total']??0))?></strong><span>Demo records installed</span></div>
</section>
<section class="demo-stat-grid">
<?php foreach($modules as [$key,$label,$expected]): ?>
 <div class="demo-stat"><span><?=pm_e($label)?></span><strong><span data-demo-count="<?=pm_e($key)?>"><?=number_format((int)($counts[$key]??0))?></span> <small>/ <?=$expected?></small></strong></div>
<?php endforeach; ?>
</section>
<section class="demo-actions-grid" data-demo-controls>
 <article class="demo-action-panel">
  <span class="demo-tag">SAFE TO REPEAT</span><h2>Load demo data</h2>
  <p>Inserts missing synthetic test content for the public experience and newsroom. Existing records are left intact.</p>
  <button class="admin-primary" type="button" data-demo-apply>Load demo data</button>
 </article>
 <article class="demo-action-panel">
  <span class="demo-tag">EXPLICIT CONFIRMATION</span><h2>Remove demo data</h2>
  <p>Removes only records tagged with this fixture suite, keeping unrelated application data untouched.</p>
  <label for="demo-purge-confirm">Type <strong>REMOVE DEMO</strong> to confirm</label>
  <input type="text" id="demo-purge-confirm" data-demo-purge-confirm placeholder="REMOVE DEMO" spellcheck="false" autocomplete="off">
  <button class="admin-secondary demo-danger" type="button" data-demo-purge disabled>Remove demo data</button>
 </article>
</section>
<p class="demo-status" data-demo-status role="status" aria-live="polite">Ready to manage synthetic QA records.</p>
<section class="admin-panel">
 <div class="admin-panel-head"><h2>Test the application</h2></div>
 <div class="demo-links">
  <a href="<?=pm_e(pm_url('/'))?>" target="_blank" rel="noopener">Reader feed</a>
  <a href="<?=pm_e(pm_url('/watch'))?>" target="_blank" rel="noopener">Watch & live</a>
  <a href="<?=pm_e(pm_url('/admin/inbox'))?>">Editorial inbox</a>
  <a href="<?=pm_e(pm_url('/utility'))?>" target="_blank" rel="noopener">Pune Utility</a>
 </div>
</section>
<p class="demo-caution">Demo fixtures appear on the nonproduction reader site and are not real news. Keep staging access-controlled and excluded from search indexing. Production use is blocked.</p>
