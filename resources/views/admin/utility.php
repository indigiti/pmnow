<header class="admin-top"><div><p>Pune Utility</p><h1>Utility desk</h1></div><a class="admin-secondary" href="<?=pm_e(pm_url('/utility'))?>" target="_blank" rel="noopener">Open public utility</a></header>

<section class="admin-kpis">
  <?php foreach([['Entities',$utilityMetrics['entities']??0],['Active updates',$utilityMetrics['active_updates']??0],['Critical',$utilityMetrics['critical']??0],['Follows',$utilityMetrics['follows']??0]] as [$label,$value]): ?>
    <div class="admin-kpi"><span><?=pm_e($label)?></span><strong><?= (int)$value ?></strong></div>
  <?php endforeach; ?>
</section>

<div class="admin-grid2 utility-admin-grid">
  <section class="admin-panel">
    <div class="admin-panel-head"><div><h2>Add utility entity</h2><p>Track a corridor, service, authority feed or city utility.</p></div></div>
    <form class="admin-form utility-form" data-create-utility-entity>
      <label>Name<input name="name" required placeholder="Pune Metro service"></label>
      <label>Kind<select name="kind" required><?php foreach($utilityKinds as $kind): ?><option value="<?=pm_e($kind)?>"><?=pm_e(ucfirst($kind))?></option><?php endforeach; ?></select></label>
      <label>Authority<input name="authority" placeholder="Maha Metro"></label>
      <label>Areas<input name="areas" placeholder="Pune, Shivajinagar"></label>
      <label>Source label<input name="source_label" placeholder="Official authority"></label>
      <label>Source URL<input name="source_url" type="url" placeholder="https://…"></label>
      <button class="admin-primary" type="submit">Create entity</button>
    </form>
  </section>

  <section class="admin-panel">
    <div class="admin-panel-head"><div><h2>Publish utility update</h2><p>Creates a public advisory and notifies followers of the selected entity.</p></div></div>
    <form class="admin-form utility-form" data-create-utility-update>
      <label>Entity<select name="entity_id" required><?php foreach($utilityEntities as $entity): ?><option value="<?=pm_e($entity['id'])?>"><?=pm_e($entity['name'])?> · <?=pm_e($entity['kind'])?></option><?php endforeach; ?></select></label>
      <label>Severity<select name="severity"><option value="info">Info</option><option value="advisory">Advisory</option><option value="major">Major</option><option value="critical">Critical</option></select></label>
      <label>Area<input name="area" placeholder="Pune"></label>
      <label>Headline<input name="title" required placeholder="Temporary service advisory"></label>
      <label class="admin-span2">Summary<textarea name="summary" rows="4" placeholder="What changed, where, and what readers should know."></textarea></label>
      <label>Source label<input name="source_label" placeholder="Official authority"></label>
      <label>Source URL<input name="source_url" type="url" placeholder="https://…"></label>
      <button class="admin-primary" type="submit" <?=empty($utilityEntities)?'disabled':''?>>Publish update</button>
    </form>
  </section>
</div>

<section class="admin-panel utility-update-panel">
  <div class="admin-panel-head"><div><h2>Utility updates</h2><p>Published and resolved service information.</p></div></div>
  <?php if(!$utilityUpdates): ?><div class="admin-empty">No utility updates yet.</div><?php endif; ?>
  <table class="admin-table"><thead><tr><th>Update</th><th>Entity</th><th>Severity</th><th>Status</th><th>Verified</th><th></th></tr></thead><tbody>
  <?php foreach($utilityUpdates as $row): ?><tr>
    <td><strong><?=pm_e($row['title']??'Update')?></strong><small><?=pm_e($row['area']??'Pune')?></small></td>
    <td><?=pm_e($row['entity']['name']??'—')?></td>
    <td><span class="status"><?=pm_e($row['severity']??'info')?></span></td>
    <td><?=pm_e($row['status']??'active')?></td>
    <td><?=pm_e($row['verified_at']??'—')?></td>
    <td class="admin-actions"><?php if(($row['status']??'active')==='active'): ?><button data-resolve-utility-update="<?=pm_e($row['id'])?>">Resolve</button><?php endif; ?></td>
  </tr><?php endforeach; ?>
  </tbody></table>
</section>
