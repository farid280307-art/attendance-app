<?php

declare(strict_types=1);
?>
<nav class="app-navigation" aria-label="Menu <?= $isAdminLayout ? 'admin' : 'karyawan' ?>">
    <p class="app-navigation-label">Menu Utama</p>
    <ul class="nav flex-column gap-1">
        <?php foreach ($navigationItems as $item): ?>
            <li class="nav-item">
                <?php if (($item['disabled'] ?? false) === true): ?>
                    <span class="app-nav-link is-disabled" aria-disabled="true">
                        <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= e($item['label']) ?></span>
                        <span class="app-coming-soon ms-auto">Segera</span>
                    </span>
                <?php else: ?>
                    <a
                        class="app-nav-link<?= ($item['active'] ?? false) ? ' is-active' : '' ?>"
                        href="<?= e(url($item['path'])) ?>"
                        <?= ($item['active'] ?? false) ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
