<?php

use Dotenv\Dotenv;
use WebTheory\Exterminate\Exterminator;

use function Env\env;

$root = dirname(__DIR__);

require_once "$root/vendor/autoload.php";

Dotenv::createUnsafeImmutable($root)->load();

Exterminator::debug([
    'enable' => env('DEBUG_ENABLE') ?? true,
    'display' => env('DEBUG_DISPLAY') ?? true,
    'log' => "$root/logs/collection.log",
    'system' => [
        'host_os' => env('HOST_OS'),
        'host_path' => env('HOST_PATH'),
        'guest_path' => env('GUEST_PATH'),
    ],
    'error_logger' => [
        'channel' => env('LOG_CHANNEL'),
    ],
    'error_handler' => true,
    'var_dumper' => [
        'root' => $root,
        'theme' => env('VAR_DUMP_THEME'),
        'server_host' => env('VAR_DUMP_SERVER_HOST'),
    ],
    'xdebug' => [
        'file_link_format' => Exterminator::EDITOR_FORMATS[env('DEBUG_EDITOR') ?? 'phpstorm'],
    ],
]);
