<header class="admin-top"><div><p>Editorial workflow</p><h1>Content Inbox</h1></div><button class="admin-secondary" data-rebuild-search>Rebuild search index</button></header>
<div class="admin-filterbar"><button data-inbox-filter="all" class="active">All</button><button data-inbox-filter="ready">Ready</button><button data-inbox-filter="review_required">Review</button><button data-inbox-filter="published">Published</button><button data-inbox-filter="rejected">Rejected</button></div>
<section class="admin-panel">
<table class="admin-table inbox-table"><thead><tr><th><input type="checkbox" data-select-all></th><th>Content</th><th>Source</th><th>AI / editorial</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($items as $item): $status=$item['workflow_status']??'ready'; $cat=$item['final_category_slugs'][0]??($item['analysis']['category']['category_slug']??'—'); ?>
<tr data-content-row data-status="<?= pm_e($status) ?>">
<td><input type="checkbox" data-content-select value="<?= pm_e($item['id']) ?>"></td>
<td><strong><?= pm_e($item['title'] ?: ($item['caption']??substr((string)($item['body']??''),0,100))) ?></strong><small><?= pm_e($item['content_type']??'content') ?> · <?= pm_e($item['published_at']??$item['created_at']??'') ?></small></td>
<td><strong><?= pm_e($item['source']['name']??'Unknown') ?></strong><small><?= pm_e($item['source_handle']??'') ?></small></td>
<td><span class="taxonomy-pill"><?= pm_e($cat) ?></span><small><?= pm_e(implode(', ', $item['final_location_slugs']??[])) ?></small></td>
<td><span class="status <?= pm_e($status) ?>"><?= pm_e(strtoupper(str_replace('_',' ',$status))) ?></span></td>
<td class="admin-actions"><button data-editorial-action="approve" data-content-id="<?= pm_e($item['id']) ?>">Approve</button><button data-editorial-action="review" data-content-id="<?= pm_e($item['id']) ?>">Review</button><button data-editorial-action="reject" data-content-id="<?= pm_e($item['id']) ?>">Reject</button></td>
</tr><?php endforeach; ?>
</tbody></table>
</section>
<div class="bulkbar" data-bulkbar hidden><span><strong data-selected-count>0</strong> selected</span><button data-bulk-action="approve">Approve</button><button data-bulk-action="review">Needs review</button><button data-bulk-action="archive">Archive</button></div>
