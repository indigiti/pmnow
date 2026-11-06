<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav area-pulse-page">
  <section class="area-pulse-intro">
    <div class="pulse-eyebrow"><span class="pulse-dot"></span> PMNow · Local discovery</div>
    <h1>Your city,<br><em>mapped.</em></h1>
    <p>Discover recent reporting around Pune and PCMC, organised automatically by neighbourhood.</p>
    <div class="pulse-meta">
      <span><?= (int)$areaTotals['with_reports'] ?> neighbourhoods with reports</span>
      <span>Last 7 days</span>
      <a href="<?=pm_e(pm_url('/profile'))?>">Edit my areas <?=pm_icon('arrow-right','sm')?></a>
    </div>
  </section>

  <section class="area-map-panel" aria-label="Pune area reporting overview">
    <div class="area-map-heading">
      <div><span class="pm-kicker">Neighbourhood guide</span><h2>Explore the local map</h2></div>
      <span class="pulse-window">REPORTS · 7 DAYS</span>
    </div>
    <div class="area-map-illustration" data-area-map aria-label="Approximate area locations, not a street or live-traffic map">
      <div class="map-grid" aria-hidden="true"></div>
      <span class="map-axis map-axis-top" aria-hidden="true">PCMC</span>
      <span class="map-axis map-axis-bottom" aria-hidden="true">PUNE</span>
      <?php foreach($areaRows as $area): ?>
        <button type="button"
          class="map-pin <?= $area['stories']>0?'has-reports':'' ?> <?= $area['traffic_reports']>0?'has-traffic-reports':'' ?>"
          style="--pin-x:<?= (float)$area['x'] ?>%;--pin-y:<?= (float)$area['y'] ?>%"
          data-map-area="<?=pm_e($area['slug'])?>"
          aria-label="<?=pm_e($area['name'].': '.$area['stories'].' recent reports, '.$area['traffic_reports'].' traffic reports')?>"
          aria-pressed="false"
          title="<?=pm_e($area['name'])?>">
          <span class="pin-core"></span>
        </button>
      <?php endforeach; ?>
    </div>
    <div class="area-map-legend">
      <span><i class="legend-dot traffic"></i> Traffic stories reported</span>
      <span><i class="legend-dot coverage"></i> Local coverage</span>
      <span><i class="legend-dot none"></i> No recent reports</span>
    </div>
    <p class="map-selection-status" role="status" aria-live="polite" data-area-map-selection>Select a dot to highlight its neighbourhood below.</p>
    <p class="map-disclaimer">Indicative neighbourhood locations and news counts, not GPS navigation or live road congestion. Demo content is explicitly labelled where present.</p>
  </section>

  <section class="area-directory" aria-labelledby="area-directory-heading">
    <div class="area-directory-heading">
      <div><span class="pm-kicker">Stay connected</span><h2 id="area-directory-heading">Pune neighbourhoods</h2></div>
      <span class="area-count"><?=count($areaRows)?> areas</span>
    </div>
    <div class="area-directory-list">
      <?php foreach($areaRows as $area): ?>
        <article class="area-directory-item <?= $area['following']?'is-following':'' ?>"
          data-area-row="<?=pm_e($area['slug'])?>">
          <button class="area-directory-focus" type="button" data-map-area="<?=pm_e($area['slug'])?>"
            aria-label="Highlight <?=pm_e($area['name'])?> on the area map">
            <span class="area-directory-dot"><?=pm_icon('pin','sm')?></span>
          </button>
          <div class="area-directory-info">
            <a href="<?=pm_e(pm_url('/area/'.$area['slug']))?>"><h3><?=pm_e($area['name'])?></h3></a>
            <p><?= (int)$area['stories'] ?> local report<?= $area['stories']===1?'':'s' ?>
              <?php if($area['traffic_reports']>0): ?> · <?= (int)$area['traffic_reports'] ?> traffic <?=(int)$area['traffic_reports']===1?'story':'stories'?><?php endif; ?>
              <?php if($area['following']): ?> · Following<?php endif; ?>
            </p>
            <?php if($area['latest_headline']): ?>
              <a class="area-latest-link" href="<?=pm_e($area['latest_path'])?>"><?=pm_e($area['latest_headline'])?></a>
            <?php endif; ?>
          </div>
          <a class="area-directory-arrow" href="<?=pm_e(pm_url('/area/'.$area['slug']))?>"
             aria-label="Read <?=pm_e($area['name'])?> news"><?=pm_icon('arrow-right','sm')?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
