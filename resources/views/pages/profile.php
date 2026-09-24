<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav">
  <section class="profile-card">
    <span class="pm-kicker">Personal edition</span>
    <div class="person"><div class="avatar">PR</div><div><div class="profile-name"><?= pm_e($state['user']['name']??'Pune Reader') ?></div><small>My Pune · <?= pm_e(substr($state['user']['id']??'',0,13)) ?>…</small></div></div>
    <form class="preference-form" data-preferences>
      <fieldset>
        <legend>My neighbourhoods</legend>
        <p class="pm-muted">Choose the Pune areas that should receive extra weight in your For You feed.</p>
        <div class="pref-grid">
          <?php $selectedAreas=array_map('strtolower',$state['user']['preferences']['areas']??[]); foreach($areas as $area): $name=(string)($area['name']??''); ?>
          <label class="pref-option"><input type="checkbox" name="areas" value="<?=pm_e($name)?>" <?=in_array(strtolower($name),$selectedAreas,true)?'checked':''?>><span class="chip"><?=pm_e($name)?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <fieldset>
        <legend>My topics</legend>
        <p class="pm-muted">Traffic, civic, Metro and other interests influence ranking without hiding important Pune news.</p>
        <div class="pref-grid">
          <?php $selectedChannels=array_map('strtolower',$state['user']['preferences']['channels']??[]); foreach($channels as $channel): $name=(string)($channel['name']??''); ?>
          <label class="pref-option"><input type="checkbox" name="channels" value="<?=pm_e($name)?>" <?=in_array(strtolower($name),$selectedChannels,true)?'checked':''?>><span class="chip"><?=pm_e($name)?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <div class="preference-actions"><button class="btn red" type="submit">Save My Pune</button><span class="pm-muted" data-preference-status></span></div>
    </form>
  </section>
  <section class="sect"><div class="secttitle"><div><span class="pm-kicker">Library</span><h2>Saved stories</h2></div><span class="link"><?= count($saved) ?> saved</span></div></section>
  <section class="saved-list"><?php if(!$saved): ?><div class="empty">Save a story from Home, Gallery, Live or Watch and it will appear here.</div><?php endif; ?><?php foreach($saved as $story): $m=$story['media'][0]??null; ?><a class="saved-row" href="<?= pm_e(pm_story_path($story)) ?>"><img src="<?= pm_e(pm_media($m)) ?>" alt="<?=pm_e($story['headline']??'Saved Pune story')?>" loading="lazy"><div><small><?= pm_e($story['categories'][0]['name']??'Pune') ?></small><h3><?= pm_e($story['headline']) ?></h3><small><?=pm_icon('pin','sm')?> <?= pm_e($story['locations'][0]['name']??'Pune') ?></small></div></a><?php endforeach; ?></section>
</main>
