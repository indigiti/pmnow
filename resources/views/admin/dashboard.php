<header class="admin-top"><div><p>Operations</p><h1>Newsroom dashboard</h1></div><button class="admin-primary" data-scheduler-tick>Run scheduler</button></header>
<section class="admin-kpis">
<?php foreach ([['Ready',$metrics['ready']??0],['Review',$metrics['review_required']??0],['Published',$metrics['published']??0],['Rejected',$metrics['rejected']??0]] as [$label,$value]): ?>
<div class="admin-kpi"><span><?= pm_e($label) ?></span><strong><?= (int)$value ?></strong></div>
<?php endforeach; ?>
</section>
<div class="admin-grid2">
<section class="admin-panel"><div class="admin-panel-head"><h2>Source health</h2><a href="<?=pm_e(pm_url('/admin/sources'))?>">Manage</a></div>
<table class="admin-table"><thead><tr><th>Source</th><th>Provider</th><th>Status</th><th>Last sync</th></tr></thead><tbody>
<?php foreach ($sources as $s): ?><tr><td><strong><?= pm_e($s['name']??'') ?></strong><small><?= pm_e($s['handle']??'') ?></small></td><td><?= pm_e($s['provider']??'') ?></td><td><span class="status <?= pm_e($s['health_status']??'disabled') ?>"><?= pm_e(strtoupper($s['health_status']??'disabled')) ?></span></td><td><?= pm_e($s['last_sync_at']??'—') ?></td></tr><?php endforeach; ?>
</tbody></table></section>
<section class="admin-panel"><div class="admin-panel-head"><h2>Recent jobs</h2><a href="<?=pm_e(pm_url('/admin/jobs'))?>">Open queue</a></div>
<?php if (!$jobs): ?><div class="admin-empty">No jobs yet.</div><?php endif; ?>
<?php foreach ($jobs as $j): ?><div class="job-row"><div><strong><?= pm_e($j['type']??'JOB') ?></strong><small><?= pm_e($j['subject_id']??'') ?></small></div><span class="status <?= pm_e($j['status']??'queued') ?>"><?= pm_e(strtoupper($j['status']??'queued')) ?></span></div><?php endforeach; ?>
</section></div>
