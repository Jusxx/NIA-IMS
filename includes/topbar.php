<?php
// includes/topbar.php
$userName = $_SESSION['user']['fullname'] ?? $_SESSION['user']['username'] ?? 'User';
$userRole = role_label($_SESSION['user']['role'] ?? '');
?>
<header class="sticky top-0 z-20 flex items-center justify-between border-b border-solid border-border-light dark:border-border-dark px-4 lg:px-10 py-4 bg-card-light dark:bg-card-dark">
  <div class="flex items-center gap-3 min-w-0">
    <button
      id="imsSidebarToggle"
      type="button"
      class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 lg:hidden"
      aria-label="Open menu"
      title="Open menu"
    >
      <span class="material-symbols-outlined text-gray-600 dark:text-gray-300 text-[20px] leading-none">menu</span>
    </button>

    <div class="flex flex-col min-w-0">
      <h2 class="text-lg font-bold text-text-light dark:text-text-dark truncate"><?= h($topTitle ?? 'Dashboard') ?></h2>
      <span class="text-xs text-gray-500 dark:text-gray-400 truncate"><?= h($userName) ?><?= $userRole ? " - " . h($userRole) : "" ?></span>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <a class="flex items-center gap-2 px-3 py-2 rounded-DEFAULT hover:bg-gray-100 dark:hover:bg-gray-800"
       href="<?= route('logout') ?>"
       data-loading
       data-loading-text="Signing out...">
      <span class="material-symbols-outlined text-gray-600 dark:text-gray-300">logout</span>
      <span class="hidden sm:inline text-sm text-text-light dark:text-text-dark">Logout</span>
    </a>
  </div>
</header>

<script>
(() => {
  const sidebar = document.getElementById('imsSidebar');
  const sidebarBackdrop = document.getElementById('imsSidebarBackdrop');
  const openBtn = document.getElementById('imsSidebarToggle');
  const closeBtn = document.getElementById('imsSidebarClose');
  if (!sidebar || !sidebarBackdrop || !openBtn) return;

  const desktopMq = window.matchMedia('(min-width: 1024px)');

  const closeSidebar = () => {
    sidebar.classList.add('-translate-x-full');
    sidebarBackdrop.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  };

  const openSidebar = () => {
    if (desktopMq.matches) return;
    sidebar.classList.remove('-translate-x-full');
    sidebarBackdrop.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  };

  const syncLayoutForViewport = () => {
    if (desktopMq.matches) {
      sidebarBackdrop.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
      return;
    }
    // Keep mobile menu closed by default.
    sidebar.classList.add('-translate-x-full');
  };

  openBtn.addEventListener('click', openSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  sidebarBackdrop.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeSidebar();
  });

  sidebar.querySelectorAll('a[href]').forEach((link) => {
    link.addEventListener('click', () => {
      if (!desktopMq.matches) closeSidebar();
    });
  });

  if (typeof desktopMq.addEventListener === 'function') {
    desktopMq.addEventListener('change', syncLayoutForViewport);
  } else if (typeof desktopMq.addListener === 'function') {
    desktopMq.addListener(syncLayoutForViewport);
  }

  syncLayoutForViewport();
})();
</script>
