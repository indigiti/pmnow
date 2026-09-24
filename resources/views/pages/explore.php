<?php require $viewRoot . '/components/header.php'; ?>
<main class="scroll withnav">
  <form class="searchbox" action="<?=pm_e(pm_url('/explore'))?>" method="get" data-search-form>
    <?=pm_icon('search','sm')?><label class="sr-only" for="explore-search">Search Pune news</label><input id="explore-search" name="q" placeholder="Search Pune, stories, areas…" autocomplete="off"><button class="link" type="submit">Search</button>
  </form>
  <section id="searchResults" class="sect" hidden></section>
  <section class="sect">
    <div class="secttitle"><div><span class="pm-kicker">Neighbourhoods</span><h2>Your areas</h2></div><a class="link" href="<?=pm_e(pm_url('/profile'))?>">Edit</a></div>
    <div class="areas"><?php foreach(['Baner','Kothrud','Aundh','Hadapsar','Shivajinagar','Wakad'] as $area): ?><button class="chip" type="button" data-area="<?= pm_e($area) ?>"><?= pm_e($area) ?></button><?php endforeach; ?></div>
  </section>
  <section class="sect">
    <div class="secttitle"><div><span class="pm-kicker">Around Pune</span><h2>Trending near you</h2></div><a class="link" href="<?=pm_e(pm_url('/'))?>">Latest</a></div>
    <div class="hscroll">
      <?php foreach (array_slice($stories,0,4) as $story): $m=$story['media'][0]??null; ?>
      <a class="trend" href="<?= pm_e(pm_story_path($story)) ?>">
        <div class="trendimg"><img src="<?= pm_e(pm_media($m)) ?>" alt="<?=pm_e($story['headline']??'Pune story')?>" loading="lazy"><span class="dist"><?=pm_icon('pin','sm')?> <?= pm_e($story['locations'][0]['name']??'Pune') ?></span></div>
        <div class="trendbody"><span class="area"><?= pm_e($story['categories'][0]['name']??'Pune') ?></span><h3><?= pm_e($story['headline']) ?></h3><div class="stats"><span><?=pm_icon('heart','sm')?> <?= pm_e($story['likes']??'1.3K') ?></span><span><?=pm_icon('comment','sm')?> <?= pm_e($story['comments']??'82') ?></span></div></div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="sect">
    <div class="secttitle"><div><span class="pm-kicker">Browse</span><h2>Explore topics</h2></div></div>
    <div class="topicgrid">
      <?php $topics=[['Traffic','Live road updates','topic_traffic.jpg'],['Civic','PMC & PCMC','topic_civic.jpg'],['Crime','Police & safety','topic_crime.jpg'],['Food','Places & openings','topic_food.jpg'],['Events','What’s on','topic_events.jpg'],['Sports','Local & national','topic_sports.jpg']]; foreach($topics as $t): ?>
      <button class="topic" type="button" data-topic="<?= pm_e(strtolower($t[0])) ?>"><img src="<?=pm_e(pm_url('/media/'.(string)$t[2]))?>" alt="" loading="lazy"><span class="topicicon"><?=pm_icon('grid','sm')?></span><span class="topictext"><h3><?= pm_e($t[0]) ?></h3><p><?= pm_e($t[1]) ?></p></span></button>
      <?php endforeach; ?>
    </div>
  </section>
</main>
