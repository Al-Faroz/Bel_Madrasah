<?php
$adminNavigation = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'short' => 'DB', 'href' => 'dashboard.php'],
    ['key' => 'packages', 'label' => 'Paket Jadwal', 'short' => 'PJ', 'href' => 'packages.php'],
    ['key' => 'schedule-editor', 'label' => 'Editor Jadwal', 'short' => 'EJ', 'href' => 'schedule-editor.php'],
    ['key' => 'settings', 'label' => 'Konfigurasi', 'short' => 'KG', 'href' => 'settings.php'],
    ['key' => 'backup', 'label' => 'Backup & Restore', 'short' => 'BR', 'href' => 'backup.php'],
    ['key' => 'public', 'label' => 'Tampilan Publik', 'short' => 'TP', 'href' => 'public.php'],
    ['key' => 'diagnostics', 'label' => 'Pemeriksaan', 'short' => 'PM', 'href' => 'diagnostics.php'],
];
?>
<nav class="main-nav" aria-label="Navigasi utama">
    <p class="nav-section-label">MENU UTAMA</p>
    <?php foreach ($adminNavigation as $item): ?>
        <?php $isActive = ($activePage ?? '') === $item['key']; ?>
        <a class="nav-item<?= $isActive ? ' active' : '' ?>" href="<?= $item['href'] ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <span class="nav-icon" aria-hidden="true"><?= htmlspecialchars($item['short'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span class="nav-label"><?= htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <?php if ($isActive): ?><span class="nav-active-indicator" aria-hidden="true"></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
