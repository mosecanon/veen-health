<?php
/**
 * Veen HMS front controller. All requests are routed here (clean URLs).
 */
require __DIR__ . '/../bootstrap/app.php';

(new Veen\Core\App())->run();
