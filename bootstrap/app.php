<?php
/**
 * Veen HMS bootstrap: autoloader, helpers, environment.
 */
define('BASE_PATH', dirname(__DIR__));
define('VEEN_START', microtime(true));

spl_autoload_register(function (string $class): void {
    $map = [
        'Veen\\Core\\' => BASE_PATH . '/core/',
        'App\\'        => BASE_PATH . '/app/',
        'Database\\'   => BASE_PATH . '/database/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require BASE_PATH . '/core/helpers.php';

Veen\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set(config('app.timezone', 'UTC'));

if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0'); // errors are rendered by App handler
}
