<?php
$footerEscape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<footer>
    <span>Bel Madrasah · <?= $footerEscape($footerPageLabel ?? 'Area Admin') ?></span>
    <span><?= $footerEscape($footerMeta ?? 'Data lokal') ?></span>
    <span class="scheduler-runtime-status" data-scheduler-status data-scheduler-state="waiting" role="status" aria-live="polite"><span class="scheduler-status-dot" aria-hidden="true"></span><span data-scheduler-status-text>Menyiapkan scheduler…</span></span>
</footer>
