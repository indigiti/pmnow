<header class="admin-top"><div><p>M13 · Community</p><h1>Community desk</h1></div><a class="admin-secondary" href="<?=pm_e(pm_url('/community'))?>" target="_blank" rel="noopener">Open public Community</a></header>
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Moderation queue</h2><p>Community submissions stay unpublished until an editor approves them.</p></div></div>
<?php if(!$communityQueue): ?><div class="admin-empty">No posts awaiting moderation.</div><?php endif; ?>
<table class="admin-table"><thead><tr><th>Post</th><th>Area</th><th>Type</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
<?php foreach($communityQueue as $row): ?><tr><td><strong><?=pm_e($row['title'])?></strong><small><?=pm_e($row['body'])?></small></td><td><?=pm_e($row['area'])?></td><td><?=pm_e($row['type'])?></td><td><?=pm_e($row['created_at']??'')?></td><td class="admin-actions"><button data-community-moderate="publish" data-community-id="<?=pm_e($row['id'])?>">Publish</button><button data-community-moderate="reject" data-community-id="<?=pm_e($row['id'])?>">Reject</button></td></tr><?php endforeach; ?>
</tbody></table></section>
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Reader reports</h2><p>Open reports submitted against published Community posts.</p></div></div>
<?php if(!$communityReports): ?><div class="admin-empty">No community reports.</div><?php endif; ?>
<table class="admin-table"><thead><tr><th>Post ID</th><th>Reason</th><th>Status</th><th>Reported</th></tr></thead><tbody><?php foreach($communityReports as $row): ?><tr><td><?=pm_e($row['post_id'])?></td><td><?=pm_e($row['reason'])?></td><td><?=pm_e($row['status'])?></td><td><?=pm_e($row['created_at']??'')?></td></tr><?php endforeach; ?></tbody></table></section>
