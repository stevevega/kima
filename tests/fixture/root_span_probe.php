<?php
require __DIR__ . '/../bootstrap.php';

use DDTrace\GlobalTracer;
use Kima\Error;
use Kima\Prime\App;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

echo 'root scope: ', null === GlobalTracer::get()->getRootScope() ? 'none' : 'open', PHP_EOL;

if ('run' === $argv[1]) {
    $action = App::get_instance()->run(['/' => 'Index']);
    echo 'action: ', $action, PHP_EOL;
    exit;
}

// Error::set() reports the class method two frames above it as the caller
final class Probe
{
    public static function fail(): void
    {
        self::set_error();
    }

    private static function set_error(): void
    {
        Error::set('root span probe');
    }
}

Probe::fail();
