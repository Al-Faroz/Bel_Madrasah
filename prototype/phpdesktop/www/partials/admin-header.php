<?php
$headerEscape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<header class="topbar">
    <div class="brand-mark" aria-hidden="true">BM</div>
    <div>
        <p class="eyebrow"><?= $headerEscape($pageEyebrow ?? 'ADMINISTRASI') ?></p>
        <h1><?= $headerEscape($pageHeading ?? 'Bel Madrasah') ?></h1>
        <p class="subtitle"><?= $headerEscape($pageSubtitle ?? 'Pengelolaan aplikasi bel madrasah') ?></p>
    </div>
    <span class="status-pill"><span class="status-dot"></span> <?= $headerEscape($pageStatusLabel ?? 'AREA ADMIN') ?></span>
</header>
