<?php
$adminNavigation = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="13" width="7" height="8" rx="1"/>'],
    ['key' => 'packages', 'label' => 'Paket Jadwal', 'href' => 'packages.php', 'icon' => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z"/><path d="M3.5 7.8 12 12l8.5-4.2M12 12v9"/>'],
    ['key' => 'schedule-editor', 'label' => 'Editor Jadwal', 'href' => 'schedule-editor.php', 'icon' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18M7 14h3M7 17h6"/>'],
    ['key' => 'settings', 'label' => 'Konfigurasi', 'href' => 'settings.php', 'icon' => '<path d="M4 6h16M4 12h16M4 18h16"/><circle cx="9" cy="6" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="11" cy="18" r="2"/>'],
    ['key' => 'backup', 'label' => 'Backup & Restore', 'href' => 'backup.php', 'icon' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3 2.1 0 4-.4 5.8-1.1"/><path d="m15 16 3 3 4-5"/>'],
    ['key' => 'public', 'label' => 'Tampilan Publik', 'href' => 'public.php', 'icon' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 9h10M7 12h6"/>'],
    ['key' => 'diagnostics', 'label' => 'Pemeriksaan', 'href' => 'diagnostics.php', 'icon' => '<path d="M3 12h4l3-7 4 14 3-7h4"/>'],
];
?>
<nav class="main-nav" aria-label="Navigasi utama">
    <p class="nav-section-label">MENU UTAMA</p>
    <?php foreach ($adminNavigation as $item): ?>
        <?php $isActive = ($activePage ?? '') === $item['key']; ?>
        <a class="nav-item<?= $isActive ? ' active' : '' ?>" href="<?= $item['href'] ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><?= $item['icon'] ?></svg></span>
            <span class="nav-label"><?= htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <?php if ($isActive): ?><span class="nav-active-indicator" aria-hidden="true"></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
