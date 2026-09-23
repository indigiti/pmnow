<nav class="bottomnav" aria-label="Primary">
  <a class="navitem <?= ($activeNav??'')==='home'?'active':'' ?>" href="<?=pm_e(pm_url('/'))?>"><span>⌂</span><span>Home</span></a>
  <a class="navitem <?= ($activeNav??'')==='explore'?'active':'' ?>" href="<?=pm_e(pm_url('/explore'))?>"><span>⌕</span><span>Explore</span></a>
  <button class="navitem" type="button" data-toast="Citizen tip composer is reserved for the newsroom milestone"><span class="navplus">＋</span></button>
  <a class="navitem <?= ($activeNav??'')==='watch'?'active':'' ?>" href="<?=pm_e(pm_url('/watch'))?>"><span>▶</span><span>Watch</span></a>
  <a class="navitem <?= ($activeNav??'')==='profile'?'active':'' ?>" href="<?=pm_e(pm_url('/profile'))?>"><span>◎</span><span>Profile</span></a>
</nav>
