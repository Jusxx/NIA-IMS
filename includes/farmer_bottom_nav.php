<?php
if (role() !== 'Farmer') return;

$activeKey = $active ?? '';

$items = [
  ['key' => 'farmer_dashboard', 'label' => 'Home', 'icon' => 'home', 'page' => 'farmer_dashboard'],
  ['key' => 'my_requests', 'label' => 'Requests', 'icon' => 'inbox', 'page' => 'my_requests'],
  ['key' => 'my_schedule', 'label' => 'Schedule', 'icon' => 'calendar_month', 'page' => 'my_schedule'],
  ['key' => 'profile', 'label' => 'Profile', 'icon' => 'person', 'page' => 'profile'],
];
?>

<nav class="fixed bottom-0 inset-x-0 z-40 border-t border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark lg:hidden">
  <ul class="grid grid-cols-4">
    <?php foreach ($items as $item): ?>
      <?php
        $isActive = ($item['key'] === $activeKey);
        $itemClass = $isActive
          ? 'text-primary font-semibold'
          : 'text-gray-600 dark:text-gray-300';
      ?>
      <li>
        <a
          href="<?= route($item['page']) ?>"
          class="flex flex-col items-center justify-center gap-0.5 py-2.5"
          aria-label="<?= h($item['label']) ?>"
          title="<?= h($item['label']) ?>"
        >
          <span class="material-symbols-outlined text-[20px] leading-none <?= $itemClass ?>"><?= h($item['icon']) ?></span>
          <span class="text-[11px] <?= $itemClass ?>"><?= h($item['label']) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
