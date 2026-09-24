<nav class="bottomnav" aria-label="Primary">
  <a class="navitem <?= ($activeNav??'')==='home'?'active':'' ?>" href="<?=pm_e(pm_url('/'))?>" <?=($activeNav??'')==='home'?'aria-current="page"':''?>><?=pm_icon('home')?><span>Home</span></a>
  <a class="navitem <?= ($activeNav??'')==='explore'?'active':'' ?>" href="<?=pm_e(pm_url('/explore'))?>" <?=($activeNav??'')==='explore'?'aria-current="page"':''?>><?=pm_icon('search')?><span>Explore</span></a>
  <a class="navitem <?= ($activeNav??'')==='watch'?'active':'' ?>" href="<?=pm_e(pm_url('/watch'))?>" <?=($activeNav??'')==='watch'?'aria-current="page"':''?>><?=pm_icon('play')?><span>Watch</span></a>
  <a class="navitem <?= ($activeNav??'')==='notifications'?'active':'' ?>" href="<?=pm_e(pm_url('/notifications'))?>" <?=($activeNav??'')==='notifications'?'aria-current="page"':''?>><?=pm_icon('bell')?><span>Alerts</span></a>
  <a class="navitem <?= ($activeNav??'')==='profile'?'active':'' ?>" href="<?=pm_e(pm_url('/profile'))?>" <?=($activeNav??'')==='profile'?'aria-current="page"':''?>><?=pm_icon('user')?><span>My Pune</span></a>
</nav>
