<header class="admin-top"><div><p>Distribution</p><h1>Reader alerts</h1></div><a class="admin-secondary" href="<?=pm_e(pm_url('/channels/whatsapp.json'))?>" target="_blank" rel="noopener">Open channel feed</a></header>
<section class="admin-panel">
  <div class="admin-panel-head"><div><h2>Published stories</h2><p>Send an in-app alert to eligible readers. Delivery is idempotent per story and alert kind.</p></div></div>
  <?php if(!$stories): ?><div class="admin-empty">No published stories available.</div><?php endif; ?>
  <table class="admin-table"><thead><tr><th>Story</th><th>Published</th><th>Distribution</th></tr></thead><tbody>
  <?php foreach($stories as $story): ?><tr>
    <td><strong><?=pm_e($story['headline']??'Pune update')?></strong><small><?=pm_e($story['id']??'')?></small></td>
    <td><?=pm_e($story['published_at']??$story['created_at']??'—')?></td>
    <td class="admin-actions distribution-actions">
      <button data-distribute-story="<?=pm_e($story['id'])?>" data-distribution-kind="breaking">Breaking</button>
      <button data-distribute-story="<?=pm_e($story['id'])?>" data-distribution-kind="neighbourhood">Neighbourhood</button>
      <button data-distribute-story="<?=pm_e($story['id'])?>" data-distribution-kind="topic">Topic</button>
    </td>
  </tr><?php endforeach; ?>
  </tbody></table>
</section>
<section class="admin-panel distribution-contracts"><div class="admin-panel-head"><h2>Channel contracts</h2></div><div class="channel-contract-grid"><a href="<?=pm_e(pm_url('/channels/whatsapp.json'))?>" target="_blank">WhatsApp-ready JSON</a><a href="<?=pm_e(pm_url('/channels/telegram.json'))?>" target="_blank">Telegram-ready JSON</a></div><p>External sending is intentionally separate from editorial targeting; provider credentials can be attached later without changing these contracts.</p></section>
