<header class="topbar safe">
  <a class="logo now" href="<?=pm_e(pm_url('/'))?>"><span class="red">PUNE</span>MIRROR <small>NOW</small></a>
  <a class="place" href="<?=pm_e(pm_url('/explore?q=Pune'))?>"><?=pm_icon('pin','sm')?> <span>Pune</span></a>
  <a class="iconbtn notify" href="<?=pm_e(pm_url('/notifications'))?>" aria-label="Notifications"><?=pm_icon('bell')?><?php if (($unreadNotifications ?? 0) > 0): ?><span class="badge"><?= (int)$unreadNotifications ?></span><?php endif; ?></a>
</header>
