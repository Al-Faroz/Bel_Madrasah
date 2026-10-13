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
</footer>
