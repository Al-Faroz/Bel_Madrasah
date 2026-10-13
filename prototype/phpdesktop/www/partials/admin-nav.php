<?php
$adminNavigation = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
    ['key' => 'packages', 'label' => 'Paket jadwal', 'href' => 'packages.php'],
    ['key' => 'schedule-editor', 'label' => 'Editor jadwal', 'href' => 'schedule-editor.php'],
    ['key' => 'settings', 'label' => 'Konfigurasi', 'href' => 'settings.php'],
    ['key' => 'backup', 'label' => 'Backup data', 'href' => 'backup.php'],
    ['key' => 'public', 'label' => 'Tampilan publik', 'href' => 'public.php'],
    ['key' => 'diagnostics', 'label' => 'Pemeriksaan', 'href' => 'diagnostics.php'],
];
?>
<nav class="main-nav" aria-label="Navigasi utama">
    <?php foreach ($adminNavigation as $item): ?>
        <?php $isActive = ($activePage ?? '') === $item['key']; ?>
        <a href="<?= $item['href'] ?>" target="_self"<?= $isActive ? ' class="active" aria-current="page"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
    <?php endforeach; ?>
</nav>
