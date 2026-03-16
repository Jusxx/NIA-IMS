<?php
$active = $active ?? 'dashboard';
$roleNow = role();

function navItem($key, $label, $icon, $page, $activeKey) {
  $isActive = $key === $activeKey;
  $wrap = $isActive ? 'bg-primary/10 dark:bg-primary/20' : '';
  $iconClass = $isActive ? 'fill text-primary' : 'text-gray-600 dark:text-gray-300';
  $textClass = $isActive ? 'text-primary font-bold' : 'text-text-light dark:text-text-dark font-medium';
  ?>
  <a class="flex items-center gap-3 px-3 py-2 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800 <?= $wrap ?>"
     href="<?= route($page) ?>">
    <span class="material-symbols-outlined <?= $iconClass ?>"><?= $icon ?></span>
    <p class="<?= $textClass ?> text-sm leading-normal"><?= h($label) ?></p>
  </a>
  <?php
}
?>

<div id="imsSidebarBackdrop" class="fixed inset-0 z-40 hidden bg-black/40 lg:hidden"></div>

<aside
  id="imsSidebar"
  class="fixed inset-y-0 left-0 z-50 flex h-screen w-64 max-w-[85vw] -translate-x-full flex-col justify-between overflow-y-auto bg-card-light p-4 border-r border-border-light transition-transform duration-200 dark:bg-card-dark dark:border-border-dark lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:self-start lg:w-64 lg:max-w-none lg:translate-x-0"
>
  <div class="flex flex-col gap-6">
    <div class="px-2 flex items-start justify-between gap-2">
      <div class="flex items-start gap-2">
        <img
          src="<?= base_path('images/nia-logo.png') ?>"
          alt="NIA Logo"
          class="h-9 w-9 rounded-full object-cover border border-border-light dark:border-border-dark"
        >
        <div>
          <h1 class="text-text-light dark:text-text-dark text-base font-bold">NIA IMS</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Irrigation Management</p>
        </div>
      </div>
      <button
        type="button"
        id="imsSidebarClose"
        class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
        aria-label="Close menu"
        title="Close menu"
      >
        <span class="material-symbols-outlined text-gray-600 dark:text-gray-300 text-[20px] leading-none">close</span>
      </button>
    </div>

    <nav class="flex flex-col gap-2">
      <?php if ($roleNow === 'Farmer'): ?>
        <?php navItem('farmer_dashboard','Dashboard','dashboard','farmer_dashboard',$active); ?>
        <?php navItem('my_requests','My Requests','inbox','my_requests',$active); ?>
        <?php navItem('my_schedule','My Schedule / Task Status','calendar_month','my_schedule',$active); ?>
        <?php navItem('profile','Profile','person','profile',$active); ?>
      

        

      <?php else: ?>
        <?php if (can_page('dashboard')) navItem('dashboard','Dashboard','dashboard','dashboard',$active); ?>
        <?php if (can_page('schedules')) navItem('schedules','Schedules','calendar_month','schedules',$active); ?>
        <?php if (can_page('tasks')) navItem('tasks','Irrigation Tasks','assignment','tasks',$active); ?>
        <?php if (can_page('areas')) navItem('areas','Areas / Canals','map','areas',$active); ?>
        <?php if (can_page('requests')) navItem('requests','Requests','inbox','requests',$active); ?>
        <?php if (can_page('reports')) navItem('reports','Reports','bar_chart','reports',$active); ?>
        <?php if (can_page('forms')) navItem('forms','Forms','description','forms',$active); ?>
        <?php if (can_page('logs')) navItem('logs','System Logs','book','logs',$active); ?>
        <?php if (can_page('sms_logs')) navItem('sms_logs','SMS Logs','sms','sms_logs',$active); ?>
        <?php if (can_page('users')) navItem('users','Users','group','users',$active); ?>
        <?php if (can_page('farmers')) navItem('farmers','Farmers','agriculture','farmers',$active); ?>
      <?php endif; ?>
    </nav>
  </div>
</aside>
