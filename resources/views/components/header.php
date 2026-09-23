<header class="topbar safe">
  <a class="logo now" href="<?=pm_e(pm_url('/'))?>"><span class="red">PUNE</span>MIRROR <small>NOW</small></a>
  <button class="place" type="button">Pune ▾</button>
  <a class="iconbtn notify" href="<?=pm_e(pm_url('/notifications'))?>" aria-label="Notifications">♧<?php if (($unreadNotifications ?? 0) > 0): ?><span class="badge"><?= (int)$unreadNotifications ?></span><?php endif; ?></a>
</header>
