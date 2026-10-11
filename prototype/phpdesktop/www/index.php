<?php
declare(strict_types=1);

/*
 * Product entry point.
 * Runtime checks remain available from diagnostics.php and are not the home screen.
 * Keep this redirect relative so the application works in PHP Desktop offline.
 */
header('Location: public.php', true, 302);
exit;
